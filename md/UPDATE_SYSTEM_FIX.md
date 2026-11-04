# SteamCMD Update System Fix

## Problemas encontrados

1. `pages/update.php` mostraba `+force_install_dir`, pero `api.php?action=steam_update` no lo utilizaba realmente.
2. La API ejecutaba SteamCMD de forma desacoplada y respondía `ok: true` inmediatamente, sin saber si SteamCMD había terminado o fallado.
3. El frontend no consultaba ningún estado de actualización.
4. No había un log específico y simple para diagnosticar el proceso lanzado desde el panel.

## Cambios

- Se añadió `steam_update_worker.ps1`.
- El worker ejecuta SteamCMD con `+force_install_dir` apuntando a `SERVER_BASE`.
- Se crean:
  - `data/steam_update_job.json`
  - `data/steam_update_status.json`
  - `data/steam_update.log`
- Nuevo endpoint: `api.php?action=steam_update_status`.
- Estados:
  - `idle`
  - `starting`
  - `running`
  - `completed`
  - `warning`
  - `error`
- La UI consulta el estado cada 2 segundos.
- Los botones permanecen bloqueados mientras SteamCMD está activo.
- Se muestran las últimas 25 líneas del log.
- `completed` solo se utiliza cuando SteamCMD confirma la instalación completa del App ID 896660.

## Canal Normal

```text
+force_install_dir "SERVER_BASE" +login anonymous +app_update 896660 validate +quit
```

## Canal Pre-Beta

```text
+force_install_dir "SERVER_BASE" +login anonymous +app_update 896660 -beta public-test -betapassword yesimadebackups validate +quit
```
