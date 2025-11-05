<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="public/css/estilos.css" />
  <link rel="stylesheet" href="/public/css/normalize.css" />
  <link rel="stylesheet" href="/public/css/celular.css" />
  <title>Nosotros - EcoAbirgos</title>
</head>

<body>

  <?php include "views/layouts/header.php"; ?>

  <main class="acerca-main">

    <!-- Título principal -->
    <h1 class="t-p ll">SOBRE ECOABRIGO</h1>

    <!-- Introducción -->
    <section class="acerca-intro">
      <div class="acerca-intro-texto">
        <h2 class="acerca-intro-subtitulo"><ion-icon name="shirt-outline"></ion-icon> Nosotros</h2>
        <p class="acerca-intro-parrafo">
          En <strong>EcoAbirgos</strong> diseñamos y fabricamos prendas funcionales que te acompañan todos los días.
          Creemos en la durabilidad, el comercio responsable y un diseño simple que realmente sirve.
        </p>
        <p class="acerca-intro-parrafo">
          Trabajamos con proveedores locales siempre que es posible y buscamos reducir desperdicios en cada etapa:
          desde el patronaje hasta el empaque.
        </p>
      </div>
      <figure class="acerca-intro-imagen">
        <img class="acerca-img-tienda" src="public/img/icons/tienda.png" alt="Tienda EcoAbirgos">
      </figure>
    </section>

    <!-- Misión y Visión -->
    <section class="acerca-mision-vision">
      <article class="acerca-mision">
        <h3 class="acerca-mision-titulo"><ion-icon name="ribbon-outline"></ion-icon> Misión</h3>
        <p class="acerca-mision-parrafo">
          Crear abrigos y prendas versátiles que combinen calidad, precio justo y procesos responsables,
          para que más personas puedan vestirse mejor sin gastar de más.
        </p>
      </article>
      <article class="acerca-vision">
        <h3 class="acerca-vision-titulo">Visión</h3>
        <p class="acerca-vision-parrafo">
          Ser la marca de referencia en Latinoamérica por su sencillez, transparencia y resistencia real en el uso diario,
          manteniendo un impacto ambiental cada vez menor.
        </p>
      </article>
    </section>

    <!-- Valores -->
    <section class="acerca-valores">
      <h2 class="acerca-valores-titulo"><ion-icon name="earth-outline"></ion-icon> Valores</h2>
      <div class="acerca-valores-grid">
        <article class="acerca-valor-card calidad">
          <ion-icon name="happy-outline"></ion-icon>
          <h4 class="acerca-valor-nombre">Calidad honesta</h4>
          <p class="acerca-valor-descripcion">Probamos cada prenda en situaciones reales para asegurar costuras firmes y materiales que duren.</p>
        </article>
        <article class="acerca-valor-card sostenibilidad">
          <ion-icon name="happy-outline"></ion-icon>
          <h4 class="acerca-valor-nombre">Sostenibilidad práctica</h4>
          <p class="acerca-valor-descripcion">Optimizamos cortes, reusamos retazos y priorizamos tejidos con menor huella.</p>
        </article>
        <article class="acerca-valor-card comunidad">
          <ion-icon name="happy-outline"></ion-icon>
          <h4 class="acerca-valor-nombre">Comunidad</h4>
          <p class="acerca-valor-descripcion">Colaboramos con talleres y emprendedores locales para crecer juntos.</p>
        </article>
        <article class="acerca-valor-card transparencia">
          <ion-icon name="happy-outline"></ion-icon>
          <h4 class="acerca-valor-nombre">Transparencia</h4>
          <p class="acerca-valor-descripcion">Comunicamos procesos, tiempos y costos de forma clara, sin humo.</p>
        </article>
      </div>
    </section>

    <!-- Historia -->
    <section class="acerca-historia">
      <h2 class="acerca-historia-titulo">Nuestra historia</h2>
      <p class="acerca-historia-parrafo">
        Nacimos en La Paz en 2021 con un objetivo simple: fabricar abrigos que realmente abriguen,
        que no se rompan al mes y que no cuesten una fortuna. Empezamos con 20 piezas y una mesa de corte prestada;
        hoy producimos lotes pequeños, mejor controlados, sin perder el toque artesanal.
      </p>
    </section>

    <!-- Equipo -->
    <section class="acerca-equipo">
      <h2 class="acerca-equipo-titulo"><ion-icon name="people-outline"></ion-icon> Equipo</h2>
      <div class="acerca-equipo-grid">
        <article class="acerca-persona persona1">
          <ion-icon name="person-outline"></ion-icon>
          <h4 class="acerca-persona-nombre">Carla R.</h4>
          <p class="acerca-persona-rol">Producto</p>
        </article>
        <article class="acerca-persona persona2">
          <ion-icon name="person-outline"></ion-icon>
          <h4 class="acerca-persona-nombre">Diego M.</h4>
          <p class="acerca-persona-rol">Taller</p>
        </article>
        <article class="acerca-persona persona3">
          <ion-icon name="person-outline"></ion-icon>
          <h4 class="acerca-persona-nombre">Valeria T.</h4>
          <p class="acerca-persona-rol">Diseño técnico</p>
        </article>
        <article class="acerca-persona persona4">
          <ion-icon name="person-outline"></ion-icon>
          <h4 class="acerca-persona-nombre">Andrés P.</h4>
          <p class="acerca-persona-rol">Calidad</p>
        </article>
        <article class="acerca-persona persona5">
          <ion-icon name="person-outline"></ion-icon>
          <h4 class="acerca-persona-nombre">Luisa G.</h4>
          <p class="acerca-persona-rol">Operaciones</p>
        </article>
        <article class="acerca-persona persona6">
          <ion-icon name="person-outline"></ion-icon>
          <h4 class="acerca-persona-nombre">Marco A.</h4>
          <p class="acerca-persona-rol">Atención</p>
        </article>
      </div>
    </section>

    <!-- Compromisos -->
    <section class="acerca-compromisos">
      <h2 class="acerca-compromisos-titulo">Compromisos</h2>
      <ul class="acerca-compromisos-lista">
        <li class="acerca-compromiso-item">Series pequeñas para evitar sobreproducción.</li>
        <li class="acerca-compromiso-item">Reparación básica gratuita los primeros 6 meses.</li>
        <li class="acerca-compromiso-item">Etiquetas con información clara de materiales y cuidados.</li>
      </ul>
    </section>

  </main>

  <?php include "views/layouts/footer.php"; ?>

  <!-- Ionicons -->
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>

</html>
