/* RESTOMANAGER 2.0 - FUNCIONES DE LA APLICACIÓN */

let productos = [];
let pedidos = [];
let categoriaMenu = "Todas";
let categoriaCrear = "Todas";
let estadoFiltro = "todos";
let kdsFiltro = "todos";
let cajaSeleccionada = null;
let cajaMetodo = "efectivo";

const carrito = new Map();

const estados = [
    "pendiente",
    "preparando",
    "listo",
    "entregado",
    "pagado"
];

const $ = id => document.getElementById(id);

function seguro(v) {
    return String(v ?? "").replace(/[&<>"']/g, c => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;"
    })[c]);
}

function normalizar(v) {
    return String(v ?? "")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase();
}

function dinero(v) {
    return "S/ " + Number(v || 0).toFixed(2);
}

function aviso(texto, error = false) {
    $("mensaje").innerHTML =
        `<div class="alert alert-${error ? "danger" : "success"}">
            ${seguro(texto)}
        </div>`;
}

/* NAVEGACION */

function mostrar(id) {
    if (!SECCIONES.includes(id)) {
        aviso("No tienes acceso a esa sección.", true);
        return;
    }

    document.querySelectorAll(".section").forEach(e => {
        e.classList.toggle("visible", e.id === id);
    });

    document.querySelectorAll(
        ".sidebar button[data-section]"
    ).forEach(b => {
        b.classList.toggle("activo", b.dataset.section === id);
    });

    actualizarTodo();
}

/* PETICIONES A LA API REST */

async function solicitar(accion, metodo = "GET", datos = null) {
    const opciones = {
        method: metodo,
        credentials: "same-origin"
    };

    if (datos !== null) {
        opciones.headers = {
            "Content-Type": "application/json"
        };
        opciones.body = JSON.stringify(datos);
    }

    const respuesta = await fetch(API + accion, opciones);

    if (respuesta.status === 401) {
        location.href = "login.php";
        throw new Error("Sesión expirada.");
    }

    let resultado;

    try {
        resultado = await respuesta.json();
    } catch {
        throw new Error("Respuesta inválida de api.php.");
    }

    if (!respuesta.ok || resultado?.error) {
        throw new Error(resultado?.error || "Error en API REST");
    }

    return resultado;
}

async function actualizarTodo() {
    try {
        const consultas = [
            solicitar("productos"),
            solicitar("pedidos")
        ];

        if (ROL === "gerente") {
            consultas.push(solicitar("dashboard"));
        }

        const resultado = await Promise.all(consultas);

        productos = resultado[0];
        pedidos = resultado[1];

        if (!Array.isArray(productos) ||
            !Array.isArray(pedidos)) {
            throw new Error("La API no devolvió listas válidas.");
        }

        if (ROL === "gerente") {
            const d = resultado[2];

            $("totalVentas").textContent =
                dinero(d.ventas_del_dia);

            $("totalPedidos").textContent =
                d.pedidos_del_dia;

            $("totalPendientes").textContent =
                d.pedidos_pendientes;

            $("totalListos").textContent =
                d.pedidos_listos;

            renderizarDashboard();
        }

        renderizarMenu();
        renderizarPedidos();

        if (ROL === "mesero") {
            renderizarCrear();
            renderizarEntregas();
        }

        if (ROL === "cocinero") {
            renderizarCocina();
        }

        if (ROL === "cashier") {
            renderizarCaja();
        }

    } catch (error) {
        aviso(error.message, true);
    }
}

/* DASHBOARD DEL GERENTE */

function renderizarDashboard() {
    const conteos = {
        pendiente: 0,
        preparando: 0,
        listo: 0,
        entregado: 0,
        pagado: 0
    };

    pedidos.forEach(p => {
        if (Object.prototype.hasOwnProperty.call(
            conteos, p.estado
        )) {
            conteos[p.estado]++;
        }
    });

    const total = pedidos.length || 1;

    const barras = {
        pendiente: "barraPendiente",
        preparando: "barraPreparando",
        listo: "barraListo",
        entregado: "barraEntregado",
        pagado: "barraPagado"
    };

    $("resumenEstados").innerHTML = estados.map(e => {
        $(barras[e]).style.width =
            (conteos[e] / total * 100) + "%";

        return `
            <div class="line">
                <span>${seguro(e)}</span>
                <b>${conteos[e]}</b>
            </div>
        `;
    }).join("");

    const recientes = [...pedidos]
        .sort((a, b) => Number(b.id) - Number(a.id))
        .slice(0, 5);

    $("actividadReciente").innerHTML = recientes.map(p => `
        <div class="border-bottom py-3">
            <b>Pedido #${Number(p.id)}</b>
            <span class="status ${seguro(p.estado)}">
                ${seguro(p.estado)}
            </span>

            <p class="muted small mb-1 mt-2">
                ${seguro(p.mesa)} · ${seguro(p.mesero)}
            </p>

            <p class="small mb-1">
                ${seguro(p.platos || "Sin detalle")}
            </p>

            <b>${dinero(p.total)}</b>
        </div>
    `).join("") || "<p>No hay pedidos.</p>";

    $("fechaDashboard").textContent =
        new Date().toLocaleDateString("es-PE", {
            day: "numeric",
            month: "long",
            year: "numeric"
        });
}

/* PRODUCTOS Y MENU */

function emojiPlato(nombre, categoria) {
    const n = normalizar(nombre);
    const c = normalizar(categoria);

    if (n.includes("ceviche")) return "🐟";
    if (n.includes("lomo")) return "🥩";
    if (n.includes("arroz")) return "🍚";
    if (n.includes("papa")) return "🥔";
    if (n.includes("chicha")) return "🥤";
    if (n.includes("limonada")) return "🍋";
    if (n.includes("aji")) return "🍛";
    if (c.includes("postre")) return "🍰";
    if (c.includes("bebida")) return "🥤";

    return "🍽️";
}

function categoriasProductos() {
    return [
        "Todas",
        ...new Set(productos.map(
            p => p.categoria || "Otros"
        ))
    ];
}

function renderizarMenu() {
    const categorias = categoriasProductos();

    if (!categorias.includes(categoriaMenu)) {
        categoriaMenu = "Todas";
    }

    $("menuTotal").textContent = productos.length;
    $("menuCategorias").textContent = categorias.length - 1;

    const promedio = productos.length
        ? productos.reduce(
            (total, p) => total + Number(p.precio), 0
        ) / productos.length
        : 0;

    $("menuPromedio").textContent = dinero(promedio);

    $("filtrosCategorias").innerHTML =
        categorias.map((cat, i) => `
            <button class="chip ${categoriaMenu === cat ? "active" : ""}"
                    onclick="categoriaMenu=categoriasProductos()[${i}];
                             renderizarMenu()">
                ${seguro(cat)}
            </button>
        `).join("");

    filtrarMenu();
}

function filtrarMenu() {
    const busqueda = normalizar($("buscarProducto").value);
    const orden = $("ordenMenu").value;

    const lista = productos.filter(p =>
        normalizar(p.nombre).includes(busqueda) &&
        (categoriaMenu === "Todas" ||
         (p.categoria || "Otros") === categoriaMenu)
    );

    if (orden === "mayor") {
        lista.sort((a, b) =>
            Number(b.precio) - Number(a.precio)
        );
    } else if (orden === "menor") {
        lista.sort((a, b) =>
            Number(a.precio) - Number(b.precio)
        );
    } else {
        lista.sort((a, b) =>
            String(a.nombre).localeCompare(
                String(b.nombre), "es"
            )
        );
    }

    $("listaProductos").innerHTML = lista.map(p => `
        <article class="dish">

            <div class="dish-pic">
                ${emojiPlato(p.nombre, p.categoria)}
            </div>

            <div class="dish-info">
                <small class="muted">
                    ${seguro(p.categoria)}
                </small>

                <h5 class="fw-bold mt-2">
                    ${seguro(p.nombre)}
                </h5>

                <p class="price mb-1">
                    ${dinero(p.precio)}
                </p>

                <small class="text-success">
                    ● Disponible
                </small>
            </div>

        </article>
    `).join("") ||
    '<div class="panel">No hay productos.</div>';
}

/* CREAR PEDIDO */

function renderizarCrear() {
    const categorias = categoriasProductos();

    if (!categorias.includes(categoriaCrear)) {
        categoriaCrear = "Todas";
    }

    $("categoriasCrear").innerHTML =
        categorias.map((cat, i) => `
            <button type="button"
                    class="chip ${cat === categoriaCrear ? "active" : ""}"
                    onclick="categoriaCrear=categoriasProductos()[${i}];
                             renderizarCrear()">
                ${seguro(cat)}
            </button>
        `).join("");

    filtrarCrear();
    renderizarTicket();
}

function filtrarCrear() {
    const busqueda = normalizar($("buscarCrear").value);

    const lista = productos.filter(p =>
        normalizar(p.nombre).includes(busqueda) &&
        (categoriaCrear === "Todas" ||
         (p.categoria || "Otros") === categoriaCrear)
    );

    $("productosPedido").innerHTML = lista.map(p => {
        const id = Number(p.id);
        const cantidad = carrito.get(id) || 0;

        return `
            <article class="dish">

                <div class="dish-pic">
                    ${emojiPlato(p.nombre, p.categoria)}
                </div>

                <div class="dish-info">

                    <small class="muted">
                        ${seguro(p.categoria)}
                    </small>

                    <h6 class="fw-bold mt-2">
                        ${seguro(p.nombre)}
                    </h6>

                    <div class="d-flex justify-content-between
                                align-items-center gap-2">

                        <strong class="price">
                            ${dinero(p.precio)}
                        </strong>

                        <button type="button"
                                class="btn btn-red btn-sm"
                                onclick="cambiarCantidad(${id},1)">
                            + Agregar ${cantidad ? "(" + cantidad + ")" : ""}
                        </button>

                    </div>

                </div>

            </article>
        `;
    }).join("") ||
    '<div class="panel">No se encontraron productos.</div>';
}

function cambiarCantidad(id, diferencia) {
    const actual = carrito.get(id) || 0;
    const nueva = Math.max(0, Math.min(100, actual + diferencia));

    if (nueva === 0) {
        carrito.delete(id);
    } else {
        carrito.set(id, nueva);
    }

    renderizarTicket();
    filtrarCrear();
}

function renderizarTicket() {
    let total = 0;
    let cantidadTotal = 0;
    let html = "";

    carrito.forEach((cantidad, id) => {
        const producto = productos.find(
            p => Number(p.id) === id
        );

        if (!producto) return;

        const subtotal = Number(producto.precio) * cantidad;

        total += subtotal;
        cantidadTotal += cantidad;

        html += `
            <div class="ticket-item">

                <div>
                    <b class="small">
                        ${seguro(producto.nombre)}
                    </b>

                    <div class="muted small">
                        ${dinero(producto.precio)} c/u
                    </div>

                    <div class="qty">
                        <button type="button"
                                onclick="cambiarCantidad(${id},-1)">
                            −
                        </button>

                        <b>${cantidad}</b>

                        <button type="button"
                                onclick="cambiarCantidad(${id},1)">
                            +
                        </button>
                    </div>
                </div>

                <b class="price">${dinero(subtotal)}</b>

            </div>
        `;
    });

    $("ticketProductos").innerHTML = html ||
        '<p class="text-center muted py-3">Todavía no seleccionaste productos.</p>';

    $("cantidadTotal").textContent = cantidadTotal;
    $("totalNuevo").textContent = dinero(total);
}

function limpiarCarrito() {
    carrito.clear();
    renderizarTicket();
    filtrarCrear();
}

if (ROL === "mesero") {
    $("formPedido").addEventListener("submit", async evento => {
        evento.preventDefault();

        const mesa = $("mesa").value.trim();

        const seleccion = [...carrito].map(
            ([id, cantidad]) => ({id, cantidad})
        );

        if (!mesa) {
            aviso("Escribe una mesa.", true);
            return;
        }

        if (!seleccion.length) {
            aviso("Agrega al menos un producto.", true);
            return;
        }

        const boton = $("btnEnviarPedido");
        boton.disabled = true;

        try {
            const respuesta = await solicitar(
                "crear_pedido",
                "POST",
                {mesa, productos: seleccion}
            );

            $("mesa").value = "";
            carrito.clear();

            await actualizarTodo();

            mostrar("pedidos");

            aviso(
                "Pedido #" + respuesta.pedido_id +
                " enviado a cocina correctamente."
            );

        } catch (error) {
            aviso(error.message, true);
        } finally {
            boton.disabled = false;
        }
    });
}

/* LISTA DE PEDIDOS */

function renderizarPedidos() {
    $("pedidosCantidad").textContent = pedidos.length;

    $("pedidosCocina").textContent =
        pedidos.filter(p =>
            ["pendiente", "preparando"].includes(p.estado)
        ).length;

    $("pedidosListos").textContent =
        pedidos.filter(p => p.estado === "listo").length;

    $("pedidosCobrar").textContent =
        pedidos.filter(p => p.estado === "entregado").length;

    const opciones = ["todos", ...estados];

    $("filtrosEstados").innerHTML = opciones.map((e, i) => {
        const cantidad = e === "todos"
            ? pedidos.length
            : pedidos.filter(p => p.estado === e).length;

        return `
            <button
                class="chip ${estadoFiltro === e ? "active" : ""}"
                onclick="estadoFiltro=['todos',...estados][${i}];
                         renderizarPedidos()">
                ${seguro(e === "todos" ? "Todos" : e)}
                (${cantidad})
            </button>
        `;
    }).join("");

    filtrarPedidos();
}

function filtrarPedidos() {
    const busqueda = normalizar($("buscarPedido").value);

    const lista = pedidos.filter(p => {
        const texto = normalizar([
            p.id, p.mesa, p.mesero, p.platos, p.estado
        ].join(" "));

        return texto.includes(busqueda) &&
            (estadoFiltro === "todos" || p.estado === estadoFiltro);
    });

    $("cantidadFiltrada").textContent = lista.length + " pedidos";

    $("tablaPedidos").innerHTML = lista.map(p => `
        <tr>
            <td>
                <b class="text-danger">#${Number(p.id)}</b>
                <div class="small muted">${seguro(p.mesa)}</div>
            </td>

            <td>${seguro(p.creado_en || "—")}</td>
            <td>${seguro(p.mesero)}</td>
            <td>${seguro(p.platos || "Sin detalle registrado")}</td>

            <td>
                <span class="status ${seguro(p.estado)}">
                    ${seguro(p.estado)}
                </span>
            </td>

            <td><b>${dinero(p.total)}</b></td>

            <td>
                <button class="btn btn-outline-secondary btn-sm"
                        onclick="verPedido(${Number(p.id)})">
                    Ver
                </button>
            </td>
        </tr>
    `).join("") ||
    '<tr><td colspan="7" class="text-center py-4 muted">No se encontraron pedidos.</td></tr>';
}

function verPedido(id) {
    const p = pedidos.find(x => Number(x.id) === id);
    if (!p) return;

    $("tituloDetalle").textContent = "Detalle del Pedido #" + id;

    $("contenidoDetalle").innerHTML = `
        <p><b>Mesa:</b> ${seguro(p.mesa)}</p>
        <p><b>Mesero:</b> ${seguro(p.mesero)}</p>
        <p><b>Fecha:</b> ${seguro(p.creado_en || "—")}</p>

        <p>
            <b>Estado:</b>
            <span class="status ${seguro(p.estado)}">
                ${seguro(p.estado)}
            </span>
        </p>

        <hr>

        <h6>Platos de la comanda</h6>
        <p>${seguro(p.platos || "Sin detalle registrado")}</p>

        ${p.observaciones ? `
            <div class="alert alert-warning">
                ${seguro(p.observaciones)}
            </div>
        ` : ""}

        <hr>
        <h4 class="price">Total: ${dinero(p.total)}</h4>
    `;

    bootstrap.Modal.getOrCreateInstance($("modalPedido")).show();
}

/* MONITOR KDS DE COCINA */

function minutosTranscurridos(fecha) {
    if (!fecha) return null;

    const fechaPedido = new Date(
        String(fecha).replace(" ", "T")
    );

    const diferencia = Date.now() - fechaPedido.getTime();

    return Number.isFinite(diferencia)
        ? Math.max(0, Math.floor(diferencia / 60000))
        : null;
}

function renderizarCocina() {
    const pendientes = pedidos.filter(
        p => p.estado === "pendiente"
    ).length;

    const preparando = pedidos.filter(
        p => p.estado === "preparando"
    ).length;

    const listos = pedidos.filter(
        p => p.estado === "listo"
    ).length;

    const activos = pedidos.filter(
        p => ["pendiente", "preparando"].includes(p.estado)
    );

    const demorados = activos.filter(p => {
        const minutos = minutosTranscurridos(p.creado_en);
        return minutos !== null && minutos > 18;
    }).length;

    $("kdsPendientes").textContent = pendientes;
    $("kdsPreparando").textContent = preparando;
    $("kdsDemoradas").textContent = demorados;
    $("kdsListos").textContent = listos;

    const opciones = [
        ["todos", "Todas"],
        ["pendiente", "Pendientes"],
        ["preparando", "En preparación"]
    ];

    $("kdsFiltros").innerHTML = opciones.map(([valor, nombre]) => {
        const cantidad = valor === "todos"
            ? activos.length
            : activos.filter(p => p.estado === valor).length;

        return `
            <button
                class="chip ${kdsFiltro === valor ? "active" : ""}"
                onclick="kdsFiltro='${valor}';renderizarCocina()">
                ${nombre} (${cantidad})
            </button>
        `;
    }).join("");

    const lista = activos
        .filter(p =>
            kdsFiltro === "todos" || p.estado === kdsFiltro
        )
        .sort((a, b) => Number(a.id) - Number(b.id));

    $("listaCocina").innerHTML = lista.map(p => {
        const minutos = minutosTranscurridos(p.creado_en);
        const demorado = minutos !== null && minutos > 18;

        const siguiente = p.estado === "pendiente"
            ? "preparando"
            : "listo";

        return `
            <article class="kds-card
                ${demorado ? "delayed" : ""}
                ${p.estado === "preparando" ? "preparing" : ""}">

                <div class="kds-head">

                    <div class="d-flex justify-content-between
                                align-items-center">

                        <b>Comanda #${Number(p.id)}</b>

                        <b>
                            ${minutos === null ? "—" : minutos + " min"}
                            ${demorado ? "⚠️" : ""}
                        </b>

                    </div>

                    <div class="small mt-2">
                        ${seguro(p.mesa)} · ${seguro(p.mesero)}
                    </div>

                </div>

                <div class="kds-body">

                    <div class="d-flex justify-content-between
                                align-items-center gap-2">

                        <b>Platos a preparar</b>

                        <span class="status ${seguro(p.estado)}">
                            ${seguro(p.estado)}
                        </span>

                    </div>

                    <div class="kds-dishes">
                        ${seguro(p.platos || "Sin detalle registrado")}
                    </div>

                    ${p.observaciones ? `
                        <div class="kds-note">
                            <b>Observaciones:</b>
                            ${seguro(p.observaciones)}
                        </div>
                    ` : ""}

                    <p class="small muted">
                        Creado: ${seguro(p.creado_en || "—")}
                    </p>

                    <button
                        class="btn ${p.estado === "pendiente" ? "btn-red" : "btn-green"} w-100 py-2 fw-bold"
                        onclick="cambiarEstado(${Number(p.id)},'${siguiente}')">

                        ${p.estado === "pendiente"
                            ? "▶ Iniciar preparación"
                            : "✓ Marcar como listo"}

                    </button>

                </div>

            </article>
        `;
    }).join("") || `
        <div class="panel text-center" style="grid-column:1/-1">
            <h5>✅ Cocina al día</h5>
            <p>No hay comandas pendientes en este filtro.</p>
        </div>
    `;

    $("kdsClock").textContent =
        new Date().toLocaleTimeString("es-PE", {
            hour: "2-digit",
            minute: "2-digit"
        });
}

/* ENTREGAS DEL MESERO */

let filtroEntregas = "listo";

function renderizarEntregas() {

    if (ROL !== "mesero") return;

    const hoy = new Date().toLocaleDateString("en-CA");

    const listos = pedidos.filter(
        p => p.estado === "listo"
    );

    const entregados = pedidos.filter(
        p => p.estado === "entregado"
    );

    const entregadosHoy = entregados.filter(p =>
        String(p.actualizado_en || "").startsWith(hoy)
    );

    $("entregasListos").textContent = listos.length;
    $("entregasHoy").textContent = entregadosHoy.length;
    $("entregasCobrar").textContent = entregados.length;

    const opciones = [
        ["listo", "Listos"],
        ["entregado", "Entregados"],
        ["todos", "Todos"]
    ];

    $("filtrosEntregas").innerHTML = opciones.map(
        ([estado, nombre]) => {

            const cantidad = estado === "todos"
                ? listos.length + entregados.length
                : pedidos.filter(p => p.estado === estado).length;

            return `
                <button
                    class="chip ${filtroEntregas === estado ? "active" : ""}"
                    onclick="filtroEntregas='${estado}';
                             renderizarEntregas()">

                    ${nombre} (${cantidad})

                </button>
            `;
        }
    ).join("");

    const busqueda = normalizar(
        $("buscarEntrega").value
    );

    const lista = pedidos.filter(p => {

        const estadoPermitido =
            ["listo", "entregado"].includes(p.estado);

        const coincideEstado =
            filtroEntregas === "todos" ||
            p.estado === filtroEntregas;

        const texto = normalizar([
            p.id,
            p.mesa,
            p.mesero,
            p.platos
        ].join(" "));

        return estadoPermitido &&
               coincideEstado &&
               texto.includes(busqueda);
    });

    $("listaEntregas").innerHTML = lista.map(p => {

        const estaListo = p.estado === "listo";

        return `
            <article class="entrega-card">

                <div class="entrega-card-header">

                    <div class="d-flex justify-content-between
                                align-items-center gap-2">

                        <h5 class="fw-bold mb-0">
                            Pedido #${Number(p.id)}
                        </h5>

                        <span class="status ${seguro(p.estado)}">
                            ${estaListo
                                ? "Listo para entregar"
                                : "Entregado"}
                        </span>

                    </div>

                    <p class="muted small mb-0 mt-2">
                        ${seguro(p.mesa)} ·
                        ${seguro(p.mesero)}
                    </p>

                </div>

                <div class="entrega-card-body">

                    <h6 class="fw-bold">
                        🍽️ Platos de la comanda
                    </h6>

                    <div class="entrega-detalle">
                        ${seguro(
                            p.platos ||
                            "Sin detalle registrado"
                        )}
                    </div>

                    ${p.observaciones ? `
                        <div class="entrega-alerta">
                            <strong>Observaciones:</strong>
                            ${seguro(p.observaciones)}
                        </div>
                    ` : ""}

                    <div class="d-flex justify-content-between
                                align-items-center">

                        <span class="muted">
                            Total del pedido
                        </span>

                        <h5 class="price mb-0">
                            ${dinero(p.total)}
                        </h5>

                    </div>

                    <p class="small muted mt-3 mb-0">
                        Registrado:
                        ${seguro(p.creado_en || "—")}
                    </p>

                </div>

                <div class="entrega-card-footer">

                    ${estaListo ? `
                        <button
                            class="btn btn-green w-100 py-2 fw-bold"
                            onclick="cambiarEstado(
                                ${Number(p.id)}, 'entregado'
                            )">

                            ✓ Marcar como entregado
                        </button>
                    ` : `
                        <div class="text-success fw-semibold text-center">
                            ✓ Pedido entregado · Pendiente de cobro
                        </div>
                    `}

                </div>

            </article>
        `;
    }).join("") || `
        <div class="entrega-vacio">
            <div style="font-size:40px">🍽️</div>

            <h5 class="fw-bold mt-3">
                No hay pedidos en este estado
            </h5>

            <p>
                Los pedidos aparecerán cuando cocina
                los marque como listos.
            </p>
        </div>
    `;
}


/* CAJA Y PAGOS */

function renderizarCaja() {
    const pendientes = pedidos.filter(
        p => p.estado === "entregado"
    );

    const pagados = pedidos.filter(
        p => p.estado === "pagado"
    );

    $("cajaPendientes").textContent = pendientes.length;

    $("cajaMontoPendiente").textContent = dinero(
        pendientes.reduce((total, p) =>
            total + Number(p.total || 0), 0
        )
    );

    $("cajaPagados").textContent = pagados.length;

    $("cajaTotalPagado").textContent = dinero(
        pagados.reduce((total, p) =>
            total + Number(p.total || 0), 0
        )
    );

    $("cajaNumeroCuentas").textContent = pendientes.length;

    const busqueda = normalizar($("buscarCaja").value);

    const visibles = pendientes.filter(p =>
        normalizar([
            p.id, p.mesa, p.mesero, p.platos
        ].join(" ")).includes(busqueda)
    );

    if (!pendientes.some(
        p => Number(p.id) === cajaSeleccionada
    )) {
        cajaSeleccionada = pendientes.length
            ? Number(pendientes[0].id)
            : null;
    }

    $("listaCaja").innerHTML = visibles.map(p => `
        <button
            type="button"
            class="cash-item ${Number(p.id) === cajaSeleccionada ? "selected" : ""}"
            onclick="seleccionarCaja(${Number(p.id)})">

            <div class="d-flex justify-content-between">
                <b>${seguro(p.mesa)}</b>
                <b class="price">${dinero(p.total)}</b>
            </div>

            <div class="small muted">
                Pedido #${Number(p.id)} · ${seguro(p.mesero)}
            </div>

        </button>
    `).join("") ||
    '<p class="muted">No hay cuentas pendientes.</p>';

    const seleccionado = pendientes.find(
        p => Number(p.id) === cajaSeleccionada
    );

    if (!seleccionado) {
        $("liquidacionCaja").innerHTML = `
            <div class="text-center muted py-5">
                No hay cuentas por cobrar.
                Los pedidos aparecerán cuando el mesero
                los marque como entregados.
            </div>
        `;
        return;
    }

    const metodos = [
        ["efectivo", "💵 Efectivo"],
        ["tarjeta", "💳 Tarjeta"],
        ["yape", "📱 Yape"],
        ["plin", "📱 Plin"]
    ];

    $("liquidacionCaja").innerHTML = `
        <div class="d-flex align-items-center
                    justify-content-between gap-2 mb-2">

            <div>
                <span class="small text-danger fw-bold">
                    LIQUIDACIÓN DE CUENTA
                </span>

                <h4 class="fw-bold mt-1">
                    ${seguro(seleccionado.mesa)} ·
                    Pedido #${Number(seleccionado.id)}
                </h4>
            </div>

            <span class="status entregado">Entregado</span>

        </div>

        <p class="muted small">
            Mesero: ${seguro(seleccionado.mesero)}
        </p>

        <hr>

        <h6 class="fw-bold">Detalle del consumo</h6>

        <p class="mb-3">
            ${seguro(seleccionado.platos || "Sin detalle registrado")}
        </p>

        <div class="cash-breakdown">
            <span>Total registrado en MySQL</span>
            <strong class="price">
                ${dinero(seleccionado.total)}
            </strong>
        </div>

        <h6 class="fw-bold mt-4">Método de pago</h6>

        <div class="payment-methods mb-3">
            ${metodos.map(([valor, texto]) => `
                <label>
                    <input
                        type="radio"
                        name="metodoPago"
                        value="${valor}"
                        ${cajaMetodo === valor ? "checked" : ""}
                        onchange="cajaMetodo=this.value;actualizarVuelto()">
                    ${texto}
                </label>
            `).join("")}
        </div>

        <div id="bloqueEfectivo">

            <label class="form-label fw-semibold"
                   for="montoRecibido">
                Efectivo recibido (S/)
            </label>

            <input
                type="number"
                min="0"
                step="0.01"
                id="montoRecibido"
                class="form-control"
                placeholder="Ingresa el monto entregado"
                oninput="actualizarVuelto()">

            <div class="cash-breakdown mt-2">
                <span>Vuelto estimado</span>
                <strong id="vueltoCaja">S/ 0.00</strong>
            </div>

        </div>

        <div class="alert alert-light border mt-3 small">
            El pago se registra internamente.
            No valida cobros externos ni emite SUNAT.
        </div>

        <button
            id="btnConfirmarCaja"
            class="btn btn-red btn-lg w-100 fw-bold"
            onclick="pagarSeleccionado()">

            ✓ Confirmar pago ${dinero(seleccionado.total)}

        </button>
    `;

    actualizarVuelto();
}

function seleccionarCaja(id) {
    cajaSeleccionada = id;
    cajaMetodo = "efectivo";
    renderizarCaja();
}

function actualizarVuelto() {
    if (ROL !== "cashier") return;

    const bloque = $("bloqueEfectivo");
    if (!bloque) return;

    bloque.style.display =
        cajaMetodo === "efectivo" ? "block" : "none";

    const pedido = pedidos.find(
        p => Number(p.id) === cajaSeleccionada
    );

    if (!pedido) return;

    const recibido = Number(
        $("montoRecibido")?.value || 0
    );

    const vuelto = Math.max(
        0, recibido - Number(pedido.total)
    );

    $("vueltoCaja").textContent = dinero(vuelto);
}

/* ACTUALIZAR ESTADO DEL PEDIDO */

async function cambiarEstado(id, estado) {
    try {
        await solicitar(
            "cambiar_estado",
            "PATCH",
            {id, estado}
        );

        aviso("Pedido actualizado correctamente.");
        await actualizarTodo();

    } catch (error) {
        aviso(error.message, true);
    }
}

/* CONFIRMAR PAGO */

async function pagarSeleccionado() {
    const pedido = pedidos.find(
        p => Number(p.id) === cajaSeleccionada &&
             p.estado === "entregado"
    );

    if (!pedido) {
        aviso("Selecciona una cuenta válida.", true);
        return;
    }

    if (cajaMetodo === "efectivo") {
        const campo = $("montoRecibido");
        const recibido = Number(campo.value);

        if (!campo.value ||
            !Number.isFinite(recibido) ||
            recibido < Number(pedido.total)) {

            aviso(
                "Ingresa un monto recibido igual o superior al total.",
                true
            );
            return;
        }
    }

    const confirmar = confirm(
        "¿Registrar el pago del pedido #" +
        pedido.id + " por " + dinero(pedido.total) +
        " mediante " + cajaMetodo + "?"
    );

    if (!confirmar) return;

    const boton = $("btnConfirmarCaja");
    boton.disabled = true;

    try {
        await solicitar("pagar", "POST", {
            id: Number(pedido.id),
            metodo: cajaMetodo
        });

        aviso(
            "Pago del pedido #" + pedido.id +
            " registrado correctamente."
        );

        cajaSeleccionada = null;
        cajaMetodo = "efectivo";

        await actualizarTodo();

    } catch (error) {
        aviso(error.message, true);
    } finally {
        if (document.contains(boton)) {
            boton.disabled = false;
        }
    }
}

actualizarTodo();

const emojiPlatoOriginal = emojiPlato;

emojiPlato = function(nombre, categoria) {

    const nombreNormalizado = normalizar(nombre);

    const imagenes = [
    ["aji de gallina", "ajidegallina.jpg"],
    ["arroz con pollo", "arrozconpollo.jpg"],
    ["ceviche", "ceviche.jpg"],
    ["chicha", "chicha.jpg"],
    ["inka kola", "inkakola.jpg"],
    ["inca kola", "inkakola.jpg"],
    ["limonada", "limonada.jpg"],
    ["papa a la huancaina", "papaalahuancaina.jpg"],
    ["suspiro", "suspiro_limeno.jpg"],
    ["tequeno", "tequeños.jpg"],
    ["lomo", "lomo.jpg"]
];

    const encontrada = imagenes.find(
        ([palabra]) => nombreNormalizado.includes(palabra)
    );

    if (!encontrada) {
        return emojiPlatoOriginal(nombre, categoria);
    }

    const archivo = encontrada[1];

    const ruta = "assets/img/" + encodeURIComponent(archivo);

    const emoji = emojiPlatoOriginal(nombre, categoria);

    return `
        <img
            src="${ruta}"
            class="foto-platillo"
            alt="${seguro(nombre)}"
            loading="lazy"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <span
            class="foto-respaldo"
            style="display:none">
            ${emoji}
        </span>
    `;
};
