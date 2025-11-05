<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Producto - EcoAbirgos</title>
  <link rel="stylesheet" href="public/css/estilos.css">
</head>

<body>
  <?php
  include 'app/config/database.php';

  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if ($id <= 0) die('ID de producto no válido.');

  // Producto actual
  $sql = "SELECT id, titulo, imagen, color, talla, descripcion, precio, etiqueta, descuento, categoria, cantidad, vendedor
          FROM productos WHERE id = ?";
  $params = [$id];
  $result = sqlsrv_query($conn, $sql, $params);
  $p = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
  if (!$p) die('Producto no encontrado.');

  $etiqueta = strtolower(trim($p['etiqueta'] ?? ''));
  $descuento = (float)($p['descuento'] ?? 0);
  $precioOriginal = (float)$p['precio'];
  $precioFinal = $precioOriginal;
  if ($etiqueta === 'descuento' && $descuento > 0) {
      $precioFinal = $precioOriginal - ($precioOriginal * ($descuento / 100));
  }

  // Productos relacionados por categoría, excluyendo actual
  $sqlRelacionados = "SELECT TOP 4 id, titulo, imagen, etiqueta, descuento, precio 
                      FROM productos 
                      WHERE categoria = ? AND id <> ? AND cantidad > 0
                      ORDER BY id DESC";
  $paramsRelacionados = [$p['categoria'], $id];
  $resultRelacionados = sqlsrv_query($conn, $sqlRelacionados, $paramsRelacionados);
  $productosRelacionados = [];
  while ($row = sqlsrv_fetch_array($resultRelacionados, SQLSRV_FETCH_ASSOC)) {
      $productosRelacionados[] = $row;
  }
  ?>

  <?php include 'views/layouts/header.php'; ?>

  <h1 class="titulo-principal t-p ll">Estilo que conquista - EcoAbirgos</h1>

  <main class="contenido-producto">

    <!-- IMAGEN PRINCIPAL -->
    <div class="imagen-producto">
      <img class="img-producto"
           src="imagenes/<?php echo htmlspecialchars($p['imagen']); ?>"
           alt="<?php echo htmlspecialchars($p['titulo']); ?>">
    </div>

    <!-- DATOS DEL PRODUCTO -->
    <div class="datos-producto">
      <h2 class="nombre-producto"><?php echo htmlspecialchars($p['titulo']); ?></h2>

      <?php if ($etiqueta): ?>
        <span class="etiqueta-producto <?= htmlspecialchars($etiqueta); ?>">
          <?= ucfirst($etiqueta); ?><?= $etiqueta === 'descuento' ? ' - ' . $descuento . '%' : ''; ?>
        </span>
      <?php endif; ?>

      <p class="precio-producto">
        <strong>Precio:</strong>
        <?php if ($etiqueta === 'descuento' && $descuento > 0): ?>
          <span class="precio-original"><?= number_format($precioOriginal, 2); ?> Bs</span>
          <span class="precio-descuento"><?= number_format($precioFinal, 2); ?> Bs</span>
        <?php else: ?>
          <?= number_format($precioOriginal, 2); ?> Bs
        <?php endif; ?>
      </p>

      <!-- DESCRIPCIÓN EXTENDIDA -->
      <div class="descripcion-producto">
        <p class="texto-descripcion"><?php echo nl2br(htmlspecialchars($p['descripcion'])); ?></p>
        <p class="texto-extra">EcoAbirgos ofrece ropa ecológica y sostenible con estilo único, ideal para todas las edades y ocasiones. Materiales de calidad y cuidado con el medio ambiente.</p>
        <p class="boton-mas-vendido">Lo más vendido</p>
      </div>

      <!-- COLORES DISPONIBLES -->
      <!-- <div class="paleta-colores">
        <p class="etiqueta-color">Colores disponibles:</p>
        <div class="colores-disponibles">
          <span class="color-opcion" style="background-color:#FF0000;">Rojo</span>
          <span class="color-opcion" style="background-color:#00FF00;">Verde</span>
          <span class="color-opcion" style="background-color:#0000FF;">Azul</span>
          <span class="color-opcion" style="background-color:#FFFF00;">Amarillo</span>
        </div>
      </div> -->

      <!-- TALLAS DISPONIBLES -->
      <div class="tallas-disponibles">
        <p class="etiqueta-talla">Tallas disponibles:</p>
        <span class="talla-opcion">S</span>
        <span class="talla-opcion">M</span>
        <span class="talla-opcion">L</span>
        <span class="talla-opcion">XL</span>
      </div>

      <!-- FORMULARIO CARRITO -->
      <form action="carrito.php?action=add" method="POST" class="formulario-carrito">
        <input type="hidden" name="id" value="<?= (int)$p['id']; ?>">
        <input type="hidden" name="cantidad" value="1">
        <button type="submit" class="boton-anadir-carrito">Añadir a la cesta</button>
      </form>

      <p class="informacion-envio">Envío GRATIS en pedidos de 70 Bs o más y Click & Collect</p>
      <h3 class="titulo-disponibilidad">Disponibilidad en tienda</h3>

      <!-- VALORACIONES -->
      <div class="valoraciones-producto">
        <p class="etiqueta-valoraciones">Valoraciones:</p>
        <span class="estrella">★</span>
        <span class="estrella">★</span>
        <span class="estrella">★</span>
        <span class="estrella">★</span>
        <span class="estrella-vacia">☆</span>
        <p class="cantidad-valoraciones">(24 opiniones)</p>
      </div>

      <!-- BOTONES SOCIALES -->
      <div class="botones-sociales">
        <p class="etiqueta-compartir">Compartir:</p>
        <button class="btn-facebook">Facebook</button>
        <button class="btn-twitter">Twitter</button>
        <button class="btn-whatsapp">WhatsApp</button>
      </div>

      <!-- PRODUCTOS RELACIONADOS -->
      <?php if (!empty($productosRelacionados)): ?>
      <section class="productos-relacionados">
        <h3 class="titulo-relacionados">Productos relacionados</h3>
        <div class="lista-productos-relacionados">
          <?php foreach ($productosRelacionados as $rel): 
              $precioRel = (float)$rel['precio'];
              $etiquetaRel = strtolower(trim($rel['etiqueta'] ?? ''));
              $descuentoRel = (float)($rel['descuento'] ?? 0);
              $precioFinalRel = $precioRel;
              if ($etiquetaRel === 'descuento' && $descuentoRel > 0) {
                  $precioFinalRel = $precioRel - ($precioRel * ($descuentoRel / 100));
              }
          ?>
          <div class="producto-relacionado">
            <a href="anuncio.php?id=<?= (int)$rel['id']; ?>">
              <img src="imagenes/<?= htmlspecialchars($rel['imagen']); ?>" alt="<?= htmlspecialchars($rel['titulo']); ?>">
              <p><?= htmlspecialchars($rel['titulo']); ?></p>
              <p class="precio-relacionado">
                <?php if ($etiquetaRel === 'descuento' && $descuentoRel > 0): ?>
                  <span class="precio-original"><?= number_format($precioRel, 2); ?> Bs</span>
                  <span class="precio-descuento"><?= number_format($precioFinalRel, 2); ?> Bs</span>
                <?php else: ?>
                  <?= number_format($precioRel, 2); ?> Bs
                <?php endif; ?>
              </p>
            </a>
          </div>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>
    </div>
  </main>

  <?php include 'views/layouts/footer.php'; ?>
</body>

</html>
