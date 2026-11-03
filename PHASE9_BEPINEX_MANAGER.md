# Phase 9 — BepInEx Manager

## Objetivo

Agregar instalación y actualización automática de **denikson/BepInExPack_Valheim** desde Thunderstore para preparar el servidor dedicado para mods.

## Fuente oficial

- Package page:
  - https://old.thunderstore.io/c/valheim/p/denikson/BepInExPack_Valheim/
- Metadata API:
  - https://thunderstore.io/api/experimental/package/denikson/BepInExPack_Valheim/

El panel usa:
- `latest.version_number`
- `latest.download_url`

## Funciones

- Detecta si existe `BepInEx/core/BepInEx.dll`.
- Detecta `doorstop_config.ini` y `winhttp.dll`.
- Lee la versión DLL en Windows mediante PowerShell.
- Guarda la versión exacta del paquete Thunderstore instalado en:
  - `data/bepinex_state.json`
- Consulta la última versión disponible en Thunderstore.
- Estados:
  - No instalado
  - Instalado / versión de paquete desconocida
  - Actualizado
  - Update disponible
  - Versión local superior
- Instala la última versión directamente en `SERVER_BASE`.
- Antes de actualizar crea un backup en:
  - `backups/bepinex/`
- Conserva y fusiona la estructura existente de:
  - `BepInEx/plugins`
  - `BepInEx/config`
- Bloquea instalación/actualización si `valheim_server.exe` está ejecutándose.
- Registra eventos en Event Center.

## Archivos nuevos

```text
lib/bepinex_manager.php
bepinex_api.php
pages/bepinex.php
data/bepinex_state.json   (se crea al instalar)
```

## Diferencia entre versión del pack y versión DLL

Thunderstore versiona el paquete `BepInExPack_Valheim` de forma independiente de la versión interna de `BepInEx.dll`.

Por eso una instalación antigua detectada automáticamente puede mostrar:

```text
DLL 5.4.x.x (pack desconocido)
```

Después de instalar o actualizar desde el panel, queda registrada la versión exacta del paquete Thunderstore y la comparación futura es fiable.

## Seguridad

La URL del package API está hardcodeada al paquete oficial.
El downloader valida que el host de descarga pertenezca a Thunderstore/CDN.
No se acepta una URL arbitraria desde el navegador.

## Próximo paso

Construir **Thunderstore Mod Manager** reutilizando esta base:

1. Buscar paquetes.
2. Leer `manifest.json`.
3. Resolver dependencias.
4. Descargar versiones.
5. Instalar en BepInEx.
6. Registrar versión instalada.
7. Detectar updates.
8. Desactivar / eliminar.
9. Rollback.
