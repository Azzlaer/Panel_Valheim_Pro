<?php
require_once __DIR__ . '/world_manager.php';

function br_backup_root(): string {
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'worlds';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        throw new RuntimeException('No se pudo crear backups/worlds');
    }
    return $dir;
}

function br_manifest_path(string $zip): string {
    return $zip . '.json';
}

function br_zip_add_dir(ZipArchive $zip, string $sourceDir, string $prefix): int {
    $count = 0;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($it as $file) {
        if (!$file->isFile()) continue;
        $real = $file->getRealPath();
        if (!$real) continue;
        $rel = str_replace('\\','/', substr($real, strlen($sourceDir) + 1));
        if ($zip->addFile($real, trim($prefix,'/').'/'.$rel)) $count++;
    }
    return $count;
}

function br_create_world_backup(string $world, string $reason = 'manual'): array {
    if (wm_server_running()) {
        throw new RuntimeException('Detén el servidor antes de crear un backup consistente desde el panel');
    }
    if (!class_exists('ZipArchive')) throw new RuntimeException('ZipArchive no está habilitado');
    if (!wm_safe_name($world)) throw new RuntimeException('Nombre de mundo inválido');

    $scan = wm_scan();
    $found = null;
    foreach ($scan['worlds'] as $w) {
        if (strcasecmp($w['name'], $world) === 0) { $found = $w; break; }
    }
    if (!$found) throw new RuntimeException('Mundo no encontrado');

    $stamp = date('Ymd_His');
    $safeWorld = preg_replace('/[^A-Za-z0-9_.-]/','_', $world);
    $zipPath = br_backup_root() . DIRECTORY_SEPARATOR . $safeWorld . '_' . $reason . '_' . $stamp . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE|ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('No se pudo crear el ZIP');
    }

    $root = wm_worlds_dir();
    $files = 0;

    if (($found['format'] ?? '') === 'directory_v1') {
        $src = $root . DIRECTORY_SEPARATOR . $world;
        $files += br_zip_add_dir($zip, $src, $world);
    } else {
        foreach (['.fwl','.db','.db.old','.fwl.old','.old'] as $suffix) {
            $f = $root . DIRECTORY_SEPARATOR . $world . $suffix;
            if (is_file($f) && $zip->addFile($f, basename($f))) $files++;
        }
    }
    $zip->close();

    if (!is_file($zipPath)) throw new RuntimeException('El ZIP no fue creado');

    $manifest = [
        'world' => $world,
        'format' => $found['format'] ?? 'unknown',
        'created_at' => date('c'),
        'reason' => $reason,
        'files' => $files,
        'size_bytes' => filesize($zipPath),
        'source_mtime' => $found['mtime'] ?? null,
        'complete_marker' => (bool)($found['has_complete_marker'] ?? false),
        'chunk_count' => (int)($found['chunk_count'] ?? 0),
    ];
    @file_put_contents(br_manifest_path($zipPath), json_encode($manifest, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), LOCK_EX);

    return ['zip'=>$zipPath,'name'=>basename($zipPath),'manifest'=>$manifest];
}

function br_list(): array {
    $dir = br_backup_root();
    $items = [];
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*.zip') ?: [] as $zip) {
        $manifest = [];
        $mf = br_manifest_path($zip);
        if (is_file($mf)) {
            $manifest = json_decode((string)file_get_contents($mf), true) ?: [];
        }
        $items[] = [
            'name' => basename($zip),
            'size_bytes' => filesize($zip),
            'mtime' => date('Y-m-d H:i:s', filemtime($zip)),
            'manifest' => $manifest,
        ];
    }
    usort($items, fn($a,$b)=>strcmp($b['mtime'],$a['mtime']));
    return $items;
}

function br_copy_tree(string $src, string $dst): void {
    if (!is_dir($src)) throw new RuntimeException('Directorio origen inválido');
    if (!is_dir($dst) && !@mkdir($dst, 0775, true)) {
        throw new RuntimeException('No se pudo crear directorio destino');
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $rel = substr($item->getPathname(), strlen($src)+1);
        $target = $dst . DIRECTORY_SEPARATOR . $rel;
        if ($item->isDir()) {
            if (!is_dir($target) && !@mkdir($target,0775,true)) throw new RuntimeException('No se pudo crear carpeta');
        } else {
            $parent = dirname($target);
            if (!is_dir($parent) && !@mkdir($parent,0775,true)) throw new RuntimeException('No se pudo crear carpeta');
            if (!@copy($item->getPathname(), $target)) throw new RuntimeException('No se pudo copiar archivo');
        }
    }
}

function br_safe_extract(string $zipPath, string $dest): void {
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) throw new RuntimeException('No se pudo abrir el backup');
    for ($i=0;$i<$zip->numFiles;$i++) {
        $name = $zip->getNameIndex($i);
        if (!is_string($name)) continue;
        $norm = str_replace('\\','/',$name);
        if (str_contains($norm,'../') || str_starts_with($norm,'/') || preg_match('/^[A-Za-z]:/', $norm)) {
            $zip->close();
            throw new RuntimeException('ZIP inseguro: ruta fuera de alcance');
        }
    }
    if (!$zip->extractTo($dest)) {
        $zip->close();
        throw new RuntimeException('No se pudo extraer el backup');
    }
    $zip->close();
}

function br_restore(string $backupName): array {
    if (wm_server_running()) throw new RuntimeException('Detén el servidor antes de restaurar');
    $backupName = basename($backupName);
    $zipPath = br_backup_root() . DIRECTORY_SEPARATOR . $backupName;
    if (!is_file($zipPath)) throw new RuntimeException('Backup no encontrado');

    $manifest = [];
    $mf = br_manifest_path($zipPath);
    if (is_file($mf)) $manifest = json_decode((string)file_get_contents($mf), true) ?: [];
    $world = trim((string)($manifest['world'] ?? ''));
    if (!wm_safe_name($world)) throw new RuntimeException('El backup no contiene metadata válida de mundo');

    $safety = br_create_world_backup($world, 'pre_restore');

    $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vh_restore_' . bin2hex(random_bytes(5));
    if (!@mkdir($tmp, 0775, true)) throw new RuntimeException('No se pudo crear temporal de restauración');

    try {
        br_safe_extract($zipPath, $tmp);
        $root = wm_worlds_dir();
        $format = (string)($manifest['format'] ?? '');

        if ($format === 'directory_v1') {
            $src = $tmp . DIRECTORY_SEPARATOR . $world;
            if (!is_dir($src)) throw new RuntimeException('El backup no contiene la carpeta del mundo');
            $target = $root . DIRECTORY_SEPARATOR . $world;
            if (is_dir($target)) wm_delete_tree($target);
            if (!@rename($src, $target)) {
                br_copy_tree($src, $target);
            }
        } else {
            foreach (['.fwl','.db','.db.old','.fwl.old','.old'] as $suffix) {
                $src = $tmp . DIRECTORY_SEPARATOR . $world . $suffix;
                if (is_file($src)) {
                    $dst = $root . DIRECTORY_SEPARATOR . basename($src);
                    if (!@copy($src, $dst)) throw new RuntimeException('No se pudo restaurar ' . basename($src));
                }
            }
        }

        return [
            'ok'=>true,
            'world'=>$world,
            'restored_from'=>$backupName,
            'safety_backup'=>basename($safety['zip']),
        ];
    } finally {
        if (is_dir($tmp)) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
            foreach($it as $x){$x->isDir()?@rmdir($x->getPathname()):@unlink($x->getPathname());}
            @rmdir($tmp);
        }
    }
}
