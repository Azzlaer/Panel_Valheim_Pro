#  Valheim Admin Panel

Panel de administracion web para un servidor dedicado de **Valheim** con soporte para RCON, listas de usuarios, alertas programadas y mas.

## Caracteristicas principales

- **Autenticacion**  
  - Login con usuario/contrase?a definidos en `config.php`.

- **Gestion de Servidores**  
  - Iniciar y detener el servidor.  
  - Editar `servers.json` desde la interfaz.

- **Plugins & Config**  
  - Listado de plugins (`.dll`) en `BepInEx\plugins`.  
  - Edicion de archivos `.cfg` con resaltado de sintaxis en modal CodeMirror.

- **Listas de Jugadores**  
  - Administrar `adminlist.txt`, `bannedlist.txt` y `permittedlist.txt` (agregar/eliminar entradas en vivo via AJAX).

- **Visor de Logs**  
  - Lectura en tiempo real con auto-scroll de los logs de servidor y SteamCMD.

- **Actualizacion de Servidor**  
  - Ejecuta actualizaciones (normal o pre-beta) usando `steamcmd.exe` cuando el servidor este detenido.

- **RCON en vivo**  
  - Enviar comandos RCON al servidor y ver la respuesta en tiempo real.

- **Alertas Programadas**  
  - Programa mensajes personalizados que se repiten a intervalos configurables.
  - Persistencia en `alerts.json` y ejecucion mediante un worker en Python.

- **Cron/Restart**  
  - Reinicios programados con avisos de cuenta regresiva via RCON (`save` avisos reinicio).

## ğŸ› ï¸?Requisitos

- **Servidor Web**: XAMPP/WAMP o similar con PHP 8+  
- **Python 3.x** (para el worker de Alertas)  
- **Valheim dedicado** con [ValheimRcon Mod](https://github.com/Tristan-dvr/ValheimRcon)

## ğŸ“‚ Estructura

```
valheim/
©À api.php
©À config.php
©À index.php          # login
©À dashboard.php      # interfaz principal
©À alerts.json
©À servers.json
©À alerts_worker.py   # worker de alertas (Python)
©À pages/
	©¸ servers.php
	©¸ plugins.php
	©¸ cfg.php
	©¸ lists.php
	©¸ logs.php
	©¸ update.php
	©¸ rcon.php
	©¸ alerts.php
©À header.php
©À footer.php
```

## âš™ï¸ ConfiguraciÃ³n

1. **Editar `config.php`**  
   Define:
   ```php
   define('PANEL_USER', 'admin');
   define('PANEL_PASS', 'tu_password');
   define('RCON_HOST', '127.0.0.1');
   define('RCON_PORT', 2458);
   define('RCON_PASSWORD', 'password_rcon');
   ```
   Ajusta las rutas a plugins, cfg, serverDir, etc.

2. **Crear archivos necesarios**  
   ```bash
   echo "[]" > alerts.json
   echo "[]" > servers.json   # o agrega tu servidor segun el ejemplo
   ```

3. **Configurar el worker de Alertas**  
   Programa la ejecucion cada minuto:
   ```powershell
   schtasks /Create /TN "ValheimAlertsWorker" /TR "python C:\xampp\htdocs\valheim\alerts_worker.py" /SC MINUTE /MO 1 /F
   ```
   Para probar manualmente:
   ```powershell
   python C:\xampp\htdocs\valheim\alerts_worker.py
   ```

## ğŸ•¹ï¸?Uso

1. Visita `http://tu-host/valheim/`  
2. Inicia sesiÃ³n con las credenciales de `config.php`.  
3. Usa el menÃº lateral para acceder a las distintas secciones.

## ğŸ”’ Seguridad

- Verifica que el panel solo sea accesible en red segura.  
- Cambia la contrase?a por defecto en `config.php`.  
- Manten los permisos de archivos seguros.

## ğŸ§‘â€ğŸ’?Contribuciones

Pull requests y mejoras son bienvenidas!  
Por favor abre un issue antes de grandes cambios.

## ğŸ“„ Licencia

MIT License.
