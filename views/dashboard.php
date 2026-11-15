<?php
/** @var string $rol */
/** @var string $nombre */
if ($rol === "gerente"):
?>

<section id="dashboard" class="section visible">

    <div class="panel"
         style="background:linear-gradient(110deg,#fff,#fff0f0);
                border-left:5px solid #d61a1a">

        <h3 class="fw-bold">
            ¡Bienvenido, <?= esc($nombre) ?>!
        </h3>

        <p class="muted">
            Resumen operacional y financiero del restaurante.
        </p>

        <p id="fechaDashboard" class="small muted"></p>

        <button class="btn btn-red" onclick="actualizarTodo()">
            ↻ Actualizar datos
        </button>
    </div>

    <div class="statgrid">

        <div class="stat">
            <div class="label">Ventas del día</div>
            <div class="num" id="totalVentas">S/ 0.00</div>
            <small>Pagos registrados hoy</small>
        </div>

        <div class="stat">
            <div class="label">Pedidos del día</div>
            <div class="num" id="totalPedidos">0</div>
        </div>

        <div class="stat">
            <div class="label">Pendientes</div>
            <div class="num" id="totalPendientes">0</div>
        </div>

        <div class="stat">
            <div class="label">Listos</div>
            <div class="num" id="totalListos">0</div>
        </div>

    </div>

    <div class="row g-3">

        <div class="col-lg-7">
            <div class="panel">

                <h5 class="fw-bold">Estados de pedidos</h5>

                <div class="bar">
                    <span id="barraPendiente"
                          style="background:#e7bd73"></span>

                    <span id="barraPreparando"
                          style="background:#729fdf"></span>

                    <span id="barraListo"
                          style="background:#16836b"></span>

                    <span id="barraEntregado"
                          style="background:#b8bec5"></span>

                    <span id="barraPagado"
                          style="background:#d61a1a"></span>
                </div>

                <div id="resumenEstados"></div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="panel">
                <h5 class="fw-bold">Actividad reciente</h5>
                <div id="actividadReciente"></div>
            </div>
        </div>

    </div>

    <div class="panel">
        <button class="btn btn-red me-2"
                onclick="mostrar('pedidos')">
            Ver pedidos
        </button>

        <button class="btn btn-outline-secondary"
                onclick="mostrar('menu')">
            Consultar menú
        </button>
    </div>

</section>

<?php endif; ?>
