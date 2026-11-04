# Valheim Enterprise Panel v3.0.0-alpha — Integrated Roadmap

Esta rama integra las fases posteriores a World Manager V2 y BepInEx Manager.

## Módulos incorporados

### 1. Backup & Restore Center V3
- Backup por mundo, no de `worlds_local` completo.
- Soporte de mundos Valheim 1.0 y legacy.
- Restore seguro.
- Snapshot automático `pre_restore` antes de restaurar.
- Bloqueo si el servidor está en ejecución.
- Manifest JSON junto a cada ZIP.

### 2. World Creation Wizard
- Prepara `-world` y preset oficial.
- Modifiers oficiales:
  - combat
  - deathpenalty
  - resources
  - raids
  - portals
- Set keys:
  - nobuildcost
  - playerevents
  - passivemobs
  - nomap
- Limpia modifiers/setkeys heredados antes de crear un mundo nuevo.
- Opción preparar o preparar + iniciar.

### 3. Network & Crossplay Center
- Puerto base y PORT+1.
- Steam backend / Crossplay PlayFab.
- `-public`.
- `-instanceid`.
- Diagnóstico de regla de Firewall Windows por ejecutable.

### 4. Permissions Center V2
- `adminlist.txt`
- `bannedlist.txt`
- `permittedlist.txt`
- Validación de Platform User ID.
- Advertencia visual cuando la whitelist está activa.

### 5. Linux & Docker Readiness
En Linux comprueba:
- libatomic1
- libpulse-dev
- libpulse0
- GLIBC
- start_server.sh
- docker_start_server.sh
- Docker CLI
- Docker daemon

### 6. Thunderstore Mod Marketplace
- Búsqueda de paquetes Valheim.
- Caché local del catálogo.
- Metadata de versión.
- Dependencias recursivas.
- Integración automática con BepInEx Manager.
- Instalación en SERVER_BASE.
- Registro local de paquetes.
- Detección de updates.
- Desinstalación por archivos registrados.
- Hash SHA-256 por archivo: si un archivo fue modificado/reemplazado por otro paquete, no se elimina silenciosamente.

## Arquitectura nueva

```text
lib/server_params.php
lib/backup_restore.php
lib/thunderstore_manager.php

backup_v3_api.php
world_create_api.php
network_api.php
permissions_api.php
platform_api.php
mods_api.php

pages/backups.php
pages/world_create.php
pages/network.php
pages/lists.php
pages/platform.php
pages/mod_market.php
```

## Thunderstore

El catálogo usa actualmente el endpoint de comunidad Valheim V1 con caché de 30 minutos. Thunderstore ha marcado ese endpoint como deprecado en favor de índices nuevos, por lo que el backend está aislado en `lib/thunderstore_manager.php` para migrar la fuente sin tocar la UI.

La instalación detallada de cada paquete usa la API experimental por namespace/name/version.

## Seguridad

- Operaciones destructivas requieren sesión.
- Operaciones de escritura usan CSRF.
- Mods/BepInEx no se modifican con Valheim ejecutándose.
- ZIPs se validan contra path traversal.
- Descargas de BepInEx validan dominio Thunderstore/CDN.
- Desinstalación de mods compara SHA-256 antes de borrar archivos rastreados.

## Próximos pasos

- Migrar el catálogo a package-listing-index cuando estabilicemos el parser.
- Rollback de mod individual.
- Perfiles/modpacks.
- Clasificación server-side/client-side.
- Compatibilidad Hexium opcional.
- Crash Recovery automático.
- Audit Log multiusuario.
