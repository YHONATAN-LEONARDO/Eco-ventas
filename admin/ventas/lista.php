<?php
// admin/ventas/lista.php
include '../../app/config/session.php';
include '../../app/config/database.php';

/* ============================
   Consulta principal de ventas
   ============================ */
$sql = "
  SELECT
    v.id,
    v.fecha,
    v.total,
    v.estado,
    c.nombre   AS cliente_nombre,
    c.apellido AS cliente_apellido,
    ve.nombre  AS vendedor_nombre,
    ve.apellido AS vendedor_apellido,
    COUNT(d.id) AS cantidad_productos
  FROM ventas v
  LEFT JOIN clientes c ON v.cliente_id = c.id
  LEFT JOIN vendedor ve ON v.vendedor = ve.id
  LEFT JOIN ventas_detalle d ON v.id = d.venta_id
  GROUP BY v.id, v.fecha, v.total, v.estado, c.nombre, c.apellido, ve.nombre, ve.apellido
  ORDER BY v.id DESC
";

$resultado = sqlsrv_query($conn, $sql);
if ($resultado === false) {
  die('Error al consultar ventas: ' . print_r(sqlsrv_errors(), true));
}

/* ============================
   Funciones de formato
   ============================ */
function sv_fmt_fecha($f)
{
  if ($f instanceof DateTime) return $f->format('Y-m-d H:i');
  if (is_array($f) && isset($f['date'])) return date('Y-m-d H:i', strtotime($f['date']));
  return htmlspecialchars((string)$f);
}
function sv_fmt_bs($n)
{
  return number_format((float)$n, 2, '.', '');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Listado de Ventas</title>
  <link rel="stylesheet" href="/admin/panel.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f0f4f8;
      margin: 0;
      padding: 0;
      color: #0f172a;
    }

    h1 {
      text-align: center;
      padding: 1rem 0;
      font-size: 1.8rem;
      color: #1e40af;
    }

    .actions {
      padding: 1rem;
      text-align: center;
    }

    a.btn {
      display: inline-block;
      background: #1e40af;
      color: #fff;
      padding: 8px 16px;
      text-decoration: none;
      border-radius: 6px;
      font-weight: bold;
      transition: background 0.3s;
    }

    a.btn:hover {
      background: #2563eb;
    }

    table {
      width: 95%;
      max-width: 1200px;
      margin: 1.5rem auto;
      border-collapse: collapse;
      box-shadow: 0 2px 8px rgba(0, 0, 50, 0.1);
      border-radius: 8px;
      overflow: hidden;
    }

    th,
    td {
      padding: 12px 15px;
      border-bottom: 1px solid #cbd5e1;
      text-align: left;
    }

    th {
      background: #1e3a8a;
      color: #fff;
      text-transform: uppercase;
      font-size: 0.9rem;
    }

    td.total,
    td.cantidad {
      text-align: right;
      font-weight: bold;
      color: #1e3a8a;
    }

    tr:nth-child(even) {
      background: #eff6ff;
    }

    tr:hover {
      background: #dbeafe;
      transition: background 0.3s;
    }
  </style>
</head>

<body>

  <h1>Listado de Ventas</h1>

  <div class="actions">
    <a href="../index.php" class="btn">Volver</a>
  </div>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Fecha</th>
        <th>Cliente</th>
        <th>Cant. Productos</th>
        <th>Estado</th>
        <th>Total (Bs)</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = sqlsrv_fetch_array($resultado, SQLSRV_FETCH_ASSOC)):
        $cliente = trim(($row['cliente_nombre'] ?? '') . ' ' . ($row['cliente_apellido'] ?? ''));
        $vendedor = trim(($row['vendedor_nombre'] ?? '') . ' ' . ($row['vendedor_apellido'] ?? ''));
      ?>
        <tr>
          <td><?= (int)$row['id']; ?></td>
          <td><?= sv_fmt_fecha($row['fecha']); ?></td>
          <td><?= htmlspecialchars($cliente ?: '—'); ?></td>
          <td class="cantidad"><?= (int)$row['cantidad_productos']; ?></td>
          <td><?= htmlspecialchars($row['estado']); ?></td>
          <td class="total"><?= sv_fmt_bs($row['total']); ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

</body>

</html>