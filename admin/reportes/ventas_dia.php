<?php
// admin/index.php
include __DIR__ . '/../../app/config/database.php';

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
function fmon($n) { return number_format((float)$n, 2, '.', ','); }

// KPIs base
$stock = tabla_existe($conn,'productos') ? kpi_scalar($conn,"SELECT SUM(CAST(cantidad AS FLOAT)) FROM productos") : 0;
$vendidos = tabla_existe($conn,'ventas_detalle') ? kpi_scalar($conn,"SELECT SUM(CAST(cantidad AS FLOAT)) FROM ventas_detalle") : 0;
$ventas = tabla_existe($conn,'ventas') ? kpi_scalar($conn,"SELECT COUNT(*) FROM ventas") : 0;
$ingresos = tabla_existe($conn,'ventas') ? kpi_scalar($conn,"SELECT SUM(CAST(total AS FLOAT)) FROM ventas") : 0;
$clientes = tabla_existe($conn,'clientes') ? kpi_scalar($conn,"SELECT COUNT(*) FROM clientes") : 0;
$proveedores = tabla_existe($conn,'proveedores') ? kpi_scalar($conn,"SELECT COUNT(*) FROM proveedores") : 0;
$hoy = tabla_existe($conn,'ventas') ? kpi_scalar($conn,"SELECT SUM(CAST(total AS FLOAT)) FROM ventas WHERE CONVERT(date,fecha)=CONVERT(date,GETDATE())") : 0;
$mes = tabla_existe($conn,'ventas') ? kpi_scalar($conn,"SELECT SUM(CAST(total AS FLOAT)) FROM ventas WHERE MONTH(fecha)=MONTH(GETDATE()) AND YEAR(fecha)=YEAR(GETDATE())") : 0;

// NUEVOS: gastos en compras y ganancia
$gastos = tabla_existe($conn,'compras') ? kpi_scalar($conn,"SELECT SUM(CAST(total AS FLOAT)) FROM compras") : 0;
$ganancia = $ingresos - $gastos;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel de Administración</title>
<link rel="stylesheet" href="/public/css/normalize.css">
<link rel="stylesheet" href="/admin/panel.css">
<style>
  .content { padding: 20px; }
  .btn {
    display:inline-block;background:#000;color:#fff;padding:12px 20px;
    border-radius:10px;text-decoration:none;font-weight:bold;cursor:pointer;
    transition: background .3s;
  }
  .btn:hover { background:#444; }
  .grid-kpis {
    display:grid;grid-template-columns:repeat(4,1fr);
    gap:16px;margin-top:20px;
  }
  .kpi {
    background:#fff;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.08);
    padding:16px;text-align:center;
  }
  .kpi-label { text-transform:uppercase;font-size:13px;color:#777;letter-spacing:.05em; }
  .kpi-value { font-size:26px;font-weight:700;margin-top:6px; }
  .charts { margin-top:30px;display:grid;grid-template-columns:repeat(2,1fr);gap:20px; }
  .chart-card { background:#fff;border-radius:12px;padding:16px;box-shadow:0 4px 12px rgba(0,0,0,.08); }
  @media(max-width:900px){ .grid-kpis{grid-template-columns:repeat(2,1fr);} .charts{grid-template-columns:1fr;} }
</style>
</head>
<body>
<div class="layout">
  <?php include '../sidebar.php'; ?>

  <main class="content">
    <h1>PANEL DE ADMINISTRACIÓN</h1>

    <!-- Botón único -->
     <a class="btn" href="../index.php">Volver</a>
    <form action="/admin/reportes/informe_general.php" method="get">
      <button class="btn" type="submit">Generar reporte PDF</button>
    </form>

    <!-- KPIs en 4 columnas -->
    <section class="grid-kpis">
      <div class="kpi"><p class="kpi-label">Stock disponible</p><p class="kpi-value"><?php echo fnum($stock); ?></p></div>
      <div class="kpi"><p class="kpi-label">Productos vendidos</p><p class="kpi-value"><?php echo fnum($vendidos); ?></p></div>
      <div class="kpi"><p class="kpi-label">Ventas totales</p><p class="kpi-value"><?php echo fnum($ventas); ?></p></div>
      <div class="kpi"><p class="kpi-label">Ingresos</p><p class="kpi-value">Bs <?php echo fmon($ingresos); ?></p></div>

      <div class="kpi"><p class="kpi-label">Gasto en compras</p><p class="kpi-value">Bs <?php echo fmon($gastos); ?></p></div>
      <div class="kpi"><p class="kpi-label">Ganancia neta</p><p class="kpi-value">Bs <?php echo fmon($ganancia); ?></p></div>
      <div class="kpi"><p class="kpi-label">Clientes</p><p class="kpi-value"><?php echo fnum($clientes); ?></p></div>
      <div class="kpi"><p class="kpi-label">Proveedores</p><p class="kpi-value"><?php echo fnum($proveedores); ?></p></div>
    </section>

    <!-- Gráficas -->
    <div class="charts">
      <div class="chart-card"><h3>Ventas últimos 7 días</h3><canvas id="chart7"></canvas></div>
      <div class="chart-card"><h3>Ventas por género</h3><canvas id="chartCat"></canvas></div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Datos de ejemplo
  new Chart(document.getElementById('chart7'), {
    type:'line',
    data:{
      labels:['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'],
      datasets:[{label:'Ventas (Bs)',data:[120,90,140,110,180,150,130]}]
    },
    options:{responsive:true,scales:{y:{beginAtZero:true}}}
  });

  // Solo hombre y mujer
  new Chart(document.getElementById('chartCat'), {
    type:'doughnut',
    data:{
      labels:['Hombre','Mujer'],
      datasets:[{data:[60,40]}]
    },
    options:{responsive:true}
  });
</script>
</body>
</html>
