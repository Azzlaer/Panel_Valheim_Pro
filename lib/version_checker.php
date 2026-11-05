<?php
/**
 * Valheim Dedicated Server version checker.
 * Read-only helpers: local Steam manifest, SteamCMD app info and Iron Gate news.
 */

const VC_APP_ID = 896660;
const VC_IRONGATE_NEWS = 'https://valheim.com/news/';

function vc_manifest_candidates(): array {
    $candidates = [];

    if (defined('SERVER_BASE') && SERVER_BASE !== '') {
        $base = rtrim((string)SERVER_BASE, "\\/");
        // .../steamapps/common/Valheim dedicated server -> .../steamapps/appmanifest_896660.acf
        $steamapps = dirname(dirname($base));
        $candidates[] = $steamapps . DIRECTORY_SEPARATOR . 'appmanifest_' . VC_APP_ID . '.acf';
    }

    if (defined('STEAMCMD_PATH') && STEAMCMD_PATH !== '') {
        $root = rtrim((string)STEAMCMD_PATH, "\\/");
        $candidates[] = $root . DIRECTORY_SEPARATOR . 'steamapps' . DIRECTORY_SEPARATOR . 'appmanifest_' . VC_APP_ID . '.acf';
    }

    if (defined('STEAMCMD_EXE') && STEAMCMD_EXE !== '') {
        $root = dirname((string)STEAMCMD_EXE);
        $candidates[] = $root . DIRECTORY_SEPARATOR . 'steamapps' . DIRECTORY_SEPARATOR . 'appmanifest_' . VC_APP_ID . '.acf';
    }

    return array_values(array_unique($candidates));
}

function vc_find_manifest(): ?string {
    foreach (vc_manifest_candidates() as $path) {
        if (is_file($path) && is_readable($path)) return $path;
    }
    return null;
}

function vc_parse_acf_value(string $acf, string $key): ?string {
    if (preg_match('/"' . preg_quote($key, '/') . '"\s+"([^"]*)"/i', $acf, $m)) {
        return trim((string)$m[1]);
    }
    return null;
}

function vc_local_info(): array {
    $manifest = vc_find_manifest();
    if ($manifest === null) {
        return [
            'ok' => false,
            'buildid' => null,
            'branch' => null,
            'manifest' => null,
            'error' => 'No se encontró appmanifest_' . VC_APP_ID . '.acf en las rutas Steam detectadas.',
        ];
    }

    $raw = @file_get_contents($manifest);
    if ($raw === false) {
        return [
            'ok' => false,
            'buildid' => null,
            'branch' => null,
            'manifest' => $manifest,
            'error' => 'No se pudo leer el manifest local de Steam.',
        ];
    }

    $build = vc_parse_acf_value($raw, 'buildid');
    $beta = vc_parse_acf_value($raw, 'BetaKey');
    if ($beta === null || $beta === '' || strtolower($beta) === 'none') $beta = 'public';

    return [
        'ok' => $build !== null && $build !== '',
        'buildid' => $build,
        'branch' => $beta,
        'manifest' => $manifest,
        'error' => ($build === null || $build === '') ? 'El manifest existe, pero no contiene buildid.' : null,
    ];
}

function vc_steamcmd_exe(): ?string {
    if (defined('STEAMCMD_EXE') && is_file((string)STEAMCMD_EXE)) return (string)STEAMCMD_EXE;
    return null;
}

function vc_parse_public_build(string $output): ?string {
    $pos = stripos($output, '"public"');
    if ($pos === false) $pos = stripos($output, 'public');
    if ($pos === false) return null;

    $slice = substr($output, $pos, 2500);
    if (preg_match('/"buildid"\s+"?(\d+)"?/i', $slice, $m)) {
        return (string)$m[1];
    }
    return null;
}

function vc_remote_steam(): array {
    $exe = vc_steamcmd_exe();
    if ($exe === null) {
        return ['ok'=>false,'buildid'=>null,'error'=>'SteamCMD no fue encontrado en STEAMCMD_EXE.'];
    }

    @set_time_limit(60);

    if (PHP_OS_FAMILY === 'Windows') {
        $quotedExe = '"' . str_replace('"', '""', $exe) . '"';
        $cmd = $quotedExe . ' +login anonymous +app_info_update 1 +app_info_print ' . VC_APP_ID . ' +quit 2>&1';
    } else {
        $cmd = escapeshellarg($exe) . ' +login anonymous +app_info_update 1 +app_info_print ' . VC_APP_ID . ' +quit 2>&1';
    }

    $output = (string)@shell_exec($cmd);
    if (trim($output) === '') {
        return ['ok'=>false,'buildid'=>null,'error'=>'SteamCMD no devolvió información.'];
    }

    $build = vc_parse_public_build($output);
    if ($build === null) {
        return [
            'ok'=>false,
            'buildid'=>null,
            'error'=>'SteamCMD respondió, pero no se pudo identificar el build de la rama public.',
        ];
    }

    return ['ok'=>true,'buildid'=>$build,'branch'=>'public','error'=>null];
}

function vc_http_get(string $url, int $timeout=15): string {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => min(8, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERAGENT => 'ValheimEnterprisePanel/3.3 VersionChecker',
            CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml'],
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false || $code < 200 || $code >= 400) {
            throw new RuntimeException('HTTP Iron Gate falló' . ($err ? ': ' . $err : ' (HTTP ' . $code . ')'));
        }
        return (string)$body;
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout' => $timeout,
            'header' => "User-Agent: ValheimEnterprisePanel/3.3 VersionChecker\r\nAccept: text/html\r\n",
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) throw new RuntimeException('No se pudo conectar con Iron Gate.');
    return (string)$body;
}

function vc_irongate_latest(): array {
    try {
        $html = vc_http_get(VC_IRONGATE_NEWS, 15);

        if (!preg_match('/Patch\s+([0-9]+\.[0-9]+\.[0-9]+)/i', $html, $m)) {
            return ['ok'=>false,'version'=>null,'url'=>VC_IRONGATE_NEWS,'error'=>'No se pudo detectar la versión más reciente en las noticias oficiales.'];
        }

        $version = (string)$m[1];
        return ['ok'=>true,'version'=>$version,'url'=>VC_IRONGATE_NEWS,'error'=>null];
    } catch (Throwable $e) {
        return ['ok'=>false,'version'=>null,'url'=>VC_IRONGATE_NEWS,'error'=>$e->getMessage()];
    }
}

function vc_compare(?string $local, ?string $remote): array {
    if (!$local || !$remote) {
        return ['state'=>'unknown','label'=>'No se pudo comparar'];
    }
    if ($local === $remote) {
        return ['state'=>'current','label'=>'Servidor actualizado'];
    }
    if (ctype_digit($local) && ctype_digit($remote) && (int)$local < (int)$remote) {
        return ['state'=>'outdated','label'=>'Actualización disponible'];
    }
    return ['state'=>'different','label'=>'Build diferente a public'];
}
