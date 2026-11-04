# Valheim Enterprise Panel - Fase 1 V2

## Implementado

- Instalador Wizard V2 de 7 pasos.
- Diagnóstico previo de PHP, shell_exec, ZipArchive, PowerShell y permisos.
- Detección inicial de rutas comunes de SteamCMD y Valheim.
- Login compatible con `password_hash()` / `password_verify()`.
- Compatibilidad temporal con instalaciones antiguas que usan `ADMIN_PASS`.
- Nuevo `Health Center` para diagnóstico del host y módulos.
- Nueva sección `Configuración` basada en `data/panel_settings.json`.
- Personalización de nombre del panel, marca, subtítulo, color, zona horaria, retención de backups y refresco.
- Header actualizado para usar la configuración de marca y mostrar versión.
- Instalador anterior preservado como `install_legacy.php`.

## Archivos nuevos

- `lib/panel_settings.php`
- `data/panel_settings.json`
- `pages/health.php`
- `pages/settings.php`
- `ENTERPRISE_V2_NOTES.md`

## Archivos modificados

- `install.php`
- `index.php`
- `header.php`

## Siguiente fase sugerida

- Dashboard Gaming Overview con CPU/RAM/Uptime/Jugadores.
- Estado STARTING/ONLINE/STOPPING/CRASHED/UPDATING.
- Backup Manager V2 con restauración, retención y progreso.
- Automatización/scheduler visual.
- Separación progresiva de `api.php` en módulos.

## Fase 2 - Game Server Overview
- Nuevo módulo `pages/overview.php` como dashboard principal.
- Estados operativos: ONLINE, OFFLINE, CRASHED y UNTRACKED.
- Métricas de PID, CPU, RAM, uptime y handles del proceso Valheim.
- Estado RCON, SteamCMD, worlds_local, backups y uso de disco.
- Health Score resumido con acceso directo a Health Center.
- Acciones rápidas: iniciar, detener y detectar PID.
- Auto-refresh configurable desde `panel_settings.json`.
- Sidebar reorganizado con Overview como inicio.
- Versión incrementada a 2.1.0-alpha.
- Backups reforzados: control de ZipArchive, permisos y errores JSON legibles.

## Phase 3 — Automation & Event Center (v2.2.0-alpha)
- Automation Center con tareas de backup, restart, Steam Update y RCON.
- Event Center con hasta 500 eventos recientes.
- Estado persistente del servidor.
- Campana de notificaciones en topbar.
- Worker PHP compatible con navegador autenticado o Windows Task Scheduler.
- `run_automation_worker.bat` para ejecución periódica por CLI.

## v2.3.0-alpha / Phase 4
- Players Live Center con ValheimRcon.
- serverStats, players, findPlayer y moderación.
- Historial de conexiones y eventos.
- Health Center RCON/ValheimRcon.
