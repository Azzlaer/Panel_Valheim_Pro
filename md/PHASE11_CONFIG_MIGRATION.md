# Phase 11 — Migration & Configuration Import Center

## Objetivo

Permitir actualizar desde versiones antiguas de Valheim Enterprise Panel sin tener que reconfigurar manualmente rutas, puertos, SteamCMD, RCON y directorios.

## Entradas soportadas

- `config.php` legacy
- JSON de configuración compatible

## Seguridad

El archivo PHP importado:

- NO se incluye con `include` o `require`
- NO se ejecuta
- NO se pasa a `eval()`
- se analiza con `token_get_all()`
- solo se aceptan expresiones simples y constantes conocidas

Las contraseñas legacy almacenadas como:

```php
define('ADMIN_PASS', '...');
```

NO se migran automáticamente.

El usuario debe:
- conservar un `ADMIN_PASS_HASH` existente; o
- establecer una contraseña nueva.

## Staging

Importar un archivo no cambia `config.php`.

Flujo:

1. Subir archivo.
2. Parsear.
3. Mapear claves antiguas.
4. Derivar rutas nuevas.
5. Comparar contra el esquema actual.
6. Mostrar informe.
7. Completar parámetros faltantes.
8. Confirmar.
9. Crear backup del config actual.
10. Aplicar configuración nueva.

## Estados del informe

- ✅ OK
- 🧠 Derivado
- ⚠️ Ruta a revisar
- ❌ Faltante
- ❌ Inválido
- ○ Opcional

## Alias legacy

Se contemplan alias como:

```text
VALHEIM_PATH        -> SERVER_PATH
VALHEIM_SERVER_PATH -> SERVER_PATH
VALHEIM_DIR         -> SERVER_BASE
VALHEIM_BASE        -> SERVER_BASE
WORLD_DIR           -> WORLDS_DIR
WORLDS_PATH         -> WORLDS_DIR
SAVE_DIR            -> SERVER_DIR
SAVEDIR             -> SERVER_DIR
BEPINEX_PLUGINS     -> PLUGINS_DIR
BEPINEX_CONFIG      -> CFG_DIR
STEAMCMD             -> STEAMCMD_EXE
STEAM_CMD            -> STEAMCMD_EXE
RCON_PASSWORD        -> RCON_PASS
RCON_IP              -> RCON_HOST
```

## Derivación automática

Si existe `SERVER_BASE`, se pueden proponer:

- `SERVER_PATH`
- `PLUGINS_DIR`
- `CFG_DIR`

Si existe `SERVER_DIR`:

- `WORLDS_DIR`
- `SERVER_LOG`

Si existe `STEAMCMD_EXE`:

- `STEAMCMD_PATH`

Si existe `STEAMCMD_PATH`:

- `STEAMCMD_LOG`

## Acceso

Instalación nueva:

```text
install.php
  -> 🧭 Importar configuración antigua
  -> migration.php
```

Panel instalado:

```text
Sidebar
  -> 🧭 Migración
```

## Archivos

```text
lib/config_migrator.php
migration_api.php
migration.php
pages/migration.php
```

## Backup

Antes de aplicar una migración sobre una instalación existente:

```text
backups/config/config_YYYYMMDD_HHMMSS.php.bak
```

## Versión

`3.1.0-alpha`
