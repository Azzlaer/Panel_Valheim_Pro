<?php
/**
 * Valheim World Manager V2
 * Supports legacy .fwl/.db saves and Valheim 1.0 directory-based worlds/backups.
 */

function wm_worlds_dir(): string {
    if (!defined('WORLDS_DIR')) {
        throw new RuntimeException('WORLDS_DIR no está definido');
    }
    $dir = realpath(WORLDS_DIR);
    if ($dir === false || !is_dir($dir)) {
        throw new RuntimeException('WORLDS_DIR no existe o no es válido: ' . WORLDS_DIR);
    }
    return rtrim($dir, "\\/");
}

function wm_safe_name(string $name): bool {
    return $name !== ''
        && !str_contains($name, '..')
        && !str_contains($name, '/')
        && !str_contains($name, '\\')
        && !str_contains($name, ':')
        && (bool)preg_match('/^[\pL\pN _().\-]+$/u', $name);
}

function wm_path_inside(string $path, string $base): bool {
    $real = realpath($path);
    $baseReal = realpath($base);
    if ($real === false || $baseReal === false) return false;
    $real = strtolower(rtrim(str_replace('/', '\\', $real), '\\'));
    $baseReal = strtolower(rtrim(str_replace('/', '\\', $baseReal), '\\'));
    return $real === $baseReal || str_starts_with($real . '\\', $baseReal . '\\');
}

function wm_server_running(): bool {
    if (PHP_OS_FAMILY === 'Windows') {
        $out = @shell_exec('tasklist /FI "IMAGENAME eq valheim_server.exe" /FO CSV /NH 2>NUL');
        if (!is_string($out) || trim($out) === '') return false;
        return stripos($out, 'valheim_server.exe') !== false;
    }
    $out = @shell_exec("pgrep -f 'valheim_server' 2>/dev/null");
    return is_string($out) && trim($out) !== '';
}

function wm_active_world(): ?string {
    if (!defined('SERVERS_JSON') || !is_file(SERVERS_JSON)) return null;
    $servers = json_decode((string)@file_get_contents(SERVERS_JSON), true);
    if (!is_array($servers) || empty($servers[0]['params'])) return null;
    $params = (string)$servers[0]['params'];
    if (preg_match('/(?:^|\s)-world\s+(?:"([^"]+)"|(\S+))/i', $params, $m)) {
        return trim($m[1] !== '' ? $m[1] : $m[2]);
    }
    return null;
}

function wm_set_active_world(string $world): bool {
    if (!wm_safe_name($world)) {
        throw new RuntimeException('Nombre de mundo inválido');
    }
    if (!defined('SERVERS_JSON') || !is_file(SERVERS_JSON)) {
        throw new RuntimeException('servers.json no existe');
    }
    $servers = json_decode((string)file_get_contents(SERVERS_JSON), true);
    if (!is_array($servers) || !isset($servers[0])) {
        throw new RuntimeException('servers.json inválido');
    }
    $params = (string)($servers[0]['params'] ?? '');
    $quoted = '-world "' . str_replace('"', '', $world) . '"';
    if (preg_match('/(?:^|\s)-world\s+(?:"[^"]*"|\S+)/i', $params)) {
        $params = preg_replace('/(?:^|\s)-world\s+(?:"[^"]*"|\S+)/i', ' ' . $quoted, $params, 1);
    } else {
        $params = trim($params) . ' ' . $quoted;
    }
    $servers[0]['params'] = trim((string)$params);
    return file_put_contents(
        SERVERS_JSON,
        json_encode($servers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    ) !== false;
}

function wm_parse_backup_name(string $name): ?array {
    if (!preg_match('/^(.+)_backup_(auto-)?(\d{8})-(\d{6})$/i', $name, $m)) {
        return null;
    }
    $stamp = DateTime::createFromFormat('Ymd-His', $m[3] . '-' . $m[4]);
    return [
        'world' => $m[1],
        'backup_type' => !empty($m[2]) ? 'auto' : 'manual',
        'backup_at' => $stamp ? $stamp->format('Y-m-d H:i:s') : null,
        'backup_ts' => $stamp ? $stamp->getTimestamp() : null,
    ];
}

function wm_directory_stats(string $dir): array {
    $bytes = 0;
    $files = 0;
    $dirs = 0;
    $chunks = 0;
    $okMarkers = 0;
    $fwl2 = 0;
    $db2 = 0;
    $latest = @filemtime($dir) ?: 0;

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $latest = max($latest, (int)$item->getMTime());
        if ($item->isDir()) {
            $dirs++;
            continue;
        }
        $files++;
        $bytes += (int)$item->getSize();
        $n = strtolower($item->getFilename());
        if (str_ends_with($n, '.chunk')) $chunks++;
        if (preg_match('/^_main\.\d+\.ok$/', $n)) $okMarkers++;
        if (preg_match('/^_main\.\d+\.fwl2$/', $n)) $fwl2++;
        if (preg_match('/^_main\.\d+\.db2$/', $n)) $db2++;
    }
    return compact('bytes','files','dirs','chunks','okMarkers','fwl2','db2','latest');
}

function wm_scan(): array {
    $root = wm_worlds_dir();
    $active = wm_active_world();
    $worlds = [];
    $backups = [];
    $legacyGroups = [];

    foreach (scandir($root) ?: [] as $name) {
        if ($name === '.' || $name === '..') continue;
        $path = $root . DIRECTORY_SEPARATOR . $name;

        if (is_dir($path)) {
            $stats = wm_directory_stats($path);
            $parsed = wm_parse_backup_name($name);
            $item = [
                'name' => $name,
                'format' => 'directory_v1',
                'size_bytes' => $stats['bytes'],
                'size_mb' => round($stats['bytes'] / 1048576, 2),
                'file_count' => $stats['files'],
                'dir_count' => $stats['dirs'],
                'chunk_count' => $stats['chunks'],
                'ok_markers' => $stats['okMarkers'],
                'has_complete_marker' => $stats['okMarkers'] > 0,
                'has_fwl2' => $stats['fwl2'] > 0,
                'has_db2' => $stats['db2'] > 0,
                'mtime' => $stats['latest'] ? date('Y-m-d H:i:s', $stats['latest']) : null,
                'mtime_ts' => $stats['latest'],
            ];
            if ($parsed) {
                $backups[] = array_merge($item, $parsed);
            } else {
                $item['active'] = $active !== null && strcasecmp($active, $name) === 0;
                $worlds[] = $item;
            }
            continue;
        }

        if (!is_file($path)) continue;
        $lower = strtolower($name);
        if (!preg_match('/\.(fwl|db|old)$/i', $lower)) continue;

        $base = preg_replace('/\.(fwl|db)(\.old)?$|\.old$/i', '', $name);
        if (!isset($legacyGroups[$base])) {
            $legacyGroups[$base] = [
                'name' => $base,
                'format' => 'legacy',
                'files' => [],
                'size_bytes' => 0,
                'mtime_ts' => 0,
                'active' => $active !== null && strcasecmp($active, $base) === 0,
            ];
        }
        $legacyGroups[$base]['files'][] = $name;
        $legacyGroups[$base]['size_bytes'] += (int)filesize($path);
        $legacyGroups[$base]['mtime_ts'] = max($legacyGroups[$base]['mtime_ts'], (int)filemtime($path));
    }

    foreach ($legacyGroups as $g) {
        $g['size_mb'] = round($g['size_bytes'] / 1048576, 2);
        $g['file_count'] = count($g['files']);
        $g['dir_count'] = 0;
        $g['chunk_count'] = 0;
        $g['ok_markers'] = 0;
        $g['has_complete_marker'] = false;
        $g['has_fwl2'] = false;
        $g['has_db2'] = false;
        $g['mtime'] = $g['mtime_ts'] ? date('Y-m-d H:i:s', $g['mtime_ts']) : null;
        $worlds[] = $g;
    }

    usort($worlds, fn($a,$b) => ($b['active'] <=> $a['active']) ?: (($b['mtime_ts'] ?? 0) <=> ($a['mtime_ts'] ?? 0)));
    usort($backups, fn($a,$b) => (($b['backup_ts'] ?? $b['mtime_ts'] ?? 0) <=> ($a['backup_ts'] ?? $a['mtime_ts'] ?? 0)));

    $backupGroups = [];
    foreach ($backups as $b) {
        $backupGroups[$b['world']][] = $b;
    }

    return [
        'root' => $root,
        'active_world' => $active,
        'worlds' => $worlds,
        'backups' => $backups,
        'backup_groups' => $backupGroups,
        'stats' => [
            'worlds' => count($worlds),
            'backups' => count($backups),
            'bytes' => array_sum(array_column($worlds, 'size_bytes')) + array_sum(array_column($backups, 'size_bytes')),
            'new_format_worlds' => count(array_filter($worlds, fn($w) => $w['format'] === 'directory_v1')),
            'legacy_worlds' => count(array_filter($worlds, fn($w) => $w['format'] === 'legacy')),
        ],
    ];
}

function wm_resolve_entry(string $name): string {
    if (!wm_safe_name($name)) throw new RuntimeException('Nombre inválido');
    $root = wm_worlds_dir();
    $path = $root . DIRECTORY_SEPARATOR . $name;
    if (!file_exists($path)) throw new RuntimeException('Elemento no encontrado');
    if (!wm_path_inside($path, $root)) throw new RuntimeException('Ruta fuera de WORLDS_DIR');
    return $path;
}

function wm_delete_tree(string $path): void {
    if (is_file($path) || is_link($path)) {
        if (!@unlink($path)) throw new RuntimeException('No se pudo eliminar: ' . basename($path));
        return;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
        if ($item->isDir()) {
            if (!@rmdir($item->getPathname())) throw new RuntimeException('No se pudo eliminar carpeta: ' . $item->getPathname());
        } else {
            if (!@unlink($item->getPathname())) throw new RuntimeException('No se pudo eliminar archivo: ' . $item->getPathname());
        }
    }
    if (!@rmdir($path)) throw new RuntimeException('No se pudo eliminar carpeta: ' . basename($path));
}

function wm_delete_world(string $name): void {
    if (wm_server_running()) throw new RuntimeException('Detén el servidor antes de eliminar un mundo');
    $active = wm_active_world();
    if ($active !== null && strcasecmp($active, $name) === 0) {
        throw new RuntimeException('No se puede eliminar el mundo activo. Selecciona otro mundo primero.');
    }
    $root = wm_worlds_dir();
    $dir = $root . DIRECTORY_SEPARATOR . $name;
    if (is_dir($dir)) {
        if (!wm_path_inside($dir, $root)) throw new RuntimeException('Ruta fuera de alcance');
        wm_delete_tree($dir);
        return;
    }

    $deleted = 0;
    foreach (['.fwl','.db','.db.old','.fwl.old','.old'] as $suffix) {
        $file = $root . DIRECTORY_SEPARATOR . $name . $suffix;
        if (is_file($file) && @unlink($file)) $deleted++;
    }
    if ($deleted === 0) throw new RuntimeException('Mundo no encontrado');
}

function wm_zip_entry(string $name): array {
    if (!class_exists('ZipArchive')) throw new RuntimeException('ZipArchive no está habilitado');
    if (!wm_safe_name($name)) throw new RuntimeException('Nombre inválido');

    $root = wm_worlds_dir();
    $exact = $root . DIRECTORY_SEPARATOR . $name;
    $tmp = tempnam(sys_get_temp_dir(), 'vhworld_');
    if ($tmp === false) throw new RuntimeException('No se pudo crear archivo temporal');
    @unlink($tmp);
    $zipPath = $tmp . '.zip';

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('No se pudo crear ZIP');
    }

    $added = 0;
    if (is_dir($exact)) {
        if (!wm_path_inside($exact, $root)) throw new RuntimeException('Ruta fuera de alcance');
        $base = dirname($exact);
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($exact, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($it as $file) {
            if (!$file->isFile()) continue;
            $real = $file->getRealPath();
            if (!$real) continue;
            $rel = str_replace('\\', '/', substr($real, strlen($base) + 1));
            if ($zip->addFile($real, $rel)) $added++;
        }
    } elseif (is_file($exact)) {
        if (!wm_path_inside($exact, $root)) throw new RuntimeException('Ruta fuera de alcance');
        if ($zip->addFile($exact, basename($exact))) $added++;
    } else {
        // Mundo legacy: agrupar todos los archivos asociados al mismo basename.
        foreach (['.fwl','.db','.db.old','.fwl.old','.old'] as $suffix) {
            $legacy = $root . DIRECTORY_SEPARATOR . $name . $suffix;
            if (is_file($legacy) && wm_path_inside($legacy, $root)) {
                if ($zip->addFile($legacy, basename($legacy))) $added++;
            }
        }
    }

    if ($added === 0) {
        $zip->close();
        @unlink($zipPath);
        throw new RuntimeException('No se encontraron archivos para descargar');
    }

    $zip->close();
    return ['path'=>$zipPath,'name'=>$name . '.zip'];
}
