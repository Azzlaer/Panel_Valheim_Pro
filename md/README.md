# ⚔️ Valheim Enterprise Panel

Panel web de administración para **Valheim Dedicated Server**, diseñado para gestionar un servidor desde una interfaz moderna tipo **Gaming / Enterprise**, con soporte para procesos, PID, SteamCMD, mapas, backups, BepInEx, RCON, automatizaciones, eventos y jugadores en tiempo real mediante **ValheimRcon**.

> **Versión actual:** `v2.3.0-alpha`  
> **Plataforma principal:** Windows 10/11 + XAMPP  
> **Linux:** soporte experimental / parcial

---

## 🎮 ¿Qué es Valheim Enterprise Panel?

Valheim Enterprise Panel permite administrar un servidor dedicado de Valheim desde el navegador sin tener que operar constantemente desde consola, PowerShell o archivos de configuración.

El proyecto está pensado especialmente para servidores alojados en **Windows**, aunque varios módulos son portables a Linux y la arquitectura se está preparando para soporte multiplataforma.

Incluye:

- panel de control del servidor
- detección y registro de PID
- inicio y detención del servidor
- SteamCMD
- consola RCON
- jugadores online
- moderación
- mapas y mundos
- backups
- plugins BepInEx
- archivos CFG
- logs
- listas de administradores / baneados / permitidos
- Health Center
- Automation Center
- Event Center
- historial de jugadores
- personalización visual
- instalador web guiado

---

# ✨ Características principales

## 🏠 Game Server Overview

Dashboard central con información operativa del servidor:

- estado `ONLINE`
- estado `OFFLINE`
- estado `CRASHED`
- estado `UNTRACKED`
- PID registrado
- CPU
- RAM
- uptime
- handles
- cantidad de procesos Valheim
- estado de RCON
- estado de SteamCMD
- estado de `worlds_local`
- cantidad y tamaño de backups
- espacio en disco
- Health Score
- acciones rápidas

Acciones disponibles:

- ▶️ Iniciar
- 🛑 Detener
- 🔎 Detectar PID
- 🔄 Actualizar

---

## 🖥️ Administración del servidor

Permite administrar la instancia configurada en `servers.json`.

Incluye:

- nombre del servidor
- ruta de `valheim_server.exe`
- parámetros de arranque
- puerto
- estado
- PID
- iniciar servidor
- detener servidor
- editor integrado de `servers.json`

Ejemplo:

```json
[
    {
        "id": 1,
        "name": "Servidor Principal",
        "path": "C:\\Servidores\\Steam\\steamapps\\common\\Valheim dedicated server\\valheim_server.exe",
        "params": "-nographics -batchmode -name \"Mi Servidor\" -port 2456 -world \"World01\" -password \"CAMBIAR_PASSWORD\" -crossplay -savedir \"C:\\Servidores\\Steam\\steamapps\\common\\Valheim dedicated server\\server1\" -public 1"
    }
]
```

---

## 🔎 Detección de PID

El panel utiliza un archivo:

```text
server.pid
```

para asociar la instancia administrada con el proceso real `valheim_server.exe`.

En Windows la detección utiliza:

- PowerShell
- `Get-CimInstance Win32_Process`
- `tasklist`
- `taskkill`

El panel también puede detectar un servidor ejecutándose pero todavía no asociado, mostrándolo como:

```text
UNTRACKED
```

---

# 👥 ValheimRcon Live Center

El módulo **Players Live** utiliza el mod:

**Tristan / ValheimRcon**

Thunderstore:

```text
https://thunderstore.io/c/valheim/p/Tristan/ValheimRcon/
```

Permite consultar jugadores y administrar el servidor mediante RCON.

## Funciones disponibles

- jugadores online
- SteamID / identificador
- posición
- respuesta RCON original
- estadísticas del servidor
- FPS
- memoria
- día del mundo
- consultar jugador
- Kick
- Ban
- Unban
- agregar Admin
- eliminar Admin
- agregar Permitted
- eliminar Permitted
- consultar Admin List
- consultar Ban List
- consultar Permitted List
- mensajes globales

Comandos utilizados:

```text
players
serverStats
findPlayer
kick
ban
unban
addAdmin
removeAdmin
addPermitted
removePermitted
adminlist
banlist
permitted
showMessage
```

---

# 📡 Event Center

El sistema registra eventos importantes del panel.

Ejemplos:

```text
🟢 Servidor iniciado
🔎 PID detectado
🔴 Servidor detenido
👤 Jugador conectado
🚪 Jugador desconectado
💾 Backup creado
⚠️ Automatización fallida
🚫 Jugador baneado
👑 Administrador agregado
📢 Mensaje RCON enviado
```

Los eventos se almacenan en:

```text
data/events.json
```

---

# ⏱️ Automation Center

Permite crear tareas automatizadas para el servidor.

Actualmente soporta tareas como:

- backups
- reinicios
- actualizaciones SteamCMD
- mensajes RCON
- tareas periódicas
- tareas a una hora específica
- activar / desactivar tareas
- ejecutar tareas manualmente

Archivos principales:

```text
automation_worker.php
run_automation_worker.bat
data/automation_tasks.json
```

En Windows puede integrarse con **Programador de tareas de Windows**.

---

# 💾 Backups

El sistema puede crear respaldos ZIP del directorio de mundos.

Funciones:

- crear backup
- listar backups
- tamaño
- fecha
- eliminar backup
- backups automáticos
- validación de `ZipArchive`
- control de permisos
- detección de errores

El módulo requiere:

```text
PHP ZipArchive
```

---

# 🗺️ Mapas y mundos

Administra archivos de:

```text
worlds_local
```

Formatos reconocidos:

```text
.fwl
.db
.old
```

Funciones:

- listar archivos
- subir archivo
- descargar archivo
- eliminar archivo
- ver tamaño

---

# 🧩 BepInEx / Plugins

El panel puede trabajar con:

```text
BepInEx/plugins
BepInEx/config
```

Incluye:

- listado de plugins
- subir plugins
- habilitar / deshabilitar
- administrar DLL
- gestionar archivos CFG
- editar configuraciones desde navegador

---

# 📜 Logs

Permite visualizar:

- log del servidor Valheim
- log de SteamCMD

Configurables mediante:

```php
SERVER_LOG
STEAMCMD_LOG
LOG_MAX_LINES
```

---

# 👑 Listas del servidor

Administra:

```text
adminlist.txt
bannedlist.txt
permittedlist.txt
```

Desde el panel se pueden consultar y modificar estas listas.

---

# 🩺 Health Center

Health Center verifica la instalación del panel y sus dependencias.

Comprueba elementos como:

- PHP
- `shell_exec`
- `ZipArchive`
- PowerShell
- `valheim_server.exe`
- SteamCMD
- `worlds_local`
- backups
- plugins BepInEx
- configuración BepInEx
- RCON
- ValheimRcon
- `server.pid`

Su objetivo es facilitar el diagnóstico cuando alguna función no responde correctamente.

---

# 🎨 Configuración del Panel

La configuración visual del panel se almacena en:

```text
data/panel_settings.json
```

Permite personalizar:

- nombre del panel
- branding
- subtítulo
- color principal
- zona horaria
- retención de backups
- auto-refresh
- versión visible

---

# 📁 Estructura del proyecto

```text
Valheim-Enterprise-Panel/
│
├── api.php
├── config.php
├── dashboard.php
├── header.php
├── index.php
├── install.php
├── logout.php
│
├── automation_worker.php
├── run_automation_worker.bat
├── launch_valheim.ps1
│
├── rcon_alert.py
├── rcon_restart.py
├── alerts_worker.py
├── alerts_worker.php
│
├── servers.json
├── server.pid
│
├── assets/
│   └── style.css
│
├── backups/
│
├── data/
│   ├── automation_tasks.json
│   ├── events.json
│   ├── panel_settings.json
│   ├── players_history.json
│   ├── players_live.json
│   └── server_state.json
│
├── lib/
│   ├── event_center.php
│   ├── panel_settings.php
│   └── rcon_live.php
│
└── pages/
    ├── overview.php
    ├── servers.php
    ├── players.php
    ├── procesos_valheim.php
    ├── rcon.php
    ├── backups.php
    ├── maps.php
    ├── plugins.php
    ├── cfg.php
    ├── lists.php
    ├── logs.php
    ├── update.php
    ├── automation.php
    ├── events.php
    ├── health.php
    ├── settings.php
    └── ...
```

---

# 🪟 Instalación en Windows

## ✅ Plataforma recomendada

El proyecto está desarrollado principalmente para:

```text
Windows 10 / Windows 11
Apache
PHP 8.1+
XAMPP
SteamCMD
Valheim Dedicated Server
```

También se recomienda:

```text
Python 3
PowerShell 5.1+
BepInEx
ValheimRcon
```

---

## 1. Instalar XAMPP

Instala XAMPP con:

- Apache
- PHP

Ejemplo:

```text
C:\xampp
```

Copia el proyecto dentro de:

```text
C:\xampp\htdocs\Panel_Valheim
```

---

## 2. Habilitar extensiones PHP

Edita:

```text
C:\xampp\php\php.ini
```

Comprueba que esté habilitado:

```ini
extension=zip
```

También asegúrate de que `shell_exec` no esté deshabilitado.

Busca:

```ini
disable_functions=
```

Si `shell_exec` aparece en esa lista, deberás retirarlo para utilizar funciones como:

- SteamCMD
- procesos
- PID
- PowerShell
- automatizaciones

Después reinicia Apache.

---

## 3. Instalar SteamCMD

Ejemplo recomendado:

```text
C:\Servidores\Steam\steamcmd.exe
```

Instalar Valheim Dedicated Server:

```bat
steamcmd.exe +login anonymous +force_install_dir "C:\Servidores\Steam\steamapps\common\Valheim dedicated server" +app_update 896660 validate +quit
```

App ID:

```text
896660
```

---

## 4. Abrir el instalador del panel

Desde el navegador:

```text
http://127.0.0.1/Panel_Valheim/install.php
```

El Wizard V2 guía por:

1. Bienvenida
2. Requisitos
3. Administrador
4. Servidor Valheim
5. SteamCMD / RCON
6. Rutas y módulos
7. Finalizar

---

## 5. Configurar rutas

Ejemplo:

```php
define('SERVER_BASE', 'C:\\Servidores\\Steam\\steamapps\\common\\Valheim dedicated server\\');

define('SERVER_PATH',
    'C:\\Servidores\\Steam\\steamapps\\common\\Valheim dedicated server\\valheim_server.exe'
);

define('SERVER_DIR',
    'C:\\Servidores\\Steam\\steamapps\\common\\Valheim dedicated server\\server1\\'
);

define('WORLDS_DIR',
    'C:\\Servidores\\Steam\\steamapps\\common\\Valheim dedicated server\\server1\\worlds_local'
);

define('PLUGINS_DIR',
    'C:\\Servidores\\Steam\\steamapps\\common\\Valheim dedicated server\\BepInEx\\plugins'
);

define('CFG_DIR',
    'C:\\Servidores\\Steam\\steamapps\\common\\Valheim dedicated server\\BepInEx\\config'
);
```

---

# 🛰️ Configurar ValheimRcon

## 1. Instalar BepInEx

Instala una versión de BepInEx compatible con tu servidor Valheim.

El directorio debería contener:

```text
BepInEx/
├── plugins/
└── config/
```

---

## 2. Instalar ValheimRcon

Descarga:

```text
Tristan / ValheimRcon
```

desde Thunderstore.

Copia el plugin correspondiente en:

```text
BepInEx/plugins
```

Al iniciar el servidor se generará su archivo de configuración, normalmente en:

```text
BepInEx/config/org.tristan.rcon.cfg
```

---

## 3. Configurar RCON

Configura el mismo host, puerto y contraseña tanto en ValheimRcon como en el panel.

Ejemplo en `config.php`:

```php
define('RCON_HOST', '127.0.0.1');
define('RCON_PORT', 2974);
define('RCON_PASS', 'CAMBIAR_PASSWORD_RCON');
```

> No publiques tu contraseña RCON en GitHub.

Luego abre:

```text
Health Center
```

y confirma:

```text
✅ ValheimRcon detectado
✅ Puerto RCON disponible
```

---

# ⏱️ Automatizaciones en Windows

Puedes ejecutar:

```text
run_automation_worker.bat
```

manualmente o configurarlo mediante:

```text
Programador de tareas de Windows
```

Recomendación:

```text
Ejecutar cada 1 minuto
```

De esta manera las tareas se procesan aunque ningún usuario tenga abierto el panel.

---

# 🐧 Instalación en Linux

## ⚠️ Estado actual

Linux **todavía no es una plataforma completamente soportada** por esta versión.

La interfaz web, RCON, backups y varias funciones PHP son portables, pero existen componentes específicos de Windows.

Actualmente se utilizan comandos como:

```text
PowerShell
Get-CimInstance
tasklist
taskkill
cmd.exe
start
.bat
```

Por ello las funciones relacionadas con:

- detección PID
- procesos
- iniciar/detener servidor
- métricas del proceso
- Health Center PowerShell
- worker de reinicio
- algunas automatizaciones

requieren adaptación en Linux.

---

## Componentes que pueden funcionar en Linux

Con las rutas y permisos adecuados, estos módulos son mayormente portables:

- login
- frontend
- configuración
- mapas
- backups
- CFG
- plugins
- listas
- logs
- Event Center
- RCON
- Players Live
- ValheimRcon
- historial de jugadores

---

## Requisitos Linux sugeridos

Ejemplo Debian / Ubuntu:

```bash
sudo apt update

sudo apt install -y \
    apache2 \
    php \
    php-cli \
    php-zip \
    php-json \
    python3 \
    unzip
```

Puedes comprobar:

```bash
php -v
php -m | grep zip
```

---

## Permisos

Ejemplo:

```bash
sudo chown -R www-data:www-data /var/www/html/Panel_Valheim
sudo chmod -R 775 /var/www/html/Panel_Valheim/data
sudo chmod -R 775 /var/www/html/Panel_Valheim/backups
```

El usuario de Apache debe poder leer:

- instalación de Valheim
- mundos
- logs
- BepInEx

y escribir en:

- `data`
- `backups`
- `server.pid`

---

## Rutas Linux

Ejemplo:

```php
define('SERVER_BASE',
    '/opt/valheim/'
);

define('SERVER_PATH',
    '/opt/valheim/valheim_server.x86_64'
);

define('SERVER_DIR',
    '/opt/valheim/server1/'
);

define('WORLDS_DIR',
    '/opt/valheim/server1/worlds_local'
);

define('PLUGINS_DIR',
    '/opt/valheim/BepInEx/plugins'
);

define('CFG_DIR',
    '/opt/valheim/BepInEx/config'
);
```

> Cambiar únicamente las rutas **no convierte todavía todos los módulos a Linux**.

---

# 🔧 Adaptaciones requeridas para Linux

Para soporte Linux completo deben reemplazarse:

| Windows | Linux |
|---|---|
| `tasklist` | `ps`, `/proc` o `pgrep` |
| `taskkill` | `kill`, `pkill` |
| PowerShell CIM | `/proc`, `ps`, `pidstat` |
| `.bat` | `.sh` |
| `cmd /c` | `/bin/sh` |
| `start` | `nohup` / systemd |
| `server.pid` + tasklist | `server.pid` + `/proc/<pid>` |
| Windows Task Scheduler | `cron` o systemd timers |

Una futura versión puede implementar una capa:

```text
ProcessManagerWindows
ProcessManagerLinux
```

para hacer transparente el sistema operativo.

---

# 🐧 Automatización Linux

Cuando el worker sea totalmente portable, se podrá ejecutar con cron.

Ejemplo conceptual:

```cron
* * * * * /usr/bin/php /var/www/html/Panel_Valheim/automation_worker.php >/dev/null 2>&1
```

Actualmente algunas tareas internas del worker siguen utilizando comandos específicos de Windows, por lo que deben revisarse antes de usarlas en producción Linux.

---

# 🔐 Seguridad

## Nunca publiques credenciales

Antes de subir el proyecto a GitHub revisa:

```text
config.php
servers.json
alerts.json
*.log
server.pid
```

Especialmente:

```php
ADMIN_PASS
ADMIN_PASSWORD_HASH
RCON_PASS
```

---

## Recomendación para `.gitignore`

```gitignore
config.php
server.pid

backups/*
!backups/.gitkeep

data/events.json
data/players_live.json
data/players_history.json
data/automation_tasks.json
data/server_state.json

alerts.json

*.log
*.bak
*.zip

.vscode/
.idea/
```

---

## No expongas el panel directamente a Internet

Para administración remota se recomienda:

- VPN
- Tailscale
- WireGuard
- Cloudflare Access
- reverse proxy con HTTPS
- firewall
- autenticación adicional

Evita publicar directamente Apache/XAMPP sin protección.

---

# 🔑 Login

El instalador moderno utiliza:

```php
password_hash()
password_verify()
```

Las instalaciones antiguas pueden mantener compatibilidad temporal con:

```php
ADMIN_PASS
```

Se recomienda migrar siempre al sistema de hash.

---

# 🧪 Diagnóstico

Si una función falla:

1. abre **Health Center**
2. verifica rutas
3. verifica `ZipArchive`
4. verifica `shell_exec`
5. comprueba permisos
6. verifica PowerShell en Windows
7. revisa RCON
8. revisa logs de Apache/PHP
9. revisa consola del navegador

Ejemplo común:

```text
Unexpected token '<'
```

normalmente significa que PHP devolvió un Warning/Fatal Error en HTML cuando el frontend esperaba JSON.

---

# 🧰 Archivos importantes

## `config.php`

Configuración principal.

## `servers.json`

Definición y parámetros de las instancias Valheim.

## `server.pid`

PID asociado al servidor administrado.

## `api.php`

Router/API principal del panel.

## `lib/rcon_live.php`

Cliente y lógica del módulo ValheimRcon Live Center.

## `lib/event_center.php`

Eventos y actividad.

## `automation_worker.php`

Procesamiento de tareas programadas.

---

# 📈 Roadmap

Algunas mejoras previstas:

- [ ] soporte Linux completo
- [ ] abstracción Windows/Linux de procesos
- [ ] integración systemd
- [ ] Dashboard configurable
- [ ] métricas históricas CPU/RAM
- [ ] Analytics Center
- [ ] Crash Recovery
- [ ] Safe Mode
- [ ] restauración de backups
- [ ] Plugin Manager V2
- [ ] Theme Manager
- [ ] usuarios y roles
- [ ] Audit Log
- [ ] notificaciones Discord
- [ ] actualización automática del panel
- [ ] soporte multi-servidor completo
- [ ] soporte multi-game

---

# 🖥️ Compatibilidad

| Plataforma | Estado |
|---|---|
| Windows 11 + XAMPP | ✅ Recomendado |
| Windows 10 + XAMPP | ✅ Compatible |
| Windows Server | 🟢 Debería funcionar |
| Linux + Apache/PHP | 🟡 Parcial / experimental |
| Linux + Nginx/PHP-FPM | 🟡 Parcial / experimental |

---

# ❤️ Créditos

Proyecto desarrollado y personalizado para administración de servidores Valheim.

**Azzlaer / LatinBattle**

Desarrollo y asistencia:

**ChatGPT · OpenAI**

ValheimRcon:

**Tristan**

---

# ⚠️ Disclaimer

Este proyecto es una herramienta comunitaria independiente.

No está afiliado oficialmente con:

- Iron Gate AB
- Coffee Stain Publishing
- Valve
- Steam
- Thunderstore
- BepInEx

Valheim y sus marcas pertenecen a sus respectivos propietarios.

---

# 📜 Licencia

Define aquí la licencia que deseas utilizar para el repositorio.

Ejemplos recomendados:

```text
MIT
GPL-3.0
Apache-2.0
```

Si el proyecto será público, se recomienda agregar un archivo:

```text
LICENSE
```

en la raíz del repositorio.

---

## ⚔️ Valheim Enterprise Panel

> Administra. Automatiza. Supervisa. Juega.
