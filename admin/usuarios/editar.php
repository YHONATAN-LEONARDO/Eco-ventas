<?php
// admin/usuarios/editar.php
include '../../app/config/session.php';
include '../../app/config/database.php';

// CSRF token
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  die('ID inválido.');
}

// Cargar datos actuales del empleado
$sql = "SELECT id, nombre, apellido, correo, telefono, rol FROM empleados WHERE id = ?";
$stmt = sqlsrv_query($conn, $sql, [$id]);
if ($stmt === false) die('Error al consultar empleado: ' . print_r(sqlsrv_errors(), true));
$emp = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$emp) die('Empleado no encontrado.');

$nombre   = $emp['nombre'];
$apellido = $emp['apellido'];
$correo   = $emp['correo'];
$telefono = $emp['telefono'];
$rol      = $emp['rol'];

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validar CSRF
  if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
    die('CSRF token inválido.');
  }

  $nombre   = trim($_POST['nombre']   ?? '');
  $apellido = trim($_POST['apellido'] ?? '');
  $correo   = trim($_POST['correo']   ?? '');
  $telefono = trim($_POST['telefono'] ?? '');
  $rol      = trim($_POST['rol']      ?? '');

  $pass        = $_POST['password']  ?? '';
  $pass_confirm= $_POST['confirmar'] ?? '';

  // Validaciones básicas
  if ($nombre === '')   $errores[] = 'Debes ingresar el nombre.';
  if ($apellido === '') $errores[] = 'Debes ingresar el apellido.';
  if ($correo === '')   $errores[] = 'Debes ingresar el correo.';
  if ($telefono === '') $errores[] = 'Debes ingresar el teléfono.';
  if ($rol === '')      $errores[] = 'Debes seleccionar el rol.';

  if ($pass !== '' && $pass !== $pass_confirm) {
    $errores[] = 'Las contraseñas no coinciden.';
  }

  // Verificar correo duplicado (otro empleado)
  if (empty($errores)) {
    $sqlDup = "SELECT COUNT(*) AS cnt FROM empleados WHERE correo = ? AND id <> ?";
    $stDup  = sqlsrv_query($conn, $sqlDup, [$correo, $id]);
    if ($stDup === false) {
      $errores[] = 'Error validando correo.';
    } else {
      $dup = sqlsrv_fetch_array($stDup, SQLSRV_FETCH_ASSOC);
      if ($dup && (int)$dup['cnt'] > 0) {
        $errores[] = 'El correo ya está registrado por otro empleado.';
      }
    }
  }

  if (empty($errores)) {
    if ($pass !== '') {
      // Actualiza con contraseña
      $hash = password_hash($pass, PASSWORD_BCRYPT);
      $sqlU = "UPDATE empleados
               SET nombre = ?, apellido = ?, correo = ?, telefono = ?, rol = ?, [password] = ?
               WHERE id = ?";
      $params = [$nombre, $apellido, $correo, $telefono, $rol, $hash, $id];
    } else {
      // Actualiza sin tocar contraseña
      $sqlU = "UPDATE empleados
               SET nombre = ?, apellido = ?, correo = ?, telefono = ?, rol = ?
               WHERE id = ?";
      $params = [$nombre, $apellido, $correo, $telefono, $rol, $id];
    }

    $stmtU = sqlsrv_query($conn, $sqlU, $params);
    if ($stmtU) {
      header('Location: ./lista.php?mensaje=2');
      exit;
    } else {
      $errores[] = 'Error al actualizar el empleado.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Usuarios | Editar Empleado</title>
  <link rel="stylesheet" href="/admin/panel.css">
</head>
<body class="us-page us-page--crear">
  <header class="header us-header">
    <h1 class="titulo us-title">Editar Empleado</h1>
  </header>

  <div class="us-actions-top">
    <a href="./lista.php" class="us-btn us-btn--back">Volver</a>
  </div>

  <?php if (!empty($errores)) { foreach($errores as $e) { ?>
    <div class="alerta error us-alert us-alert--error" id="us-msg"><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php }} ?>

  <main class="us-container">
    <form class="us-form" method="POST" action="">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">

      <fieldset class="us-fieldset">
        <legend class="us-legend">Datos del empleado #<?php echo (int)$id; ?></legend>

        <label class="us-label" for="nombre">Nombre</label>
        <input class="us-input" type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>" required>

        <label class="us-label" for="apellido">Apellido</label>
        <input class="us-input" type="text" id="apellido" name="apellido" value="<?php echo htmlspecialchars($apellido, ENT_QUOTES, 'UTF-8'); ?>" required>

        <label class="us-label" for="correo">Correo</label>
        <input class="us-input" type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($correo, ENT_QUOTES, 'UTF-8'); ?>" required>

        <label class="us-label" for="telefono">Teléfono</label>
        <input class="us-input" type="tel" id="telefono" name="telefono" value="<?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?>" required>

        <label class="us-label" for="rol">Rol</label>
        <select class="us-select" id="rol" name="rol" required>
          <option value="">— Seleccione —</option>
          <option value="admin"    <?php echo $rol==='admin'    ? 'selected' : ''; ?>>Administrador</option>
          <option value="vendedor" <?php echo $rol==='vendedor' ? 'selected' : ''; ?>>Vendedor</option>
          <option value="almacen"  <?php echo $rol==='almacen'  ? 'selected' : ''; ?>>Almacén</option>
        </select>
      </fieldset>

      <fieldset class="us-fieldset">
        <legend class="us-legend">Cambio de contraseña (opcional)</legend>
        <label class="us-label" for="password">Nueva contraseña</label>
        <input class="us-input" type="password" id="password" name="password" placeholder="Dejar vacío para mantener">

        <label class="us-label" for="confirmar">Confirmar nueva contraseña</label>
        <input class="us-input" type="password" id="confirmar" name="confirmar" placeholder="Repite la contraseña">
      </fieldset>

      <button class="us-btn us-btn--primary us-btn--submit" type="submit">Guardar Cambios</button>
    </form>
  </main>

  <script>
    const m = document.getElementById('us-msg');
    if (m) setTimeout(()=> m.style.display='none', 3000);
  </script>
</body>
</html>
