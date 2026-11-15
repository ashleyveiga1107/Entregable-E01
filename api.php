<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["usuario_id"])) {
    http_response_code(401);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["error" => "Debes iniciar sesión"]);
    exit;
}

header("Content-Type: application/json; charset=utf-8");

$accion = $_GET["accion"] ?? "";
$metodo = $_SERVER["REQUEST_METHOD"];

$rol = $_SESSION["rol"] ?? "";

$permisos = [
    "productos" => ["gerente", "mesero", "cocinero", "cashier"],
    "pedidos" => ["gerente", "mesero", "cocinero", "cashier"],
    "dashboard" => ["gerente"],
    "crear_pedido" => ["mesero"],
    "cambiar_estado" => ["mesero", "cocinero"],
    "pagar" => ["cashier"]
];

if (!isset($permisos[$accion])) {
    http_response_code(404);
    echo json_encode(["error" => "Ruta no encontrada"]);
    exit;
}

if (!in_array($rol, $permisos[$accion], true)) {
    http_response_code(403);
    echo json_encode([
        "error" => "No tienes permiso para realizar esta acción"
    ]);
    exit;
}

function responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function recibirDatos() {
    return json_decode(file_get_contents("php://input"), true) ?? [];
}

try {

    // CONSULTAR PRODUCTOS
    if ($accion === "productos" && $metodo === "GET") {
        $sql = "SELECT * FROM productos
                WHERE disponible = 1 ORDER BY nombre";
        responder($conexion->query($sql)->fetchAll());
    }

    // CONSULTAR PEDIDOS
    if ($accion === "pedidos" && $metodo === "GET") {
        $sql = "SELECT
            p.*,
            u.nombre AS mesero,
            (
                SELECT GROUP_CONCAT(
                    CONCAT(d.cantidad, ' x ', pr.nombre)
                    SEPARATOR ', '
                )
                FROM pedido_detalles d
                INNER JOIN productos pr
                    ON d.producto_id = pr.id
                WHERE d.pedido_id = p.id
            ) AS platos
            FROM pedidos p
            INNER JOIN usuarios u
            ON p.mesero_id = u.id
            ORDER BY p.id DESC";

        responder($conexion->query($sql)->fetchAll());
    }

    // DASHBOARD
    if ($accion === "dashboard" && $metodo === "GET") {
        $pedidos = $conexion->query(
            "SELECT COUNT(*) FROM pedidos
             WHERE DATE(creado_en) = CURDATE()"
        )->fetchColumn();

        $ventas = $conexion->query(
            "SELECT COALESCE(SUM(monto),0) FROM pagos
             WHERE DATE(pagado_en) = CURDATE()"
        )->fetchColumn();

        $pendientes = $conexion->query(
            "SELECT COUNT(*) FROM pedidos
             WHERE estado IN ('pendiente','preparando')"
        )->fetchColumn();

        $listos = $conexion->query(
            "SELECT COUNT(*) FROM pedidos
             WHERE estado = 'listo'"
        )->fetchColumn();

        responder([
            "pedidos_del_dia" => (int)$pedidos,
            "ventas_del_dia" => (float)$ventas,
            "pedidos_pendientes" => (int)$pendientes,
            "pedidos_listos" => (int)$listos
        ]);
    }

    // CREAR PEDIDO
    if ($accion === "crear_pedido" && $metodo === "POST") {
        $datos = recibirDatos();
        $mesa = trim($datos["mesa"] ?? "");
        $productos = $datos["productos"] ?? [];

        if ($mesa === "" || !is_array($productos) || !$productos) {
            responder(["error" => "Mesa o productos inválidos"], 400);
        }

        if (strlen($mesa) > 20) {
            responder(["error" => "Nombre de mesa demasiado largo"], 400);
        }

        $conexion->beginTransaction();

        $mesero = $conexion->query(
            "SELECT id FROM usuarios
             WHERE rol='mesero' AND activo=1 LIMIT 1"
        )->fetchColumn();

        if (!$mesero) {
            throw new Exception("No hay un mesero registrado");
        }

        $buscar = $conexion->prepare(
            "SELECT id, precio FROM productos
             WHERE id=? AND disponible=1"
        );

        $items = [];
        $totalCentimos = 0;

        foreach ($productos as $item) {
            $id = filter_var(
                $item["id"] ?? null,
                FILTER_VALIDATE_INT
            );
            $cantidad = filter_var(
                $item["cantidad"] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id || !$cantidad || $cantidad < 1 || $cantidad > 100) {
                throw new Exception("Producto o cantidad inválida");
            }

            $buscar->execute([$id]);
            $producto = $buscar->fetch();

            if (!$producto) {
                throw new Exception("Producto no disponible");
            }

            $precioCentimos = (int)round(
                (float)$producto["precio"] * 100
            );

            $subtotalCentimos = $precioCentimos * $cantidad;
            $totalCentimos += $subtotalCentimos;

            $items[] = [
                $id,
                $cantidad,
                $precioCentimos / 100,
                $subtotalCentimos / 100
            ];
        }

        $insertar = $conexion->prepare(
            "INSERT INTO pedidos
             (mesa, mesero_id, estado, total)
             VALUES (?, ?, 'pendiente', ?)"
        );

        $insertar->execute([
            $mesa, $mesero, $totalCentimos / 100
        ]);

        $pedidoId = $conexion->lastInsertId();

        $detalle = $conexion->prepare(
            "INSERT INTO pedido_detalles
             (pedido_id, producto_id, cantidad,
              precio_unitario, subtotal)
             VALUES (?, ?, ?, ?, ?)"
        );

        foreach ($items as $item) {
            $detalle->execute([$pedidoId, ...$item]);
        }

        $conexion->commit();

        responder([
            "mensaje" => "Pedido creado correctamente",
            "pedido_id" => (int)$pedidoId
        ], 201);
    }

    // CAMBIAR ESTADO
    if ($accion === "cambiar_estado" && $metodo === "PATCH") {
        $datos = recibirDatos();
        
        $id = filter_var($datos["id"] ?? null, FILTER_VALIDATE_INT);
        $estado = $datos["estado"] ?? "";

    if (
    ($estado === "preparando" || $estado === "listo")
    && $rol !== "cocinero"
    ) {
    responder([
        "error" => "Solo cocina puede preparar pedidos"
    ], 403);
    }

    if ($estado === "entregado" && $rol !== "mesero") {
    responder([
        "error" => "Solo el mesero puede entregar pedidos"
    ], 403);
    }


        if (!$id) {
            responder(["error" => "ID inválido"], 400);
        }

        $transiciones = [
            "pendiente" => "preparando",
            "preparando" => "listo",
            "listo" => "entregado"
        ];

        $conexion->beginTransaction();

        $consulta = $conexion->prepare(
            "SELECT estado FROM pedidos WHERE id=? FOR UPDATE"
        );
        $consulta->execute([$id]);
        $actual = $consulta->fetchColumn();

        if (!$actual) {
            $conexion->rollBack();
            responder(["error" => "Pedido no encontrado"], 404);
        }

        if (($transiciones[$actual] ?? null) !== $estado) {
            $conexion->rollBack();
            responder(["error" => "Cambio de estado no permitido"], 400);
        }

        $actualizar = $conexion->prepare(
            "UPDATE pedidos SET estado=? WHERE id=?"
        );
        $actualizar->execute([$estado, $id]);

        $conexion->commit();
        responder(["mensaje" => "Estado actualizado"]);
    }

    // REGISTRAR PAGO
    if ($accion === "pagar" && $metodo === "POST") {
        $datos = recibirDatos();
        $id = filter_var($datos["id"] ?? null, FILTER_VALIDATE_INT);
        $metodoPago = $datos["metodo"] ?? "";

        if (!$id || !in_array(
            $metodoPago,
            ["efectivo", "tarjeta", "yape", "plin"],
            true
        )) {
            responder(["error" => "Datos de pago inválidos"], 400);
        }

        $conexion->beginTransaction();

        $buscar = $conexion->prepare(
            "SELECT estado, total
             FROM pedidos WHERE id=? FOR UPDATE"
        );
        $buscar->execute([$id]);
        $pedido = $buscar->fetch();

        if (!$pedido || $pedido["estado"] !== "entregado") {
            $conexion->rollBack();
            responder([
                "error" => "El pedido debe estar entregado"
            ], 400);
        }

        $cajero = $conexion->query(
            "SELECT id FROM usuarios
             WHERE rol='cashier' AND activo=1 LIMIT 1"
        )->fetchColumn();

        if (!$cajero) {
            throw new Exception("No hay cajero registrado");
        }

        $pago = $conexion->prepare(
            "INSERT INTO pagos
             (pedido_id, cajero_id, monto, metodo)
             VALUES (?, ?, ?, ?)"
        );
        $pago->execute([
            $id, $cajero, $pedido["total"], $metodoPago
        ]);

        $actualizar = $conexion->prepare(
            "UPDATE pedidos SET estado='pagado' WHERE id=?"
        );
        $actualizar->execute([$id]);

        $conexion->commit();
        responder(["mensaje" => "Pago registrado correctamente"]);
    }

    responder(["error" => "Ruta no encontrada"], 404);

} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    error_log($e->getMessage());
    responder([
        "error" => "No se pudo completar la operación. Revisa los datos."
    ], 500);
}
