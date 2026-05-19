<?php
require_once __DIR__ . '/config.php';

// Si ya está logueado, ir al dashboard
if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

// Proceso de login
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
        $_SESSION['logged_in'] = true;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valheim Control Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root{
            --bg-main: #0b0f14;
            --bg-card: rgba(17, 24, 39, 0.88);
            --bg-input: #111827;
            --border: rgba(255,255,255,0.08);
            --text-main: #f3f4f6;
            --text-soft: #9ca3af;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --success: #10b981;
            --danger: #ef4444;
            --shadow: 0 20px 60px rgba(0,0,0,0.45);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(59,130,246,0.14), transparent 28%),
                radial-gradient(circle at bottom right, rgba(16,185,129,0.08), transparent 22%),
                linear-gradient(135deg, #0a0f16 0%, #0f1722 45%, #0b1220 100%);
            color: var(--text-main);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-shell {
            width: 100%;
            max-width: 1080px;
            min-height: 620px;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            border: 1px solid var(--border);
            border-radius: 24px;
            overflow: hidden;
            background: rgba(10, 15, 25, 0.72);
            backdrop-filter: blur(16px);
            box-shadow: var(--shadow);
        }

        .login-brand {
            position: relative;
            padding: 56px 48px;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0)),
                linear-gradient(135deg, rgba(59,130,246,0.16), rgba(15,23,42,0.4));
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .brand-top .eyebrow {
            display: inline-block;
            font-size: 12px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #93c5fd;
            margin-bottom: 18px;
            font-weight: 700;
        }

        .brand-top h1 {
            font-size: 42px;
            line-height: 1.05;
            font-weight: 800;
            margin: 0 0 18px;
        }

        .brand-top p {
            font-size: 16px;
            line-height: 1.7;
            color: var(--text-soft);
            max-width: 520px;
            margin: 0;
        }

        .feature-list {
            display: grid;
            gap: 14px;
            margin-top: 40px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 14px;
            color: #dbeafe;
        }

        .feature-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 12px rgba(59,130,246,0.75);
            flex-shrink: 0;
        }

        .brand-footer {
            color: var(--text-soft);
            font-size: 13px;
        }

        .login-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 32px;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0));
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 34px 30px 28px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.28);
        }

        .login-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(59,130,246,0.12);
            color: #bfdbfe;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            margin-bottom: 18px;
        }

        .login-card h2 {
            margin: 0 0 8px;
            font-size: 28px;
            font-weight: 800;
        }

        .login-card .subtitle {
            color: var(--text-soft);
            margin-bottom: 28px;
            font-size: 14px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #d1d5db;
            margin-bottom: 8px;
        }

        .form-control {
            background: var(--bg-input);
            border: 1px solid rgba(255,255,255,0.06);
            color: var(--text-main);
            border-radius: 14px;
            padding: 13px 14px;
            height: auto;
        }

        .form-control::placeholder {
            color: #6b7280;
        }

        .form-control:focus {
            background: var(--bg-input);
            color: #fff;
            border-color: rgba(59,130,246,0.8);
            box-shadow: 0 0 0 0.2rem rgba(59,130,246,0.12);
        }

        .btn-login {
            width: 100%;
            border: none;
            border-radius: 14px;
            padding: 14px 18px;
            font-weight: 700;
            font-size: 15px;
            background: linear-gradient(135deg, var(--accent), #1d4ed8);
            color: white;
            transition: all 0.2s ease;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, var(--accent-hover), #1e40af);
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 14px;
            border: none;
            font-size: 14px;
        }

        .login-footer {
            margin-top: 18px;
            text-align: center;
            color: var(--text-soft);
            font-size: 12px;
        }

        @media (max-width: 992px) {
            .login-shell {
                grid-template-columns: 1fr;
                max-width: 520px;
            }

            .login-brand {
                display: none;
            }

            .login-panel {
                padding: 22px;
            }

            .login-card {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="login-brand">
            <div class="brand-top">
                <div class="eyebrow">LatinBattle.com · Enterprise Control</div>
                <h1>Valheim<br>Server Panel</h1>
                <p>
                    Plataforma administrativa diseñada para operación, monitoreo y gestión
                    segura de servidores dedicados Valheim. Un entorno más limpio, más sólido
                    y preparado para trabajo continuo.
                </p>

                <div class="feature-list">
                    <div class="feature-item">
                        <span class="feature-dot"></span>
                        <span>Control de servidor con PID dedicado</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-dot"></span>
                        <span>Administración centralizada y segura</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-dot"></span>
                        <span>Actualizaciones, logs y configuración remota</span>
                    </div>
                </div>
            </div>

            <div class="brand-footer">
                Desarrollado para LatinBattle.com · Powered by Azzlaer & ChatGPT OpenAI
            </div>
        </div>

        <div class="login-panel">
            <div class="login-card">
                <div class="login-badge">● Secure Access</div>
                <h2>Iniciar sesión</h2>
                <div class="subtitle">
                    Accede al panel de administración del servidor.
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger text-center">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label for="username" class="form-label">Usuario</label>
                        <input
                            type="text"
                            class="form-control"
                            id="username"
                            name="username"
                            placeholder="Ingresa tu usuario"
                            required
                            autofocus>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Contraseña</label>
                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            placeholder="Ingresa tu contraseña"
                            required>
                    </div>

                    <button type="submit" class="btn-login">
                        Entrar al panel
                    </button>
                </form>

                <div class="login-footer">
                    Acceso restringido · Panel administrativo privado
                </div>
            </div>
        </div>
    </div>
</body>
</html>