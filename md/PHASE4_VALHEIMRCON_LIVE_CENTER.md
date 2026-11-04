# Phase 4 — ValheimRcon Live Center

## Incluye
- Players Live Center por RCON.
- `players`, `serverStats`, `findPlayer`.
- Kick/Ban/Unban y gestión Admin/Permitted.
- Listas admin/ban/permitted.
- Mensajes globales con `showMessage`.
- Cache `data/players_live.json`.
- Historial `data/players_history.json`.
- Eventos de conexión/desconexión integrados en Event Center.
- Health Center detecta `org.tristan.rcon.cfg` y puerto RCON.

## Requisito
Instalar Tristan / ValheimRcon y configurar `BepInEx/config/org.tristan.rcon.cfg` con el mismo host/puerto/password de `config.php`.

## Nota sobre parsing
El panel conserva la respuesta RCON original por jugador y extrae SteamID/posición cuando aparecen en el formato devuelto por el mod. Esto permite compatibilidad con distintas versiones/formateos de ValheimRcon.
