# Limpieza del proyecto - Enterprise V2 Phase 2

## Archivos eliminados con alta confianza

- `777_config.php` — copia byte-a-byte de `config.php`; no estaba referenciado.
- `lib.tmp` — placeholder de desarrollo (`placeholder`), sin uso.
- `test_control.php` — herramienta de prueba antigua para start/stop y PID; no estaba enlazada ni incluida.
- `footer.php` — no estaba incluido por ningún archivo; `header.php` ya contiene el cierre completo del documento y el router AJAX.
- `install_legacy.php` — instalador anterior conservado solo como respaldo durante Fase 1; el flujo activo usa `install.php`.
- `launch_valheim_manual_pid.py` — utilidad manual/hardcodeada creada para pruebas; el panel actual usa su propia gestión de procesos/PID y `launch_valheim.ps1`.

## Archivos conservados aunque no estén en el menú principal

Se conservaron por precaución porque contienen funciones reales o pueden ser invocados externamente:

- `pages/crons.php`
- `pages/alerts.php`
- `pages/rdp_port.php`
- `pages/fwl_editor.php`
- `pages/save_editor.php`
- `alerts_worker.py`
- `alerts_worker.php`
- `rcon_alert.py`
- `rcon_restart.py`
- `tools_fwl_api.php`
- `launch_valheim.ps1`

Estos archivos no deben considerarse basura automáticamente: algunos dependen de workers, tareas programadas o acceso manual y una búsqueda estática no puede detectar una llamada externa de Windows Task Scheduler.

## Validaciones realizadas

- Todos los archivos PHP restantes pasan `php -l`.
- Se revisaron las rutas activas del sidebar.
- Se revisaron referencias directas a workers, scripts Python/PowerShell y `tools_fwl_api.php`.
- Se evitó borrar cualquier módulo con lógica funcional aunque no tenga enlace visible en el menú.
