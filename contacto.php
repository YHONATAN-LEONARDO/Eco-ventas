<?php
/* =========================================================
   contacto.php — guarda en dbo.contactos y muestra mensajes
   ========================================================= */


session_start();
$errores = [];
$ok = null;
$nombre = $correo = $asunto = $mensaje = '';

require_once __DIR__ . '/app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre  = trim($_POST['nombre']  ?? '');
  $correo  = trim($_POST['correo']  ?? '');
  $asunto  = trim($_POST['asunto']  ?? '');
  $mensaje = trim($_POST['mensaje'] ?? '');

  if ($nombre === '') {
    $errores[] = 'El nombre es obligatorio.';
  }
  if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'El correo no es válido.';
  }
  if ($asunto === '') {
    $errores[] = 'El asunto es obligatorio.';
  }
  if ($mensaje === '') {
    $errores[] = 'El mensaje es obligatorio.';
  }

  if (!$errores) {
    $ip        = $_SERVER['REMOTE_ADDR']     ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $insertOk = false;

    if (isset($conn) && $conn) {
      $sql = "INSERT INTO dbo.contactos (nombre, correo, asunto, mensaje, ip, user_agent)
              VALUES (?, ?, ?, ?, ?, ?)";
      $params = [$nombre, $correo, $asunto, $mensaje, $ip, $userAgent];
      $stmt = sqlsrv_query($conn, $sql, $params);
      if ($stmt === false) {
        $errores[] = 'Error al guardar (sqlsrv): ' . print_r(sqlsrv_errors(), true);
      } else {
        $insertOk = true;
        sqlsrv_free_stmt($stmt);
      }
    } elseif (isset($pdo) && $pdo) {
      $sql = "INSERT INTO contactos (nombre, correo, asunto, mensaje, ip, user_agent)
              VALUES (:nombre, :correo, :asunto, :mensaje, :ip, :ua)";
      $stmt = $pdo->prepare($sql);
      $insertOk = $stmt->execute([
        ':nombre'  => $nombre,
        ':correo'  => $correo,
        ':asunto'  => $asunto,
        ':mensaje' => $mensaje,
        ':ip'      => $ip,
        ':ua'      => $userAgent,
      ]);
      if (!$insertOk) {
        $errores[] = 'Error al guardar (PDO).';
      }
    } else {
      $errores[] = 'No se encontró conexión a la base de datos ($conn o $pdo).';
    }

    if ($insertOk) {
      $ok = '¡Gracias! Tu mensaje fue enviado correctamente.';
      $nombre = $correo = $asunto = $mensaje = '';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="public/css/estilos.css" />
  <link rel="stylesheet" href="/public/css/normalize.css">
    <link rel="stylesheet" href="/public/css/celular.css" >

  <title>Contáctanos</title>
</head>

<body>
  <?php include "views/layouts/header.php"; ?>

  <h1 class="t-p ll">Contáctanos</h1>
  <main class="contacto-main">
    <section class="contacto-layout">
      <div class="contacto-formulario">

        <!-- NUEVO TEXTO INFORMATIVO -->
        <p style="background:#f8f9fa; padding:10px 15px; border-radius:6px; margin-bottom:15px;">
          ¿Tienes alguna <strong>felicitación</strong>, <strong>reclamo</strong> o deseas dejar un <strong>comentario o sugerencia</strong>?
          Usa este formulario.
        </p>
        <!-- FIN NUEVO TEXTO -->

        <?php if ($ok): ?>
          <div class="alerta-exito"><?= htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($errores): ?>
          <div class="alerta-error">
            <ul>
              <?php foreach ($errores as $e): ?>
                <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form action="" method="post" class="contacto-form" novalidate>
          <label class="contacto-label">
            <span class="contacto-span">Nombre</span>
            <input type="text" name="nombre" class="contacto-input" required
              value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>">
          </label>

          <label class="contacto-label">
            <span class="contacto-span">Correo</span>
            <input type="email" name="correo" class="contacto-input" required
              value="<?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8') ?>">
          </label>

          <label class="contacto-label contacto-full">
            <span class="contacto-span">Asunto</span>
            <input type="text" name="asunto" class="contacto-input" required
              value="<?= htmlspecialchars($asunto, ENT_QUOTES, 'UTF-8') ?>">
          </label>

          <label class="contacto-label contacto-full">
            <span class="contacto-span">Mensaje</span>
            <textarea name="mensaje" rows="6" class="contacto-textarea" required><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></textarea>
          </label>

          <button type="submit" class="contacto-boton">Enviar</button>
        </form>
      </div>

      <aside class="contacto-info">
        <div class="contacto-tarjeta">
          <h2 class="contacto-subtitulo">Información</h2>
          <p class="contacto-dato">Teléfono: +591 76543210</p>
          <p class="contacto-dato">Correo: contacto@ejemplo.com</p>
          <p class="contacto-dato">Dirección: Av. Siempre Viva 742</p>
        </div>

        <figure class="contacto-mapa">
          <img src="public/img/icons/mapa.png" alt="Mapa de ubicación" class="contacto-mapa-img">
        </figure>

        <div class="contacto-redes">
          <ion-icon name="logo-facebook" class="contacto-red"></ion-icon>
          <ion-icon name="logo-instagram" class="contacto-red"></ion-icon>
          <ion-icon name="logo-twitter" class="contacto-red"></ion-icon>
        </div>
      </aside>
    </section>
  </main>

  <?php include "views/layouts/footer.php"; ?>
</body>

</html>
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>