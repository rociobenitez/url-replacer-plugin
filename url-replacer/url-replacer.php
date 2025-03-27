<?php
/**
 * Plugin Name: URL Replacer
 * Description: Herramienta para buscar y reemplazar URLs en varias tablas de la base de datos, respetando la serialización y generando logs. Incluye un modo de prueba y un modo de reemplazo real.
 * Version: 1.0
 * Author: MKtmedianet
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

define('URL_REPLACER_MAIN_FILE', __FILE__);

// Requerimos los archivos de clases
require_once plugin_dir_path(__FILE__) . 'includes/class-url-replacer-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-url-replacer-processor.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-url-replacer-logger.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-url-replacer-csv.php';

/**
 * Función de inicialización del plugin.
 * Se instancia la clase Admin que usa las demás clases.
 */
function url_replacer_init_plugin() {
    $admin = new URL_Replacer_Admin();
    // Hook para crear menú
    add_action('admin_menu', [ $admin, 'registerMenus' ]);
    // Hook para AJAX de descarga del log (opcional)
    add_action('wp_ajax_url_replacer_download_log', [ $admin, 'handleLogDownload' ]);
}

// Hook de activación del plugin
register_activation_hook(__FILE__, 'url_replacer_activate');
function url_replacer_activate() {
    // Opcional: crear archivo log vacío, preparar opciones, etc.
}

// Hook de desactivación
register_deactivation_hook(__FILE__, 'url_replacer_deactivate');
function url_replacer_deactivate() {
    // Limpiar si hace falta algo
}

// Ejecutar inicialización
url_replacer_init_plugin();
