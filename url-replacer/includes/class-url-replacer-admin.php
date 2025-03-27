<?php
/**
 * Manejo de menús de WP y pantallas de administrador:
 * - Modo Prueba
 * - Reemplazar URLs
 * - Subir CSV
 * - Descargar Log
 */

if (!defined('ABSPATH')) {
    exit;
}

class URL_Replacer_Admin {

    private $processor;
    private $logger;
    private $csvHandler;

    public function __construct() {
        $this->processor = new URL_Replacer_Processor();
        $this->logger = new URL_Replacer_Logger();
        $this->csvHandler = new URL_Replacer_CSV();
    }

    /**
     * Registra el menú y submenús en el panel de WP.
     */
    public function registerMenus() {
        add_menu_page(
            'URL Replacer',
            'URL Replacer',
            'manage_options',
            'url-replacer',
            [ $this, 'renderTestPage' ], // Default to Modo Prueba
            'dashicons-update',
            99
        );

        // Submenú 1: Modo Prueba
        add_submenu_page(
            'url-replacer',
            'Modo Prueba',
            'Modo Prueba',
            'manage_options',
            'url-replacer',
            [ $this, 'renderTestPage' ]
        );

        // Submenú 2: Reemplazar
        add_submenu_page(
            'url-replacer',
            'Reemplazar URLs',
            'Reemplazar URLs',
            'manage_options',
            'url-replacer-update',
            [ $this, 'renderReplacePage' ]
        );

        // Submenú 3: Subir CSV
        add_submenu_page(
            'url-replacer',
            'Subir CSV',
            'Subir CSV',
            'manage_options',
            'url-replacer-csv',
            [ $this, 'renderCSVPage' ]
        );

        // Submenú 4: Descargar Log
        add_submenu_page(
            'url-replacer',
            'Descargar Log',
            'Descargar Log',
            'manage_options',
            'url-replacer-log',
            [ $this, 'renderDownloadLogPage' ]
        );
    }

    /**
     * Modo Prueba: muestra coincidencias y recuento total.
     */
    public function renderTestPage() {
        echo '<div class="wrap">';
        echo '<h1>Modo Prueba - URL Replacer</h1>';
        echo '<p>Busca las apariciones de una URL en varias tablas sin modificar la base de datos.</p>';

        // Formulario
        if (isset($_POST['test_submit']) && !empty($_POST['test_url'])) {
            $testUrl = sanitize_text_field($_POST['test_url']);
            echo '<h2>Resultados:</h2>';

            // Llamamos al processor para buscar
            $results = $this->processor->findOccurrences($testUrl);
            // $results será un array con info de cada tabla, filas encontradas, recuento total, etc.

            if (empty($results['tables'])) {
                echo '<p>No se encontraron coincidencias en ninguna tabla.</p>';
            } else {
                echo '<p>Coincidencias totales: <strong>' . esc_html($results['total_count']) . '</strong></p>';
                // Mostrar la información tabla por tabla
                foreach ($results['tables'] as $tableResult) {
                    echo '<h3>' . esc_html($tableResult['label']) . ' (' . esc_html($tableResult['table']) . ')</h3>';
                    if ($tableResult['db_error']) {
                        echo '<p style="color:red;">Error de base de datos: ' . esc_html($tableResult['db_error']) . '</p>';
                        continue;
                    }
                    if (empty($tableResult['rows'])) {
                        echo '<p>No se encontraron coincidencias.</p>';
                        continue;
                    }
                    // Mostrar las filas
                    echo '<table class="widefat striped">';
                    echo '<thead><tr><th>ID</th><th>Fragmento</th></tr></thead><tbody>';
                    foreach ($tableResult['rows'] as $row) {
                        $id = $row['id'];
                        $fragment = $row['fragment'];
                        echo '<tr><td>' . esc_html($id) . '</td><td>' . $fragment . '</td></tr>';
                    }
                    echo '</tbody></table>';
                }
            }

        } else {
            // Form
            echo '<form method="post">';
            echo '<table class="form-table">';
            echo '<tr><th><label for="test_url">URL a buscar</label></th>';
            echo '<td><input type="text" name="test_url" id="test_url" class="regular-text" required></td></tr>';
            echo '</table>';
            submit_button('Buscar', 'primary', 'test_submit');
            echo '</form>';
        }

        echo '</div>';
    }

    /**
     * Reemplazar URLs: hace la actualización en la DB.
     */
    public function renderReplacePage() {
        echo '<div class="wrap">';
        echo '<h1>Reemplazar URLs</h1>';
        echo '<p>Busca la URL antigua y la reemplaza por la nueva en la base de datos (respetando serialización).</p>';

        if (isset($_POST['replace_submit']) && !empty($_POST['old_url']) && !empty($_POST['new_url'])) {
            $old = sanitize_text_field($_POST['old_url']);
            $new = sanitize_text_field($_POST['new_url']);

            $log = $this->processor->replaceOccurrences($old, $new);
            // $log es un array con mensajes de log

            // Guardar log en archivo
            $this->logger->writeLog($log);

            echo '<h2>Resultado</h2>';
            if (empty($log)) {
                echo '<p>No se realizaron cambios.</p>';
            } else {
                echo '<ul>';
                foreach ($log as $line) {
                    echo '<li>' . esc_html($line) . '</li>';
                }
                echo '</ul>';
                echo '<p>Se ha guardado un log en wp-content/uploads/url-replacer-log.txt</p>';
            }

        } else {
            // Form
            echo '<form method="post">';
            echo '<table class="form-table">';
            echo '<tr><th><label for="old_url">URL antigua</label></th>';
            echo '<td><input type="text" name="old_url" class="regular-text" required></td></tr>';
            echo '<tr><th><label for="new_url">URL nueva</label></th>';
            echo '<td><input type="text" name="new_url" class="regular-text" required></td></tr>';
            echo '</table>';
            submit_button('Reemplazar', 'primary', 'replace_submit');
            echo '</form>';
        }

        echo '</div>';
    }

    /**
     * Subir CSV con pares old/new.
     */
    public function renderCSVPage() {
        echo '<div class="wrap">';
        echo '<h1>Subir CSV de URLs</h1>';
        echo '<p>Sube un archivo CSV que <strong>no incluya</strong> fila de encabezados y contenga exactamente <strong>dos columnas</strong> por línea: <strong>URL antigua</strong> y <strong>URL nueva</strong></p>';
        echo '<p>';
        echo 'A modo de ejemplo, el CSV podría tener dos líneas como estas:<br>';
        echo '<code style="display:inline-block;margin: 8px 0;">https://oldurl1.com;https://newurl1.com<br>';
        echo 'https://oldurl2.com;https://newurl2.com</code>';
        echo '</p>';

        echo '<p>Tras subir el archivo, podrás revisar cuántas coincidencias se encuentran y, si todo está correcto, aplicar el cambio masivo.</p>';

        // Enlace o botón para descargar archivo de ejemplo
        $sample_csv_url = plugins_url('assets/sample.csv', URL_REPLACER_MAIN_FILE);
        echo '<p><a href="' . esc_url($sample_csv_url) . '" class="button" download>Descargar CSV de ejemplo</a></p>';
    
        // Comprobar si hay POST "csv_step" para definir si es paso 1 o 2
        $step = isset($_POST['csv_step']) ? sanitize_text_field($_POST['csv_step']) : 'upload';
    
        if ($step === 'upload') {
            // PASO 1: Subir CSV y Mostrar preview
            if (isset($_POST['upload_csv']) && !empty($_FILES['csv_file']['tmp_name'])) {
                $file = $_FILES['csv_file'];
                $filename = $_FILES['csv_file']['name'];
                $ext = pathinfo($filename, PATHINFO_EXTENSION);

                // Verificar el nonce al procesar
                if ( ! isset($_POST['url_replacer_csv_nonce']) 
                    || ! wp_verify_nonce($_POST['url_replacer_csv_nonce'], 'url_replacer_csv_action') ) 
                {
                    echo '<div class="notice notice-error"><p>Token de seguridad inválido. Recarga la página e inténtalo de nuevo.</p></div>';
                    return;
                }

                // Validar extensión .csv
                if ( strtolower($ext) !== 'csv' ) {
                    echo '<div class="notice notice-error"><p>El archivo debe tener extensión .csv</p></div>';
                    $this->renderCSVUploadForm();
                    echo '</div>';
                    return;
                }

                // Validar que no haya error de subida
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    echo '<div class="notice notice-error"><p>Error al subir el archivo (código: ' . esc_html($file['error']) . ').</p></div>';
                    $this->renderCSVUploadForm();
                    echo '</div>';
                    return;
                }
    
                // Procesar el CSV
                $pairs = $this->csvHandler->processUploadedFile($file);
                if ($pairs === false) {
                    // El CSV no cumplía requisitos (2 columnas por fila)
                    echo '<div class="notice notice-error"><p>El archivo CSV no tiene el formato correcto (2 columnas por fila). Revisa el contenido.</p></div>';
                    $this->renderCSVUploadForm();
                    echo '</div>';
                    return;
                }
    
                // Calcular coincidencias de cada par
                if (empty($pairs)) {
                    echo '<div class="notice notice-warning"><p>No se encontraron filas en el CSV.</p></div>';
                    $this->renderCSVUploadForm();
                    echo '</div>';
                    return;
                }
    
                $previewData = [];
                $totalAll = 0;
    
                foreach ($pairs as $row) {
                    $oldUrl = $row[0];
                    $newUrl = $row[1];
    
                    // findOccurrences para contar
                    $results   = $this->processor->findOccurrences($oldUrl);
                    $countThis = $results['total_count'];
                    $totalAll += $countThis;
    
                    $previewData[] = [
                        'old'   => $oldUrl,
                        'new'   => $newUrl,
                        'count' => $countThis,
                    ];
                }
    
                // Guardar la vista previa en una transient (para el paso 2)
                set_transient('url_replacer_csv_preview', $previewData, HOUR_IN_SECONDS);
    
                // Mostrar tabla de vista previa
                echo '<h2 style="margin-top:2em;">Vista previa</h2>';

                $totalUrls = count($previewData);
                echo '<p>Se han detectado <strong>' . $totalUrls . '</strong> URLs en el archivo CSV.</p>';
                echo '<p>Total de coincidencias encontradas en la base de datos: <strong>' . esc_html($totalAll) . '</strong></p>';
    
                // Botón para aplicar
                echo '<form method="post">';
                echo '<input type="hidden" name="csv_step" value="apply">';
                submit_button('Aplicar todo', 'primary', 'apply_csv');
                echo '</form>';
    
            } else {
                // Si no se subió nada todavía, mostramos el formulario
                $this->renderCSVUploadForm();
            }
        }
    
        // PASO 2: el usuario hizo clic en "Aplicar todo"
        elseif ($step === 'apply') {
            $previewData = get_transient('url_replacer_csv_preview');
            if (empty($previewData) || !is_array($previewData)) {
                echo '<div class="notice notice-error"><p>No hay datos de vista previa o han caducado. Sube el CSV de nuevo.</p></div>';
                $this->renderCSVUploadForm();
                echo '</div>';
                return;
            }
    
            $logAll = [];
            foreach ($previewData as $item) {
                $oldUrl = $item['old'];
                $newUrl = $item['new'];
                // Reemplazar
                $log = $this->processor->replaceOccurrences($oldUrl, $newUrl);
                // Combinar logs
                $logAll = array_merge($logAll, $log);
            }
    
            // Guardar log en archivo
            $this->logger->writeLog($logAll);
            // Limpiamos la transient
            delete_transient('url_replacer_csv_preview');
    
            echo '<h2>Reemplazo finalizado</h2>';
            if (empty($logAll)) {
                echo '<div class="notice notice-info"><p>No se realizaron cambios.</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>Cambios realizados.</p></div>';
                echo '<ul>';
                foreach ($logAll as $line) {
                    echo '<li>' . esc_html($line) . '</li>';
                }
                echo '</ul>';
                echo '<p>Log guardado en <strong>url-replacer-log.txt</strong>.</p>';
            }
        }
    
        echo '</div>';
    }
    
    private function renderCSVUploadForm() {
        echo '<form method="post" enctype="multipart/form-data">';
        echo '<input type="hidden" name="csv_step" value="upload">';
        wp_nonce_field('url_replacer_csv_action', 'url_replacer_csv_nonce');
        echo '<table class="form-table">';
        echo '<tr><th><label for="csv_file">Archivo CSV</label></th>';
        echo '<td><input type="file" name="csv_file" accept=".csv" required></td></tr>';
        echo '</table>';
        submit_button('Subir CSV', 'primary', 'upload_csv');
        echo '</form>';
    }    

    /**
     * Descargar log guardado.
     */
    public function renderDownloadLogPage() {
        echo '<div class="wrap">';
        echo '<h1>Descargar Log</h1>';

        $filePath = $this->logger->getLogFilePath();
        if (!file_exists($filePath)) {
            echo '<p>No se ha generado ningún log todavía.</p>';
        } else {
            // Generar enlace de descarga con admin-ajax
            $downloadLink = admin_url('admin-ajax.php?action=url_replacer_download_log');
            echo '<p>Puedes descargar el log actual en el siguiente enlace:</p>';
            echo '<p><a href="' . esc_url($downloadLink) . '" class="button button-primary">Descargar Log</a></p>';
        }
        echo '</div>';
    }

    /**
     * Manejar la descarga del log vía AJAX.
     */
    public function handleLogDownload() {
        $filePath = $this->logger->getLogFilePath();
        if (!file_exists($filePath)) {
            wp_die('No hay log para descargar.');
        }

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="url-replacer-log.txt"');
        readfile($filePath);
        exit;
    }
}
