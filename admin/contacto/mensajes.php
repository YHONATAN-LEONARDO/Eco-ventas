<?php
// admin/contactos/lista.php
include '../../app/config/session.php';
include '../../app/config/database.php';

// Consulta de contactos (ajusta si tu esquema difiere)
$sql = "
  SELECT TOP 100
    id,
    creado_en,
    nombre,
    correo,
    asunto,
    mensaje,
    ip,
    user_agent
  FROM dbo.contactos
  ORDER BY id DESC
";
$resultado = sqlsrv_query($conn, $sql);
if ($resultado === false) {
  die('Error al consultar contactos: ' . print_r(sqlsrv_errors(), true));
}

function sv_fmt_fecha($f)
{
  // SQLSRV puede devolver DateTime o array con 'date'
  if ($f instanceof DateTime) return $f->format('Y-m-d H:i');
  if (is_array($f) && isset($f['date'])) return date('Y-m-d H:i', strtotime($f['date']));
  return htmlspecialchars((string)$f);
}
function sv_trunc($txt, $len = 120)
{
  $txt = (string)$txt;
  return mb_strlen($txt, 'UTF-8') > $len ? mb_substr($txt, 0, $len, 'UTF-8') . '…' : $txt;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Contactos | Listado</title>
  <link rel="stylesheet" href="/admin/panel.css">
</head>
<style>
  /* ----------------- BASE ----------------- */
  body {
    font-family: Arial, sans-serif;
    background: #f0f4f8;
    margin: 0;
    padding: 0;
    color: #0f172a;
  }

  h1 {
    text-align: center;
    padding: 1.5rem 0;
    font-size: 2rem;
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

  /* ----------------- TABLA ----------------- */
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
    text-align: left;
    border-bottom: 1px solid #cbd5e1;
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

  /* ----------------- BADGES DE ESTADO ----------------- */
  .estado {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 0.85rem;
    color: #fff;
    text-transform: capitalize;
    font-weight: bold;
  }

  .estado-pagado {
    background: #2563eb;
  }

  /* azul fuerte */
  .estado-pendiente {
    background: #f59e0b;
  }

  /* naranja */
  .estado-enviado {
    background: #0ea5e9;
  }

  /* celeste */
  .estado-cancelado {
    background: #dc2626;
  }

  /* rojo */

  /* ----------------- RESPONSIVE ----------------- */
  @media (max-width: 768px) {

    table,
    thead,
    tbody,
    th,
    td,
    tr {
      display: block;
    }

    thead tr {
      display: none;
    }

    tr {
      margin-bottom: 1rem;
      border-bottom: 2px solid #cbd5e1;
    }

    td {
      text-align: right;
      padding-left: 50%;
      position: relative;
    }

    td::before {
      content: attr(data-label);
      position: absolute;
      left: 15px;
      top: 12px;
      font-weight: bold;
      text-transform: uppercase;
      font-size: 0.75rem;
      color: #1e3a8a;
    }
  }
</style>

<body class="sv-page sv-page--contactos">

  <header class="header sv-header">
    <h1 class="titulo sv-title">Listado de Contactos</h1>
  </header>

  <!-- Botón Volver -->
  <div class="sv-actions">
    <a href="../index.php" class="sv-btn sv-btn--back">Volver</a>
  </div>

  <main class="sv-container">
    <div class="sv-table-wrap">
      <table class="sv-table">
        <thead class="sv-table__head">
          <tr class="sv-table__head-row">
            <th class="sv-th sv-th--id">ID</th>
            <th class="sv-th sv-th--fecha">Fecha</th>
            <th class="sv-th sv-th--cliente">Nombre</th>
            <th class="sv-th sv-th--cliente">Correo</th>
            <th class="sv-th sv-th--estado">Asunto</th>
            <th class="sv-th sv-th--total">Mensaje</th>
          </tr>
        </thead>
        <tbody class="sv-table__body">
          <?php while ($row = sqlsrv_fetch_array($resultado, SQLSRV_FETCH_ASSOC)) { ?>
            <tr class="sv-row">
              <td class="sv-td sv-td--id"><?php echo (int)$row['id']; ?></td>
              <td class="sv-td sv-td--fecha"><?php echo sv_fmt_fecha($row['creado_en']); ?></td>
              <td class="sv-td sv-td--cliente"><?php echo htmlspecialchars($row['nombre'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="sv-td sv-td--cliente"><?php echo htmlspecialchars($row['correo'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="sv-td sv-td--estado"><?php echo htmlspecialchars($row['asunto'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="sv-td sv-td--total">
                <?php echo nl2br(htmlspecialchars(sv_trunc($row['mensaje'] ?? ''), ENT_QUOTES, 'UTF-8')); ?>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </main>
</body>

</html>