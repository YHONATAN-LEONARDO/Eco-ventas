<?php 
    include '../../app/config/session.php';
    include  '../../app/config/database.php'; 
    $consultaVendedor = "SELECT * FROM vendedor";
    $resultado = sqlsrv_query($conn, $consultaVendedor);

    $titulo      = '';
    $precio      = '';
    $descripcion = '';
    $cantidad    = '';
    $categoria   = '';
    $talla       = '';
    $genero      = '';
    $color       = '';
    $vendedor    = '';
    $etiqueta    = 'Normal';
    $descuento   = 0;

    $errores = []; 
    if($_SERVER['REQUEST_METHOD'] === 'POST'){
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

        $imagen = $_FILES['imagen'];

        if (!$titulo) { $errores[] = 'Debes añadir un título'; }
        if (!$precio || $precio <= 0) { $errores[] = 'Debes ingresar un precio válido'; }
        if (!$descripcion) { $errores[] = 'Debes añadir una descripción'; }
        if (!$cantidad || $cantidad <= 0) { $errores[] = 'Debes indicar la cantidad'; }
        if (!$categoria) { $errores[] = 'Debes seleccionar una categoría'; }
        if (!$talla) { $errores[] = 'Debes seleccionar una talla'; }
        if (!$genero) { $errores[] = 'Debes seleccionar el género'; }
        if (!$color) { $errores[] = 'Debes añadir un color'; }
        if (!$vendedor) { $errores[] = 'Debes seleccionar un vendedor'; }
        if(!$imagen || !$imagen['name']){ $errores[]='La imagen es obligatoria'; }

        if(empty($errores)){
            //  subida de archivos 
            $carpetaImg = '../../imagenes/';
            if(!is_dir($carpetaImg)) { mkdir($carpetaImg); }
            // generar nombre unico
            $nombreImg = md5(uniqid(rand(),true)) . ".jpg";
            move_uploaded_file($imagen['tmp_name'],$carpetaImg . $nombreImg );

            $sql = "INSERT INTO productos 
                    (titulo, precio, descripcion, cantidad, categoria, talla, genero, color, vendedor, imagen, etiqueta, descuento)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$titulo,$precio,$descripcion,$cantidad,$categoria,$talla,$genero,$color,$vendedor,$nombreImg,$etiqueta,$descuento];
            $stmt = sqlsrv_query($conn, $sql, $params);
            if($stmt){
                header('Location: ./productos.php?mensaje=1');
                exit;
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Nueva Prenda</title>
    <link rel="stylesheet" href="/admin/panel.css">
    <style>
        .hidden { display: none; }
    </style>
</head>
<body class="cp-page cp-page--crear">

    <header class="header cp-header">
        <h1 class="titulo cp-title">Registro de Ropa</h1>
    </header>

    <?php foreach($errores as $error){ ?>
        <div class="alerta error cp-alert cp-alert--error" id="mensaje">
            <?php echo $error;?>
        </div>
    <?php } ?>

    <a href="./productos.php">
        <button class="cp-btn cp-btn--back" type="button">Volver</button>
    </a>

    <main class="contenedor cp-container">
        <form class="formulario cp-form" method="POST" action="./crear.php" enctype="multipart/form-data">

            <!-- Información general -->
            <fieldset class="fieldset cp-fieldset">
                <legend class="legend cp-legend">Información General</legend>

                <label for="titulo" class="label cp-label">Título:</label>
                <input type="text" id="titulo" name="titulo" class="input cp-input" placeholder="Título de la prenda" value="<?php echo $titulo?>">

                <label for="precio" class="label cp-label">Precio:</label>
                <input type="number" id="precio" name="precio" class="input cp-input" placeholder="Precio en Bs" min="0" value="<?php echo $precio?>">

                <label for="imagen" class="label cp-label">Imagen:</label>
                <input type="file" id="imagen" name="imagen" class="input cp-input" accept="image/jpeg, image/png">

                <label for="descripcion" class="label cp-label">Descripción:</label>
                <textarea id="descripcion" name="descripcion" class="textarea cp-textarea" rows="4" placeholder="Descripción breve de la prenda"><?php echo $descripcion?></textarea>
            </fieldset>

            <!-- Detalles de la prenda -->
            <fieldset class="fieldset cp-fieldset">
                <legend class="legend cp-legend">Detalles de la Prenda</legend>

                <label for="cantidad" class="label cp-label">Cantidad:</label>
                <input type="number" id="cantidad" name="cantidad" class="input cp-input" placeholder="Cantidad" min="0" value="<?php echo $cantidad?>">

                <label for="categoria" class="label cp-label">Tipo de prenda:</label>
                <select id="categoria" name="categoria" class="select cp-select">
                    <option value="">Seleccione una opción</option>
                    <option value="camisa">Camisa</option>
                    <option value="pantalon">Pantalón</option>
                    <option value="abrigo">Abrigo</option>
                    <option value="falda">Falda</option>
                    <option value="vestido">Vestido</option>
                </select>

                <label for="talla" class="label cp-label">Talla:</label>
                <select id="talla" name="talla" class="select cp-select">
                    <option value="">--Seleccione--</option>
                    <option value="S">S</option>
                    <option value="M">M</option>
                    <option value="L">L</option>
                    <option value="XL">XL</option>
                </select>

                <label for="genero" class="label cp-label">Género:</label>
                <select id="genero" name="genero" class="select cp-select">
                    <option value="">Seleccione</option>
                    <option value="hombre">Hombre</option>
                    <option value="mujer">Mujer</option>
                </select>

                <label for="color" class="label cp-label">Color:</label>
                <input type="text" id="color" name="color" class="input cp-input" placeholder="Ejemplo: Negro, Blanco, Gris" value="<?php echo $color?>">

                <label for="etiqueta" class="label cp-label">Etiqueta:</label>
                <select id="etiqueta" name="etiqueta" class="select cp-select">
                    <option value="Normal" <?php if($etiqueta=='Normal') echo 'selected'; ?>>Normal</option>
                    <option value="Descuento" <?php if($etiqueta=='Descuento') echo 'selected'; ?>>Descuento</option>
                    <option value="Nuevo" <?php if($etiqueta=='Nuevo') echo 'selected'; ?>>Nuevo</option>
                </select>

                <div id="campo-descuento" class="<?php echo ($etiqueta=='Oferta' || $etiqueta=='Descuento') ? '' : 'hidden'; ?>">
                    <label for="descuento" class="label cp-label">Porcentaje de descuento (%):</label>
                    <input type="number" id="descuento" name="descuento" class="input cp-input" min="0" max="100" value="<?php echo $descuento; ?>">
                </div>
            </fieldset>

            <!-- Información del vendedor -->
            <fieldset class="fieldset cp-fieldset">
                <legend class="legend cp-legend">Vendedor</legend>

                <label for="vendedor" class="label cp-label">Nombre del vendedor:</label>
                <select id="vendedor" name="vendedor" class="select cp-select">
                    <option value="">--Seleccione--</option>
                    <?php while ($row = sqlsrv_fetch_array($resultado, SQLSRV_FETCH_ASSOC)) { ?>
                        <option value="<?php echo $row['id']; ?>">
                            <?php echo $row['nombre'] . " " . $row['apellido']; ?>
                        </option>
                    <?php } ?>
                </select>
            </fieldset>

            <input type="submit" value="Crear Producto" class="boton-verde cp-btn cp-btn--submit">
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
