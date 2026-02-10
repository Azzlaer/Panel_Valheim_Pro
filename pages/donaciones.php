<?php
require_once "../config.php";

if (!is_logged_in()) {
    echo '<div class="alert alert-danger">Sesión expirada. Vuelve a iniciar sesión.</div>';
    return;
}
?>

<style>
.donation-card {
    background: rgba(25,25,25,0.95);
    border-radius: 18px;
    padding: 22px;
    border: 1px solid rgba(255,255,255,0.06);
    box-shadow: 0 0 15px rgba(0,0,0,0.45);
    transition: 0.3s;
}
.donation-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0 25px rgba(0,0,0,0.6);
}
.donation-title {
    font-size: 1.2rem;
    font-weight: 700;
}
.donation-table {
    width: 100%;
    font-size: 0.95rem;
}
.donation-table td {
    padding: 6px 10px;
    vertical-align: top;
}
.donation-table td:first-child {
    color: #aaa;
    width: 35%;
}
.section-note {
    font-size: 0.95rem;
    color: #bbb;
}
</style>

<div class="container-fluid text-light py-3">

    <h2 class="fw-bold mb-3">💖 Donaciones</h2>

    <p class="section-note mb-4">
        Este proyecto es <b>independiente</b> y se desarrolla con tiempo y recursos propios.<br>
        Si te resulta útil y deseas apoyar su desarrollo, puedes hacerlo mediante las siguientes opciones.
    </p>

    <h4 class="fw-bold mb-3">🇨🇱 Donaciones desde Chile</h4>

    <div class="row g-4">

        <!-- Mercado Pago -->
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

        <!-- Banco Estado -->
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

        <!-- Banco Santander -->
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

        <!-- BCI MACH -->
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

        <!-- Banco Falabella -->
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

    <hr class="my-5">

    <h4 class="fw-bold mb-3">🌍 Donaciones Internacionales / Cripto (USD)</h4>

    <div class="donation-card">
        <p class="section-note mb-3">
            También puedes apoyar el proyecto mediante depósito en dólares (World App).
        </p>
        <table class="donation-table">
            <tr><td>Banco</td><td>Lead Bank</td></tr>
            <tr><td>Tipo de cuenta</td><td>Cuenta Corriente</td></tr>
            <tr><td>N° Ruta bancaria</td><td>101019644</td></tr>
            <tr><td>N° Cuenta</td><td>214027546387</td></tr>
            <tr><td>Beneficiario</td><td>Andres Wurth Aranda</td></tr>
            <tr><td>Dirección</td><td>1801 Main St., Kansas City, MO 64108</td></tr>
        </table>
    </div>

    <p class="section-note mt-4">
        🙏 Gracias por apoyar el desarrollo y mejora continua de este panel.
    </p>

</div>
