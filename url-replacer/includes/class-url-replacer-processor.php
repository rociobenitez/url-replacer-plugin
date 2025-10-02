<?php
/**
 * Procesa la lógica de búsqueda, recuento y reemplazo de URLs en la base de datos.
 */
if (!defined('ABSPATH')) {
    exit;
}

class URL_Replacer_Processor {

    public function __construct() {
        // dependencias o configuraciones
    }

    /**
     * Busca una URL en las tablas definidas, retorna array con:
     * - 'tables': info por cada tabla (label, table, rows, db_error)
     * - 'total_count': número total de apariciones.
     */
    public function findOccurrences($url) {
        global $wpdb;

        $targets = $this->getTargets();
        $totalCount = 0;
        $tableResults = [];

        foreach ($targets as $t) {
            $tableName = $wpdb->prefix . $t['table'];
            if (!$this->tableExists($tableName)) {
                $tableResults[] = [
                    'label'   => $t['label'],
                    'table'   => $tableName,
                    'rows'    => [],
                    'db_error'=> null
                ];
                continue;
            }
            // Suprimir errores y capturar
            $wpdb->suppress_errors(true);
            $query = $wpdb->prepare(
                "SELECT {$t['id_column']}, {$t['value_column']} FROM $tableName WHERE {$t['value_column']} LIKE %s",
                '%' . $wpdb->esc_like($url) . '%'
            );
            $rows = $wpdb->get_results($query);
            $dbError = $wpdb->last_error;
            $wpdb->suppress_errors(false);

            if ($dbError) {
                $tableResults[] = [
                    'label' => $t['label'],
                    'table' => $tableName,
                    'rows'  => [],
                    'db_error' => $dbError
                ];
                continue;
            }

            if (!$rows) {
                $tableResults[] = [
                    'label' => $t['label'],
                    'table' => $tableName,
                    'rows'  => [],
                    'db_error' => null
                ];
                continue;
            }

            // Procesar cada fila y calcular cuántas veces aparece la URL
            $processedRows = [];
            foreach ($rows as $r) {
                $idVal = $r->{$t['id_column']};
                $text  = $r->{$t['value_column']};
                // Recuento de ocurrencias en la cadena
                $occurrences = $this->countSubstrings($text, $url);
                $totalCount += $occurrences;

                // Fragmento resaltado
                $fragment = $this->highlightFragment($text, $url);
                $processedRows[] = [
                    'id'       => $idVal,
                    'fragment' => $fragment
                ];
            }

            $tableResults[] = [
                'label'   => $t['label'],
                'table'   => $tableName,
                'rows'    => $processedRows,
                'db_error'=> null
            ];
        }

        return [
            'tables'      => $tableResults,
            'total_count' => $totalCount
        ];
    }

    /**
     * Reemplaza la URL antigua por la nueva en cada fila y guarda un log.
     */
    public function replaceOccurrences($old, $new) {
        global $wpdb;
        $log = [];
        $targets = $this->getTargets();

        foreach ($targets as $t) {
            $tableName = $wpdb->prefix . $t['table'];
            if (!$this->tableExists($tableName)) {
                $log[] = "[OMITIDO] La tabla $tableName no existe.";
                continue;
            }

            $wpdb->suppress_errors(true);
            $query = $wpdb->prepare(
                "SELECT {$t['id_column']}, {$t['value_column']} FROM $tableName WHERE {$t['value_column']} LIKE %s",
                '%' . $wpdb->esc_like($old) . '%'
            );
            $rows = $wpdb->get_results($query);
            $dbError = $wpdb->last_error;
            $wpdb->suppress_errors(false);

            if ($dbError) {
                $log[] = "[ERROR DB] $tableName: " . $dbError;
                continue;
            }
            if (!$rows) {
                continue; // Sin coincidencias
            }

            foreach ($rows as $r) {
                $idVal = $r->{$t['id_column']};
                $original = $r->{$t['value_column']};

                $maybeUnserialized = maybe_unserialize($original);
                $replaced = $this->recursiveReplace($maybeUnserialized, $old, $new);
                $final = (is_array($replaced) || is_object($replaced))
                    ? maybe_serialize($replaced)
                    : $replaced;

                if ($final !== $original) {
                    $wpdb->update(
                        $tableName,
                        [ $t['value_column'] => $final ],
                        [ $t['id_column']    => $idVal ]
                    );
                    $log[] = sprintf('%s (ID: %s) - Campo %s reemplazado.',
                        $tableName, $idVal, $t['value_column']
                    );
                }
            }
        }

        return $log;
    }

    /**
     * Destaca un fragmento de texto donde se encuentra la URL antigua.
     * Muestra 30 caracteres antes y 30 después de la coincidencia.
     */
    public function highlightFragment($text, $search) {
        $pos = stripos($text, $search);
        if ($pos === false) {
            // Si no se encuentra, mostramos sólo un máximo de 100 chars
            return esc_html(substr($text, 0, 100)) . '...';
        }

        $start = max(0, $pos - 30);
        $length = strlen($search) + 60;
        $fragment = substr($text, $start, $length);

        // Escapar y resaltar
        $escaped_fragment = esc_html($fragment);
        $escaped_search = esc_html($search);
        $highlighted = str_ireplace($escaped_search, '<mark>' . $escaped_search . '</mark>', $escaped_fragment);

        // Contexto
        $prefix = ($start > 0) ? '...' : '';
        $suffix = ($start + $length < strlen($text)) ? '...' : '';

        // Permitimos la etiqueta <mark> en la salida
        // Si quieres permitir también <a>, <p>, etc. agrégalos al array
        $allowed_tags = [
            'mark' => []
        ];

        return wp_kses($prefix . $highlighted . $suffix, $allowed_tags);
    }

    /**
     * Recursivamente reemplaza la cadena $old por $new.
     */
    private function recursiveReplace($data, $old, $new) {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = $this->recursiveReplace($v, $old, $new);
            }
            return $data;
        } elseif (is_object($data)) {
            foreach ($data as $k => $v) {
                $data->$k = $this->recursiveReplace($v, $old, $new);
            }
            return $data;
        } elseif (is_string($data)) {
            return str_ireplace($old, $new, $data);
        }
        return $data;
    }

    /**
     * Cuenta cuántas veces aparece $needle en $haystack (ignorando mayús/minús).
     */
    private function countSubstrings($haystack, $needle) {
        return substr_count(strtolower($haystack), strtolower($needle));
    }

    /**
     * Retorna la lista de tablas/columnas a procesar (puedes extraerlo a un config).
     */
    private function getTargets() {
        return [
            [
                'table'       => 'posts',
                'id_column'   => 'ID',
                'value_column'=> 'post_content',
                'label'       => 'Post Content'
            ],
            [
                'table'       => 'postmeta',
                'id_column'   => 'meta_id',
                'value_column'=> 'meta_value',
                'label'       => 'Post Meta'
            ],
            [
                'table'       => 'termmeta',
                'id_column'   => 'meta_id',
                'value_column'=> 'meta_value',
                'label'       => 'Term Meta'
            ]
        ];
    }

    /**
     * Comprueba si la tabla existe.
     */
    private function tableExists($table) {
        global $wpdb;
        $check = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        return ($check === $table);
    }
}
