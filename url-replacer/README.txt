=== URL Replacer ===
Contributors: Rocío Benítez García
Tags: urls, replace, wordpress, csv
Requires at least: 5.0
Tested up to: 6.3
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

# URL Replacer

Plugin de WordPress para buscar y reemplazar URLs en múltiples tablas de la base de datos. Respeta la serialización, permite subir un CSV con pares [URL antigua, URL nueva], genera logs y ofrece un modo de prueba antes de modificar nada.

## Descripción

URL Replacer facilita la tarea de:
* Buscar una URL antigua en la base de datos y ver cuántas coincidencias hay, sin modificar nada (modo prueba).
* Reemplazar dicha URL por una nueva en distintas tablas (posts, postmeta, termmeta, etc.), cuidando la serialización de datos.
* Subir un archivo CSV con pares [URL antigua, URL nueva] y procesar en modo vista previa y luego aplicar los cambios masivos.
* Generar un log de las modificaciones realizadas.

### Características Principales
* **Modo Prueba**: busca coincidencias y cuenta apariciones, sin cambios reales.
* **Modo Reemplazo**: respeta la serialización, evitando romper campos serializados de plugins como ACF.
* **Soporte CSV**: sube un archivo con múltiples pares de URLs.
* **Log**: registra los reemplazos en un archivo `url-replacer-log.txt`.
* **Descarga del Log** desde el panel de administración.

## Instalación

1. Descarga y extrae el contenido del plugin en tu carpeta `/wp-content/plugins/`.
2. Asegúrate de que la ruta sea `/wp-content/plugins/url-replacer/`.
3. Activa el plugin desde el panel de administración de WordPress, en “Plugins”.
4. Verás un nuevo menú "URL Replacer" en tu escritorio de WordPress.

## Uso

1. Dirígete a **URL Replacer** → **Modo Prueba** para localizar coincidencias de una URL en la base de datos.
2. Para hacer el reemplazo real, ve a **Reemplazar URLs**, ingresa la antigua y la nueva.
3. Si deseas procesar varias a la vez, usa **Subir CSV** para cargar un archivo sin cabeceras. Cada línea debe ser `URL_ANTIGUA,URL_NUEVA`.
4. Puedes descargar un log de los cambios desde **Descargar Log**.

## FAQ

**P:** ¿Qué sucede con campos serializados?  
**R:** El plugin deserializa, reemplaza recursivamente, y vuelve a serializar para no dañar la estructura.

**P:** ¿Puedo usarlo para encontrar una cadena que no sea URL?  
**R:** Técnicamente sí, el plugin busca una subcadena. Pero está pensado para URLs.

**P:** ¿Qué ocurre si subo un CSV con muchas entradas?  
**R:** Se recomienda hacer una vista previa y luego aplicarlas en bloque. Para sitios grandes, haz un respaldo antes.

## Screenshots
1. Descripción para screenshot-1.png
2. Descripción para screenshot-2.png

## Changelog

### 1.0
* Versión inicial con modo prueba y reemplazo.
* Soporte CSV y logs.

## Upgrade Notice

### 1.0
Versión inicial estable.

## Créditos

Desarrollado por [Rocío Benítez García].

## Licencia

Este plugin se distribuye bajo la [GPL v2 o posterior](https://www.gnu.org/licenses/gpl-2.0.html).
