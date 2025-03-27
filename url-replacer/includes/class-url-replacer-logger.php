<?php
/**
 * Manejo del archivo de log: escritura, path, etc.
 */
if (!defined('ABSPATH')) {
    exit;
}

class URL_Replacer_Logger {

    // Ruta del archivo de log
    public function getLogFilePath() {
        $upload = wp_upload_dir();
        return $upload['basedir'] . '/url-replacer-log.txt';
    }

    /**
     * Escribe un array de entradas de log al archivo.
     */
    public function writeLog($entries) {
        if (!is_array($entries) || empty($entries)) {
            return;
        }
        $logFile = $this->getLogFilePath();
        $timestamp = '[' . date('Y-m-d H:i:s') . '] ';

        $content = PHP_EOL . $timestamp . '--- Reemplazo de URLs ---' . PHP_EOL;
        foreach ($entries as $line) {
            $content .= $timestamp . $line . PHP_EOL;
        }

        file_put_contents($logFile, $content, FILE_APPEND | LOCK_EX);
    }
}
