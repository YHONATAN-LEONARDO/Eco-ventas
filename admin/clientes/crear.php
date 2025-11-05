<?php
include '../../app/config/session.php';
include '../../app/config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$errores = [];
$nombre = $apellido = $correo = $telefono = $pass = $confirmar = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'], $token)) {
        $errores[] = 'Token inválido, recarga la página.';
    } else {
        $nombre    = trim($_POST['nombre'] ?? '');
        $apellido  = trim($_POST['apellido'] ?? '');
        $correo    = trim($_POST['correo'] ?? '');
        $telefono  = trim($_POST['telefono'] ?? '');
        $pass      = $_POST['password'] ?? '';
        $confirmar = $_POST['confirmar'] ?? '';

        // Validaciones
        if ($nombre === '') $errores[] = 'El nombre es obligatorio.';
        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL))
            $errores[] = 'Debes ingresar un correo válido.';
        if ($pass === '' || strlen($pass) < 8)
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        if ($pass !== $confirmar)
            $errores[] = 'Las contraseñas no coinciden.';

        // Verificar duplicados por correo en ambas tablas
        if (empty($errores)) {
            $chkU = sqlsrv_query($conn, "SELECT 1 FROM dbo.usuarios WHERE correo = ?", [$correo]);
            if ($chkU && sqlsrv_fetch($chkU) === true) $errores[] = 'El correo ya existe en usuarios.';
            if ($chkU) sqlsrv_free_stmt($chkU);

            $chkC = sqlsrv_query($conn, "SELECT 1 FROM dbo.clientes WHERE correo = ?", [$correo]);
            if ($chkC && sqlsrv_fetch($chkC) === true) $errores[] = 'El correo ya existe en clientes.';
            if ($chkC) sqlsrv_free_stmt($chkC);
        }

        if (empty($errores)) {
            // Transacción: crear usuario (login) y cliente (ficha)
            if (!sqlsrv_begin_transaction($conn)) {
                $errores[] = 'No se pudo iniciar la transacción.';
            } else {
                try {
                    // 1) Crear usuario con rol cliente
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    $sqlUser = "INSERT INTO dbo.usuarios (nombre, correo, password_hash, rol)
                                VALUES (?, ?, ?, 'cliente')";
                    $okUser = sqlsrv_query($conn, $sqlUser, [$nombre, $correo, $hash]);
                    if ($okUser === false) {
                        throw new Exception('Error al crear el usuario.');
                    }
                    if ($okUser) sqlsrv_free_stmt($okUser);

                    // 2) Crear cliente (sin contraseña)
                    $sqlCli = "INSERT INTO dbo.clientes (nombre, apellido, correo, telefono)
                               VALUES (?, ?, ?, ?)";
                    $okCli = sqlsrv_query($conn, $sqlCli, [
                        $nombre,
                        $apellido !== '' ? $apellido : null,
                        $correo,
                        $telefono !== '' ? $telefono : null
                    ]);
                    if ($okCli === false) {
                        throw new Exception('Error al crear el cliente.');
                    }
                    if ($okCli) sqlsrv_free_stmt($okCli);

                    // Commit
                    if (!sqlsrv_commit($conn)) {
                        throw new Exception('No se pudo confirmar la transacción.');
                    }

                    // Redirigir (PRG)
                    header('Location: ./lista.php?mensaje=1');
                    exit;

                } catch (Throwable $e) {
                    sqlsrv_rollback($conn);
                    // Para depurar, puedes descomentar la línea de abajo temporalmente:
                    // $errores[] = print_r(sqlsrv_errors(), true);
                    $errores[] = $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Nuevo Cliente</title>
  <link rel="stylesheet" href="/admin/panel.css">
  <style>
    body {background:#f9fafb; font-family:sans-serif;}
    .wrap{max-width:700px;margin:2rem auto;padding:1rem}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:1.5rem;box-shadow:0 2px 8px rgba(0,0,0,.05)}
    h1{margin-bottom:1rem;text-align:center}
    form{display:grid;gap:1rem}
    label{font-weight:600;color:#374151}
    input{width:100%;padding:.5rem;border:1px solid #d1d5db;border-radius:.5rem}
    .error{background:#fee2e2;color:#7f1d1d;padding:.5rem .75rem;border-radius:.5rem;margin-bottom:.5rem}
    .actions{display:flex;justify-content:flex-end;gap:.5rem;margin-top:1rem}
    .btn{padding:.5rem 1rem;border:none;border-radius:.5rem;cursor:pointer;font-weight:600}
    .btn-primary{background:#2563eb;color:white}
    .btn-back{background:#8092b7ff;color:white;text-decoration:none}
    .btn-back:hover{background:#6c7ba1;color:white}
  </style>
</head>
<body>
  <div class="wrap">
    <a href="./lista.php" class="btn btn-back">← Volver</a>
    <div class="card">
      <h1>Registrar nuevo cliente</h1>

      <?php if ($errores) foreach ($errores as $e): ?>
        <div class="error"><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>

      <form method="POST" autocomplete="off" novalidate>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES) ?>">

        <div>
          <label>Nombre *</label>
          <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>
        </div>

        <div>
          <label>Apellido</label>
          <input type="text" name="apellido" value="<?= htmlspecialchars($apellido) ?>">
        </div>

        <div>
          <label>Correo *</label>
          <input type="email" name="correo" value="<?= htmlspecialchars($correo) ?>" required>
        </div>

        <div>
          <label>Teléfono</label>
          <input type="tel" name="telefono" value="<?= htmlspecialchars($telefono) ?>">
        </div>

        <div>
          <label>Contraseña (mín. 8) *</label>
          <input type="password" name="password" required minlength="8">
        </div>

        <div>
          <label>Confirmar contraseña *</label>
          <input type="password" name="confirmar" required minlength="8">
        </div>

        <div class="actions">
          <button type="submit" class="btn btn-primary">Crear cliente</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
