<?php
// views/usuarios/login.php
require '../../app/config/database.php';
session_start();

$mensaje = "";
$correo_post = "";

/* Si ya está logueado, redirige según rol */
$rolSesion = strtolower($_SESSION['usuario_rol'] ?? $_SESSION['rol'] ?? '');
if ($rolSesion) {
    $destino = in_array($rolSesion, ['admin', 'vendedor', 'almacen']) ? '/admin' : '/';
    header("Location: {$destino}");
    exit;
}

/* Procesar login */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? '') === "login") {

    $correo_post = trim($_POST["correo"] ?? '');
    $password    = $_POST["password"] ?? '';

    if ($correo_post === '' || $password === '') {
        $mensaje = "Ingresa correo y contraseña.";
    } else {

        $u = null;

        // Intento #1: tabla usuarios (password_hash)
        $sqlU  = "SELECT id, nombre, correo, password_hash AS pass, LOWER(rol) AS rol
                  FROM dbo.usuarios WHERE correo = ?";
        $stmtU = sqlsrv_query($conn, $sqlU, [$correo_post]);
        if ($stmtU !== false) {
            $u = sqlsrv_fetch_array($stmtU, SQLSRV_FETCH_ASSOC) ?: null;
            sqlsrv_free_stmt($stmtU);
        } else {
            $mensaje = "Error al consultar usuarios.";
        }

        // Intento #2: tabla empleados (texto plano)
        if (!$u) {
            $sqlE  = "SELECT id,
                             (nombre + ' ' + ISNULL(apellido,'')) AS nombre,
                             correo,
                             [password] AS pass,
                             LOWER(rol) AS rol
                      FROM dbo.empleados
                      WHERE correo = ?";
            $stmtE = sqlsrv_query($conn, $sqlE, [$correo_post]);
            if ($stmtE !== false) {
                $u = sqlsrv_fetch_array($stmtE, SQLSRV_FETCH_ASSOC) ?: null;
                sqlsrv_free_stmt($stmtE);
            } else {
                $mensaje = $mensaje ?: "Error al consultar empleados.";
            }
        }

        // Verificación de contraseña
        $ok = false;
        if ($u) {
            if (isset($u['pass']) && strlen($u['pass']) === 60) {
                // usuarios con password_hash
                $ok = password_verify($password, $u['pass']);
            } else {
                // empleados con texto plano
                $ok = $password === $u['pass'];
            }
        }

        if ($ok) {
            $rol = strtolower($u["rol"] ?? 'cliente');
            session_regenerate_id(true);
            $_SESSION["auth"]           = true;
            $_SESSION["usuario_id"]     = $u["id"];
            $_SESSION["usuario_nombre"] = $u["nombre"];
            $_SESSION["usuario_rol"]    = $rol;
            $_SESSION["rol"]            = $rol;   // compat
            $_SESSION["login"]          = true;

            $destino = in_array($rol, ['admin', 'vendedor', 'almacen']) ? '/admin' : '/';
            header("Location: {$destino}");
            exit;
        } else {
            $mensaje = $mensaje ?: "Correo o contraseña inválidos.";
        }
    }
}

sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - EcoAbrigo</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="login-body">

    <?php if (!empty($mensaje)): ?>
        <div class="mensaje visible" id="mensaje">
            <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <script>
            const msg = document.getElementById('mensaje');
            if (msg) {
                setTimeout(() => msg.classList.remove('visible'), 3000);
                setTimeout(() => msg.remove(), 3500);
            }
        </script>
    <?php endif; ?>

    <a href="/" class="volver">⮌</a>

    <main class="login">
        <section class="login-box">
            <h2>Iniciar Sesión</h2>
            <form class="formulario" action="login.php" method="POST" autocomplete="off" novalidate>
                <label for="correo">Correo electrónico:</label>
                <input type="email" name="correo" placeholder="Ingresa tu correo" required
                    value="<?php echo htmlspecialchars($correo_post, ENT_QUOTES, 'UTF-8'); ?>">

                <label for="password">Contraseña:</label>
                <input type="password" name="password" placeholder="Ingresa tu contraseña" required>

                <button type="submit" name="accion" value="login">Ingresar</button>
            </form>

            <p>¿No tienes cuenta? <a href="registro.php" class="registrate">Regístrate aquí</a></p>
        </section>
    </main>

</body>

</html>
