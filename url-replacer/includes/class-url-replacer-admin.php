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
        echo '<div class="notice notice-info inline">';
        echo '<p><strong>¿Qué hace esta herramienta?</strong></p>';
        echo '<p>Busca todas las apariciones de una URL específica en tu base de datos de WordPress <strong>sin realizar cambios</strong>. ';
        echo 'Podrás ver exactamente dónde aparece la URL y cuántas veces antes de decidir si quieres reemplazarla.</p>';
        echo '</div>';

        // Información sobre qué tablas se revisan
        echo '<div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin: 20px 0;">';
        echo '<h4 style="margin-top: 0;">Tablas que se analizan:</h4>';
        echo '<ul style="margin-bottom: 0;">';
        echo '<li><strong>Contenido de Posts:</strong> El contenido principal de entradas y páginas</li>';
        echo '<li><strong>Meta de Posts:</strong> Campos personalizados y metadatos</li>';
        echo '<li><strong>Meta de Términos:</strong> Metadatos de categorías y etiquetas</li>';
        echo '</ul>';
        echo '</div>';

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
            wp_nonce_field('url_replacer_test_action', 'url_replacer_test_nonce');
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

        echo '<div class="notice notice-warning inline">';
        echo '<p><strong>Atención: Esta acción modifica tu base de datos</strong></p>';
        echo '<p>Esta herramienta buscará la URL antigua y la reemplazará por la nueva en toda tu base de datos. ';
        echo '<strong>Se recomienda hacer una copia de seguridad antes de proceder.</strong></p>';
        echo '</div>';

        echo '<div style="background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; margin: 20px 0;">';
        echo '<h4 style="margin-top: 0;">Consejos:</h4>';
        echo '<ul>';
        echo '<li>Usa primero el <strong>Modo Prueba</strong> para verificar qué se va a cambiar</li>';
        echo '<li>El reemplazo respeta la serialización de WordPress automáticamente</li>';
        echo '<li>Se generará un log detallado de todos los cambios realizados</li>';
        echo '</ul>';
        echo '</div>';

        if (isset($_POST['replace_submit']) && !empty($_POST['old_url']) && !empty($_POST['new_url'])) {
            $old = sanitize_text_field($_POST['old_url']);
            $new = sanitize_text_field($_POST['new_url']);

            $log = $this->processor->replaceOccurrences($old, $new);
            // $log es un array con mensajes de log

            // Guardar log en archivo
            $this->logger->writeLog($log);

            echo '<h2>Resultado del reemplazo</h2>';
            if (empty($log)) {
                echo '<div class="notice notice-info"><p>No se encontraron coincidencias para reemplazar.</p></div>';
            } else {
                $changesCount = count($log);
                echo '<div class="notice notice-success"><p><strong>¡Éxito!</strong> Se realizaron ' . $changesCount . ' cambios en la base de datos.</p></div>';
                echo '<details><summary>Ver detalles de los cambios</summary>';
                echo '<ul>';
                foreach ($log as $line) {
                    echo '<li>' . esc_html($line) . '</li>';
                }
                echo '</ul></details>';
                echo '<p><em>Log guardado en wp-content/uploads/url-replacer-log.txt</em></p>';
            }

        } else {
            // Form
            echo '<form method="post">';
            wp_nonce_field('url_replacer_replace_action', 'url_replacer_replace_nonce');
            echo '<table class="form-table">';
            echo '<tr><th><label for="old_url">URL antigua</label></th>';
            echo '<td>';
            echo '<input type="text" name="old_url" class="regular-text" required placeholder="https://sitio-viejo.com/imagen.jpg">';
            echo '<p class="description">La URL que quieres reemplazar (debe coincidir exactamente)</p>';
            echo '</td></tr>';
            echo '<tr><th><label for="new_url">URL nueva</label></th>';
            echo '<td>';
            echo '<input type="text" name="new_url" class="regular-text" required placeholder="https://sitio-nuevo.com/imagen.jpg">';
            echo '<p class="description">La nueva URL que reemplazará a la anterior</p>';
            echo '</td></tr>';
            echo '</table>';
            
            echo '<div class="notice notice-info inline" style="margin-top: 20px;">';
            echo '<p><strong>Antes de continuar:</strong> ¿Has hecho una copia de seguridad de tu base de datos?</p>';
            echo '</div>';
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
        echo '<h1>Reemplazo Masivo con CSV</h1>';
    
        echo '<div class="notice notice-info inline">';
        echo '<p><strong>¿Cuándo usar esta opción?</strong></p>';
        echo '<p>Perfecto cuando necesitas reemplazar múltiples URLs de una sola vez, como al migrar un sitio completo o cambiar un CDN.</p>';
        echo '</div>';

        // Instrucciones más claras
        echo '<div style="background: #f0f8ff; padding: 20px; border: 1px solid #0073aa; margin: 20px 0;">';
        echo '<h3 style="margin-top: 0;">Formato del archivo CSV</h3>';
        echo '<p><strong>Requisitos importantes:</strong></p>';
        echo '<ul>';
        echo '<li>Archivo con extensión <code>.csv</code></li>';
        echo '<li><strong>Sin fila de encabezados</strong> (no incluyas títulos como "URL Antigua, URL Nueva")</li>';
        echo '<li>Exactamente <strong>2 columnas por fila</strong> separadas por punto y coma (<code>;</code>)</li>';
        echo '<li>Primera columna: URL antigua</li>';
        echo '<li>Segunda columna: URL nueva</li>';
        echo '</ul>';
        
        echo '<h4>Ejemplo correcto:</h4>';
        echo '<pre style="background: white; padding: 10px; border: 1px solid #ddd;">';
        echo 'https://sitio-viejo.com/imagen1.jpg;https://sitio-nuevo.com/imagen1.jpg' . PHP_EOL;
        echo 'https://sitio-viejo.com/imagen2.png;https://sitio-nuevo.com/imagen2.png' . PHP_EOL;
        echo 'https://sitio-viejo.com/documento.pdf;https://sitio-nuevo.com/documento.pdf';
        echo '</pre>';
        echo '</div>';

        // Proceso paso a paso
        echo '<div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #00a32a; margin: 20px 0;">';
        echo '<h4 style="margin-top: 0;">Proceso en 3 pasos:</h4>';
        echo '<ol>';
        echo '<li><strong>Subir:</strong> Sube tu archivo CSV</li>';
        echo '<li><strong>Revisar:</strong> Ve la vista previa de todos los cambios</li>';
        echo '<li><strong>Aplicar:</strong> Confirma y ejecuta el reemplazo masivo</li>';
        echo '</ol>';
        echo '</div>';

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
                } elseif (isset($pairs['error'])) {
                    echo '<div class="notice notice-error"><p>' . esc_html($pairs['error']) . '</p></div>';
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
                
                // Tabla detallada
                echo '<table class="widefat striped">';
                echo '<thead><tr><th>URL Antigua</th><th>URL Nueva</th><th>Coincidencias</th></tr></thead><tbody>';
                foreach ($previewData as $item) {
                    $statusColor = $item['count'] > 0 ? '#d63638' : '#00a32a';
                    echo '<tr>';
                    echo '<td><code>' . esc_html($item['old']) . '</code></td>';
                    echo '<td><code>' . esc_html($item['new']) . '</code></td>';
                    echo '<td style="color: ' . $statusColor . '; font-weight: bold;">' . $item['count'] . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';

                if ($totalAll > 0) {
                    echo '<div class="notice notice-warning inline"><p><strong>Atención:</strong> Se realizarán cambios en ' . $totalAll . ' ubicaciones de la base de datos.</p></div>';
                }

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
