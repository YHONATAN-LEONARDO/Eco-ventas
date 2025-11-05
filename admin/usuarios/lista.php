<?php
// admin/usuarios/lista.php
include '../../app/config/session.php';
include '../../app/config/database.php';

// CSRF token
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

// Procesar eliminación en el mismo archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
  // Validar CSRF
  if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
    die('CSRF token inválido.');
  }

  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  if ($id > 0) {
    $sqlDel = "DELETE FROM empleados WHERE id = ?";
    $stDel  = sqlsrv_query($conn, $sqlDel, [$id]);
    if ($stDel) {
      header('Location: ./lista.php?mensaje=3'); // eliminado
      exit;
    } else {
      $delError = 'Error al eliminar el empleado.';
    }
  } else {
    $delError = 'ID inválido para eliminar.';
  }
}

// Mensajes
$mensaje = isset($_GET['mensaje']) ? (int)$_GET['mensaje'] : 0;

/*
  Supuesto tabla: empleados(id, nombre, apellido, correo, telefono, rol, creado_en)
*/
$sql = "
  SELECT
    id,
    nombre,
    apellido,
    correo,
    telefono,
    rol,
    creado_en
  FROM empleados
  ORDER BY id DESC
";
$resultado = sqlsrv_query($conn, $sql);
if ($resultado === false) {
  die('Error al consultar empleados: ' . print_r(sqlsrv_errors(), true));
}

function ue_fmt_fecha($f){
  if ($f instanceof DateTime) return $f->format('Y-m-d');
  if (is_array($f) && isset($f['date'])) return date('Y-m-d', strtotime($f['date']));
  return htmlspecialchars((string)$f, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Usuarios | Empleados</title>
  <link rel="stylesheet" href="/admin/panel.css">
</head>
<body class="ue-page ue-page--lista">

  <header class="header ue-header">
    <h1 class="titulo ue-title">Empleados</h1>
  </header>

  <div class="ue-actions-top">
    <a href="../index.php" class="ue-btn ue-btn--back">Volver</a>
    <a href="./crear.php" class="ue-btn ue-btn--primary">Nuevo Empleado</a>
  </div>

  <?php if ($mensaje === 1) { ?>
    <div class="alerta exito ue-alert" id="ue-msg">Empleado creado correctamente</div>
  <?php } elseif ($mensaje === 2) { ?>
    <div class="alerta exito ue-alert" id="ue-msg">Empleado actualizado correctamente</div>
  <?php } elseif ($mensaje === 3) { ?>
    <div class="alerta exito ue-alert" id="ue-msg">Empleado eliminado correctamente</div>
  <?php } ?>

  <?php if (!empty($delError)) { ?>
    <div class="alerta error ue-alert" id="ue-msg"><?php echo htmlspecialchars($delError, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php } ?>

  <main class="ue-container">
    <div class="ue-table-wrap">
      <table class="ue-table">
        <thead class="ue-table__head">
          <tr class="ue-table__head-row">
            <th class="ue-th ue-th--id">ID</th>
            <th class="ue-th ue-th--nombre">Nombre</th>
            <th class="ue-th ue-th--correo">Correo</th>
            <th class="ue-th ue-th--telefono">Teléfono</th>
            <th class="ue-th ue-th--rol">Rol</th>
            <th class="ue-th ue-th--creado">Creado</th>
            <th class="ue-th ue-th--acciones">Acciones</th>
          </tr>
        </thead>
        <tbody class="ue-table__body">
          <?php while($row = sqlsrv_fetch_array($resultado, SQLSRV_FETCH_ASSOC)) {
            $nombreCompleto = trim(($row['nombre'] ?? '').' '.($row['apellido'] ?? ''));
          ?>
          <tr class="ue-row">
            <td class="ue-td ue-td--id"><?php echo (int)$row['id']; ?></td>
            <td class="ue-td ue-td--nombre"><?php echo htmlspecialchars($nombreCompleto ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="ue-td ue-td--correo"><?php echo htmlspecialchars($row['correo'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="ue-td ue-td--telefono"><?php echo htmlspecialchars($row['telefono'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="ue-td ue-td--rol"><?php echo htmlspecialchars($row['rol'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="ue-td ue-td--creado"><?php echo ue_fmt_fecha($row['creado_en']); ?></td>
            <td class="ue-td ue-td--acciones">
              <a class="ue-btn ue-btn--mini ue-btn--primary" href="./editar.php?id=<?php echo (int)$row['id']; ?>">Editar</a>

              <form class="ue-form ue-form--inline" action="" method="POST">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                <button type="submit" class="ue-btn ue-btn--mini ue-btn--danger">Eliminar</button>
              </form>
            </td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </main>

  <script>
    const m = document.getElementById('ue-msg');
    if (m) setTimeout(()=> m.style.display='none', 3000);
  </script>
</body>
</html>
