<section id="menu" class="section">

    <div class="d-flex justify-content-between
                flex-wrap align-items-center gap-2 mb-3">

        <div>
            <h3 class="fw-bold">Menú del Restaurante</h3>
            <p class="muted mb-0">
                Productos y precios registrados en MySQL.
            </p>
        </div>

        <button class="btn btn-red" onclick="actualizarTodo()">
            ↻ Actualizar catálogo
        </button>
    </div>

    <div class="statgrid">

        <div class="stat">
            <div class="label">Productos disponibles</div>
            <div class="num" id="menuTotal">0</div>
        </div>

        <div class="stat">
            <div class="label">Categorías</div>
            <div class="num" id="menuCategorias">0</div>
        </div>

        <div class="stat">
            <div class="label">Precio promedio</div>
            <div class="num" id="menuPromedio">S/ 0.00</div>
        </div>

    </div>

    <div class="panel">

        <div class="row g-2">

            <div class="col-md-8">
                <input
                    class="form-control"
                    id="buscarProducto"
                    placeholder="Buscar plato..."
                    oninput="filtrarMenu()">
            </div>

            <div class="col-md-4">
                <select id="ordenMenu"
                        class="form-select"
                        onchange="filtrarMenu()">

                    <option value="nombre">Nombre A-Z</option>
                    <option value="mayor">Mayor precio</option>
                    <option value="menor">Menor precio</option>

                </select>
            </div>

        </div>

        <div class="chips mt-3" id="filtrosCategorias"></div>

    </div>

    <div class="catalog" id="listaProductos"></div>

</section>
