<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public/css/estilos.css">
    <link rel="stylesheet" href="/public/css/normalize.css">
    <link rel="stylesheet" href="/public/css/celular.css">

    <title>EcoAbrigo</title>
</head>

<body>
    <?php include "views/layouts/header.php"; ?>
    <!-- mensaje -->
    <h1 class="t-p">Moda que habla por ti</h1>
    <main class="principal">
        <div class="img-p">
            <img src="public/img/principal/hombre.jpg" class="img-principal" alt="">
            <h2>Estilo que te acompaña</h2>

            <a href="hombre.php?genero=1">
                <button>Comprar</button>
            </a>
        </div>
        <h1 class="t-p">Fuerza, estilo y confianza en cada detalle</h1>

        <div class="carousel">
            <div class="group">
                <img class="card" src="public/img/principal/im1.png" alt="">
                <img class="card" src="public/img/principal/im2.png" alt="">
                <img class="card" src="public/img/principal/im3.png" alt="">
                <img class="card" src="public/img/principal/im4.png" alt="">
                <img class="card" src="public/img/principal/im5.jpg" alt="">
                <img class="card" src="public/img/principal/im6.jpg" alt="">
                <img class="card" src="public/img/principal/im7.png" alt="">
            </div>
            <div aria-hidden class="group">
                <img class="card" src="public/img/principal/im1.png" alt="">
                <img class="card" src="public/img/principal/im2.png" alt="">
                <img class="card" src="public/img/principal/im3.png" alt="">
                <img class="card" src="public/img/principal/im4.png" alt="">
                <img class="card" src="public/img/principal/im5.jpg" alt="">
                <img class="card" src="public/img/principal/im6.jpg" alt="">
                <img class="card" src="public/img/principal/im7.png" alt="">
            </div>
        </div>
        <div class="img-p">
            <img src="public/img/principal/mujer.jpg" class="img-principal" alt="">
            <h2>Moda que inspira</h2>


            <a href="mujer.php?genero=0">
                <button>Comprar</button>

            </a>
        </div>
        <h1 class="t-p">Brilla con cada paso que das</h1>

        <div class="carousel">
            <div class="group">
                <img class="card" src="public/img/principal/m1.jpg" alt="">
                <img class="card" src="public/img/principal/m2.jpg" alt="">
                <img class="card" src="public/img/principal/m3.jpg" alt="">
                <img class="card" src="public/img/principal/m4.jpg" alt="">
                <img class="card" src="public/img/principal/m5.jpg" alt="">
                <img class="card" src="public/img/principal/m6.jpg" alt="">
                <img class="card" src="public/img/principal/m7.jpg" alt="">
                <img class="card" src="public/img/principal/m8.jpg" alt="">
            </div>
            <div aria-hidden class="group">
                <img class="card" src="public/img/principal/m1.jpg" alt="">
                <img class="card" src="public/img/principal/m2.jpg" alt="">
                <img class="card" src="public/img/principal/m3.jpg" alt="">
                <img class="card" src="public/img/principal/m4.jpg" alt="">
                <img class="card" src="public/img/principal/m5.jpg" alt="">
                <img class="card" src="public/img/principal/m6.jpg" alt="">
                <img class="card" src="public/img/principal/m7.jpg" alt="">
                <img class="card" src="public/img/principal/m8.jpg" alt="">
            </div>
        </div>
    </main>

    <?php include "views/layouts/footer.php"; ?>
</body>

</html>