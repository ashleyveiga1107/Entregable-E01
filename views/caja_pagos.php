<?php
/** @var string $rol */
if ($rol === "cashier"):
?>

<section id="caja" class="section visible">

    <div class="panel">

        <div class="d-flex flex-wrap justify-content-between
                    align-items-center gap-3">

            <div>

                <span class="text-danger fw-bold small">
                    MÓDULO CAJERO · PAGOS LOCALES
                </span>

                <h3 class="fw-bold mt-2 mb-1">
                    Caja y Pagos
                </h3>

                <p class="muted mb-0">
                    Liquidación de pedidos entregados.
                    Registro mediante API REST y MySQL.
                </p>

            </div>

            <button class="btn btn-red"
                    onclick="actualizarTodo()">
                ↻ Actualizar cuentas
            </button>

        </div>

    </div>

    <div class="statgrid">

        <div class="stat">
            <div class="label">Cuentas por cobrar</div>
            <div class="num" id="cajaPendientes">0</div>
        </div>

        <div class="stat">
            <div class="label">Monto pendiente</div>
            <div class="num" id="cajaMontoPendiente">S/ 0.00</div>
        </div>

        <div class="stat">
            <div class="label">Pedidos pagados (histórico)</div>
            <div class="num" id="cajaPagados">0</div>
        </div>

        <div class="stat">
            <div class="label">
                Total cobrado (histórico disponible)
            </div>
            <div class="num" id="cajaTotalPagado">S/ 0.00</div>
        </div>

    </div>

    <div class="cash-layout">

        <!-- CUENTAS PENDIENTES -->
        <div class="panel">

            <div class="d-flex justify-content-between
                        align-items-center mb-3">

                <h5 class="fw-bold mb-0">
                    Cuentas por cobrar
                </h5>

                <span class="badge text-bg-danger"
                      id="cajaNumeroCuentas">
                    0
                </span>

            </div>

            <input
                class="form-control mb-3"
                id="buscarCaja"
                placeholder="Buscar mesa o pedido..."
                oninput="renderizarCaja()">

            <div id="listaCaja" class="cash-list"></div>

        </div>

        <!-- LIQUIDACION -->
        <div class="panel">

            <div id="liquidacionCaja">

                <div class="text-center text-muted py-5">
                    Selecciona una cuenta de la izquierda
                    para registrar el pago.
                </div>

            </div>

        </div>

    </div>

    <p class="muted small">
        Esta pantalla registra pagos internos.
        No emite comprobantes electrónicos SUNAT ni
        procesa pagos mediante pasarelas externas.
    </p>

</section>

<?php endif; ?>
