<?php
/** @var string $rol */
if ($rol === "cocinero"):
?>

<section id="cocina" class="section visible">

    <div class="panel">

        <div class="kds-top">

            <div>
                <span class="text-danger fw-bold small">
                    DESPACHO & FUEGOS
                </span>

                <h3 class="fw-bold mb-1">
                    Monitor KDS de Cocina
                </h3>

                <div class="kds-meta">
                    Actualización manual · Datos de API REST
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">

                <span class="fw-bold" id="kdsClock"></span>

                <button class="btn btn-red"
                        onclick="actualizarTodo()">
                    ↻ Actualizar comandas
                </button>

            </div>

        </div>

        <hr>

        <div class="chips" id="kdsFiltros"></div>

    </div>

    <div class="statgrid">

        <div class="stat">
            <div class="label">Pendientes</div>
            <div class="num" id="kdsPendientes">0</div>
        </div>

        <div class="stat">
            <div class="label">Preparando</div>
            <div class="num" id="kdsPreparando">0</div>
        </div>

        <div class="stat">
            <div class="label">Demoradas (más de 18 min)</div>
            <div class="num" id="kdsDemoradas">0</div>
        </div>

        <div class="stat">
            <div class="label">Listos para entrega</div>
            <div class="num" id="kdsListos">0</div>
        </div>

    </div>

    <div id="listaCocina" class="kds-grid"></div>

    <p class="muted small mt-3">
        Los tiempos se calculan desde la creación del pedido.
        Los estados se actualizan por pedido completo.
    </p>

</section>

<?php endif; ?>
