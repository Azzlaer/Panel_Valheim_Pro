<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/panel_settings.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}

$settings = panel_load_settings();
$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['panel_settings_save'] ?? '') === '1') {
    $csrf = $_POST['csrf'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        $msg = 'Token CSRF inválido.';
        $msgType = 'danger';
    } else {
        $new = $settings;
        $new['panel_name'] = trim($_POST['panel_name'] ?? $settings['panel_name']);
        $new['brand_name'] = trim($_POST['brand_name'] ?? $settings['brand_name']);
        $new['brand_subtitle'] = trim($_POST['brand_subtitle'] ?? $settings['brand_subtitle']);
        $accent = trim($_POST['accent'] ?? $settings['accent']);
        $new['accent'] = preg_match('/^#[0-9A-Fa-f]{6}$/', $accent) ? $accent : '#3b82f6';
        $new['timezone'] = trim($_POST['timezone'] ?? $settings['timezone']);
        $new['backup_retention'] = max(1, min(100, intval($_POST['backup_retention'] ?? 15)));
        $new['auto_refresh_seconds'] = max(3, min(300, intval($_POST['auto_refresh_seconds'] ?? 10)));
        $new['show_system_status'] = !empty($_POST['show_system_status']);
        if (panel_save_settings($new)) {
            $settings = $new;
            $msg = 'Configuración guardada correctamente.';
        } else {
            $msg = 'No se pudo escribir data/panel_settings.json.';
            $msgType = 'danger';
        }
    }
}
?>
<style>
.settings-wrap .hero,.settings-wrap .cardx{background:rgba(17,24,39,.94);border:1px solid rgba(255,255,255,.08);border-radius:22px;box-shadow:0 18px 40px rgba(0,0,0,.24)}
.settings-wrap .hero{padding:24px;margin-bottom:22px;background:linear-gradient(135deg,rgba(139,92,246,.14),rgba(17,24,39,.7))}
.settings-wrap .hero h2{margin:0;color:#fff;font-weight:800}.settings-wrap .hero p{color:#9ca3af;margin:8px 0 0}
.settings-wrap .cardx{padding:20px;margin-bottom:18px}.settings-wrap .cardx h3{font-size:18px;color:#fff;margin:0 0 16px}
.settings-wrap label{color:#cbd5e1;font-size:13px;font-weight:700;margin-bottom:6px}.settings-wrap .form-control,.settings-wrap .form-select{background:#0b1220;color:#e5e7eb;border:1px solid rgba(255,255,255,.1);border-radius:12px}.settings-wrap .form-control:focus,.settings-wrap .form-select:focus{background:#0b1220;color:#fff;border-color:#60a5fa;box-shadow:none}
</style>
<div class="container-fluid mt-4 settings-wrap">
  <div class="hero"><h2>⚙️ Configuración del Panel</h2><p>Personaliza identidad, apariencia y comportamiento sin editar archivos manualmente.</p></div>
  <?php if ($msg): ?><div class="alert alert-<?= htmlspecialchars($msgType) ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <form method="post" onsubmit="return savePanelSettingsAjax(this);">
    <input type="hidden" name="panel_settings_save" value="1">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <div class="cardx">
      <h3>🎨 Identidad y apariencia</h3>
      <div class="row g-3">
        <div class="col-md-6"><label>Nombre del panel</label><input class="form-control" name="panel_name" value="<?= htmlspecialchars($settings['panel_name']) ?>"></div>
        <div class="col-md-6"><label>Marca</label><input class="form-control" name="brand_name" value="<?= htmlspecialchars($settings['brand_name']) ?>"></div>
        <div class="col-md-8"><label>Subtítulo</label><input class="form-control" name="brand_subtitle" value="<?= htmlspecialchars($settings['brand_subtitle']) ?>"></div>
        <div class="col-md-4"><label>Color principal</label><input class="form-control form-control-color w-100" type="color" name="accent" value="<?= htmlspecialchars($settings['accent']) ?>"></div>
      </div>
    </div>
    <div class="cardx">
      <h3>🧠 Comportamiento</h3>
      <div class="row g-3">
        <div class="col-md-4"><label>Zona horaria</label><input class="form-control" name="timezone" value="<?= htmlspecialchars($settings['timezone']) ?>"></div>
        <div class="col-md-4"><label>Retención de backups</label><input class="form-control" type="number" min="1" max="100" name="backup_retention" value="<?= (int)$settings['backup_retention'] ?>"></div>
        <div class="col-md-4"><label>Auto-refresh (segundos)</label><input class="form-control" type="number" min="3" max="300" name="auto_refresh_seconds" value="<?= (int)$settings['auto_refresh_seconds'] ?>"></div>
        <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="show_system_status" id="showSystem" <?= !empty($settings['show_system_status'])?'checked':'' ?>><label class="form-check-label" for="showSystem">Mostrar estado del sistema en el panel</label></div></div>
      </div>
    </div>
    <button class="btn btn-success btn-lg">💾 Guardar configuración</button>
  </form>
</div>
<script>
window.savePanelSettingsAjax=function(form){
 const fd=new FormData(form);
 fetch('pages/settings.php',{method:'POST',body:fd,credentials:'same-origin'}).then(r=>r.text()).then(html=>{document.getElementById('main').innerHTML=html;}).catch(()=>alert('No se pudo guardar la configuración.'));
 return false;
};
</script>
