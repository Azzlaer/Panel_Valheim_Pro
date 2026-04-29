<?php
require_once __DIR__ . '/../config.php'; // ✅ corregido el path

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}
?>
<div class="container mt-4">
    <h2>🕹️ RCON – Comandos Remotos</h2>
<p style="text-align: center;"><a title="Comandos RCON" href="https://github.com/Tristan-dvr/ValheimRcon/blob/master/commands.md" target="_blank">Se necesita tener el MOD: ValheimRCON - Comandos RCON</a></p>
    <p class="text-muted">
        Envía comandos RCON al servidor de Valheim (requiere el mod ValheimRcon activo).
        La respuesta del servidor se mostrará en la consola inferior.
    </p>

    <div class="input-group mb-3">
        <input type="text" id="rconCommand" class="form-control" placeholder="Escribe un comando RCON…">
        <button class="btn btn-primary" onclick="sendRcon()">▶️ Enviar</button>
    </div>

    <pre id="rconOutput" style="background:#000; color:#0f0; padding:15px; height:400px; overflow-y:auto; border-radius:10px;">
--- Esperando comandos RCON ---
    </pre>
</div>

<script>
function sendRcon(){
    const cmd = document.getElementById('rconCommand').value.trim();
    if (!cmd) { alert('Ingresa un comando RCON'); return; }

    const outBox = document.getElementById('rconOutput');
    outBox.textContent += "\n> " + cmd + "\nEnviando...\n";

    fetch('api.php?action=rcon_send', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'command=' + encodeURIComponent(cmd)
    })
    .then(r => r.json())
    .then(j => {
        if (j && j.ok) {
            outBox.textContent += j.response + "\n";
        } else {
            outBox.textContent += "❌ Error: " + (j.error || 'Desconocido') + "\n";
        }
        outBox.scrollTop = outBox.scrollHeight;
    })
    .catch(err => {
        console.error(err);
        outBox.textContent += "⚠️ Error de red\n";
        outBox.scrollTop = outBox.scrollHeight;
    });

    document.getElementById('rconCommand').value = '';
}
</script>
