## Changelog

### [1.0.0] - 2025-03-02

#### Added

- Funcionalidad inicial de búsqueda y reemplazo de URLs
- Modo de prueba para visualizar cambios antes de aplicar
- Soporte para carga masiva mediante archivos CSV
- Sistema de logging completo
- Interfaz de administración intuitiva
- Preservación automática de datos serializados
- Validación y sanitización de entradas

#### Security

- Implementación de nonces para todos los formularios
- Verificación de permisos de usuario
- Sanitización completa de inputs
- Uso de `$wpdb->prepare()` para consultas seguras

### [1.0.1] - 2025-10-02

#### Added

- Tabla de resumen de resultados
- Archivo admin.css para estilos personalizados

#### Improved

- Verificación de compatibilidad mínima de WordPress 6.0 y PHP 7.4
- Refactorización completa del archivo principal para mayor simplicidad
- Optimización del código eliminando complejidades innecesarias
- Estilos CSS para el panel de administración
- Documentación técnica mejorada en README
- Mejoras en la experiencia de usuario (UX) en la interfaz de administración:
  - Contexto claro sobre qué hace cada función
  - Feedback visual mejorado durante el proceso de búsqueda y reemplazo
  - Instrucciones paso a paso
  - Advertencias apropiadas sobre acciones destructivas
  - Mensajes de estado más informativos

#### Technical

- Simplificación de funciones de activación y desactivación
- Eliminación de código redundante y verificaciones innecesarias
- Mejor organización de hooks y constantes del plugin
