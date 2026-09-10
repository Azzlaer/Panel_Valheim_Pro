<?php
function panel_settings_path(): string {
    return __DIR__ . '/../data/panel_settings.json';
}

function panel_default_settings(): array {
    return [
        'panel_name' => 'Valheim Enterprise Panel',
        'brand_name' => 'LatinBattle.com',
        'brand_subtitle' => 'Game Server Control Panel',
        'accent' => '#3b82f6',
        'timezone' => 'America/Santiago',
        'backup_retention' => 15,
        'auto_refresh_seconds' => 10,
        'show_system_status' => true,
        'version' => '2.1.0-alpha'
    ];
}

function panel_load_settings(): array {
    $defaults = panel_default_settings();
    $path = panel_settings_path();
    if (!is_file($path)) return $defaults;
    $raw = @file_get_contents($path);
    $data = @json_decode((string)$raw, true);
    if (!is_array($data)) return $defaults;
    return array_replace($defaults, $data);
}

function panel_save_settings(array $settings): bool {
    $path = panel_settings_path();
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) return false;
    $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $json !== false && @file_put_contents($path, $json . PHP_EOL, LOCK_EX) !== false;
}
