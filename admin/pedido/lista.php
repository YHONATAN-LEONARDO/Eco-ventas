<?php
require_once __DIR__ . '/../../app/config/session.php';
require_once __DIR__ . '/../../app/config/database.php';

$sql = "
SELECT 
  v.id AS venta_id,
  v.fecha,
  v.estado,
  v.total,
  c.nombre AS cliente_nombre,
  c.apellido AS cliente_apellido,
  f.nit_cliente,
  f.razon_social,
  f.lugar_entrega,
  f.observacion,
  p.titulo AS producto_titulo,
  p.imagen,
  vd.cantidad,
  vd.precio_unitario
FROM ventas v
LEFT JOIN clientes c ON v.cliente_id = c.id
JOIN ventas_detalle vd ON v.id = vd.venta_id
JOIN productos p ON vd.producto_id = p.id
LEFT JOIN facturas f ON v.id = f.venta_id
ORDER BY v.fecha DESC, v.id DESC

";

$stmt = sqlsrv_query($conn, $sql);

$pedidos = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
  $vid = (int)$row['venta_id'];
  if (!isset($pedidos[$vid])) {
    $pedidos[$vid] = [
      'id' => $vid,
      'fecha' => $row['fecha'],
      'estado' => $row['estado'],
      'total' => $row['total'],
      'cliente' => trim(($row['cliente_nombre'] ?? '') . ' ' . ($row['cliente_apellido'] ?? '')),
      'nit' => $row['nit_cliente'] ?? '',
      'razon' => $row['razon_social'] ?? '',
      'lugar' => $row['lugar_entrega'] ?? '',
      'obs' => $row['observacion'] ?? '',
      'productos' => []
    ];
  }
  $pedidos[$vid]['productos'][] = [
    'titulo' => $row['producto_titulo'],
    'imagen' => $row['imagen'] ?? '',
    'cantidad' => $row['cantidad'],
    'precio' => $row['precio_unitario']
  ];
}

function fmt_fecha($f)
{
  if ($f instanceof DateTime) return $f->format('Y-m-d H:i');
  if (is_array($f) && isset($f['date'])) return date('Y-m-d H:i', strtotime($f['date']));
  return htmlspecialchars((string)$f);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Pedidos | EcoAbrigo</title>
  <style>
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: #f0f4f8;
      margin: 0;
      padding: 30px;
      color: #333;
    }

    h1 {
      text-align: center;
      color: #1e3a8a;
      margin-bottom: 25px;
    }

    .pedido {
      background: #fff;
      border: 1px solid #dce4ef;
      border-radius: 10px;
      margin-bottom: 30px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      padding: 20px;
    }

    .pedido h2 {
      margin: 0 0 8px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: #0f172a;
      font-size: 1.1rem;
    }

    .estado {
      padding: 4px 10px;
      border-radius: 8px;
      font-size: 0.85rem;
      color: #fff;
      text-transform: capitalize;
    }

    .estado-pagado {
      background: #2563eb;
    }

    .estado-pendiente {
      background: #f59e0b;
    }

    .estado-enviado {
      background: #0ea5e9;
    }

    .estado-cancelado {
      background: #dc2626;
    }

    .info {
      font-size: 0.9rem;
      color: #475569;
      margin-bottom: 10px;
    }

    .detalle-extra {
      background: #eff6ff;
      border-left: 4px solid #2563eb;
      padding: 10px 12px;
      border-radius: 6px;
      margin-bottom: 10px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    th,
    td {
      padding: 10px;
      border-bottom: 1px solid #e5e7eb;
      text-align: left;
    }

    th {
      background: #1e40af;
      color: #fff;
      text-transform: uppercase;
      font-size: 0.85rem;
    }

    td {
      vertical-align: middle;
    }

    .img-prod {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 6px;
      border: 1px solid #e2e8f0;
      margin-right: 8px;
    }

    .prod-info {
      display: flex;
      align-items: center;
    }

    .prod-titulo {
      font-weight: 600;
      color: #0f172a;
    }

    .total {
      text-align: right;
      font-weight: bold;
      color: #1e3a8a;
      margin-top: 10px;
      font-size: 1rem;
    }
  </style>
</head>

<body>
  <h1>Listado de Pedidos</h1>

  <?php foreach ($pedidos as $p): ?>
    <div class="pedido">
      <h2>
        Pedido #<?= $p['id'] ?> - <?= htmlspecialchars($p['cliente']) ?>
        <span class="estado estado-<?= strtolower($p['estado']) ?>">
          <?= htmlspecialchars($p['estado']) ?>
        </span>
      </h2>
      <div class="info">Fecha: <?= fmt_fecha($p['fecha']) ?></div>

      <?php if ($p['razon'] || $p['lugar'] || $p['obs']): ?>
        <div class="detalle-extra">
          <?php if ($p['razon']): ?>
            <div><strong>Factura:</strong> <?= htmlspecialchars($p['razon']) ?> (NIT: <?= htmlspecialchars($p['nit']) ?>)</div>
          <?php endif; ?>
          <?php if ($p['lugar']): ?>
            <div><strong>Lugar de entrega:</strong> <?= htmlspecialchars($p['lugar']) ?></div>
          <?php endif; ?>
          <?php if ($p['obs']): ?>
            <div><strong>Observaciones:</strong> <?= htmlspecialchars($p['obs']) ?></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <table>
        <thead>
          <tr>
            <th>Imagen</th>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Precio (Bs)</th>
            <th>Subtotal (Bs)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($p['productos'] as $pr):
            $sub = $pr['cantidad'] * $pr['precio'];
            $img = htmlspecialchars($pr['imagen'] ?? '');
          ?>
            <tr>
              <td>
                <?php if ($img): ?>
                  <img class="img-prod" src="/imagenes/<?= $img ?>" alt="<?= htmlspecialchars($pr['titulo']) ?>">
                <?php else: ?>
                  <img class="img-prod" src="/imagenes/default.png" alt="sin imagen">
                <?php endif; ?>
              </td>
              <td class="prod-titulo"><?= htmlspecialchars($pr['titulo']) ?></td>
              <td><?= (int)$pr['cantidad'] ?></td>
              <td><?= number_format($pr['precio'], 2) ?></td>
              <td><?= number_format($sub, 2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="total">Total: <?= number_format($p['total'], 2) ?> Bs</div>
    </div>
  <?php endforeach; ?>
</body>

</html>