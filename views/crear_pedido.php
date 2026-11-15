<?php
/** @var string $rol */
/** @var string $nombre */
if ($rol === "mesero"):
?>

<section id="crear" class="section visible">

    <span class="text-danger fw-bold small">
        TERMINAL MESERO · COMANDA DIGITAL
    </span>

    <h3 class="fw-bold mt-2">
        Nueva Comanda / Crear Pedido
    </h3>

    <p class="muted">
        Selecciona los platos y envía la comanda a cocina.
    </p>

    <form id="formPedido">

        <div class="pos-layout">

            <!-- CATALOGO DE PLATOS -->
            <div>

                <div class="panel">

                    <div class="row g-3">

                        <div class="col-md-7">

                            <label for="mesa" class="form-label fw-bold">
                                Número de mesa
                            </label>

                            <input
                                class="form-control"
                                id="mesa"
                                maxlength="20"
                                placeholder="Ejemplo: Mesa 7"
                                required>
                        </div>

                        <div class="col-md-5">

                            <label class="form-label fw-bold">
                                Mesero asignado
                            </label>

                            <input
                                class="form-control"
                                value="<?= esc($nombre) ?>"
                                disabled>
                        </div>

                    </div>

                </div>

                <div class="panel">

                    <h5 class="fw-bold">
                        Seleccionar platos
                    </h5>

                    <input
                        id="buscarCrear"
                        class="form-control my-3"
                        type="search"
                        placeholder="Buscar platos o bebidas..."
                        oninput="filtrarCrear()">

                    <div id="categoriasCrear" class="chips"></div>

                </div>

                <div class="pos-grid" id="productosPedido"></div>

            </div>

            <!-- TICKET / COMANDA ACTUAL -->
            <div class="ticket">

                <div class="ticket-head">

                    <h5 class="fw-bold mb-1">
                        🧾 Comanda actual
                    </h5>

                    <small class="muted">
                        Resumen de productos seleccionados
                    </small>

                </div>

                <div class="ticket-body" id="ticketProductos">
                    Todavía no seleccionaste platos.
                </div>

                <div class="ticket-total">

                    <div class="d-flex justify-content-between">
                        <span>Platos seleccionados</span>
                        <b id="cantidadTotal">0</b>
                    </div>

                    <div class="d-flex justify-content-between
                                align-items-center mt-3">

                        <b>TOTAL</b>

                        <h4 class="price mb-0" id="totalNuevo">
                            S/ 0.00
                        </h4>

                    </div>

                </div>

                <div class="p-3">

                    <button type="submit"
                            class="btn btn-red w-100 py-3 fw-bold"
                            id="btnEnviarPedido">
                        Enviar a cocina
                    </button>

                    <button type="button"
                            onclick="limpiarCarrito()"
                            class="btn btn-outline-secondary w-100 mt-2">
                        Limpiar selección
                    </button>

                </div>

            </div>

        </div>

    </form>

</section>

<?php endif; ?>
