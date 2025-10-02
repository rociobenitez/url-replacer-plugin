=== URL Replacer ===
Contributors: rociobenitez
Tags: urls, replace, serializacion, csv, base de datos
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

# URL Replacer

Plugin de WordPress para buscar y reemplazar URLs en múltiples tablas de la base de datos. Respeta la serialización, permite subir un CSV con pares [URL antigua, URL nueva], genera logs y ofrece un modo de prueba antes de modificar nada.

## Descripción

URL Replacer es una herramienta de administración esencial que ofrece las siguientes funcionalidades:

* **Modo Prueba (Dry Run):** Busca coincidencias de una URL en la base de datos y contabiliza las apariciones sin ejecutar ninguna modificación.
* **Reemplazo Seguro:** Actualiza URLs en tablas clave (como `wp_posts`, `wp_postmeta`, `wp_termmeta`, etc.), asegurando la **preservación de datos serializados** (evitando la corrupción de *widgets* o campos de *plugins* como ACF).
* **Procesamiento Masivo:** Permite la subida de un archivo CSV con pares `https://principal.url.edu.gt/sede-la-antigua/` para aplicar reemplazos por lotes.
* **Auditoría y Logs:** Genera un archivo `url-replacer-log.txt` con el historial detallado de las modificaciones realizadas, accesible también para descarga desde el panel.

### Características Técnicas Clave
* **Serialización:** Deserializa/Reserializa los datos automáticamente.
* **Tablas Afectadas:** `posts`, `postmeta`, `termmeta`.
* **Permisos:** Requiere la capacidad `manage_options` (Administrador).

## Instalación

1.  **Método 1 (ZIP):** En tu panel de WordPress, ve a Plugins -> Añadir nuevo -> Subir plugin. Selecciona el archivo .zip del plugin y actívalo.
2.  **Método 2 (FTP/SFTP):** Sube la carpeta completa `url-replacer/` al directorio `/wp-content/plugins/` de tu servidor.
3.  Activa el plugin en la sección "Plugins" de tu escritorio de WordPress.
4.  El menú de configuración se encontrará como "URL Replacer".

## Uso

1.  **Modo Prueba:** Accede a **URL Replacer** → **Modo Prueba** e introduce la URL a buscar para obtener un recuento de coincidencias sin riesgo.
2.  **Reemplazo Simple:** Usa la sección **Reemplazar URLs** para definir la URL antigua y la nueva, y ejecuta la actualización.
3.  **CSV por Lotes:** En la sección **Subir CSV**, carga tu archivo CSV (sin cabeceras, delimitado por punto y coma `;`). El plugin mostrará una vista previa antes de la ejecución masiva.
4.  **Auditoría:** Descarga el log de cambios desde **Descargar Log**.

**Formato del CSV:**
Cada línea debe ser `URL_ANTIGUA;URL_NUEVA`.

Ejemplo:
https://sitio-viejo.com/pagina1;https://sitio-nuevo.com/pagina1

## Preguntas frecuentes (FAQ)

## FAQ

**P: ¿El plugin garantiza la integridad de los campos serializados (ACF, widgets, etc.)?**
**R:** Sí. El plugin detecta datos serializados, los deserializa en memoria, realiza el reemplazo recursivo en sus claves internas y los vuelve a serializar, ajustando la longitud (*length*) de la cadena de forma segura.

**P: ¿Qué ocurre si mi sitio es muy grande o subo un CSV con miles de entradas?**
**R:** El plugin está optimizado para procesar por lotes, pero en sitios con bases de datos masivas, siempre se recomienda realizar un **backup completo** de la base de datos antes de aplicar el reemplazo real.

**P: ¿Puedo revertir los cambios automáticamente?**
**R:** No hay una función de *rollback* automático. Sin embargo, el log detallado (`url-replacer-log.txt`) registra exactamente qué tabla y qué registro se modificó, permitiendo la reversión manual a un desarrollador si fuera necesario.

## Screenshots
1.  screenshot-1.png: Pantalla del Modo Prueba mostrando las coincidencias antes de la ejecución.
2.  screenshot-2.png: Interfaz de Subida de CSV con la vista previa de los pares de URLs.

## Changelog

### 1.0.1
* [Añadido] Archivo admin.css para estilos personalizados.
* [Añadido] Tabla resumen de resultados.
* [Añadido] Procesamiento de URLs con caracteres especiales en el modo CSV.
* [Mejora] Experiencia de usuario y visual del panel de administración.

### 1.0
* Versión inicial con modo prueba y reemplazo.
* Soporte CSV y logs.

## Upgrade Notice

### 1.0.1
Actualización menor con mejoras en la UX/UI del panel de administración y mejor registro de auditoría. Es seguro actualizar.

### 1.0
Versión inicial estable.

## Créditos

Desarrollado por Rocío Benítez García.

## Licencia

Este plugin se distribuye bajo la licencia [GPL v2 o posterior](https://www.gnu.org/licenses/gpl-2.0.html).
