<?php
// admin/usuarios/crear.php
include '../../app/config/session.php';
include '../../app/config/database.php';

/*
  Ajusta los nombres de columnas/tabla a tu esquema real.
  Supuesto tabla: empleados (id, nombre, apellido, correo, telefono, rol, password, creado_en)
*/

$nombre = $apellido = $correo = $telefono = $rol = '';
$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $rol      = trim($_POST['rol'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $confirm  = $_POST['confirmar'] ?? '';

    $errores = [];

    // Validaciones
    if ($nombre === '')    $errores[] = 'Debes ingresar el nombre.';
    if ($apellido === '')  $errores[] = 'Debes ingresar el apellido.';
    if ($correo === '')    $errores[] = 'Debes ingresar el correo.';
    if ($telefono === '')  $errores[] = 'Debes ingresar el teléfono.';
    if ($rol === '')       $errores[] = 'Debes seleccionar el rol.';
    if ($pass === '')      $errores[] = 'Debes ingresar una contraseña.';
    if ($pass !== $confirm)$errores[] = 'Las contraseñas no coinciden.';

    if (empty($errores)) {
        $hash = password_hash($pass, PASSWORD_BCRYPT);

        // Insertar en empleados
        $sql = "INSERT INTO empleados (nombre, apellido, correo, telefono, rol, password)
                VALUES (?, ?, ?, ?, ?, ?)";
        $params = [$nombre, $apellido, $correo, $telefono, $rol, $hash];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            // Si es vendedor, también insertar en vendedor
            if ($rol === 'vendedor') {
                $sqlV = "INSERT INTO vendedor (nombre, apellido, correo, telefono)
                         VALUES (?, ?, ?, ?)";
                $paramsV = [$nombre, $apellido, $correo, $telefono];
                sqlsrv_query($conn, $sqlV, $paramsV);
            }

            header('Location: ./lista.php?mensaje=1');
            exit;
        } else {
            $errores[] = 'Error al crear el empleado.';
        }
    }
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Usuarios | Crear Empleado</title>
  <link rel="stylesheet" href="/admin/panel.css">
</head>
<body class="us-page us-page--crear">

  <header class="header us-header">
    <h1 class="titulo us-title">Crear Empleado</h1>
  </header>

  <!-- Botón Volver -->
  <div class="us-actions-top">
    <a href="./lista.php" class="us-btn us-btn--back">Volver</a>
  </div>

  <?php if (!empty($errores)) { foreach($errores as $e) { ?>
    <div class="alerta error us-alert us-alert--error" id="us-msg"><?php echo htmlspecialchars($e); ?></div>
  <?php }} ?>

  <main class="us-container">
    <form class="us-form" method="POST" action="./crear.php" autocomplete="off">
      <fieldset class="us-fieldset">
        <legend class="us-legend">Datos del empleado</legend>

        <label class="us-label" for="nombre">Nombre</label>
        <input class="us-input" type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>

        <label class="us-label" for="apellido">Apellido</label>
        <input class="us-input" type="text" id="apellido" name="apellido" value="<?php echo htmlspecialchars($apellido); ?>" required>

        <label class="us-label" for="correo">Correo</label>
        <input class="us-input" type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($correo); ?>" required>

        <label class="us-label" for="telefono">Teléfono</label>
        <input class="us-input" type="tel" id="telefono" name="telefono" value="<?php echo htmlspecialchars($telefono); ?>" required>

        <label class="us-label" for="rol">Rol</label>
        <select class="us-select" id="rol" name="rol" required>
          <option value="">— Seleccione —</option>
          <option value="admin"     <?php echo $rol==='admin'?'selected':''; ?>>Administrador</option>
          <option value="vendedor"  <?php echo $rol==='vendedor'?'selected':''; ?>>Vendedor</option>
          <option value="almacen"   <?php echo $rol==='almacen'?'selected':''; ?>>Almacén</option>
        </select>

        <label class="us-label" for="password">Contraseña</label>
        <input class="us-input" type="password" id="password" name="password" required>

        <label class="us-label" for="confirmar">Confirmar contraseña</label>
        <input class="us-input" type="password" id="confirmar" name="confirmar" required>
      </fieldset>

      <button class="us-btn us-btn--primary us-btn--submit" type="submit">Crear Empleado</button>
    </form>
  </main>

  <script>
    const m = document.getElementById('us-msg');
    if (m) setTimeout(()=> m.style.display='none', 3000);
  </script>
</body>
</html>
