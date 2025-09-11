#  Panel Valheim Server

¡Bienvenido al **Panel Valheim Server**! Un panel rápido, sencillo y en PHP para administrar tu servidor de Valheim. 

---

## IMAGENES

![Descripci贸n de la imagen](https://github.com/Azzlaer/Panel-Valheim-Server-/blob/main/1.png)
![Descripci贸n de la imagen](https://github.com/Azzlaer/Panel-Valheim-Server-/blob/main/2.png)
![Descripci贸n de la imagen](https://github.com/Azzlaer/Panel-Valheim-Server-/blob/main/3.png)
![Descripci贸n de la imagen](https://github.com/Azzlaer/Panel-Valheim-Server-/blob/main/4.png)
![Descripci贸n de la imagen](https://github.com/Azzlaer/Panel-Valheim-Server-/blob/main/5.png)



##  ¿Cómo funciona?

Este panel consiste principalmente en:

- `index.php` – Archivo principal que carga la interfaz del panel.
- `servers.json` – Archivo con la configuración de los servidores que deseas manejar.
- Imágenes (`1.png`, `2.png`, ...) que muestran cómo luce la interfaz visualmente.

El funcionamiento básico es:
1. `index.php` lee `servers.json`.
2. Renderiza una interfaz web (posiblemente botones o listas) basada en esa configuración.
3. Permite al usuario interactuar (ej., ver estado del servidor, iniciar/parar servidor, etc.) mediante acciones PHP sobre esa estructura.

---

##  Requisitos de configuración

###  Requisitos mínimos
- **PHP ≥ 7.4** — Para garantizar compatibilidad con sintaxis moderna y funciones comunes.
- Extensiones PHP recomendadas:
  - `json` — Para manejar `servers.json`.
  - `curl` (si planeas hacer peticiones externas o llamadas HTTP).
  - `mbstring` — Para el correcto manejo de cadenas UTF-8.

###  Configuración del entorno
1. Coloca `index.php`, `servers.json` y las imágenes en un servidor web con soporte PHP (Apache, Nginx, etc.).
2. Asegúrate de que el servidor tenga permisos de lectura (y escritura si modificas `servers.json`) para esos archivos.
3. Si usas `.json` como almacenamiento directo, valida que la sintaxis sea correcta (puedes usar [JSONLint](https://jsonlint.com) para verificar).

---

##  Cómo usarlo

1. Edita `servers.json` para definir tu(s) servidor(es). Ejemplo simple:
   ```json
   [
     {
       "name": "Servidor Valheim 1",
       "ip": "127.0.0.1",
       "port": 2456,
       "password": "secreto123"
     }
   ]
