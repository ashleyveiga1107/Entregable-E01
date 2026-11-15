<?php
/** @var string $rol */
/** @var string $nombre */
if ($rol === "mesero"): 
?>

<section id="entregas" class="section">

    <!-- ENCABEZADO -->
    <div class="entregas-header">

        <div>
            <span class="text-danger fw-bold small">
                OPERACIONES DE SALÓN
            </span>

            <h3 class="fw-bold mt-2 mb-1">
                Recojo y Entrega
            </h3>

            <p class="muted">
                Comandas listas para servir
                y seguimiento de entregas.
            </p>
        </div>

        <button class="btn btn-red"
                onclick="actualizarTodo()">
            ↻ Actualizar pedidos
        </button>

    </div>

    <!-- ESTADÍSTICAS -->
    <div class="statgrid">

        <div class="stat">
            <div class="label">
                Listos para entregar
            </div>
            <div class="num" id="entregasListos">
                0
            </div>
            <small>Esperando atención del mesero</small>
        </div>

        <div class="stat">
            <div class="label">
                Entregados hoy
            </div>
            <div class="num" id="entregasHoy">
                0
            </div>
            <small>Comandas entregadas hoy</small>
        </div>

        <div class="stat">
            <div class="label">
                Por cobrar
            </div>
            <div class="num" id="entregasCobrar">
                0
            </div>
            <small>Pedidos entregados sin pagar</small>
        </div>

    </div>

    <!-- FILTROS -->
    <div class="panel">

        <div class="d-flex flex-wrap justify-content-between
                    align-items-center gap-3">

            <div>
                <h5 class="fw-bold mb-1">
                    Seguimiento de Comandas
                </h5>

                <small class="muted">
                    Selecciona un estado para consultar los pedidos.
                </small>
            </div>

            <input
                type="search"
                class="form-control"
                style="max-width:290px"
                id="buscarEntrega"
                placeholder="Buscar mesa o pedido..."
                oninput="renderizarEntregas()">

        </div>

        <div class="chips mt-3" id="filtrosEntregas"></div>

    </div>

    <!-- TARJETAS -->
    <div class="entregas-grid" id="listaEntregas"></div>

</section>

<?php endif; ?>
