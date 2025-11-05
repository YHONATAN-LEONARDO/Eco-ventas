<?php 
include '../../app/config/session.php';
include '../../app/config/database.php'; 

// Obtener ID del producto a actualizar
$id = $_GET['id'] ?? null;
if (!$id) {
    die("Error: No se proporcionó un ID válido.");
}

// Consultar producto actual
$sqlProducto = "SELECT * FROM productos WHERE id = ?";
$paramsProducto = [$id];
$stmtProducto = sqlsrv_query($conn, $sqlProducto, $paramsProducto);
$producto = sqlsrv_fetch_array($stmtProducto, SQLSRV_FETCH_ASSOC);

if (!$producto) {
    die("Error: Producto no encontrado.");
}

// Consultar vendedores
$consultaVendedor = "SELECT * FROM vendedor";
$resultado = sqlsrv_query($conn, $consultaVendedor);

// Variables iniciales con los datos actuales
$titulo       = $producto['titulo'];
$precio       = $producto['precio'];
$descripcion  = $producto['descripcion'];
$cantidad     = $producto['cantidad'];
$categoria    = $producto['categoria'];
$talla        = $producto['talla'];
$genero       = $producto['genero'];
$color        = $producto['color'];
$vendedor     = $producto['vendedor'];
$imagenActual = $producto['imagen'];
$etiqueta     = $producto['etiqueta'] ?? 'Normal';
$descuento    = $producto['descuento'] ?? 0;

$errores = []; 

// Si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo      = $_POST['titulo'];
    $precio      = $_POST['precio'];
    $descripcion = $_POST['descripcion'];
    $cantidad    = $_POST['cantidad'];
    $categoria   = $_POST['categoria'];
    $talla       = $_POST['talla'];
    $genero      = $_POST['genero'];
    $color       = $_POST['color'];
    $vendedor    = $_POST['vendedor'];
    $etiqueta    = $_POST['etiqueta'] ?? 'Normal';
    $descuento   = $_POST['descuento'] ?? 0;
    $imagen      = $_FILES['imagen'];

    // Validaciones básicas
    if (!$titulo) $errores[] = 'Debes añadir un título';
    if (!$precio || $precio <= 0) $errores[] = 'Debes ingresar un precio válido';
    if (!$descripcion) $errores[] = 'Debes añadir una descripción';
    if (!$cantidad || $cantidad <= 0) $errores[] = 'Debes indicar la cantidad';
    if (!$categoria) $errores[] = 'Debes seleccionar una categoría';
    if (!$talla) $errores[] = 'Debes seleccionar una talla';
    if (!$genero) $errores[] = 'Debes seleccionar el género';
    if (!$color) $errores[] = 'Debes añadir un color';
    if (!$vendedor) $errores[] = 'Debes seleccionar un vendedor';

    if (empty($errores)) {
        // Manejo de imagen
        $carpetaImg = '../../imagenes/';
        if (!is_dir($carpetaImg)) {
            mkdir($carpetaImg);
        }
        if($imagen['name']){
             unlink($carpetaImg . $producto['imagen']);
        }
        if ($imagen && $imagen['name']) {
            // Subir nueva imagen
            $nombreImg = md5(uniqid(rand(), true)) . ".jpg";
            move_uploaded_file($imagen['tmp_name'], $carpetaImg . $nombreImg);
        } else {
            // Mantener la actual
            $nombreImg = $imagenActual;
        }

        // Actualizar en la base de datos
        $sql = "UPDATE productos 
                SET titulo = ?, precio = ?, descripcion = ?, cantidad = ?, categoria = ?, 
                    talla = ?, genero = ?, color = ?, vendedor = ?, imagen = ?, etiqueta = ?, descuento = ?
                WHERE id = ?";
        
        $params = [
            $titulo, $precio, $descripcion, $cantidad, $categoria,
            $talla, $genero, $color, $vendedor, $nombreImg, $etiqueta, $descuento, $id
        ];
        
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            header('Location: ./productos.php?mensaje=2');
            exit;
        } else {
            $errores[] = "Error al actualizar el producto.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Prenda</title>
    <link rel="stylesheet" href="/admin/panel.css">
    <style>
        .hidden { display: none; }
    </style>
</head>
<body class="up-page up-page--editar">
    <header class="header up-header">
        <h1 class="titulo up-title">Actualizar Producto</h1>
    </header>

    <?php foreach($errores as $error){ ?>
        <div class="alerta error up-alert up-alert--error" id="mensaje">
            <?php echo $error; ?>
        </div>
    <?php } ?>

    <a href="./productos.php"><button type="button" class="up-btn up-btn--back">Volver</button></a>

    <main class="contenedor up-container">
        <form class="formulario up-form" method="POST" enctype="multipart/form-data">
            <!-- Información general -->
            <fieldset class="fieldset up-fieldset">
                <legend class="legend up-legend">Información General</legend>

                <label for="titulo" class="label up-label">Título:</label>
                <input type="text" id="titulo" name="titulo" class="input up-input" value="<?php echo $titulo; ?>">

                <label for="precio" class="label up-label">Precio:</label>
                <input type="number" id="precio" name="precio" class="input up-input" value="<?php echo $precio; ?>">

                <label for="imagen" class="label up-label">Imagen actual:</label>
                <img class="up-img-preview" src="../../imagenes/<?php echo $imagenActual; ?>" width="100" alt="Imagen actual">
                <input type="file" id="imagen" name="imagen" class="input up-input" accept="image/jpeg, image/png">

                <label for="descripcion" class="label up-label">Descripción:</label>
                <textarea id="descripcion" name="descripcion" class="textarea up-textarea"><?php echo $descripcion; ?></textarea>
            </fieldset>

            <!-- Detalles -->
            <fieldset class="fieldset up-fieldset">
                <legend class="legend up-legend">Detalles de la Prenda</legend>

                <label for="cantidad" class="label up-label">Cantidad:</label>
                <input type="number" id="cantidad" name="cantidad" class="input up-input" value="<?php echo $cantidad; ?>">

                <label for="categoria" class="label up-label">Tipo de prenda:</label>
                <select id="categoria" name="categoria" class="select up-select">
                    <option value="">Seleccione una opción</option>
                    <option value="camisa" <?php if($categoria=='camisa') echo 'selected'; ?>>Camisa</option>
                    <option value="pantalon" <?php if($categoria=='pantalon') echo 'selected'; ?>>Pantalón</option>
                    <option value="abrigo" <?php if($categoria=='abrigo') echo 'selected'; ?>>Abrigo</option>
                    <option value="falda" <?php if($categoria=='falda') echo 'selected'; ?>>Falda</option>
                    <option value="vestido" <?php if($categoria=='vestido') echo 'selected'; ?>>Vestido</option>
                </select>

                <label for="talla" class="label up-label">Talla:</label>
                <select id="talla" name="talla" class="select up-select">
                    <option value="">--Seleccione--</option>
                    <option value="S" <?php if($talla=='S') echo 'selected'; ?>>S</option>
                    <option value="M" <?php if($talla=='M') echo 'selected'; ?>>M</option>
                    <option value="L" <?php if($talla=='L') echo 'selected'; ?>>L</option>
                    <option value="XL" <?php if($talla=='XL') echo 'selected'; ?>>XL</option>
                </select>

                <label for="genero" class="label up-label">Género:</label>
                <select id="genero" name="genero" class="select up-select">
                    <option value="">Seleccione</option>
                    <option value="hombre" <?php if($genero=='hombre') echo 'selected'; ?>>Hombre</option>
                    <option value="mujer" <?php if($genero=='mujer') echo 'selected'; ?>>Mujer</option>
                </select>

                <label for="color" class="label up-label">Color:</label>
                <input type="text" id="color" name="color" class="input up-input" value="<?php echo $color; ?>">

                <label for="etiqueta" class="label up-label">Etiqueta:</label>
                <select id="etiqueta" name="etiqueta" class="select up-select">
                    <option value="Normal" <?php if($etiqueta=='Normal') echo 'selected'; ?>>Normal</option>
                    <option value="Descuento" <?php if($etiqueta=='Descuento') echo 'selected'; ?>>Descuento</option>
                    <option value="Nuevo" <?php if($etiqueta=='Nuevo') echo 'selected'; ?>>Nuevo</option>
                </select>

                <div id="campo-descuento" class="<?php echo ($etiqueta=='Oferta' || $etiqueta=='Descuento') ? '' : 'hidden'; ?>">
                    <label for="descuento" class="label up-label">Porcentaje de descuento (%):</label>
                    <input type="number" id="descuento" name="descuento" class="input up-input" min="0" max="100" value="<?php echo $descuento; ?>">
                </div>
            </fieldset>

            <!-- Vendedor -->
            <fieldset class="fieldset up-fieldset">
                <legend class="legend up-legend">Vendedor</legend>
                <label for="vendedor" class="label up-label">Nombre del vendedor:</label>
                <select id="vendedor" name="vendedor" class="select up-select">
                    <option value="">--Seleccione--</option>
                    <?php while ($row = sqlsrv_fetch_array($resultado, SQLSRV_FETCH_ASSOC)) { ?>
                        <option value="<?php echo $row['id']; ?>" <?php if($vendedor == $row['id']) echo 'selected'; ?>>
                            <?php echo $row['nombre'] . " " . $row['apellido']; ?>
                        </option>
                    <?php } ?>
                </select>
            </fieldset>

            <input type="submit" value="Actualizar Producto" class="boton-verde up-btn up-btn--submit">
        </form>
    </main>

    <script>
        document.getElementById('etiqueta').addEventListener('change', function() {
            const campo = document.getElementById('campo-descuento');
            if (this.value === 'Oferta' || this.value === 'Descuento') {
                campo.classList.remove('hidden');
            } else {
                campo.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
