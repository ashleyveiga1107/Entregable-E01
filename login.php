<?php
session_start();
require_once "config.php";

// Si ya inició sesión, ir al sistema.
if (isset($_SESSION["usuario_id"])) {
    header("Location: index.php");
    exit;
}

// Token de seguridad para el formulario.
if (empty($_SESSION["csrf_login"])) {
    $_SESSION["csrf_login"] = bin2hex(random_bytes(32));
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $token = $_POST["csrf"] ?? "";

    if (!hash_equals($_SESSION["csrf_login"], $token)) {
        $error = "Solicitud inválida. Actualiza la página.";
    } else {

        $sql = "SELECT id, nombre, email, password, rol
                FROM usuarios
                WHERE email = ? AND activo = 1
                LIMIT 1";

        $consulta = $conexion->prepare($sql);
        $consulta->execute([$email]);

        $usuario = $consulta->fetch();

        if ($usuario &&
            password_verify($password, $usuario["password"])) {

            session_regenerate_id(true);

            $_SESSION["usuario_id"] = $usuario["id"];
            $_SESSION["nombre"] = $usuario["nombre"];
            $_SESSION["rol"] = $usuario["rol"];

            unset($_SESSION["csrf_login"]);

            header("Location: index.php");
            exit;
        }

        $error = "Correo o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Iniciar Sesión | RestoManager 2.0</title>

<script src="https://cdn.tailwindcss.com"></script>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined"
rel="stylesheet">

<style>
body {
    font-family: 'Inter', sans-serif;
    background: #f9f9fc;
}

.login-card {
    background: white;
    box-shadow: 0 15px 55px rgba(0,0,0,0.08);
}

.btn-rojo {
    background: #d61a1a;
    transition: 0.2s;
}

.btn-rojo:hover {
    background: #b51414;
}

.campo:focus {
    border-color: #d61a1a;
    outline: none;
    box-shadow: 0 0 0 3px rgba(214,26,26,0.12);
}

.rol-card {
    border: 1px solid #e5e7eb;
    transition: 0.2s;
}

.rol-card:hover {
    border-color: #d61a1a;
    background: #fff7f7;
    transform: translateY(-2px);
}

.logo {
    width: 76px;
    height: 76px;
    border-radius: 50%;
    background: #d61a1a;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    font-weight: 800;
    border: 5px solid #fee2e2;
}

</style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-[520px]">

<div class="login-card rounded-2xl border border-gray-200 p-6 sm:p-8">

<!-- LOGOTIPO -->
<div class="flex flex-col items-center text-center mb-7">

    <div class="logo mb-3">
        SP
    </div>

    <span class="text-xs font-semibold text-emerald-700
    bg-emerald-50 border border-emerald-200
    rounded-full px-3 py-1 mb-3">
        ● RestoManager 2.0
    </span>

    <h1 class="text-2xl font-bold text-gray-900">
        Sabores Peruanos
    </h1>

    <p class="text-sm text-gray-500 mt-2">
        Portal de Gestión Gastronómica
    </p>

    <p class="text-xs text-gray-400 mt-1">
        Ingrese sus credenciales para acceder al sistema.
    </p>

</div>

<!-- ERROR DE AUTENTICACIÓN -->
<?php if ($error): ?>

<div class="bg-red-50 border border-red-200
text-red-700 rounded-lg p-3 mb-5 text-sm">

    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>

</div>

<?php endif; ?>

<!-- FORMULARIO REAL PHP -->
<form method="POST" action="login.php" class="space-y-5">

<input
    type="hidden"
    name="csrf"
    value="<?= htmlspecialchars($_SESSION["csrf_login"], ENT_QUOTES, 'UTF-8') ?>">

<!-- EMAIL -->
<div>

    <label class="block text-sm font-semibold mb-2">
        Correo Electrónico
    </label>

    <div class="relative">

        <span class="material-symbols-outlined
        absolute left-3 top-3 text-gray-400">
            alternate_email
        </span>

        <input
            id="email"
            name="email"
            type="email"
            autocomplete="username"
            required
            placeholder="usuario@resto.test"
            value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>"
            class="campo w-full border border-gray-200
            rounded-lg py-3 pl-11 pr-3 text-sm">

    </div>

</div>

<!-- CONTRASEÑA -->
<div>

    <div class="flex justify-between items-center mb-2">

        <label class="text-sm font-semibold">
            Contraseña
        </label>

        <span class="text-xs text-gray-400">
            Acceso seguro
        </span>

    </div>

    <div class="relative">

        <span class="material-symbols-outlined
        absolute left-3 top-3 text-gray-400">
            lock
        </span>

        <input
            id="password"
            name="password"
            type="password"
            autocomplete="current-password"
            required
            placeholder="Ingrese su contraseña"
            class="campo w-full border border-gray-200
            rounded-lg py-3 pl-11 pr-12 text-sm">

        <button
            type="button"
            onclick="mostrarPassword()"
            class="absolute right-3 top-2.5 text-gray-400"
            aria-label="Mostrar u ocultar contraseña">

            <span class="material-symbols-outlined"
            id="iconoPassword">
                visibility
            </span>

        </button>

    </div>

</div>

<!-- BOTÓN LOGIN -->
<button
    type="submit"
    class="btn-rojo w-full py-3 text-white
    font-semibold rounded-lg shadow-md
    flex items-center justify-center gap-2">

    Iniciar Sesión

    <span class="material-symbols-outlined">
        arrow_forward
    </span>

</button>

</form>

<!-- CUENTAS DE PRUEBA -->
<div class="mt-7 bg-gray-50 border border-gray-200
rounded-xl p-4">

    <div class="flex justify-between items-center gap-2 mb-2">

        <h3 class="font-semibold text-sm text-gray-800">
            Credenciales de Demostración
        </h3>

        <span class="text-xs text-emerald-700
        bg-emerald-50 rounded-full px-2 py-1">
            API REST
        </span>

    </div>

    <p class="text-xs text-gray-500 mb-4">
        Seleccione su cargo para completar el correo.
        Después ingrese su contraseña.
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

        <!-- GERENTE -->
        <button type="button"
            onclick="seleccionarRol('gerente@resto.test')"
            class="rol-card rounded-lg bg-white p-3 text-left">

            <div class="flex items-center gap-2">

                <span class="material-symbols-outlined text-red-600">
                    admin_panel_settings
                </span>

                <div>
                    <strong class="text-sm block">Gerente</strong>
                    <span class="text-xs text-gray-500">
                        Administración y ventas
                    </span>
                </div>

            </div>

        </button>

        <!-- CAJERO -->
        <button type="button"
            onclick="seleccionarRol('cashier@resto.test')"
            class="rol-card rounded-lg bg-white p-3 text-left">

            <div class="flex items-center gap-2">

                <span class="material-symbols-outlined text-emerald-600">
                    point_of_sale
                </span>

                <div>
                    <strong class="text-sm block">Cajero</strong>
                    <span class="text-xs text-gray-500">
                        Caja y pagos
                    </span>
                </div>

            </div>

        </button>

        <!-- MESERO -->
        <button type="button"
            onclick="seleccionarRol('mesero@resto.test')"
            class="rol-card rounded-lg bg-white p-3 text-left">

            <div class="flex items-center gap-2">

                <span class="material-symbols-outlined text-amber-600">
                    room_service
                </span>

                <div>
                    <strong class="text-sm block">Mesero</strong>
                    <span class="text-xs text-gray-500">
                        Pedidos y entregas
                    </span>
                </div>

            </div>

        </button>

        <!-- COCINERO -->
        <button type="button"
            onclick="seleccionarRol('cocinero@resto.test')"
            class="rol-card rounded-lg bg-white p-3 text-left">

            <div class="flex items-center gap-2">

                <span class="material-symbols-outlined text-orange-600">
                    skillet
                </span>

                <div>
                    <strong class="text-sm block">Cocinero</strong>
                    <span class="text-xs text-gray-500">
                        Cocina y preparación
                    </span>
                </div>

            </div>

        </button>

    </div>

    <p class="mt-4 text-xs text-gray-400">
        Los permisos de cada cargo son verificados por PHP.
    </p>

</div>

</div>

<!-- PIE DE PÁGINA -->
<div class="text-center mt-6">

    <p class="text-xs text-emerald-700 font-medium">
        ● Sistema local conectado a MySQL
    </p>

    <p class="text-xs text-gray-400 mt-2">
        © 2026 Sabores Peruanos —
        RestoManager 2.0
    </p>

</div>

</div>

<script>

// MOSTRAR / OCULTAR CONTRASEÑA
function mostrarPassword() {

    const input = document.getElementById("password");
    const icono = document.getElementById("iconoPassword");

    if (input.type === "password") {
        input.type = "text";
        icono.textContent = "visibility_off";
    } else {
        input.type = "password";
        icono.textContent = "visibility";
    }
}

// COMPLETAR CORREO DE DEMOSTRACIÓN
function seleccionarRol(correo) {

    document.getElementById("email").value = correo;

    document.getElementById("password").value = "";

    document.getElementById("password").focus();
}

</script>

</body>
</html>
