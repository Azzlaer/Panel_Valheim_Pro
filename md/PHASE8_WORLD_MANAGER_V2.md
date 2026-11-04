# Phase 8 — World Manager V2

## Objetivo

Adaptar Panel_Valheim_Pro al formato de mundos introducido por Valheim 1.0 sin perder compatibilidad con mundos legacy.

## Formatos soportados

### Legacy
- `World.fwl`
- `World.db`
- `World.db.old`

### Valheim 1.0
- `World/`
- `_main.N.fwl2`
- `_main.N.db2`
- `*.chunk`
- `_main.N.ok`

### Backups nativos
- `World_backup_YYYYMMDD-HHMMSS/`
- `World_backup_auto-YYYYMMDD-HHMMSS/`

## Cambios

- Nuevo `lib/world_manager.php`.
- Nuevo `worlds_api.php`.
- `pages/maps.php` convertido en World Manager V2.
- Menú renombrado de Mapas a Mundos.
- Versión del panel: `2.7.0-alpha`.
- Detección de mundo activo desde `servers.json`.
- Selección de mundo activo desde el panel.
- Descarga ZIP de mundos y backups.
- Eliminación segura de backups.
- Eliminación de mundos solo con servidor detenido y confirmación escrita.
- Bloqueo de eliminación del mundo activo.
- Métricas de tamaño, archivos, chunks y marcadores `_main.N.ok`.
- Agrupación lógica de backups automáticos y manuales.

## Motivo

El módulo anterior usaba exclusivamente:

```php
glob($dir.'/*.{fwl,db,old}', GLOB_BRACE)
```

Eso no permite gestionar correctamente mundos Valheim 1.0, que se escriben como directorios con múltiples chunks y backups también en forma de directorio.

## Próximos pasos propuestos

1. Restauración de backups 1.0 con Safe Mode.
2. Importación segura de mundos ZIP.
3. Backup consistente verificando marcadores `_main.N.ok`.
4. Retención automática de backups nativos.
5. Thunderstore Mod Manager:
   - buscar paquetes,
   - resolver versiones,
   - descargar ZIP,
   - validar dependencias,
   - instalar en BepInEx,
   - activar/desactivar,
   - actualizar,
   - rollback.
