<?php
require_once __DIR__ . '/app/config/database.php';

// 1 = hombre, 2 = mujer
$generoParam = isset($_GET['genero']) ? (int)$_GET['genero'] : 1;
$generoTexto = ($generoParam === 1) ? 'hombre' : 'mujer';

// Filtros
$buscar = $_GET['buscar'] ?? '';
$categoriaFiltro = $_GET['categoria'] ?? '';
$precioMin = isset($_GET['precio_min']) ? (float)$_GET['precio_min'] : 0;
$precioMax = isset($_GET['precio_max']) ? (float)$_GET['precio_max'] : 1000000;
$etiquetaFiltro = $_GET['etiqueta'] ?? '';
$colorFiltro = $_GET['color'] ?? '';
$tallaFiltro = $_GET['talla'] ?? '';

// Consulta dinámica
$sql = "SELECT id, titulo, descripcion, imagen, precio, color, categoria, etiqueta, talla, descuento
        FROM productos
        WHERE genero = ? AND cantidad > 0";

$params = [$generoTexto];
if ($buscar) {
    $sql .= " AND titulo LIKE ?";
    $params[] = "%$buscar%";
}
if ($categoriaFiltro) {
    $sql .= " AND categoria = ?";
    $params[] = $categoriaFiltro;
}
if ($etiquetaFiltro) {
    $sql .= " AND etiqueta = ?";
    $params[] = $etiquetaFiltro;
}
if ($colorFiltro) {
    $sql .= " AND color LIKE ?";
    $params[] = "%$colorFiltro%";
}
if ($tallaFiltro) {
    $sql .= " AND talla = ?";
    $params[] = $tallaFiltro;
}
$sql .= " AND precio BETWEEN ? AND ?";
$params[] = $precioMin;
$params[] = $precioMax;
$sql .= " ORDER BY id DESC";

$result = sqlsrv_query($conn, $sql, $params);
if ($result === false) {
    die('Error en la consulta: ' . print_r(sqlsrv_errors(), true));
}

// Agrupar por categoría
$productosPorCategoria = [];
while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    $productosPorCategoria[$row['categoria']][] = $row;
}
?>

<!-- Botón Filtros -->
<button id="btnFiltros">Filtros &#9660;</button>

<!-- Panel de filtros oculto por defecto -->
<div id="panelFiltros" style="display:none; margin-top:10px;">
    <input type="text" id="buscador" placeholder="Buscar productos..." value="<?= htmlspecialchars($buscar) ?>">

    <select id="categoriaFiltro">
        <option value="">Todas las categorías</option>
        <option value="camisa" <?= $categoriaFiltro == 'camisa' ? 'selected' : '' ?>>Camisa</option>
        <option value="pantalon" <?= $categoriaFiltro == 'pantalon' ? 'selected' : '' ?>>Pantalón</option>
        <option value="abrigo" <?= $categoriaFiltro == 'abrigo' ? 'selected' : '' ?>>Abrigo</option>
        <option value="falda" <?= $categoriaFiltro == 'falda' ? 'selected' : '' ?>>Falda</option>
        <option value="vestido" <?= $categoriaFiltro == 'vestido' ? 'selected' : '' ?>>Vestido</option>
    </select>

    <input type="number" id="precioMin" placeholder="Precio mínimo" value="<?= $precioMin ?>">
    <input type="number" id="precioMax" placeholder="Precio máximo" value="<?= $precioMax ?>">

    <select id="etiquetaFiltro">
        <option value="">Todas las etiquetas</option>
        <option value="Normal" <?= $etiquetaFiltro == 'Normal' ? 'selected' : '' ?>>Normal</option>
        <option value="Descuento" <?= $etiquetaFiltro == 'Descuento' ? 'selected' : '' ?>>Descuento</option>
        <option value="Nuevo" <?= $etiquetaFiltro == 'Nuevo' ? 'selected' : '' ?>>Nuevo</option>
    </select>

    <input type="text" id="colorFiltro" placeholder="Color" value="<?= htmlspecialchars($colorFiltro) ?>">

    <select id="tallaFiltro">
        <option value="">Todas las tallas</option>
        <option value="S" <?= $tallaFiltro == 'S' ? 'selected' : '' ?>>S</option>
        <option value="M" <?= $tallaFiltro == 'M' ? 'selected' : '' ?>>M</option>
        <option value="L" <?= $tallaFiltro == 'L' ? 'selected' : '' ?>>L</option>
        <option value="XL" <?= $tallaFiltro == 'XL' ? 'selected' : '' ?>>XL</option>
    </select>

    <button id="aplicarFiltros">Aplicar</button>
    <button id="deshacerFiltros">Deshacer</button>
</div>

<!-- Productos por categoría -->
<?php foreach (['camisa', 'pantalon', 'abrigo', 'falda', 'vestido'] as $categoria): ?>
    <?php if (!empty($productosPorCategoria[$categoria])): ?>
        <h2 class="titulo-categoria t-p"><?= ucfirst($categoria); ?></h2>
        <section class="grid-anuncios" data-categoria="<?= $categoria ?>">
            <?php foreach ($productosPorCategoria[$categoria] as $row):
                $precioOriginal = (float)$row['precio'];
                $etiqueta = strtolower(trim($row['etiqueta'] ?? ''));
                $descuento = (float)($row['descuento'] ?? 0);
                $precioFinal = $precioOriginal;
                if ($etiqueta === 'descuento' && $descuento > 0) {
                    $precioFinal = $precioOriginal - ($precioOriginal * ($descuento / 100));
                }
            ?>
                <article class="card-anuncio" data-titulo="<?= strtolower($row['titulo']) ?>" data-precio="<?= $precioOriginal ?>" data-etiqueta="<?= $row['etiqueta'] ?>">
                    <a href="anuncio.php?id=<?= (int)$row['id']; ?>" class="card-a">
                        <div class="img-container">
                            <img src="imagenes/<?= htmlspecialchars($row['imagen']); ?>" alt="<?= htmlspecialchars($row['titulo']); ?>">
                            <?php if ($etiqueta && strtolower($etiqueta) !== 'normal'): ?>
                                <span class="etiqueta <?= htmlspecialchars($etiqueta); ?>">
                                    <?= ucfirst($etiqueta); ?><?= strtolower($etiqueta) === 'descuento' ? ' - ' . $descuento . '%' : ''; ?>
                                </span>
                            <?php endif; ?>

                        </div>
                        <div class="info">
                            <h3><?= htmlspecialchars($row['titulo']); ?></h3>
                            <p class="descripcion"><?= htmlspecialchars($row['descripcion']); ?></p>
                            <p class="color">Color: <span><?= htmlspecialchars($row['color']); ?></span></p>
                            <p class="precio">
                                <?php if ($etiqueta === 'descuento' && $descuento > 0): ?>
                                    <span class="precio-original"><?= number_format($precioOriginal, 2); ?> Bs</span>
                                    <span class="precio-descuento"><?= number_format($precioFinal, 2); ?> Bs</span>
                                <?php else: ?>
                                    <?= number_format($precioOriginal, 2); ?> Bs
                                <?php endif; ?>
                            </p>
                            <button class="add-cart" type="button" data-id="<?= (int)$row['id']; ?>" data-cantidad="1">Añadir al carrito</button>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
<?php endforeach; ?>

<script>
    // Mostrar/Ocultar panel de filtros
    document.getElementById('btnFiltros').addEventListener('click', () => {
        const panel = document.getElementById('panelFiltros');
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    });

    // Aplicar filtros
    document.getElementById('aplicarFiltros').addEventListener('click', () => {
        const params = new URLSearchParams();
        params.set('genero', <?= $generoParam ?>);
        params.set('buscar', document.getElementById('buscador').value);
        params.set('categoria', document.getElementById('categoriaFiltro').value);
        params.set('precio_min', document.getElementById('precioMin').value);
        params.set('precio_max', document.getElementById('precioMax').value);
        params.set('etiqueta', document.getElementById('etiquetaFiltro').value);
        params.set('color', document.getElementById('colorFiltro').value);
        params.set('talla', document.getElementById('tallaFiltro').value);
        window.location.href = '?' + params.toString();
    });

    // Deshacer filtros
    document.getElementById('deshacerFiltros').addEventListener('click', () => {
        window.location.href = '?genero=<?= $generoParam ?>';
    });

    // Añadir al carrito
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.add-cart');
        if (!btn) return;
        e.preventDefault();
        const id = Number(btn.dataset.id || 0);
        const cantidad = Number(btn.dataset.cantidad || 1);
        if (!id || cantidad < 1) return;
        const fd = new FormData();
        fd.append('action', 'add');
        fd.append('id', id);
        fd.append('cantidad', cantidad);
        fetch('carrito.php?action=add', {
                method: 'POST',
                body: fd,
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(r => r.ok ? r.json() : null)
            .then(json => {
                if (json && json.count !== undefined) {
                    const badge = document.getElementById('cart-count');
                    if (badge) badge.textContent = json.count;
                }
            })
            .catch(() => {});
    });
</script>