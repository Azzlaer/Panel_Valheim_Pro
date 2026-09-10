<?php

class ValheimRconLiveClient {
    private $fp = null;
    private int $reqId = 0;
    private int $timeout;

    public function __construct(string $host, int $port, int $timeout = 4) {
        $this->timeout = $timeout;
        $this->fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$this->fp) {
            throw new RuntimeException("No se pudo conectar a RCON: {$errstr} ({$errno})");
        }
        stream_set_timeout($this->fp, $timeout);
    }

    private function readPacket(): ?array {
        $lenData = fread($this->fp, 4);
        if (!is_string($lenData) || strlen($lenData) !== 4) return null;
        $length = unpack('V', $lenData)[1] ?? 0;
        if ($length < 10 || $length > 1048576) return null;

        $body = '';
        while (strlen($body) < $length) {
            $chunk = fread($this->fp, $length - strlen($body));
            if ($chunk === false || $chunk === '') break;
            $body .= $chunk;
        }
        if (strlen($body) < 10) return null;

        $id = unpack('V', substr($body, 0, 4))[1] ?? 0;
        if ($id >= 0x80000000) $id -= 0x100000000;
        $type = unpack('V', substr($body, 4, 4))[1] ?? 0;
        $payload = substr($body, 8, max(0, strlen($body) - 10));
        return ['id'=>$id,'type'=>$type,'payload'=>$payload];
    }

    private function sendPacket(int $type, string $payload): array {
        $this->reqId++;
        $id = $this->reqId;
        $data = pack('V', $id) . pack('V', $type) . $payload . "\x00\x00";
        $packet = pack('V', strlen($data)) . $data;
        $written = fwrite($this->fp, $packet);
        if ($written === false) throw new RuntimeException('No se pudo enviar paquete RCON');

        $responses = [];
        $started = microtime(true);
        do {
            $packetData = $this->readPacket();
            if ($packetData === null) break;
            $responses[] = $packetData;
            if (strlen((string)$packetData['payload']) < 4000) break;
        } while ((microtime(true) - $started) < $this->timeout);

        return $responses;
    }

    public function login(string $password): void {
        $responses = $this->sendPacket(3, $password);
        foreach ($responses as $r) {
            if (($r['id'] ?? 0) === -1) throw new RuntimeException('Contraseña RCON incorrecta');
        }
    }

    public function command(string $command): string {
        $responses = $this->sendPacket(2, $command);
        $text = '';
        foreach ($responses as $r) $text .= (string)($r['payload'] ?? '');
        return trim($text);
    }

    public function close(): void {
        if (is_resource($this->fp)) fclose($this->fp);
        $this->fp = null;
    }

    public function __destruct() { $this->close(); }
}

function vlr_command(string $command): string {
    if (!defined('RCON_HOST') || !defined('RCON_PORT') || !defined('RCON_PASS')) {
        throw new RuntimeException('RCON no está configurado en config.php');
    }
    $cli = new ValheimRconLiveClient((string)RCON_HOST, (int)RCON_PORT, 4);
    try {
        $cli->login((string)RCON_PASS);
        return $cli->command($command);
    } finally {
        $cli->close();
    }
}

function vlr_safe_target(string $target): string {
    $target = trim(str_replace(["\r","\n","\0"], '', $target));
    if ($target === '' || strlen($target) > 128) throw new InvalidArgumentException('Jugador/SteamID inválido');
    $target = str_replace('"', '', $target);
    return preg_match('/\s/', $target) ? '"'.$target.'"' : $target;
}

function vlr_parse_stats(string $text): array {
    $out = ['online'=>null,'fps'=>null,'mono_mb'=>null,'heap_mb'=>null,'day'=>null,'objects'=>null,'dead_objects'=>null,'raw'=>$text];
    if (preg_match('/Stats\s*-\s*Online\s+(\d+)\s+FPS\s+([\d\.]+)/i', $text, $m)) { $out['online']=(int)$m[1]; $out['fps']=(float)$m[2]; }
    if (preg_match('/Memory\s*-\s*Mono\s+(\d+)MB,\s*Heap\s+(\d+)MB/i', $text, $m)) { $out['mono_mb']=(int)$m[1]; $out['heap_mb']=(int)$m[2]; }
    if (preg_match('/World\s*-\s*Day\s+(-?\d+),\s*Objects\s+(-?\d+),\s*Dead objects\s+(\d+)/i', $text, $m)) { $out['day']=(int)$m[1]; $out['objects']=(int)$m[2]; $out['dead_objects']=(int)$m[3]; }
    return $out;
}

function vlr_parse_list(string $text): array {
    $lines = preg_split('/\r\n|\r|\n/', trim($text));
    if (!is_array($lines)) return [];
    $items=[];
    foreach ($lines as $line) {
        $line=trim($line);
        if ($line==='' || preg_match('/^(none|no players|0 players)/i',$line)) continue;
        $items[]=$line;
    }
    return array_values(array_unique($items));
}

function vlr_parse_players(string $text): array {
    $lines = vlr_parse_list($text);
    $players=[];
    foreach ($lines as $line) {
        $steamId=''; $name=''; $position='';
        if (preg_match('/\b(\d{15,20})\b/', $line, $m)) $steamId=$m[1];
        if (preg_match('/\(([-\d\.]+)\s*,\s*([-\d\.]+)\s*,\s*([-\d\.]+)\)/', $line, $m)) $position=$m[1].', '.$m[2].', '.$m[3];

        $clean=$line;
        if ($steamId!=='') $clean=str_replace($steamId,'',$clean);
        $clean=preg_replace('/\([^\)]*\)/',' ',$clean);
        $clean=preg_replace('/\b(SteamID|Steam|ID|Player|Name|Position|Pos)\b\s*[:=\-]?/i',' ',$clean);
        $clean=trim(preg_replace('/[\|;,\-]+/',' ',(string)$clean));
        $clean=trim(preg_replace('/\s{2,}/',' ',(string)$clean));
        if ($clean!=='') $name=$clean;
        if ($name==='' && $steamId!=='') $name='Player '.$steamId;
        if ($name==='' && $line!=='') $name=$line;

        $key=$steamId!==''?$steamId:sha1(strtolower($name));
        $players[]=['key'=>$key,'name'=>$name,'steam_id'=>$steamId,'position'=>$position,'raw'=>$line];
    }
    return $players;
}

function vlr_data_file(string $name): string {
    $dir=__DIR__.'/../data'; if(!is_dir($dir)) @mkdir($dir,0777,true);
    return $dir.'/'.$name;
}

function vlr_read_json(string $file, array $default=[]): array {
    if(!is_file($file)) return $default;
    $j=json_decode((string)@file_get_contents($file),true);
    return is_array($j)?$j:$default;
}

function vlr_write_json(string $file, array $data): bool {
    return file_put_contents($file,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX)!==false;
}

function vlr_sync_player_events(array $players): void {
    $cacheFile=vlr_data_file('players_live.json');
    $historyFile=vlr_data_file('players_history.json');
    $old=vlr_read_json($cacheFile,['players'=>[]]);
    $oldPlayers=is_array($old['players']??null)?$old['players']:[];
    $oldMap=[]; foreach($oldPlayers as $p){$oldMap[(string)($p['key']??'')]=$p;}
    $newMap=[]; foreach($players as $p){$newMap[(string)($p['key']??'')]=$p;}
    $history=vlr_read_json($historyFile,[]);
    $now=time();

    foreach($newMap as $k=>$p){ if($k!=='' && !isset($oldMap[$k])){
        $history[]=['ts'=>$now,'date'=>date('Y-m-d H:i:s'),'event'=>'connected','player'=>$p];
        if(function_exists('ec_log')) ec_log('player_connected','Jugador conectado: '.($p['name']??$k),'success',['player'=>$p]);
    }}
    foreach($oldMap as $k=>$p){ if($k!=='' && !isset($newMap[$k])){
        $history[]=['ts'=>$now,'date'=>date('Y-m-d H:i:s'),'event'=>'disconnected','player'=>$p];
        if(function_exists('ec_log')) ec_log('player_disconnected','Jugador desconectado: '.($p['name']??$k),'info',['player'=>$p]);
    }}
    if(count($history)>1000) $history=array_slice($history,-1000);
    vlr_write_json($historyFile,$history);
    vlr_write_json($cacheFile,['updated_at'=>$now,'updated_date'=>date('Y-m-d H:i:s'),'players'=>array_values($players)]);
}

function vlr_mod_detection(): array {
    $cfg=''; $dll=[];
    if(defined('CFG_DIR') && is_dir(CFG_DIR)) {
        $candidate=rtrim(CFG_DIR,'\\/').DIRECTORY_SEPARATOR.'org.tristan.rcon.cfg';
        if(is_file($candidate)) $cfg=$candidate;
    }
    if(defined('PLUGINS_DIR') && is_dir(PLUGINS_DIR)) {
        $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator(PLUGINS_DIR,FilesystemIterator::SKIP_DOTS));
        foreach($it as $f){ if($f->isFile() && preg_match('/rcon/i',$f->getFilename())) $dll[]=$f->getPathname(); }
    }
    return ['detected'=>($cfg!=='' || !empty($dll)),'cfg'=>$cfg,'files'=>$dll];
}
