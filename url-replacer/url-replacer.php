<?php
/**
 * Plugin Name: URL Replacer
 * Plugin URI: https://github.com/rociobenitez/url-replacer-plugin
 * Description: Herramienta profesional para buscar y reemplazar URLs en la base de datos de WordPress. Incluye modo de prueba, reemplazo seguro con respeto de serialización, soporte para CSV masivo y logging completo.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Rocío Benítez García
 * Author URI: https://github.com/rociobenitez
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: url-replacer
 * Domain Path: /languages
 * Network: false
 * 
 * @package URLReplacer
 * @version 1.0.0
 * @author Rocío Benítez García
 * @copyright 2025 MKtmedianet
 * @license GPL-2.0-or-later
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar compatibilidad mínima
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>URL Replacer:</strong> Este plugin requiere PHP 7.4 o superior. Versión actual: ' . PHP_VERSION;
        echo '</p></div>';
    });
    return;
}

if (version_compare(get_bloginfo('version'), '6.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>URL Replacer:</strong> Este plugin requiere WordPress 6.0 o superior. Versión actual: ' . get_bloginfo('version');
        echo '</p></div>';
    });
    return;
}

// Definir constantes del plugin
define('URL_REPLACER_VERSION', '1.0.0');
define('URL_REPLACER_MAIN_FILE', __FILE__);
define('URL_REPLACER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('URL_REPLACER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('URL_REPLACER_TEXT_DOMAIN', 'url-replacer');

// Autoloader de clases del plugin
require_once URL_REPLACER_PLUGIN_DIR . 'includes/class-url-replacer-admin.php';
require_once URL_REPLACER_PLUGIN_DIR . 'includes/class-url-replacer-processor.php';
require_once URL_REPLACER_PLUGIN_DIR . 'includes/class-url-replacer-logger.php';
require_once URL_REPLACER_PLUGIN_DIR . 'includes/class-url-replacer-csv.php';

/**
 * Inicializa el plugin URL Replacer
 */
function url_replacer_init_plugin() {
    $admin = new URL_Replacer_Admin();
    add_action('admin_menu', [$admin, 'registerMenus']);
    add_action('wp_ajax_url_replacer_download_log', [$admin, 'handleLogDownload']);
}

/**
 * Carga estilos CSS en páginas del plugin
 */
function url_replacer_enqueue_admin_scripts($hook_suffix) {
    if (strpos($hook_suffix, 'url-replacer') === false) {
        return;
    }
    
    wp_enqueue_style(
        'url-replacer-admin',
        URL_REPLACER_PLUGIN_URL . 'assets/admin.css',
        [],
        URL_REPLACER_VERSION
    );
}

/**
 * Función ejecutada al activar el plugin
 */
function url_replacer_activate() {
    add_option('url_replacer_version', URL_REPLACER_VERSION);
}

/**
 * Función ejecutada al desactivar el plugin
 */
function url_replacer_deactivate() {
    delete_transient('url_replacer_csv_preview');
}

// Registrar hooks del plugin
register_activation_hook(__FILE__, 'url_replacer_activate');
register_deactivation_hook(__FILE__, 'url_replacer_deactivate');

// Inicializar el plugin
if (is_admin()) {
    url_replacer_init_plugin();
    add_action('admin_enqueue_scripts', 'url_replacer_enqueue_admin_scripts');
}
