<?php
/**
 * Helpers for reading/updating the primary Valheim server definition.
 */

function sp_servers(): array {
    if (!defined('SERVERS_JSON') || !is_file(SERVERS_JSON)) {
        throw new RuntimeException('servers.json no existe');
    }
    $data = json_decode((string)file_get_contents(SERVERS_JSON), true);
    if (!is_array($data)) throw new RuntimeException('servers.json inválido');
    return $data;
}

function sp_primary(): array {
    $servers = sp_servers();
    if (!isset($servers[0]) || !is_array($servers[0])) {
        throw new RuntimeException('No existe servidor primario');
    }
    return $servers[0];
}

function sp_save(array $servers): void {
    $json = json_encode($servers, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    if (!is_string($json) || @file_put_contents(SERVERS_JSON, $json, LOCK_EX) === false) {
        throw new RuntimeException('No se pudo guardar servers.json');
    }
}

function sp_params(): string {
    return (string)(sp_primary()['params'] ?? '');
}

function sp_get_arg(string $name): ?string {
    $params = sp_params();
    $n = preg_quote($name, '/');
    if (preg_match('/(?:^|\s)-'.$n.'\s+(?:"([^"]*)"|(\S+))/i', $params, $m)) {
        return $m[1] !== '' ? $m[1] : $m[2];
    }
    return null;
}

function sp_has_flag(string $name): bool {
    return (bool)preg_match('/(?:^|\s)-'.preg_quote($name,'/').'(?:\s|$)/i', sp_params());
}

function sp_set_arg(string $name, ?string $value, bool $quote = true): void {
    $servers = sp_servers();
    $params = (string)($servers[0]['params'] ?? '');
    $pattern = '/(?:^|\s)-'.preg_quote($name,'/').'\s+(?:"[^"]*"|\S+)/i';
    $replacement = '';
    if ($value !== null) {
        $clean = str_replace('"','', trim($value));
        $replacement = ' -'.$name.' '.($quote ? '"'.$clean.'"' : $clean);
    }
    if (preg_match($pattern, $params)) {
        $params = preg_replace($pattern, $replacement, $params, 1);
    } elseif ($value !== null) {
        $params = rtrim($params) . $replacement;
    }
    $servers[0]['params'] = trim(preg_replace('/\s+/', ' ', $params));
    sp_save($servers);
}

function sp_set_flag(string $name, bool $enabled): void {
    $servers = sp_servers();
    $params = (string)($servers[0]['params'] ?? '');
    $pattern = '/(?:^|\s)-'.preg_quote($name,'/').'(?=\s|$)/i';
    $params = preg_replace($pattern, '', $params);
    if ($enabled) $params = trim($params) . ' -' . $name;
    $servers[0]['params'] = trim(preg_replace('/\s+/', ' ', $params));
    sp_save($servers);
}
