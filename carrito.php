<?php
require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/database.php';

function is_ajax(): bool
{
  if (!empty($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) return true;
  if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) return true;
  return false;
}
function cart_init(): void
{
  if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
}
function cart_count(): int
{
  cart_init();
  $n = 0;
  foreach ($_SESSION['cart'] as $it) $n += (int)$it['cantidad'];
  return $n;
}
function cart_total(): float
{
  cart_init();
  $t = 0.0;
  foreach ($_SESSION['cart'] as $it) {
    $precio = (float)$it['precio'];
    if (!empty($it['descuento']) && $it['descuento'] > 0) {
      $precio -= $precio * ($it['descuento'] / 100);
    }
    $t += $precio * (int)$it['cantidad'];
  }
  return $t;
}
function respond_json(array $arr): never
{
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode($arr);
  exit;
}
function require_auth_or_json(): void
{
  $auth = $_SESSION['login'] ?? false;
  if ($auth) return;
  if (is_ajax()) {
    http_response_code(401);
    respond_json(['ok' => false, 'code' => 'AUTH', 'message' => 'Debes iniciar sesión.']);
  }
  header('Location: /views/usuarios/login.php');
  exit;
}

cart_init();

$action = $_POST['action'] ?? $_GET['action'] ?? 'view';

// ------- ADD -------
if ($action === 'add') {
  require_auth_or_json();

  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $cantidad = isset($_POST['cantidad']) ? max(1, (int)$_POST['cantidad']) : 1;

  if ($id <= 0) {
    if (is_ajax()) respond_json(['ok' => false, 'message' => 'ID inválido', 'count' => cart_count()]);
    header('Location: carrito.php');
    exit;
  }

  // Trae también el descuento
  $sql = "SELECT id, titulo, precio, imagen, descuento FROM productos WHERE id = ?";
  $stmt = sqlsrv_query($conn, $sql, [$id]);
  $prod = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;

  if (!$prod) {
    if (is_ajax()) respond_json(['ok' => false, 'message' => 'Producto no encontrado', 'count' => cart_count()]);
    header('Location: carrito.php?error=notfound');
    exit;
  }

  $pid = (int)$prod['id'];
  if (isset($_SESSION['cart'][$pid])) {
    $_SESSION['cart'][$pid]['cantidad'] += $cantidad;
  } else {
    $_SESSION['cart'][$pid] = [
      'id'        => $pid,
      'titulo'    => (string)$prod['titulo'],
      'precio'    => (float)$prod['precio'],
      'imagen'    => (string)$prod['imagen'],
      'descuento' => (float)($prod['descuento'] ?? 0),
      'cantidad'  => $cantidad
    ];
  }

  if (is_ajax()) respond_json(['ok' => true, 'count' => cart_count()]);
  header('Location: carrito.php?ok=1');
  exit;
}

// ------- UPDATE -------
if ($action === 'update') {
  require_auth_or_json();
  $cantidades = $_POST['cantidades'] ?? [];
  foreach ($cantidades as $pid => $qty) {
    $pid = (int)$pid;
    $qty = max(0, (int)$qty);
    if ($qty === 0) unset($_SESSION['cart'][$pid]);
    else if (isset($_SESSION['cart'][$pid])) $_SESSION['cart'][$pid]['cantidad'] = $qty;
  }
  if (is_ajax()) respond_json(['ok' => true, 'count' => cart_count()]);
  header('Location: carrito.php?ok=2');
  exit;
}

// ------- REMOVE -------
if ($action === 'remove') {
  require_auth_or_json();
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  if ($id > 0 && isset($_SESSION['cart'][$id])) unset($_SESSION['cart'][$id]);
  if (is_ajax()) respond_json(['ok' => true, 'count' => cart_count()]);
  header('Location: carrito.php');
  exit;
}

// ------- CLEAR -------
if ($action === 'clear') {
  require_auth_or_json();
  $_SESSION['cart'] = [];
  if (is_ajax()) respond_json(['ok' => true, 'count' => 0]);
  header('Location: carrito.php');
  exit;
}

// ------- VIEW -------
require_auth_or_json();
$cart = $_SESSION['cart'];
$cartEmpty = (count($cart) === 0);

?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Carrito</title>
  <link rel="stylesheet" href="/public/css/estilos.css">
  <link rel="stylesheet" href="/public/css/celular.css">
  <link rel="stylesheet" href="/public/css/normalize.css">

</head>

<body>
  <?php include "views/layouts/header.php"; ?>

  <main class="carrito-wrap ll">
    <div class="carrito-nav"><a href="/" class="btn-ghost">⮌ Seguir comprando</a></div>
    <h1 class="carrito-title">Carrito de Compras</h1>

    <?php if ($cartEmpty): ?>
      <div class="carrito-empty">Tu carrito está vacío.</div>
    <?php else: ?>
      <form method="POST" action="carrito.php">
        <input type="hidden" name="action" value="update">
        <table class="cart-table">
          <thead>
            <tr>
              <th>Producto</th>
              <th>Precio</th>
              <th>Cantidad</th>
              <th>Subtotal</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cart as $item):
              $pid = (int)$item['id'];
              $titulo = htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8');
              $precioBase = (float)$item['precio'];
              $descuento = (float)($item['descuento'] ?? 0);
              $cantidad = (int)$item['cantidad'];
              $imagen = htmlspecialchars($item['imagen'], ENT_QUOTES, 'UTF-8');
              $precioFinal = $descuento > 0 ? $precioBase - ($precioBase * ($descuento / 100)) : $precioBase;
              $subtotal = $precioFinal * $cantidad;
            ?>
              <tr>
                <td>
                  <div class="cart-item">
                    <img class="cart-img" src="imagenes/<?php echo $imagen; ?>" alt="<?php echo $titulo; ?>">
                    <div>
                      <div><?php echo $titulo; ?>
                        <?php if ($descuento > 0): ?>
                          <span class="badge-descuento">-<?php echo $descuento; ?>%</span>
                        <?php endif; ?>
                      </div>
                      <div class="cart-actions">
                        <form method="POST" action="carrito.php" onsubmit="return confirm('¿Eliminar este producto?');">
                          <input type="hidden" name="action" value="remove">
                          <input type="hidden" name="id" value="<?php echo $pid; ?>">
                          <button class="btn btn-danger" type="submit">Eliminar</button>
                        </form>
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <?php if ($descuento > 0): ?>
                    <span class="precio-original"><?php echo number_format($precioBase, 2); ?> Bs</span>
                    <span class="precio-descuento"><?php echo number_format($precioFinal, 2); ?> Bs</span>
                  <?php else: ?>
                    <?php echo number_format($precioBase, 2); ?> Bs
                  <?php endif; ?>
                </td>
                <td><input class="qty-input" type="number" min="0" name="cantidades[<?php echo $pid; ?>]" value="<?php echo $cantidad; ?>"></td>
                <td><?php echo number_format($subtotal, 2); ?> Bs</td>
                <td></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="totales">
          <div><strong>Total:</strong></div>
          <div><strong><?php echo number_format(cart_total(), 2); ?> Bs</strong></div>
        </div>

        <div class="cart-cta">
          <form method="POST" action="carrito.php" onsubmit="return confirm('¿Vaciar carrito?');">
            <input type="hidden" name="action" value="clear">
            <button class="btn btn-danger pio" type="submit">Vaciar</button>
          </form>
          <a class="btn btn-primary pio" href="comprar.php">Proceder</a>
        </div>
      </form>
    <?php endif; ?>
  </main>
</body>

</html>
<?php
if (file_exists(__DIR__ . '/views/layouts/footer.php')) include __DIR__ . '/views/layouts/footer.php';
