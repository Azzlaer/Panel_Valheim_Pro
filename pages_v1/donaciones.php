<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}
?>

<style>
.donations-wrap .panel-hero {
    background: linear-gradient(135deg, rgba(236,72,153,.14), rgba(17,24,39,.62));
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    padding: 28px;
    box-shadow: 0 18px 40px rgba(0,0,0,.28);
    margin-bottom: 22px;
}

.donations-wrap .panel-hero h2 {
    margin: 0;
    font-weight: 800;
    color: #fff;
}

.donations-wrap .panel-hero p {
    margin: 10px 0 0;
    color: #9ca3af;
    line-height: 1.7;
}

.donations-wrap .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 16px;
    margin-bottom: 22px;
}

.donations-wrap .stat-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 10px 24px rgba(0,0,0,.20);
}

.donations-wrap .stat-label {
    font-size: 12px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 8px;
}

.donations-wrap .stat-value {
    font-size: 20px;
    font-weight: 800;
    color: #fff;
    line-height: 1.4;
}

.donations-wrap .section-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(0,0,0,.24);
    margin-bottom: 22px;
}

.donations-wrap .section-header {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255,255,255,.08);
}

.donations-wrap .section-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #fff;
}

.donations-wrap .section-header small {
    color: #9ca3af;
}

.donations-wrap .section-body {
    padding: 20px;
}

.donations-wrap .donation-card {
    background: rgba(255,255,255,.025);
    border-radius: 18px;
    padding: 20px;
    border: 1px solid rgba(255,255,255,.06);
    box-shadow: 0 8px 20px rgba(0,0,0,.20);
    transition: .22s ease;
    height: 100%;
}

.donations-wrap .donation-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 28px rgba(0,0,0,.28);
    border-color: rgba(255,255,255,.10);
}

.donations-wrap .donation-title {
    font-size: 1.08rem;
    font-weight: 700;
    color: #fff;
}

.donations-wrap .donation-table {
    width: 100%;
    font-size: .95rem;
    margin-bottom: 0;
}

.donations-wrap .donation-table td {
    padding: 7px 10px;
    vertical-align: top;
    border-bottom: 1px solid rgba(255,255,255,.05);
    color: #d1d5db;
}

.donations-wrap .donation-table tr:last-child td {
    border-bottom: none;
}

.donations-wrap .donation-table td:first-child {
    color: #9ca3af;
    width: 38%;
    font-weight: 600;
}

.donations-wrap .section-note {
    font-size: .96rem;
    color: #b9c0cc;
    line-height: 1.7;
}

.donations-wrap .wallet-address {
    word-break: break-all;
    font-family: Consolas, Monaco, monospace;
    font-size: 13px;
    color: #e5e7eb;
}

.donations-wrap .thanks-box {
    background: linear-gradient(135deg, rgba(16,185,129,.10), rgba(17,24,39,.35));
    border: 1px solid rgba(16,185,129,.16);
    border-radius: 18px;
    padding: 18px 20px;
    color: #d1fae5;
    font-weight: 600;
}

@media (max-width: 991px) {
    .donations-wrap .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 575px) {
    .donations-wrap .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid py-3 donations-wrap">

    <div class="panel-hero">
        <h2>💖 Donaciones</h2>
        <p>
            Este proyecto es <strong>independiente</strong> y se desarrolla con tiempo, infraestructura y recursos propios.
            Si el panel te resulta útil y deseas apoyar su desarrollo, mantenimiento y evolución, puedes hacerlo mediante
            las siguientes opciones de aporte.
        </p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Proyecto</div>
            <div class="stat-value">Valheim Pro Panel</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Cobertura</div>
            <div class="stat-value">Chile + Internacional</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Canales</div>
            <div class="stat-value">Banco / Wallet / Exchange</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Objetivo</div>
            <div class="stat-value">Soporte y continuidad</div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-header">
            <h3>🇨🇱 Donaciones desde Chile</h3>
            <small>Opciones bancarias y transferencias nacionales disponibles.</small>
        </div>
        <div class="section-body">
            <div class="row g-4">

                <div class="col-md-6">
                    <div class="donation-card">
                        <div class="donation-title mb-2">💳 Mercado Pago</div>
                        <table class="donation-table">
                            <tr><td>Nombre</td><td>Andrés Ivan Würth Aranda</td></tr>
                            <tr><td>RUT</td><td>25.996.713-9</td></tr>
                            <tr><td>Tipo de cuenta</td><td>Cuenta Vista</td></tr>
                            <tr><td>N° Cuenta</td><td>1008465639</td></tr>
                            <tr><td>Correo</td><td>azzlaeryt@gmail.com</td></tr>
                        </table>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="donation-card">
                        <div class="donation-title mb-2">🏦 Banco Estado</div>
                        <table class="donation-table">
                            <tr><td>Nombre</td><td>ANDRES IVAN WURTH</td></tr>
                            <tr><td>RUT</td><td>25.996.713-9</td></tr>
                            <tr><td>Banco</td><td>Banco Estado</td></tr>
                            <tr><td>Tipo de cuenta</td><td>CuentaRUT</td></tr>
                            <tr><td>N° Cuenta</td><td>25996713</td></tr>
                        </table>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="donation-card">
                        <div class="donation-title mb-2">🏦 Banco Santander</div>
                        <table class="donation-table">
                            <tr><td>Nombre</td><td>Andres Ivan Wurth Aranda</td></tr>
                            <tr><td>RUT</td><td>25.996.713-9</td></tr>
                            <tr><td>Tipo de cuenta</td><td>Cuenta de Ahorro</td></tr>
                            <tr><td>N° Cuenta</td><td>0 012 07 98735 9</td></tr>
                            <tr><td>Correo</td><td>azzlaersoft@gmail.com</td></tr>
                        </table>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="donation-card">
                        <div class="donation-title mb-2">🏦 BCI / MACH</div>
                        <table class="donation-table">
                            <tr><td>Nombre</td><td>ANDRES IVAN WURTH ARANDA</td></tr>
                            <tr><td>RUT</td><td>25.996.713-9</td></tr>
                            <tr><td>Banco</td><td>Banco Crédito e Inversiones</td></tr>
                            <tr><td>Tipo de cuenta</td><td>Cuenta Corriente</td></tr>
                            <tr><td>N° Cuenta</td><td>777925996713</td></tr>
                            <tr><td>Correo</td><td>azzlaersoft@gmail.com</td></tr>
                        </table>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="donation-card">
                        <div class="donation-title mb-2">🏦 Banco Falabella</div>
                        <table class="donation-table">
                            <tr><td>Nombre</td><td>Andres Ivan Würth</td></tr>
                            <tr><td>RUT</td><td>25.996.713-9</td></tr>
                            <tr><td>Banco</td><td>Banco Falabella</td></tr>
                            <tr><td>Tipo de cuenta</td><td>Cuenta Corriente</td></tr>
                            <tr><td>N° Cuenta</td><td>1-999-666941-7</td></tr>
                            <tr><td>Correo</td><td>azzlaersoft@gmail.com</td></tr>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-header">
            <h3>🌍 Donaciones Internacionales / Cripto</h3>
            <small>Opciones para aportes fuera de Chile y canales digitales.</small>
        </div>
        <div class="section-body">
            <div class="row g-4">

                <div class="col-md-6">
                    <div class="donation-card">
                        <div class="donation-title mb-2">💵 Transferencia USD (World App)</div>
                        <table class="donation-table">
                            <tr><td>Banco</td><td>Lead Bank</td></tr>
                            <tr><td>Tipo de cuenta</td><td>Cuenta Corriente</td></tr>
                            <tr><td>N° Ruta bancaria</td><td>101019644</td></tr>
                            <tr><td>N° Cuenta</td><td>214027546387</td></tr>
                            <tr><td>Beneficiario</td><td>Andres Wurth Aranda</td></tr>
                            <tr><td>Dirección</td><td>1801 Main St., Kansas City, MO 64108</td></tr>
                        </table>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="donation-card">
                        <div class="donation-title mb-2">🪙 XTB (Cripto)</div>
                        <table class="donation-table">
                            <tr><td>ID</td><td>001dk00000IDE2nAAH</td></tr>
                        </table>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="donation-card">
                        <div class="donation-title mb-2">🌐 WorldCoin</div>
                        <table class="donation-table">
                            <tr><td>Usuario</td><td>Azzlaer</td></tr>
                            <tr><td>Dirección</td><td class="wallet-address">0x94f266f829a271086eea4337ef2baea86df0b84b</td></tr>
                            <tr><td>Red</td><td>World Chain</td></tr>
                            <tr><td>Cripto</td><td>WLD</td></tr>
                        </table>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="donation-card">
                        <div class="donation-title mb-2">🟡 Binance</div>
                        <table class="donation-table">
                            <tr><td>Usuario ID</td><td>801556059</td></tr>
                            <tr><td>Correo</td><td>azzlaersoft@gmail.com</td></tr>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="thanks-box">
        🙏 Gracias por apoyar el desarrollo, mantenimiento y mejora continua de este panel.
    </div>

</div>