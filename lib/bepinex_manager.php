<?php
/**
 * BepInEx Manager for Valheim Enterprise Panel.
 * Official package: denikson/BepInExPack_Valheim on Thunderstore.
 */

const BM_THUNDERSTORE_API = 'https://thunderstore.io/api/experimental/package/denikson/BepInExPack_Valheim/';

function bm_server_root(): string {
    if (!defined('SERVER_BASE')) {
        throw new RuntimeException('SERVER_BASE no está definido');
    }
    $root = realpath(SERVER_BASE);
    if ($root === false || !is_dir($root)) {
        throw new RuntimeException('SERVER_BASE no existe o no es válido: ' . SERVER_BASE);
    }
    return rtrim($root, "\\/");
}

function bm_data_dir(): string {
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        throw new RuntimeException('No se pudo crear data/');
    }
    return $dir;
}

function bm_state_file(): string {
    return bm_data_dir() . DIRECTORY_SEPARATOR . 'bepinex_state.json';
}

function bm_read_state(): array {
    $file = bm_state_file();
    if (!is_file($file)) return [];
    $data = json_decode((string)@file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function bm_write_state(array $state): bool {
    return @file_put_contents(
        bm_state_file(),
        json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    ) !== false;
}

function bm_http_get(string $url, int $timeout = 25): string {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERAGENT => 'ValheimEnterprisePanel/2.8 (+https://github.com/Azzlaer/Panel_Valheim_Pro)',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if (!is_string($body) || $body === '' || $status < 200 || $status >= 300) {
            throw new RuntimeException('Thunderstore no respondió correctamente' . ($err ? ': ' . $err : ' (HTTP ' . $status . ')'));
        }
        return $body;
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout' => $timeout,
            'follow_location' => 1,
            'header' => "User-Agent: ValheimEnterprisePanel/2.8\r\n",
        ],
        'ssl' => ['verify_peer'=>true, 'verify_peer_name'=>true],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    if (!is_string($body) || $body === '') {
        throw new RuntimeException('No se pudo consultar Thunderstore. Habilita cURL o allow_url_fopen.');
    }
    return $body;
}

function bm_latest_package(): array {
    $raw = bm_http_get(BM_THUNDERSTORE_API);
    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data['latest']) || !is_array($data['latest'])) {
        throw new RuntimeException('Respuesta de Thunderstore inválida');
    }
    $latest = $data['latest'];
    $version = trim((string)($latest['version_number'] ?? ''));
    $download = trim((string)($latest['download_url'] ?? ''));
    if ($version === '' || $download === '') {
        throw new RuntimeException('Thunderstore no entregó versión o URL de descarga');
    }
    $host = strtolower((string)parse_url($download, PHP_URL_HOST));
    if ($host === '' || (!str_ends_with($host, 'thunderstore.io') && !str_ends_with($host, 'thunderstorecdn.com'))) {
        throw new RuntimeException('Thunderstore devolvió un host de descarga no permitido');
    }

    return [
        'namespace' => (string)($latest['namespace'] ?? 'denikson'),
        'name' => (string)($latest['name'] ?? 'BepInExPack_Valheim'),
        'version' => $version,
        'download_url' => $download,
        'description' => (string)($latest['description'] ?? ''),
        'downloads' => (int)($latest['downloads'] ?? 0),
        'date_created' => $latest['date_created'] ?? null,
        'full_name' => (string)($latest['full_name'] ?? ('denikson-BepInExPack_Valheim-' . $version)),
    ];
}

function bm_detect_dll_version(): ?string {
    $dll = bm_server_root() . DIRECTORY_SEPARATOR . 'BepInEx' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'BepInEx.dll';
    if (!is_file($dll)) return null;

    if (PHP_OS_FAMILY === 'Windows') {
        $ps = "(Get-Item -LiteralPath " . escapeshellarg($dll) . ").VersionInfo.FileVersion";
        $cmd = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ' . escapeshellarg($ps);
        $out = trim((string)@shell_exec($cmd));
        if ($out !== '') return preg_replace('/\\s+.*/', '', $out);
    }

    return null;
}

function bm_installed_info(): array {
    $root = bm_server_root();
    $bepDir = $root . DIRECTORY_SEPARATOR . 'BepInEx';
    $dll = $bepDir . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'BepInEx.dll';
    $doorstop = $root . DIRECTORY_SEPARATOR . 'doorstop_config.ini';
    $winhttp = $root . DIRECTORY_SEPARATOR . 'winhttp.dll';
    $state = bm_read_state();

    $installed = is_dir($bepDir) && is_file($dll);
    $trackedVersion = trim((string)($state['package_version'] ?? ''));
    $dllVersion = $installed ? bm_detect_dll_version() : null;

    return [
        'installed' => $installed,
        'tracked' => $trackedVersion !== '',
        'package_version' => $trackedVersion !== '' ? $trackedVersion : null,
        'dll_version' => $dllVersion,
        'bepinex_dir' => $bepDir,
        'core_dll' => $dll,
        'doorstop_present' => is_file($doorstop),
        'winhttp_present' => is_file($winhttp),
        'plugins_dir' => $bepDir . DIRECTORY_SEPARATOR . 'plugins',
        'config_dir' => $bepDir . DIRECTORY_SEPARATOR . 'config',
        'installed_at' => $state['installed_at'] ?? null,
        'source' => $state['source'] ?? null,
        'last_backup' => $state['last_backup'] ?? null,
    ];
}

function bm_version_compare(?string $installed, string $latest): string {
    if (!$installed) return 'unknown';
    $cmp = version_compare($installed, $latest);
    if ($cmp === 0) return 'current';
    return $cmp < 0 ? 'outdated' : 'newer';
}

function bm_server_running(): bool {
    if (PHP_OS_FAMILY === 'Windows') {
        $out = @shell_exec('tasklist /FI "IMAGENAME eq valheim_server.exe" /FO CSV /NH 2>NUL');
        return is_string($out) && stripos($out, 'valheim_server.exe') !== false;
    }
    $out = @shell_exec("pgrep -f 'valheim_server' 2>/dev/null");
    return is_string($out) && trim($out) !== '';
}

function bm_copy_tree(string $src, string $dst): void {
    if (!is_dir($src)) throw new RuntimeException('Directorio origen inválido');
    if (!is_dir($dst) && !@mkdir($dst, 0775, true)) {
        throw new RuntimeException('No se pudo crear: ' . $dst);
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $relative = substr($item->getPathname(), strlen($src) + 1);
        $target = $dst . DIRECTORY_SEPARATOR . $relative;
        if ($item->isDir()) {
            if (!is_dir($target) && !@mkdir($target, 0775, true)) {
                throw new RuntimeException('No se pudo crear carpeta: ' . $target);
            }
        } else {
            $parent = dirname($target);
            if (!is_dir($parent) && !@mkdir($parent, 0775, true)) {
                throw new RuntimeException('No se pudo crear carpeta: ' . $parent);
            }
            if (!@copy($item->getPathname(), $target)) {
                throw new RuntimeException('No se pudo copiar: ' . $relative);
            }
        }
    }
}

function bm_remove_tree(string $dir): void {
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
        if ($item->isDir()) @rmdir($item->getPathname());
        else @unlink($item->getPathname());
    }
    @rmdir($dir);
}

function bm_framework_backup(): ?string {
    $root = bm_server_root();
    $bep = $root . DIRECTORY_SEPARATOR . 'BepInEx';
    if (!is_dir($bep)) return null;
    if (!class_exists('ZipArchive')) throw new RuntimeException('ZipArchive no está habilitado');

    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'bepinex';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        throw new RuntimeException('No se pudo crear backups/bepinex');
    }
    $name = 'bepinex_' . date('Ymd_His') . '.zip';
    $path = $dir . DIRECTORY_SEPARATOR . $name;
    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('No se pudo crear backup BepInEx');
    }

    $targets = [
        'BepInEx',
        'doorstop_config.ini',
        'winhttp.dll',
        'start_game_bepinex.sh',
        'start_server_bepinex.sh',
        'start_game_bepinex.bat',
        'start_server_bepinex.bat',
    ];

    foreach ($targets as $rel) {
        $full = $root . DIRECTORY_SEPARATOR . $rel;
        if (is_file($full)) {
            $zip->addFile($full, str_replace('\\', '/', $rel));
        } elseif (is_dir($full)) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($full, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($it as $file) {
                if (!$file->isFile()) continue;
                $real = $file->getRealPath();
                if (!$real) continue;
                $relative = substr($real, strlen($root) + 1);
                $zip->addFile($real, str_replace('\\', '/', $relative));
            }
        }
    }
    $zip->close();
    return $path;
}

function bm_download_to(string $url, string $dest): void {
    $body = bm_http_get($url, 120);
    if (@file_put_contents($dest, $body, LOCK_EX) === false) {
        throw new RuntimeException('No se pudo guardar el ZIP descargado');
    }
    if (filesize($dest) < 1024) {
        throw new RuntimeException('La descarga parece incompleta');
    }
}

function bm_install_latest(bool $force = false): array {
    if (bm_server_running()) {
        throw new RuntimeException('Detén el servidor Valheim antes de instalar o actualizar BepInEx');
    }
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('ZipArchive no está habilitado en PHP');
    }

    $latest = bm_latest_package();
    $installed = bm_installed_info();
    if (!$force && $installed['installed'] && $installed['package_version'] === $latest['version']) {
        return ['changed'=>false,'latest'=>$latest,'installed'=>$installed,'message'=>'BepInExPack ya está actualizado'];
    }

    $backup = $installed['installed'] ? bm_framework_backup() : null;

    $tmpBase = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vh_bepinex_' . bin2hex(random_bytes(5));
    $zipFile = $tmpBase . '.zip';
    $extract = $tmpBase . '_extract';

    try {
        bm_download_to($latest['download_url'], $zipFile);
        if (!@mkdir($extract, 0775, true) && !is_dir($extract)) {
            throw new RuntimeException('No se pudo crear directorio temporal');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) throw new RuntimeException('No se pudo abrir el ZIP de BepInEx');
        if (!$zip->extractTo($extract)) {
            $zip->close();
            throw new RuntimeException('No se pudo extraer BepInEx');
        }
        $zip->close();

        $packageRoot = $extract . DIRECTORY_SEPARATOR . 'BepInExPack_Valheim';
        if (!is_dir($packageRoot)) {
            // Fallback: localizar la carpeta esperada un nivel más abajo.
            foreach (glob($extract . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $candidate) {
                if (basename($candidate) === 'BepInExPack_Valheim') {
                    $packageRoot = $candidate;
                    break;
                }
            }
        }
        if (!is_dir($packageRoot) || !is_dir($packageRoot . DIRECTORY_SEPARATOR . 'BepInEx')) {
            throw new RuntimeException('El paquete descargado no contiene BepInExPack_Valheim/BepInEx');
        }

        // Merge sobre SERVER_BASE. Plugins/config existentes se conservan salvo archivos que el pack actualice explícitamente.
        bm_copy_tree($packageRoot, bm_server_root());

        $after = bm_installed_info();
        if (!$after['installed']) {
            throw new RuntimeException('BepInEx no quedó instalado correctamente');
        }

        $state = [
            'package' => 'denikson-BepInExPack_Valheim',
            'package_version' => $latest['version'],
            'installed_at' => date('c'),
            'source' => 'Thunderstore',
            'download_url' => $latest['download_url'],
            'last_backup' => $backup,
            'dll_version' => $after['dll_version'],
        ];
        if (!bm_write_state($state)) {
            throw new RuntimeException('BepInEx se instaló, pero no se pudo guardar metadata local');
        }

        return [
            'changed' => true,
            'latest' => $latest,
            'installed' => bm_installed_info(),
            'backup' => $backup,
            'message' => $installed['installed'] ? 'BepInExPack actualizado correctamente' : 'BepInExPack instalado correctamente',
        ];
    } finally {
        @unlink($zipFile);
        bm_remove_tree($extract);
    }
}
