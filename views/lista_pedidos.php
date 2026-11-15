<?php
/** @var string $rol */
?>

<section id="pedidos" class="section">

    <div class="d-flex justify-content-between
                flex-wrap gap-2 align-items-center mb-3">

        <div>
            <h3 class="fw-bold">
                Lista de Pedidos y Comandas
            </h3>

            <p class="muted">
                Seguimiento de pedidos reales.
            </p>
        </div>

        <div>

            <button class="btn btn-outline-secondary"
                    onclick="actualizarTodo()">
                ↻ Actualizar
            </button>

            <?php if ($rol === "mesero"): ?>

                <button class="btn btn-red"
                        onclick="mostrar('crear')">
                    + Nuevo pedido
                </button>

            <?php endif; ?>

        </div>

    </div>

    <div class="statgrid">

        <div class="stat">
            <div class="label">Total pedidos</div>
            <div id="pedidosCantidad" class="num">0</div>
        </div>

        <div class="stat">
            <div class="label">En cocina</div>
            <div id="pedidosCocina" class="num">0</div>
        </div>

        <div class="stat">
            <div class="label">Listos</div>
            <div id="pedidosListos" class="num">0</div>
        </div>

        <div class="stat">
            <div class="label">Por cobrar</div>
            <div id="pedidosCobrar" class="num">0</div>
        </div>

    </div>

    <div class="panel">

        <input
            id="buscarPedido"
            class="form-control"
            placeholder="Buscar pedido, mesa, mesero o plato..."
            oninput="filtrarPedidos()">

        <div class="chips mt-3" id="filtrosEstados"></div>

    </div>

    <div class="panel p-0 overflow-hidden">

        <div class="p-3 bg-light d-flex justify-content-between">
            <b>Comandas activas y recientes</b>
            <small id="cantidadFiltrada" class="muted"></small>
        </div>

        <div class="table-responsive">

            <table class="table mb-0">

                <thead>
                    <tr>
                        <th>Pedido / Mesa</th>
                        <th>Fecha</th>
                        <th>Mesero</th>
                        <th>Platos</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Acción</th>
                    </tr>
                </thead>

                <tbody id="tablaPedidos"></tbody>

            </table>

        </div>

    </div>

</section>
