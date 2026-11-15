<?php
session_start();

if (empty($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION["rol"] ?? "";
$nombre = $_SESSION["nombre"] ?? "Usuario";

$permisos = [
    "gerente" => ["dashboard", "menu", "pedidos"],
    "mesero" => ["crear", "pedidos", "entregas", "menu"],
    "cocinero" => ["cocina", "pedidos", "menu"],
    "cashier" => ["caja", "pedidos", "menu"]
];

$inicios = [
    "gerente" => "dashboard",
    "mesero" => "crear",
    "cocinero" => "cocina",
    "cashier" => "caja"
];

if (!isset($permisos[$rol])) {
    header("Location: logout.php");
    exit;
}

function esc($valor) {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

$links = [
    "dashboard" => ["space_dashboard", "Dashboard"],
    "crear" => ["add_circle", "Crear Pedido"],
    "pedidos" => ["receipt_long", "Lista de Pedidos"],
    "cocina" => ["skillet", "Monitor KDS"],
    "entregas" => ["room_service", "Entregas"],
    "caja" => ["point_of_sale", "Caja y Pagos"],
    "menu" => ["restaurant_menu", "Menú"]
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>RestoManager 2.0 — Sabores Peruanos</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
rel="stylesheet">

<link
href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined"
rel="stylesheet">

<link href="assets/css/estilos.css" rel="stylesheet">
</head>

<body>
<div class="container-fluid">
<div class="row">

<aside class="col-md-3 col-lg-2 sidebar">

    <div class="text-center">
        <div class="brand">
            🍽️ Sabores Peruanos
        </div>
        <small class="muted">
            RestoManager 2.0
        </small>
    </div>

    <div class="userbox text-center">
        <strong><?= esc($nombre) ?></strong>
        <br>
        <span class="muted">
            Cargo:
            <?= esc($rol === "cashier" ? "Cajero" : ucfirst($rol)) ?>
        </span>
    </div>

    <?php foreach ($permisos[$rol] as $clave): ?>

        <button
            type="button"
            data-section="<?= esc($clave) ?>"
            class="<?= $clave === $inicios[$rol] ? 'activo' : '' ?>"
            onclick="mostrar('<?= esc($clave) ?>')">

            <span class="material-symbols-outlined">
                <?= esc($links[$clave][0]) ?>
            </span>

            <?= esc($links[$clave][1]) ?>

        </button>

    <?php endforeach; ?>

    <hr>

    <a href="logout.php"
       class="btn btn-outline-danger w-100">
        Cerrar sesión
    </a>

</aside>

<main class="col-md-9 col-lg-10 content">

    <div class="d-flex justify-content-between
                flex-wrap gap-3 align-items-center mb-4">

        <div>
            <h3 class="fw-bold mb-1">
                RestoManager 2.0
            </h3>

            <p class="muted mb-0">
                Sabores Peruanos · Gestión Gastronómica
            </p>
        </div>

        <span class="badge text-bg-success">
            API REST + MySQL
        </span>

    </div>

    <div id="mensaje" aria-live="polite" role="status"></div>

    <?php require __DIR__ . "/views/dashboard.php"; ?>
    <?php require __DIR__ . "/views/menu.php"; ?>
    <?php require __DIR__ . "/views/crear_pedido.php"; ?>
    <?php require __DIR__ . "/views/lista_pedidos.php"; ?>
    <?php require __DIR__ . "/views/cocina_kds.php"; ?>
    <?php require __DIR__ . "/views/entregas.php"; ?>
    <?php require __DIR__ . "/views/caja_pagos.php"; ?>

</main>
</div>
</div>

<!-- VENTANA DE DETALLE DEL PEDIDO -->
<div class="modal fade" id="modalPedido" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="tituloDetalle">
                    Detalle del Pedido
                </h5>

                <button class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar"></button>
            </div>

            <div class="modal-body" id="contenidoDetalle"></div>

            <div class="modal-footer">
                <button class="btn btn-secondary"
                        data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const API = "api.php?accion=";
const ROL = <?= json_encode($rol) ?>;
const SECCIONES = <?= json_encode($permisos[$rol]) ?>;
</script>

<script src="assets/js/app.js"></script>

</body>
</html>
