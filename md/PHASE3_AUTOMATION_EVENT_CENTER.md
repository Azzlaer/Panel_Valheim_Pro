# Valheim Enterprise Panel — Phase 3

## Automation & Event Center

Versión: **2.2.0-alpha**

### Nuevos módulos

- `pages/automation.php`: creación y administración de tareas.
- `pages/events.php`: historial centralizado de eventos.
- `automation_worker.php`: ejecuta tareas vencidas.
- `run_automation_worker.bat`: wrapper para Programador de tareas de Windows.
- `lib/event_center.php`: almacenamiento de tareas, eventos y estado persistente.
- `data/automation_tasks.json`: tareas programadas.
- `data/events.json`: historial de eventos (máximo 500 registros).
- `data/server_state.json`: estado persistente del servidor.

### Acciones automatizables iniciales

- Backup de `WORLDS_DIR`.
- Reinicio del servidor mediante `rcon_restart.py`.
- SteamCMD Update.
- Mensaje RCON.

### Integración del panel

El sidebar incorpora:

- **⏱️ Automatización**
- **📡 Eventos**

La barra superior incorpora una **campana de eventos nuevos**.

Las operaciones de inicio, detención y detección de PID actualizan el estado persistente y generan eventos.

### Programador de tareas de Windows

Para que las automatizaciones funcionen aunque nadie tenga abierto el navegador, crea una tarea de Windows que ejecute cada minuto:

```text
run_automation_worker.bat
```

Configuración recomendada:

- Ejecutar tanto si el usuario inició sesión como si no.
- Repetir cada 1 minuto indefinidamente.
- Iniciar en la carpeta raíz del panel.
- Ejecutar con una cuenta que tenga acceso a SteamCMD, Valheim y `worlds_local`.

El worker HTTP exige sesión autenticada; por línea de comandos puede ejecutarse sin sesión web.

### Compatibilidad

Los módulos anteriores `alerts.php`, `crons.php`, `alerts_worker.php`, `alerts_worker.py`, `rcon_alert.py` y `rcon_restart.py` se mantienen para evitar romper funciones existentes.
