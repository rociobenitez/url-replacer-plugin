<?php
/**
 * Clase para procesar la subida y lectura de archivos CSV.
 */
if (!defined('ABSPATH')) {
    exit;
}

class URL_Replacer_CSV {

    /**
     * Lee un archivo CSV (two columns: [old, new]) y retorna array de pares.
     * Retorna false si el formato es inválido.
     */
    public function processUploadedFile($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        // Podrías verificar que sea .csv
        $tmpPath = $file['tmp_name'];
        $pairs = [];

        if (($handle = fopen($tmpPath, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, ';')) !== false) {
                if (count($data) !== 2) {
                    fclose($handle);
                    return ['error' => "Error en línea {$lineNumber}: Se requieren exactamente 2 columnas, se encontraron " . count($data)];
                }

                $old = sanitize_text_field($data[0]);
                $new = sanitize_text_field($data[1]);

                if (empty($old) || empty($new)) {
                    fclose($handle);
                    return ['error' => "Error en línea {$lineNumber}: No se pueden tener campos vacíos"];
                }
                
                // Validación básica de URL
                if (!filter_var($old, FILTER_VALIDATE_URL) && !filter_var($new, FILTER_VALIDATE_URL)) {
                    fclose($handle);
                    return ['error' => "Advertencia en línea {$lineNumber}: Las URLs no parecen tener formato válido"];
                }

                $pairs[] = [$old, $new];
            }
            fclose($handle);
        } else {
            return ['error' => 'No se pudo abrir el archivo CSV'];
        }

        return $pairs;
    }
}
