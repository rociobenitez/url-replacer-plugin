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
                    return false; // Formato no válido
                }
                $old = sanitize_text_field($data[0]);
                $new = sanitize_text_field($data[1]);
                $pairs[] = [$old, $new];
            }
            fclose($handle);
        } else {
            return false; // No se pudo abrir
        }

        return $pairs;
    }
}
