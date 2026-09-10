<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/panel_settings.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}

$panelSettings = panel_load_settings();

function ov_pid_file(): string {
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'server.pid';
}

function ov_read_pid(): int {
    $file = ov_pid_file();
    if (!is_file($file)) return 0;
    return max(0, (int)trim((string)@file_get_contents($file)));
}

function ov_task_by_pid(int $pid): ?array {
    if ($pid <= 0) return null;
    $out = @shell_exec('tasklist /FI "PID eq ' . $pid . '" /FO CSV /NH');
    if (!$out) return null;
    $line = trim($out);
    if ($line === '' || stripos($line, 'No tasks are running') !== false) return null;
    $row = str_getcsv($line);
    if (!$row || count($row) < 2) return null;
    return ['name' => $row[0] ?? '', 'pid' => (int)($row[1] ?? 0)];
}

function ov_valheim_processes(): array {
    $ps = 'Get-CimInstance Win32_Process | Where-Object { $_.Name -eq \"valheim_server.exe\" } | Select-Object ProcessId,Name,ExecutablePath,CreationDate,CommandLine | ConvertTo-Json -Compress';
    $out = @shell_exec('C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "' . $ps . '"');
    if (!is_string($out) || trim($out) === '') return [];
    $data = @json_decode(trim($out), true);
    if (!is_array($data)) return [];
    if (isset($data['ProcessId'])) $data = [$data];
    return $data;
}

function ov_process_perf(int $pid): array {
    if ($pid <= 0) return ['cpu' => null, 'ram_mb' => null, 'handles' => null, 'uptime_seconds' => null];

    $ps = '$p=Get-Process -Id ' . $pid . ' -ErrorAction SilentlyContinue; ' .
          '$w=Get-CimInstance Win32_PerfFormattedData_PerfProc_Process -ErrorAction SilentlyContinue | Where-Object { $_.IDProcess -eq ' . $pid . ' } | Select-Object -First 1; ' .
          'if($p){[PSCustomObject]@{CPUPercent=if($w){$w.PercentProcessorTime}else{$null};RAM=$p.WorkingSet64;Handles=$p.HandleCount;Start=$p.StartTime.ToString(\"o\")}|ConvertTo-Json -Compress}';

    $out = @shell_exec('C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "' . $ps . '"');
    $d = @json_decode(trim((string)$out), true);
    if (!is_array($d)) return ['cpu' => null, 'ram_mb' => null, 'handles' => null, 'uptime_seconds' => null];

    $uptime = null;
    if (!empty($d['Start'])) {
        try {
            $start = new DateTime($d['Start']);
            $uptime = max(0, time() - $start->getTimestamp());
        } catch (Throwable $e) {}
    }

    return [
        'cpu' => isset($d['CPUPercent']) && $d['CPUPercent'] !== null ? round((float)$d['CPUPercent'], 1) : null,
        'ram_mb' => isset($d['RAM']) ? round(((float)$d['RAM']) / 1048576, 1) : null,
        'handles' => isset($d['Handles']) ? (int)$d['Handles'] : null,
        'uptime_seconds' => $uptime,
    ];
}

function ov_format_uptime(?int $seconds): string {
    if ($seconds === null) return 'N/D';
    $days = intdiv($seconds, 86400);
    $hours = intdiv($seconds % 86400, 3600);
    $mins = intdiv($seconds % 3600, 60);
    if ($days > 0) return $days . 'd ' . $hours . 'h ' . $mins . 'm';
    if ($hours > 0) return $hours . 'h ' . $mins . 'm';
    return $mins . 'm';
}

function ov_backup_info(): array {
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'backups';
    if (!is_dir($dir)) return ['count' => 0, 'latest' => null, 'latest_time' => null, 'total_mb' => 0];
    $files = glob($dir . DIRECTORY_SEPARATOR . '*.zip') ?: [];
    usort($files, fn($a,$b) => (@filemtime($b) ?: 0) <=> (@filemtime($a) ?: 0));
    $total = 0;
    foreach ($files as $f) $total += @filesize($f) ?: 0;
    $latest = $files[0] ?? null;
    return [
        'count' => count($files),
        'latest' => $latest ? basename($latest) : null,
        'latest_time' => $latest ? date('Y-m-d H:i:s', (int)@filemtime($latest)) : null,
        'total_mb' => round($total / 1048576, 1),
    ];
}

function ov_rcon_status(): bool {
    if (!defined('RCON_HOST') || !defined('RCON_PORT')) return false;
    $errno = 0; $errstr = '';
    $fp = @fsockopen(RCON_HOST, (int)RCON_PORT, $errno, $errstr, 0.7);
    if ($fp) { fclose($fp); return true; }
    return false;
}

function ov_disk_info(): array {
    $path = defined('SERVER_BASE') ? SERVER_BASE : __DIR__;
    $total = @disk_total_space($path);
    $free = @disk_free_space($path);
    if (!$total || $free === false) return ['free_gb'=>null,'total_gb'=>null,'used_pct'=>null];
    return [
        'free_gb' => round($free / 1073741824, 1),
        'total_gb' => round($total / 1073741824, 1),
        'used_pct' => round((($total - $free) / $total) * 100, 1),
    ];
}

function ov_health(): array {
    $checks = [];
    $checks[] = ['name'=>'Valheim executable','ok'=>defined('SERVER_PATH') && is_file(SERVER_PATH)];
    $checks[] = ['name'=>'Worlds directory','ok'=>defined('WORLDS_DIR') && is_dir(WORLDS_DIR)];
    $checks[] = ['name'=>'SteamCMD','ok'=>defined('STEAMCMD_EXE') && is_file(STEAMCMD_EXE)];
    $checks[] = ['name'=>'ZipArchive','ok'=>class_exists('ZipArchive')];
    $checks[] = ['name'=>'PowerShell','ok'=>is_file('C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe')];
    $checks[] = ['name'=>'Backups writable','ok'=>is_dir(dirname(__DIR__).DIRECTORY_SEPARATOR.'backups') ? is_writable(dirname(__DIR__).DIRECTORY_SEPARATOR.'backups') : is_writable(dirname(__DIR__))];
    $ok = count(array_filter($checks, fn($c)=>$c['ok']));
    return ['ok'=>$ok,'total'=>count($checks),'checks'=>$checks];
}

function ov_snapshot(): array {
    $pid = ov_read_pid();
    $task = ov_task_by_pid($pid);
    $processes = ov_valheim_processes();
    $registeredOk = $task && strcasecmp((string)$task['name'], 'valheim_server.exe') === 0;

    if ($registeredOk) {
        $state = 'ONLINE';
        $state_class = 'online';
    } elseif ($pid > 0 && !$registeredOk) {
        $state = 'CRASHED';
        $state_class = 'crashed';
    } elseif (count($processes) > 0) {
        $state = 'UNTRACKED';
        $state_class = 'warning';
    } else {
        $state = 'OFFLINE';
        $state_class = 'offline';
    }

    $perf = $registeredOk ? ov_process_perf($pid) : ['cpu'=>null,'ram_mb'=>null,'handles'=>null,'uptime_seconds'=>null];
    $backup = ov_backup_info();
    $disk = ov_disk_info();
    $health = ov_health();

    $serverName = defined('SERVER_NAME') ? SERVER_NAME : 'Valheim Server';
    $serversFile = defined('SERVERS_JSON') && is_file(SERVERS_JSON) ? @json_decode((string)@file_get_contents(SERVERS_JSON), true) : [];
    if (is_array($serversFile) && !empty($serversFile[0]['name'])) $serverName = $serversFile[0]['name'];

    return [
        'ok'=>true,
        'server_name'=>$serverName,
        'state'=>$state,
        'state_class'=>$state_class,
        'pid'=>$registeredOk ? $pid : ($pid ?: 0),
        'valheim_process_count'=>count($processes),
        'cpu'=>$perf['cpu'],
        'ram_mb'=>$perf['ram_mb'],
        'handles'=>$perf['handles'],
        'uptime'=>ov_format_uptime($perf['uptime_seconds']),
        'rcon'=>ov_rcon_status(),
        'backup'=>$backup,
        'disk'=>$disk,
        'health'=>$health,
        'steamcmd'=>defined('STEAMCMD_EXE') && is_file(STEAMCMD_EXE),
        'worlds'=>defined('WORLDS_DIR') && is_dir(WORLDS_DIR),
        'time'=>date('H:i:s'),
    ];
}

if (isset($_GET['json'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(ov_snapshot(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$initial = ov_snapshot();
$refresh = max(5, (int)($panelSettings['auto_refresh_seconds'] ?? 10));
?>

<style>
.ov-wrap{--ov-border:rgba(255,255,255,.08);--ov-card:rgba(17,24,39,.94);}
.ov-hero{background:linear-gradient(135deg,rgba(59,130,246,.18),rgba(16,185,129,.08),rgba(17,24,39,.88));border:1px solid var(--ov-border);border-radius:24px;padding:26px;box-shadow:0 18px 44px rgba(0,0,0,.28);margin-bottom:20px;position:relative;overflow:hidden}
.ov-hero:after{content:"";position:absolute;width:240px;height:240px;border-radius:50%;right:-90px;top:-120px;background:rgba(59,130,246,.13);filter:blur(2px)}
.ov-title{font-size:28px;font-weight:850;color:#fff;margin:0}.ov-sub{color:#94a3b8;margin:7px 0 0}.ov-state{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:9px 13px;font-size:12px;font-weight:800;letter-spacing:.06em;border:1px solid transparent}.ov-state.online{background:rgba(16,185,129,.14);color:#a7f3d0;border-color:rgba(16,185,129,.3)}.ov-state.offline{background:rgba(100,116,139,.14);color:#cbd5e1;border-color:rgba(148,163,184,.2)}.ov-state.crashed{background:rgba(239,68,68,.14);color:#fecaca;border-color:rgba(239,68,68,.3)}.ov-state.warning{background:rgba(245,158,11,.14);color:#fde68a;border-color:rgba(245,158,11,.3)}
.ov-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}.ov-card{background:var(--ov-card);border:1px solid var(--ov-border);border-radius:19px;padding:18px;box-shadow:0 12px 28px rgba(0,0,0,.20)}.ov-label{font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.09em;margin-bottom:7px;font-weight:750}.ov-value{font-size:24px;font-weight:850;color:#fff}.ov-hint{font-size:12px;color:#64748b;margin-top:5px}.ov-layout{display:grid;grid-template-columns:1.35fr .65fr;gap:16px}.ov-panel{background:var(--ov-card);border:1px solid var(--ov-border);border-radius:22px;overflow:hidden;box-shadow:0 16px 36px rgba(0,0,0,.22)}.ov-panel-head{padding:17px 19px;border-bottom:1px solid var(--ov-border);display:flex;align-items:center;justify-content:space-between;gap:12px}.ov-panel-head h3{margin:0;color:#fff;font-size:17px;font-weight:800}.ov-panel-body{padding:18px}.ov-row{display:flex;justify-content:space-between;gap:15px;padding:11px 0;border-bottom:1px solid rgba(255,255,255,.055);font-size:13px}.ov-row:last-child{border-bottom:0}.ov-row span:first-child{color:#94a3b8}.ov-row strong{color:#e5e7eb;text-align:right}.ov-healthbar{height:8px;border-radius:999px;background:rgba(255,255,255,.07);overflow:hidden;margin-top:9px}.ov-healthbar>i{display:block;height:100%;background:linear-gradient(90deg,#10b981,#3b82f6);border-radius:999px}.ov-actions{display:flex;gap:9px;flex-wrap:wrap}.ov-actions .btn{border-radius:11px;font-weight:700}.ov-mini-status{font-size:12px;font-weight:750}.ov-good{color:#86efac}.ov-bad{color:#fca5a5}.ov-warn{color:#fde68a}
@media(max-width:1200px){.ov-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:900px){.ov-layout{grid-template-columns:1fr}}@media(max-width:600px){.ov-grid{grid-template-columns:1fr}.ov-title{font-size:23px}}
</style>

<div class="container-fluid mt-4 ov-wrap">
  <div class="ov-hero">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 position-relative" style="z-index:1">
      <div>
        <div class="ov-state <?= htmlspecialchars($initial['state_class']) ?>" id="ov-state">● <?= htmlspecialchars($initial['state']) ?></div>
        <h2 class="ov-title mt-3" id="ov-server-name"><?= htmlspecialchars($initial['server_name']) ?></h2>
        <p class="ov-sub">Game Server Overview · supervisión operativa de la instancia Valheim en Windows.</p>
      </div>
      <div class="ov-actions">
        <button class="btn btn-outline-light btn-sm" onclick="ovRefresh()">↻ Actualizar</button>
        <button class="btn btn-warning btn-sm" onclick="ovDetectPid()">🔎 Detectar PID</button>
        <button class="btn btn-success btn-sm" onclick="ovServer('start')">▶ Iniciar</button>
        <button class="btn btn-danger btn-sm" onclick="ovServer('stop')">■ Detener</button>
      </div>
    </div>
  </div>

  <div class="ov-grid">
    <div class="ov-card"><div class="ov-label">PID registrado</div><div class="ov-value" id="ov-pid"><?= $initial['pid'] ?: 'N/A' ?></div><div class="ov-hint">Proceso controlado por el panel</div></div>
    <div class="ov-card"><div class="ov-label">CPU proceso</div><div class="ov-value" id="ov-cpu"><?= $initial['cpu'] !== null ? $initial['cpu'].'%' : 'N/D' ?></div><div class="ov-hint">Uso instantáneo de valheim_server.exe</div></div>
    <div class="ov-card"><div class="ov-label">RAM proceso</div><div class="ov-value" id="ov-ram"><?= $initial['ram_mb'] !== null ? $initial['ram_mb'].' MB' : 'N/D' ?></div><div class="ov-hint">Working Set actual</div></div>
    <div class="ov-card"><div class="ov-label">Uptime</div><div class="ov-value" id="ov-uptime"><?= htmlspecialchars($initial['uptime']) ?></div><div class="ov-hint">Tiempo desde el arranque</div></div>
  </div>

  <div class="ov-grid">
    <div class="ov-card"><div class="ov-label">RCON</div><div class="ov-value" id="ov-rcon" style="font-size:20px"><?= $initial['rcon'] ? '🟢 Online' : '🔴 Offline' ?></div><div class="ov-hint">Puerto <?= defined('RCON_PORT') ? (int)RCON_PORT : '-' ?></div></div>
    <div class="ov-card"><div class="ov-label">Backups</div><div class="ov-value" id="ov-backups"><?= (int)$initial['backup']['count'] ?></div><div class="ov-hint" id="ov-backup-latest"><?= htmlspecialchars($initial['backup']['latest_time'] ?: 'Sin respaldos') ?></div></div>
    <div class="ov-card"><div class="ov-label">Disco usado</div><div class="ov-value" id="ov-disk"><?= $initial['disk']['used_pct'] !== null ? $initial['disk']['used_pct'].'%' : 'N/D' ?></div><div class="ov-hint" id="ov-disk-free"><?= $initial['disk']['free_gb'] !== null ? $initial['disk']['free_gb'].' GB libres' : 'No disponible' ?></div></div>
    <div class="ov-card"><div class="ov-label">Health Score</div><div class="ov-value" id="ov-health-score"><?= (int)$initial['health']['ok'] ?>/<?= (int)$initial['health']['total'] ?></div><div class="ov-healthbar"><i id="ov-health-bar" style="width:<?= $initial['health']['total'] ? round(($initial['health']['ok']/$initial['health']['total'])*100) : 0 ?>%"></i></div></div>
  </div>

  <div class="ov-layout">
    <div class="ov-panel">
      <div class="ov-panel-head"><h3>🎮 Estado operativo</h3><span class="ov-mini-status text-secondary" id="ov-updated">Actualizado <?= htmlspecialchars($initial['time']) ?></span></div>
      <div class="ov-panel-body">
        <div class="ov-row"><span>Procesos Valheim detectados</span><strong id="ov-procs"><?= (int)$initial['valheim_process_count'] ?></strong></div>
        <div class="ov-row"><span>Handles del proceso</span><strong id="ov-handles"><?= $initial['handles'] !== null ? (int)$initial['handles'] : 'N/D' ?></strong></div>
        <div class="ov-row"><span>SteamCMD</span><strong id="ov-steam" class="<?= $initial['steamcmd']?'ov-good':'ov-bad' ?>"><?= $initial['steamcmd']?'Disponible':'No detectado' ?></strong></div>
        <div class="ov-row"><span>Directorio worlds_local</span><strong id="ov-worlds" class="<?= $initial['worlds']?'ov-good':'ov-bad' ?>"><?= $initial['worlds']?'Disponible':'No detectado' ?></strong></div>
        <div class="ov-row"><span>Último backup</span><strong id="ov-last-backup"><?= htmlspecialchars($initial['backup']['latest'] ?: 'Ninguno') ?></strong></div>
        <div class="ov-row"><span>Espacio de backups</span><strong id="ov-backup-size"><?= htmlspecialchars((string)$initial['backup']['total_mb']) ?> MB</strong></div>
      </div>
    </div>

    <div class="ov-panel">
      <div class="ov-panel-head"><h3>🩺 Health Center</h3><button class="btn btn-outline-info btn-sm" onclick="ovOpenSection('pages/health')">Ver detalle</button></div>
      <div class="ov-panel-body" id="ov-health-list">
        <?php foreach ($initial['health']['checks'] as $c): ?>
          <div class="ov-row"><span><?= htmlspecialchars($c['name']) ?></span><strong class="<?= $c['ok']?'ov-good':'ov-bad' ?>"><?= $c['ok']?'✓ OK':'✕ Error' ?></strong></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const refreshMs = <?= (int)$refresh ?> * 1000;
  window.ovRefresh = async function(){
    try{
      const r = await fetch('pages/overview.php?json=1&_=' + Date.now(), {credentials:'same-origin'});
      const d = await r.json();
      if(!d.ok) throw new Error(d.error || 'Error');
      const state = document.getElementById('ov-state');
      state.className = 'ov-state ' + d.state_class;
      state.textContent = '● ' + d.state;
      document.getElementById('ov-server-name').textContent = d.server_name;
      document.getElementById('ov-pid').textContent = d.pid || 'N/A';
      document.getElementById('ov-cpu').textContent = d.cpu !== null ? d.cpu + '%' : 'N/D';
      document.getElementById('ov-ram').textContent = d.ram_mb !== null ? d.ram_mb + ' MB' : 'N/D';
      document.getElementById('ov-uptime').textContent = d.uptime;
      document.getElementById('ov-rcon').textContent = d.rcon ? '🟢 Online' : '🔴 Offline';
      document.getElementById('ov-backups').textContent = d.backup.count;
      document.getElementById('ov-backup-latest').textContent = d.backup.latest_time || 'Sin respaldos';
      document.getElementById('ov-disk').textContent = d.disk.used_pct !== null ? d.disk.used_pct + '%' : 'N/D';
      document.getElementById('ov-disk-free').textContent = d.disk.free_gb !== null ? d.disk.free_gb + ' GB libres' : 'No disponible';
      document.getElementById('ov-health-score').textContent = d.health.ok + '/' + d.health.total;
      document.getElementById('ov-health-bar').style.width = (d.health.total ? (d.health.ok/d.health.total*100) : 0) + '%';
      document.getElementById('ov-procs').textContent = d.valheim_process_count;
      document.getElementById('ov-handles').textContent = d.handles !== null ? d.handles : 'N/D';
      document.getElementById('ov-steam').textContent = d.steamcmd ? 'Disponible' : 'No detectado';
      document.getElementById('ov-steam').className = d.steamcmd ? 'ov-good' : 'ov-bad';
      document.getElementById('ov-worlds').textContent = d.worlds ? 'Disponible' : 'No detectado';
      document.getElementById('ov-worlds').className = d.worlds ? 'ov-good' : 'ov-bad';
      document.getElementById('ov-last-backup').textContent = d.backup.latest || 'Ninguno';
      document.getElementById('ov-backup-size').textContent = d.backup.total_mb + ' MB';
      document.getElementById('ov-updated').textContent = 'Actualizado ' + d.time;
      document.getElementById('ov-health-list').innerHTML = d.health.checks.map(c => `<div class="ov-row"><span>${c.name}</span><strong class="${c.ok?'ov-good':'ov-bad'}">${c.ok?'✓ OK':'✕ Error'}</strong></div>`).join('');
    }catch(e){ console.error('Overview refresh:', e); }
  };

  window.ovServer = async function(op){
    const body = new URLSearchParams(); body.set('op',op); body.set('id','1');
    try{
      const r=await fetch('api.php?action=server',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString(),credentials:'same-origin'});
      const text=await r.text(); let d; try{d=JSON.parse(text)}catch(e){throw new Error(text.slice(0,180))}
      alert((d.ok?'✅ ':'❌ ') + (d.msg || d.error || 'Operación finalizada'));
      setTimeout(ovRefresh,900);
    }catch(e){alert('⚠️ '+e.message)}
  };

  window.ovDetectPid = async function(){
    try{
      const r=await fetch('api.php?action=detect_pid',{method:'POST',credentials:'same-origin'});
      const text=await r.text(); let d; try{d=JSON.parse(text)}catch(e){throw new Error(text.slice(0,180))}
      alert((d.ok?'✅ PID detectado: ':'❌ ') + (d.ok?d.pid:(d.error||'No detectado')));
      setTimeout(ovRefresh,600);
    }catch(e){alert('⚠️ '+e.message)}
  };

  window.ovOpenSection = function(section){
    const a=document.querySelector('.sidebar .nav-link[data-section="'+section+'"]');
    if(a) a.click();
  };

  if(window.ovTimer) clearInterval(window.ovTimer);
  window.ovTimer=setInterval(function(){
    if(document.getElementById('ov-state')) ovRefresh();
    else { clearInterval(window.ovTimer); window.ovTimer=null; }
  },refreshMs);
})();
</script>
