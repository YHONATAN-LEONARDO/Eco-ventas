<?php
// admin/index.php
include __DIR__ . '/../app/config/database.php';
$active = 'dashboard';

// Funciones auxiliares
function kpi_scalar($conn, $sql) {
  $stmt = sqlsrv_query($conn, $sql);
  if (!$stmt) return 0;
  $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
  return $row ? (float)$row[0] : 0;
}
function tabla_existe($conn, $nombre) {
  $sql = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?";
  $s = sqlsrv_query($conn, $sql, [$nombre]);
  return $s && sqlsrv_fetch_array($s);
}
function fnum($n) { return number_format((float)$n, 0, '.', ','); }

// KPIs básicos (solo lo necesario)
$stock       = tabla_existe($conn,'productos') ? kpi_scalar($conn,"SELECT SUM(CAST(cantidad AS FLOAT)) FROM productos") : 0;
$vendidos    = tabla_existe($conn,'ventas_detalle') ? kpi_scalar($conn,"SELECT SUM(CAST(cantidad AS FLOAT)) FROM ventas_detalle") : 0;
$ventas      = tabla_existe($conn,'ventas') ? kpi_scalar($conn,"SELECT COUNT(*) FROM ventas") : 0;
$clientes    = tabla_existe($conn,'clientes') ? kpi_scalar($conn,"SELECT COUNT(*) FROM clientes") : 0;
$proveedores = tabla_existe($conn,'proveedores') ? kpi_scalar($conn,"SELECT COUNT(*) FROM proveedores") : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel del Sistema</title>
<link rel="stylesheet" href="/public/css/normalize.css">
<link rel="stylesheet" href="/admin/panel.css">
<style>
  body { background:#f5f6f8; font-family: 'Segoe UI', sans-serif; }
  .content { padding: 20px; }
  h1 { font-weight:600; letter-spacing:.5px; }
  .btn {
    display:inline-block; background:#222; color:#fff; padding:12px 22px;
    border-radius:10px; text-decoration:none; font-weight:bold; cursor:pointer;
    transition: background .3s;
  }
  .btn:hover { background:#444; }
  .grid-kpis {
    display:grid; grid-template-columns:repeat(4,1fr);
    gap:20px; margin-top:30px;
  }
  .kpi {
    background:#fff; border-radius:14px; box-shadow:0 3px 10px rgba(0,0,0,.08);
    padding:20px; text-align:center; transition:transform .2s;
  }
  .kpi:hover { transform:scale(1.03); }
  .kpi-label {
    text-transform:uppercase; font-size:13px; color:#666; letter-spacing:.04em;
  }
  .kpi-value {
    font-size:28px; font-weight:700; margin-top:8px; color:#222;
  }
  @media(max-width:900px){
    .grid-kpis{ grid-template-columns:repeat(2,1fr); }
  }
</style>
</head>
<body>
<div class="layout">
  <?php include __DIR__.'/sidebar.php'; ?>

  <main class="content">
    <h1>Panel General</h1>

    <!-- Botón de reporte -->
    <form action="/admin/reportes/informe_general.php" method="get">
      <button class="btn" type="submit">Generar reporte PDF</button>
    </form>

    <!-- KPIs esenciales -->
    <section class="grid-kpis">
      <div class="kpi">
        <p class="kpi-label">Stock disponible</p>
        <p class="kpi-value"><?php echo fnum($stock); ?></p>
      </div>

      <div class="kpi">
        <p class="kpi-label">Productos vendidos</p>
        <p class="kpi-value"><?php echo fnum($vendidos); ?></p>
      </div>

      <div class="kpi">
        <p class="kpi-label">Ventas totales</p>
        <p class="kpi-value"><?php echo fnum($ventas); ?></p>
      </div>

      <div class="kpi">
        <p class="kpi-label">Clientes registrados</p>
        <p class="kpi-value"><?php echo fnum($clientes); ?></p>
      </div>

      <div class="kpi">
        <p class="kpi-label">Proveedores activos</p>
        <p class="kpi-value"><?php echo fnum($proveedores); ?></p>
      </div>
    </section>
  </main>
</div>
</body>
</html>
