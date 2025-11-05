<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hombre</title>
  <link rel="stylesheet" href="public/css/estilos.css">
  <link rel="stylesheet" href="/public/css/normalize.css">
  <link rel="stylesheet" href="/public/css/celular.css">

</head>

<body>
  <?php include __DIR__ . '/views/layouts/header.php'; ?>
  <h1 class="titulo-ropa t-p ll">Elegancia a tu medida</h1>

  <main class="main-ropa">
    <img class="img-completo" src="public/img/icons/p-h1.png" alt="Hombre">
    <h1 class="titulo-ropa t-p">Diseño pensado para él</h1>

    <?php include 'anuncios.php'; ?>
  </main>


  <?php include __DIR__ . '/views/layouts/footer.php'; ?>

</body>

</html>