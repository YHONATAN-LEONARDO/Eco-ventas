<?php
require __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/../../app/config/database.php';
use Dompdf\Dompdf;

// --- Función helper ---
function kpi($sql) {
  global $conn;
  $s = sqlsrv_query($conn, $sql);
  if (!$s) return 0;
  $r = sqlsrv_fetch_array($s, SQLSRV_FETCH_NUMERIC);
  return $r ? (float)$r[0] : 0;
}
function fmt($n, $d=2){ return number_format((float)$n, $d, '.', ','); }

// --- Métricas ---
$ingresos = kpi("SELECT SUM(CAST(total AS FLOAT)) FROM ventas");
$ventas   = kpi("SELECT COUNT(*) FROM ventas");
$hoy      = kpi("SELECT SUM(CAST(total AS FLOAT)) FROM ventas WHERE CONVERT(date,fecha)=CONVERT(date,GETDATE())");
$mes      = kpi("SELECT SUM(CAST(total AS FLOAT)) FROM ventas WHERE MONTH(fecha)=MONTH(GETDATE()) AND YEAR(fecha)=YEAR(GETDATE())");
$clientes = kpi("SELECT COUNT(*) FROM clientes");
$gastos   = kpi("SELECT SUM(CAST(total AS FLOAT)) FROM compras");
$ganancia = $ingresos - $gastos;

// --- HTML Estilizado ---
$html = "
<!DOCTYPE html>
<html lang='es'>
<head>
<meta charset='UTF-8'>
<style>
  body {
    font-family: 'DejaVu Sans', sans-serif;
    background: #f8f9fa;
    color: #333;
    padding: 40px;
  }
  h1 {
    text-align: center;
    color: #212529;
    font-size: 26px;
    margin-bottom: 10px;
  }
  h2 {
    text-align: center;
    font-weight: 400;
    font-size: 16px;
    color: #555;
    margin-top: 0;
    margin-bottom: 30px;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 30px;
    font-size: 14px;
  }
  th {
    background: #343a40;
    color: white;
    text-transform: uppercase;
    letter-spacing: .05em;
    padding: 10px;
  }
  td {
    background: #fff;
    border-bottom: 1px solid #ddd;
    padding: 10px;
  }
  tr:nth-child(even) td {
    background: #f2f2f2;
  }
  .resumen {
    background: #198754;
    color: white;
    text-align: center;
    border-radius: 10px;
    padding: 15px;
    font-size: 16px;
    margin-bottom: 10px;
  }
  footer {
    text-align: right;
    font-size: 11px;
    color: #666;
    margin-top: 40px;
  }
</style>
</head>
<body>

<h1>📊 Reporte General del Sistema</h1>
<h2>EcoAbrigo - Panel Administrativo</h2>

<div class='resumen'>
  <strong>Ganancia neta:</strong> Bs ".fmt($ganancia)." &nbsp; | &nbsp;
  <strong>Gasto en compras:</strong> Bs ".fmt($gastos)." &nbsp; | &nbsp;
  <strong>Ingresos totales:</strong> Bs ".fmt($ingresos)."
</div>

<table>
  <tr><th>Métrica</th><th>Valor</th></tr>
  <tr><td>Total ingresos</td><td>Bs ".fmt($ingresos)."</td></tr>
  <tr><td>Ventas registradas</td><td>".fmt($ventas,0)."</td></tr>
  <tr><td>Ventas hoy</td><td>Bs ".fmt($hoy)."</td></tr>
  <tr><td>Ventas del mes</td><td>Bs ".fmt($mes)."</td></tr>
  <tr><td>Clientes registrados</td><td>".fmt($clientes,0)."</td></tr>
  <tr><td>Gasto en compras</td><td>Bs ".fmt($gastos)."</td></tr>
  <tr><td>Ganancia neta</td><td><strong>Bs ".fmt($ganancia)."</strong></td></tr>
</table>

<footer>
  Generado automáticamente el ".date('d/m/Y H:i')."
</footer>
</body>
</html>
";

// --- Generación PDF ---
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','portrait');
$dompdf->render();
$dompdf->stream("reporte_general.pdf", ["Attachment" => true]);
