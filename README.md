# ⚔️ Panel de Administración Valheim – LatinBattle.com

Proyecto desarrollado en colaboración entre **Azzlaer** y **ChatGPT (OpenAI)** para la comunidad de **LatinBattle.com**, diseñado para gestionar servidores dedicados de **Valheim** con control web seguro, monitoreo y automatización.

![Descripci?n de la imagen](https://github.com/Azzlaer/Panel_Valheim_Pro/blob/main/screens/1.png)
![Descripci?n de la imagen](https://github.com/Azzlaer/Panel_Valheim_Pro/blob/main/screens/2.png)
![Descripci?n de la imagen](https://github.com/Azzlaer/Panel_Valheim_Pro/blob/main/screens/3.png)
![Descripci?n de la imagen](https://github.com/Azzlaer/Panel_Valheim_Pro/blob/main/screens/4.png)
![Descripci?n de la imagen](https://github.com/Azzlaer/Panel_Valheim_Pro/blob/main/screens/5.png)
![Descripci?n de la imagen](https://github.com/Azzlaer/Panel_Valheim_Pro/blob/main/screens/6.png)
![Descripci?n de la imagen](https://github.com/Azzlaer/Panel_Valheim_Pro/blob/main/screens/7.png)
![Descripci?n de la imagen](https://github.com/Azzlaer/Panel_Valheim_Pro/blob/main/screens/8.png)
![Descripci?n de la imagen](https://github.com/Azzlaer/Panel_Valheim_Pro/blob/main/screens/9.png)
![Descripci?n de la imagen](https://github.com/Azzlaer/Panel_Valheim_Pro/blob/main/screens/10.png)

## 📌 Características principales

✔️ Autenticación con usuario y contraseña  
✔️ Protección CSRF en todas las operaciones sensibles  
✔️ Gestión visual de configuración usando `config.php`  
✔️ Inicio y detención de servidor usando **PID dedicado**  
✔️ No finaliza procesos ajenos — mata solamente su propia instancia  
✔️ Detección de estado *Online / Offline* basada en PID real  
✔️ Panel para actualización automática mediante SteamCMD  
✔️ Editor remoto para `servers.json`  
✔️ Verificador dinámico del estado del servidor  
✔️ Compatible con Windows 10/11 y servidores dedicados  

---

## 📌 ¿Por qué este panel es único?

🔹 La mayoría de paneles mata procesos por nombre (`taskkill`)  
   → lo cual rompe múltiples servidores si comparten ejecutable.

💡 Este panel usa un **PID exclusivo almacenado en `server.pid`**,  
permitiendo ejecutar múltiples instancias sin interferir.

---

## 📌 Tecnologías utilizadas

- PHP (sin frameworks, lightweight)  
- Bootstrap UI  
- PowerShell / Shell / Tasklist de Windows  
- SteamCMD integración  
- CSRF Protection  
- JSON Configuración dinámica  

---

## 📌 Archivos importantes

| Archivo | Función |
|---------|--------|
| `config.php` | Define rutas, credenciales, IP, servidor, logs |
| `servers.json` | Configura instancias, ejecutables y parámetros |
| `server.pid` | Guarda el PID único del proceso iniciado |
| `pages/servers.php` | Control de inicio/detención + estado PID |
| `pages/update.php` | Sistema de actualización SteamCMD |
| `api.php` | Maneja todas las acciones AJAX del panel |

---

## 🧠 Flujo de ejecución del servidor

1. El panel ejecuta el servidor con `start /B`
2. Se espera la carga
3. Se detecta el PID real mediante:

   ✓ Coincidencia ruta ejecutable  
   ✓ Coincidencia directorio de trabajo  

4. Se guarda en `server.pid`
5. Para detenerlo se usa: taskkill /PID <PID> /F


👉 Nunca se mata otro proceso ajeno.

---

## 🔐 Seguridad

✔️ Sesiones obligatorias  
✔️ Bloqueo de acceso sin login  
✔️ Token CSRF para formularios  
✔️ Eliminación segura del PID tras detener proceso  

---

## ✨ Créditos

👤 **Desarrollado por:**  
🔹 *Azzlaer* (LatinBattle.com)

🤖 **Asistencia técnica e IA:**
🔹 ChatGPT — OpenAI

🌐 **Distribución para:**  
💥 LatinBattle.com – Comunidad de servidores de Valheim

---

## 📬 Contacto / Soporte

📌 Sitio web oficial:  
➡️ https://LatinBattle.com

📌 Discord de comunidad:  
*(agregar enlace si corresponde)*

📌 Créditos IA:  
*ChatGPT – OpenAI asistió en el diseño del panel y automatizaciones.*

---

## 💙 Licencia

Este panel se desarrolló para uso privado / dedicado dentro de LatinBattle.com.  
Se permite modificar y extender, no revender sin autorización.

---

### 🛡️ Made with ❤️ + ⚙️ by Azzlaer & ChatGPT – OpenAI

> “Automatizando mundos vikingos con inteligencia artificial” ⚔️✨


