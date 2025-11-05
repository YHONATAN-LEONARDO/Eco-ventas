<?php
// comprar.php
require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/database.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ---------- Autenticación ---------- */
if (!isset($_SESSION['login']) || !$_SESSION['login']) {
  header('Location: /views/usuarios/login.php');
  exit;
}

/* ---------- Carrito ---------- */
$cart = $_SESSION['cart'] ?? [];
if (!$cart || count($cart) === 0) {
  header('Location: carrito.php?empty=1');
  exit;
}

/* ---------- Cliente ---------- */
$usuario_correo = $_SESSION['correo'] ?? null;
$usuario_nombre = $_SESSION['nombre'] ?? 'Cliente';

/* Buscar cliente en la tabla clientes */
$cliente_id = null;
if ($usuario_correo) {
  $stmt = sqlsrv_query($conn, "SELECT id FROM clientes WHERE correo = ?", [$usuario_correo]);
  if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
    $cliente_id = (int)$row['id'];
  }
}

/* Si no se encuentra, usar Cliente General (id=1) */
if (empty($cliente_id)) {
  $cliente_id = 1;
}

/* ---------- Métodos de pago ---------- */
$metodos = [];
$mp_q = sqlsrv_query($conn, "SELECT id, nombre FROM metodos_pago ORDER BY id");
while ($mp_q && ($r = sqlsrv_fetch_array($mp_q, SQLSRV_FETCH_ASSOC))) $metodos[] = $r;

/* ---------- Valores del formulario ---------- */
$metodo_pago_post      = $_POST['metodo_pago']      ?? '';
$referencia_post       = trim($_POST['referencia']  ?? '');
$nit_post              = trim($_POST['nit']         ?? '');
$razon_post            = trim($_POST['razon_social'] ?? '');
$obs_post              = trim($_POST['observacion'] ?? '');
$lugar_post            = trim($_POST['lugar_entrega'] ?? '');
$requiere_factura_post = isset($_POST['requiere_factura']) && $_POST['requiere_factura'] === '1';

$error = '';

/* ---------- Helpers ---------- */
function normalizar($txt)
{
  return strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $txt ?? ''));
}
function precio_final_item(array $it): float
{
  $p = (float)($it['precio'] ?? 0);
  $d = (float)($it['descuento'] ?? 0);
  if ($d > 0) $p -= $p * ($d / 100);
  return max($p, 0);
}

/* ---------- POST: Procesar compra ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    /* Validar método de pago */
    $metodo_pago = (int)($metodo_pago_post ?: 0);
    $mp_stmt = sqlsrv_query($conn, "SELECT nombre FROM metodos_pago WHERE id = ?", [$metodo_pago]);
    if (!$mp_stmt) throw new Exception('No se pudo verificar el método de pago.');
    $mp_row = sqlsrv_fetch_array($mp_stmt, SQLSRV_FETCH_ASSOC);
    if (!$mp_row) throw new Exception('Método de pago inválido.');
    $mp_nombre = $mp_row['nombre'] ?? '';
    $mp_lc     = normalizar($mp_nombre);

    /* Determinar si referencia es obligatoria */
    $requiere_referencia = (str_contains($mp_lc, 'transferencia') || str_contains($mp_lc, 'tarjeta') || str_contains($mp_lc, 'movil'));

    /* Validaciones */
    $errores = [];
    if ($requiere_referencia && $referencia_post === '') $errores[] = "Debes ingresar la referencia del pago para '{$mp_nombre}'.";
    if ($requiere_factura_post && ($nit_post === '' || $razon_post === '')) $errores[] = "Para facturar, NIT y Razón Social son obligatorios.";
    if (!empty($errores)) throw new Exception(implode(' ', $errores));

    /* Total con descuentos */
    $total = 0.0;
    foreach ($cart as $it) $total += precio_final_item($it) * (int)$it['cantidad'];

    /* Transacción */
    if (!sqlsrv_begin_transaction($conn)) throw new Exception('No se pudo iniciar la transacción.');

    /* Insert venta */
    $venta_sql = "INSERT INTO ventas (fecha, total, estado, cliente_id, vendedor)
                  OUTPUT INSERTED.id VALUES (SYSDATETIME(), ?, 'pagado', ?, NULL)";
    $venta_stmt = sqlsrv_query($conn, $venta_sql, [$total, $cliente_id]);
    if (!$venta_stmt) throw new Exception(print_r(sqlsrv_errors(), true));
    $venta_row = sqlsrv_fetch_array($venta_stmt, SQLSRV_FETCH_ASSOC);
    $venta_id  = (int)$venta_row['id'];

    /* Insert detalle */
    foreach ($cart as $it) {
      $pid          = (int)$it['id'];
      $cant         = max(1, (int)$it['cantidad']);
      $precio_final = precio_final_item($it);
      $desc         = (float)($it['descuento'] ?? 0);

      $sql_det = "INSERT INTO ventas_detalle (venta_id, producto_id, cantidad, precio_unitario, descuento)
                  VALUES (?, ?, ?, ?, ?)";
      if (!sqlsrv_query($conn, $sql_det, [$venta_id, $pid, $cant, $precio_final, $desc])) {
        throw new Exception('Error al registrar detalle: ' . print_r(sqlsrv_errors(), true));
      }

      // Actualizar stock
      @sqlsrv_query($conn, "UPDATE productos SET cantidad = cantidad - ? WHERE id = ? AND cantidad >= ?", [$cant, $pid, $cant]);
    }

    /* Insert transacción de pago */
    $trans_sql = "INSERT INTO transacciones_pago (venta_id, metodo_pago_id, cuenta_id, referencia, monto, estado)
                  VALUES (?, ?, NULL, ?, ?, 'completado')";
    if (!sqlsrv_query($conn, $trans_sql, [$venta_id, $metodo_pago, $referencia_post, $total])) {
      throw new Exception(print_r(sqlsrv_errors(), true));
    }

    /* Insert factura */
    $factura_num = 'F-' . str_pad((string)$venta_id, 6, '0', STR_PAD_LEFT);
    $nit_db      = $requiere_factura_post ? $nit_post   : null;
    $razon_db    = $requiere_factura_post ? $razon_post : null;
    $sql_factura = "INSERT INTO facturas (venta_id, numero, nit_cliente, razon_social, total, observacion, lugar_entrega, nombre_cliente)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    if (!sqlsrv_query($conn, $sql_factura, [$venta_id, $factura_num, $nit_db, $razon_db, $total, $obs_post, $lugar_post, $usuario_nombre])) {
      throw new Exception('Error al registrar factura: ' . print_r(sqlsrv_errors(), true));
    }

    sqlsrv_commit($conn);
    $_SESSION['cart'] = [];
    header('Location: exito.php?factura=' . urlencode($factura_num));
    exit;
  } catch (Exception $e) {
    @sqlsrv_rollback($conn);
    $error = "Error al procesar la compra: " . $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <title>Confirmar compra</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="public/css/estilos.css">
  <link rel="stylesheet" href="public/css/normalize.css">

</head>

<body>
  <?php include __DIR__ . '/views/layouts/header.php'; ?>

  <div class="containerl ll">
    <h1>Confirmar compra</h1>

    <?php if (!empty($error)): ?>
      <div class="nota" style="background:#fdecea;color:#611a15;border:1px solid #f5c6cb;border-radius:8px;padding:10px;margin-bottom:14px;">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <p class="nota"><strong>Cliente:</strong> <?= htmlspecialchars($usuario_nombre) ?></p>

    <table>
      <thead>
        <tr>
          <th style="text-align:left;">Producto</th>
          <th style="text-align:right;">Cantidad</th>
          <th style="text-align:right;">Precio</th>
          <th style="text-align:right;">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php $total = 0.0;
        foreach ($cart as $c):
          $titulo   = (string)($c['titulo'] ?? '');
          $imagen   = (string)($c['imagen'] ?? '');
          $precio   = (float) ($c['precio'] ?? 0);
          $desc     = (float) ($c['descuento'] ?? 0);
          $etq      = (string)($c['etiqueta'] ?? ''); // 'nuevo', 'oferta', 'descuento', etc.
          $cant     = max(1, (int)($c['cantidad'] ?? 1));
          $pFinal   = precio_final_item($c);
          $sub      = $pFinal * $cant;
          $total   += $sub;
        ?>
          <tr>
            <td>
              <div class="prod-info">
                <?php if ($imagen): ?><img class="prod-img" src="imagenes/<?= htmlspecialchars($imagen) ?>" alt="<?= htmlspecialchars($titulo) ?>"><?php endif; ?>
                <div>
                  <?= htmlspecialchars($titulo) ?>
                  <?php if ($etq !== ''): ?>
                    <span class="badge-etq"><?= htmlspecialchars(ucfirst($etq)) ?></span>
                  <?php endif; ?>
                  <?php if ($desc > 0): ?>
                    <span class="badge-desc">-<?= (int)$desc ?>%</span>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td style="text-align:right;"><?= $cant ?></td>
            <td style="text-align:right;">
              <?php if ($desc > 0): ?>
                <span class="precio-original"><?= number_format($precio, 2) ?> Bs</span>
                <span class="precio-descuento"><?= number_format($pFinal, 2) ?> Bs</span>
              <?php else: ?>
                <?= number_format($precio, 2) ?> Bs
              <?php endif; ?>
            </td>
            <td style="text-align:right;"><?= number_format($sub, 2) ?> Bs</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="total">Total: <?= number_format($total, 2) ?> Bs</div>

    <form method="POST" id="form-pago" novalidate>
      <div class="form-group">
        <label for="metodo_pago">Método de pago</label>
        <select name="metodo_pago" id="metodo_pago" required>
          <option value="">Seleccione...</option>
          <?php foreach ($metodos as $m):
            $sel = ($metodo_pago_post !== '' && (int)$metodo_pago_post === (int)$m['id']) ? 'selected' : ''; ?>
            <option value="<?= (int)$m['id'] ?>" <?= $sel ?>><?= htmlspecialchars($m['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group" id="grupo-referencia">
        <label for="referencia" id="label-referencia">Referencia de pago</label>
        <input type="text" name="referencia" id="referencia" placeholder="Ej: N° de operación o recibo" value="<?= htmlspecialchars($referencia_post) ?>">
        <small class="hint" id="hint-referencia">Obligatoria para Transferencia, Tarjeta y Pago móvil.</small>
      </div>

      <div class="form-group">
        <label><input type="checkbox" name="requiere_factura" id="requiere_factura" value="1" <?= $requiere_factura_post ? 'checked' : '' ?>> ¿Requieres factura?</label>
        <small class="hint">Si marcas esta opción, debes llenar NIT y Razón Social.</small>
      </div>

      <div class="form-group hidden" id="bloque-factura">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div>
            <label for="nit">NIT</label>
            <input type="text" name="nit" id="nit" placeholder="Ej: 123456789" value="<?= htmlspecialchars($nit_post) ?>">
          </div>
          <div>
            <label for="razon_social">Razón Social</label>
            <input type="text" name="razon_social" id="razon_social" placeholder="Ej: Juan Pérez SRL" value="<?= htmlspecialchars($razon_post) ?>">
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="lugar_entrega">Lugar de entrega</label>
        <input type="text" name="lugar_entrega" id="lugar_entrega" placeholder="Ej: Calle 12 de Obrajes N° 123" value="<?= htmlspecialchars($lugar_post) ?>">
        <small class="hint">Si tu tabla de facturas no tiene este campo, igual procesaremos la compra.</small>
      </div>

      <div class="form-group">
        <label for="observacion">Observaciones</label>
        <textarea name="observacion" id="observacion" rows="3" placeholder="Ej: Entrega rápida, envolver para regalo..."><?= htmlspecialchars($obs_post) ?></textarea>
      </div>

      <div class="botones">
        <button type="submit" class="btn btn-primary">Finalizar compra</button>
        <a href="carrito.php" class="btn btn-secondary">Cancelar</a>
      </div>
    </form>
  </div>

  <?php include __DIR__ . '/views/layouts/footer.php'; ?>

  <script>
    (function() {
      const $metodo = document.getElementById('metodo_pago');
      const $grupoRef = document.getElementById('grupo-referencia');
      const $ref = document.getElementById('referencia');
      const $hintRef = document.getElementById('hint-referencia');

      const $reqFac = document.getElementById('requiere_factura');
      const $bloqueFac = document.getElementById('bloque-factura');
      const $nit = document.getElementById('nit');
      const $razon = document.getElementById('razon_social');

      function norm(t) {
        return (t || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
      }

      function actualizarReferencia() {
        const opt = $metodo.options[$metodo.selectedIndex];
        const nombre = norm(opt ? opt.textContent : '');

        let requiere = false;
        let placeholder = 'Ej: N° de operación o recibo';
        let hint = 'Obligatoria para Transferencia, Tarjeta y Pago móvil.';

        if (nombre.includes('transferencia')) {
          requiere = true;
          placeholder = 'Ej: N° de operación / comprobante de transferencia';
          hint = 'Obligatoria para Transferencia bancaria.';
        } else if (nombre.includes('tarjeta')) {
          requiere = true;
          placeholder = 'Ej: N° de voucher o últimos 4 dígitos';
          hint = 'Obligatoria para pago con Tarjeta.';
        } else if (nombre.includes('pago movil') || nombre.includes('pagomovil') || nombre.includes('movil')) {
          requiere = true;
          placeholder = 'Ej: N° de transacción (QR / app)';
          hint = 'Obligatoria para Pago móvil.';
        } else if (nombre.includes('efectivo')) {
          requiere = false;
          placeholder = 'No requerido para efectivo';
          hint = 'No necesario para Efectivo.';
        }

        $ref.placeholder = placeholder;
        $hintRef.textContent = hint;
        $ref.required = requiere;
        $grupoRef.classList.toggle('hidden', !requiere);
      }

      function actualizarFactura() {
        const activo = $reqFac.checked;
        $bloqueFac.classList.toggle('hidden', !activo);
        $nit.required = activo;
        $razon.required = activo;
      }

      // init
      actualizarReferencia();
      actualizarFactura();

      $metodo.addEventListener('change', actualizarReferencia);
      $reqFac.addEventListener('change', actualizarFactura);
    })();
  </script>
</body>

</html>