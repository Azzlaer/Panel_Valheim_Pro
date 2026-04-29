# ⚔️ Panel de Administración Valheim – LatinBattle.com

Proyecto desarrollado en colaboración entre **Azzlaer** y **ChatGPT (OpenAI)** para la comunidad de **LatinBattle.com**, diseñado para gestionar servidores dedicados de **Valheim** con control web seguro, monitoreo, automatización y una interfaz web moderna con enfoque administrativo y empresarial.

![Captura 0](https://github.com/Azzlaer/Panel_Valheim_Pro/blob/main/screens_v1/0.png)
![Captura 1](https://github.com/Azzlaer/Panel_Valheim_Pro/blob/main/screens_v1/1.png)
![Captura 2](https://github.com/Azzlaer/Panel_Valheim_Pro/blob/main/screens_v1/2.png)
![Captura 3](https://github.com/Azzlaer/Panel_Valheim_Pro/blob/main/screens_v1/3.png)

---

## 📌 Descripción general

**Valheim Pro Panel** es una solución web pensada para administrar servidores dedicados de Valheim en **Windows 10/11**, integrando en un solo lugar las tareas más importantes de operación, mantenimiento y supervisión.

El panel permite gestionar el servidor, controlar procesos, editar configuraciones, subir plugins, administrar mapas, consultar logs, ejecutar comandos RCON, lanzar actualizaciones por SteamCMD y trabajar con respaldos, todo desde una interfaz oscura, moderna y centralizada.

---

## ✨ Características principales

- ✔️ Autenticación con usuario y contraseña
- ✔️ Protección CSRF en operaciones sensibles
- ✔️ Gestión visual de configuración usando `config.php`
- ✔️ Inicio y detención de servidor usando **PID dedicado**
- ✔️ No finaliza procesos ajenos: mata solo su propia instancia
- ✔️ Detección de estado **Online / Offline** basada en PID real
- ✔️ Panel visual para actualización automática mediante **SteamCMD**
- ✔️ Editor remoto para `servers.json`
- ✔️ Gestión de archivos CFG, INI, YAML y TXT con editor integrado
- ✔️ Gestión de plugins `.dll`, `.disable` y archivos `.db`
- ✔️ Gestión de mapas y archivos de `worlds_local`
- ✔️ Visor de logs del servidor y de SteamCMD
- ✔️ Consola RCON integrada
- ✔️ Gestión de listas: administradores, baneados y permitidos
- ✔️ Módulo de procesos activos de Valheim
- ✔️ Compatible con **Windows 10/11** y servidores dedicados

---

## 🚀 Módulos del panel

### 🖥️ Servidores
- Inicio del servidor con ventana oculta
- Detención segura mediante PID almacenado
- Estado del servidor usando `server.pid`
- Edición de `servers.json` desde modal integrado

### 🔄 Actualización
- Actualización normal del servidor
- Actualización **Pre-Beta / public-test**
- Validación del estado del servidor antes de actualizar
- Integración con SteamCMD

### ⚙️ Configuración
- Exploración de archivos en `CFG_DIR`
- Soporte para `.cfg`, `.ini`, `.yml`, `.yaml`, `.txt`
- Edición con **CodeMirror**
- Guardado y eliminación desde el panel

### 📊 Plugins / Mods
- Subida de archivos `.dll` y `.db`
- Habilitar / deshabilitar plugins mediante renombrado
- Eliminación directa desde la interfaz
- Barra de progreso de subida

### 🗺️ Mapas
- Subida de archivos de mundos
- Gestión de archivos `.fwl`, `.db`, `.old`
- Eliminación rápida desde el panel
- Organización visual por extensión

### 🗂️ Respaldos
- Creación manual de backups comprimidos
- Listado de archivos ZIP
- Descarga y eliminación de respaldos
- Gestión centralizada de `worlds_local`

### 📂 Listas
- Administración de:
  - `adminlist.txt`
  - `bannedlist.txt`
  - `permittedlist.txt`
- Alta y baja dinámica vía AJAX

### 📜 Logs
- Visor de log del servidor
- Visor de log de SteamCMD
- Limpieza manual de logs
- Autoscroll configurable
- Consola visual estilo terminal

### 🛰️ RCON
- Envío de comandos al servidor
- Respuesta en consola integrada
- Soporte para comandos remotos del mod **ValheimRcon**

### ⚙️ Procesos
- Listado de procesos `valheim_server.exe`
- Identificación del servidor actual
- Finalización manual por PID
- Vista operativa del consumo de memoria

### 🆘 Soporte
- Centro de ayuda y documentación general del proyecto
- Resumen de módulos, tecnologías y estructura

---

## 📌 ¿Por qué este panel es diferente?

La mayoría de paneles caseros para Valheim terminan procesos usando el nombre del ejecutable:

```bat
taskkill /IM valheim_server.exe /F
```

Ese enfoque es peligroso cuando existen **múltiples instancias** del servidor, ya que puede cerrar procesos ajenos.

### ✅ Este panel usa un enfoque más seguro

El sistema guarda un **PID exclusivo** en:

```text
server.pid
```

Luego, cuando se desea detener el servidor, se usa:

```bat
taskkill /PID <PID> /F
```

Esto permite:

- aislar correctamente la instancia iniciada por el panel
- evitar cerrar otros servidores abiertos
- mantener una detección de estado más precisa
- mejorar la estabilidad operativa

---

## 🧠 Flujo de ejecución del servidor

1. El panel lee la configuración desde `servers.json`
2. Lanza el servidor con `start /B`
3. Detecta el PID del proceso iniciado
4. Guarda ese PID dentro de `server.pid`
5. Usa ese archivo para:
   - comprobar si el servidor está activo
   - terminar solo el proceso correcto
6. Si el proceso deja de existir, el sistema puede limpiar el PID y marcar el servidor como apagado

👉 Nunca se mata otro proceso ajeno si el flujo se mantiene correctamente.

---

## 🔐 Seguridad

- ✔️ Sesiones obligatorias
- ✔️ Bloqueo de acceso sin login
- ✔️ Token CSRF para acciones críticas
- ✔️ Validación de rutas en operaciones sobre archivos
- ✔️ Restricción de extensiones permitidas en uploads
- ✔️ Manejo de acciones sensibles vía backend centralizado
- ✔️ Eliminación segura del PID al detener proceso

---

## 🧰 Tecnologías utilizadas

- **PHP 8.x**
- **Bootstrap 5**
- **JavaScript / Fetch / AJAX**
- **CodeMirror**
- **PowerShell**
- **Tasklist / Taskkill / Shell de Windows**
- **SteamCMD**
- **JSON**
- **Python 3** para automatizaciones auxiliares

---

## 📂 Archivos importantes

| Archivo | Función |
|--------|--------|
| `config.php` | Define rutas, credenciales, IP, servidor, logs y configuración principal |
| `servers.json` | Configura instancias, ejecutables y parámetros |
| `server.pid` | Guarda el PID único del proceso iniciado |
| `index.php` | Login del panel |
| `dashboard.php` | Entrada principal del sistema |
| `header.php` | Layout base, sidebar y navegación AJAX |
| `api.php` | Endpoints AJAX del sistema |
| `pages/servers.php` | Control de inicio/detención + estado PID |
| `pages/update.php` | Sistema de actualización por SteamCMD |
| `pages/cfg.php` | Gestión de archivos de configuración |
| `pages/plugins.php` | Gestión de plugins/mods |
| `pages/maps.php` | Gestión de mapas y mundos |
| `pages/backups.php` | Gestión de respaldos |
| `pages/lists.php` | Gestión de listas administrativas |
| `pages/logs.php` | Consola de logs |
| `pages/rcon.php` | Consola de comandos RCON |
| `pages/procesos_valheim.php` | Vista de procesos activos |
| `pages/soporte.php` | Centro de ayuda del proyecto |

---

## 📁 Estructura sugerida del proyecto

```text
valheim-panel/
├─ config.php
├─ servers.json
├─ server.pid
├─ index.php
├─ dashboard.php
├─ header.php
├─ footer.php
├─ api.php
├─ install.php
├─ pages/
│  ├─ servers.php
│  ├─ backups.php
│  ├─ maps.php
│  ├─ plugins.php
│  ├─ plugins_manage.php
│  ├─ plugins_upload.php
│  ├─ cfg.php
│  ├─ lists.php
│  ├─ logs.php
│  ├─ update.php
│  ├─ rcon.php
│  ├─ procesos_valheim.php
│  └─ soporte.php
└─ backups/
```

---

## 🛠️ Requisitos recomendados

- **Windows 10 / Windows 11**
- **XAMPP / Apache + PHP**
- **SteamCMD**
- Servidor dedicado de **Valheim**
- Acceso a carpetas locales del servidor
- Mod **ValheimRcon** si se desea usar RCON avanzado

---

## 📈 Ideas de expansión futura

El panel aún puede crecer con funciones como:

- dashboard general con métricas en vivo
- monitoreo CPU / RAM / disco / red
- reinicios programados
- cron jobs visuales
- alertas RCON automáticas
- restauración visual de backups
- historial de jugadores
- accesos rápidos de comandos RCON
- auditoría de acciones administrativas
- soporte multi-servidor real con PID por instancia

---

## 🔗 Repositorio oficial

Para actualizaciones, mejoras o reportes:

**GitHub:**  
https://github.com/Azzlaer/Panel_Valheim_Pro

---

## 📬 Contacto / Comunidad

**Sitio web oficial:**  
https://LatinBattle.com

**Discord de la comunidad:**  
https://discord.gg/mvczduBBVP

**WhatsApp comunidad:**  
https://chat.whatsapp.com/KmmccSSlnvbJgEzETF6TLR

---

## ✨ Créditos

**Desarrollado por:**  
**Azzlaer** — LatinBattle.com

**Asistencia técnica e IA:**  
**ChatGPT — OpenAI**

**Distribución / comunidad:**  
**LatinBattle.com**

---

## 💙 Licencia

Este panel fue desarrollado para uso privado / dedicado dentro de **LatinBattle.com**.

Se permite:

- modificar
- extender
- adaptar a necesidades propias

No se permite:

- revender sin autorización
- redistribuir con falsa autoría
- eliminar los créditos originales del proyecto

---

## 🛡️ Autoría

**Made with ❤️ + ⚙️ by Azzlaer & ChatGPT – OpenAI**

> “Automatizando mundos vikingos con inteligencia artificial” ⚔️✨
