  <?php
  // views/usuarios/registro.php
  // Crea usuario (dbo.usuarios) y también cliente (dbo.clientes) en UNA SOLA transacción
  require '../../app/config/database.php';

  $mensaje = "";

  // Para repoblar el formulario si hay error
  $nombre = $correo = $telefono = "";

  /* Procesar POST */
  if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? '') === "registrar") {
    $nombre    = trim($_POST["nombre"] ?? '');
    $correo    = trim($_POST["correo"] ?? '');
    $telefono  = trim($_POST["telefono"] ?? '');
    $password  = $_POST["password"]   ?? '';
    $confirmar = $_POST["confirmar"]  ?? '';

    // Validaciones básicas del lado servidor
    if ($nombre === '') {
      $mensaje = "El nombre es obligatorio.";
    } elseif ($correo === '') {
      $mensaje = "El correo es obligatorio.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
      $mensaje = "El correo no es válido.";
    } elseif ($telefono === '') {
      $mensaje = "El teléfono es obligatorio.";
    } elseif ($password === '' || $confirmar === '') {
      $mensaje = "Debes ingresar y confirmar la contraseña.";
    } elseif ($password !== $confirmar) {
      $mensaje = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 6) {
      $mensaje = "La contraseña debe tener al menos 6 caracteres.";
    } else {
      // ¿Correo ya existe en usuarios?
      $checkSql = "SELECT COUNT(*) AS existe FROM dbo.usuarios WHERE correo = ?";
      $checkStmt = sqlsrv_query($conn, $checkSql, [$correo]);
      if ($checkStmt === false) {
        $mensaje = "Error al validar el correo.";
      } else {
        $row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($checkStmt);

        if ($row && (int)$row['existe'] > 0) {
          $mensaje = "El correo ya está registrado.";
        } else {
          // Todo OK → iniciamos transacción
          $hash = password_hash($password, PASSWORD_DEFAULT);
          if (!sqlsrv_begin_transaction($conn)) {
            $mensaje = "No se pudo iniciar la transacción.";
          } else {
            try {
              // 1) Insertar en usuarios y obtener ID
              $insertU = "
                INSERT INTO dbo.usuarios (nombre, correo, password_hash, rol)
                OUTPUT INSERTED.id
                VALUES (?, ?, ?, ?)
              ";
              $stmtU = sqlsrv_query($conn, $insertU, [$nombre, $correo, $hash, 'cliente']);
              if ($stmtU === false) {
                throw new Exception("Error al registrar usuario.");
              }
              $rowU = sqlsrv_fetch_array($stmtU, SQLSRV_FETCH_ASSOC);
              sqlsrv_free_stmt($stmtU);
              if (!$rowU || !isset($rowU['id'])) {
                throw new Exception("No se obtuvo el ID del usuario.");
              }
              $usuarioId = (int)$rowU['id'];

              // 2) Insertar en clientes solo si no existe por correo
              $checkCli = sqlsrv_query($conn, "SELECT id FROM dbo.clientes WHERE correo = ?", [$correo]);
              if ($checkCli === false) {
                throw new Exception("Error al validar cliente existente.");
              }
              $cli = sqlsrv_fetch_array($checkCli, SQLSRV_FETCH_ASSOC);
              sqlsrv_free_stmt($checkCli);

              if (!$cli) {
                // Inserción básica en clientes (sin usuario_id; si luego agregas la columna, añade también el campo)
                $insCli = "INSERT INTO dbo.clientes (nombre, apellido, correo, telefono)
                          VALUES (?, NULL, ?, ?)";
                $okCli = sqlsrv_query($conn, $insCli, [$nombre, $correo, $telefono !== '' ? $telefono : null]);
                if ($okCli === false) {
                  throw new Exception("Error al crear el cliente.");
                }
              }

              // 3) Confirmar
              if (!sqlsrv_commit($conn)) {
                throw new Exception("No se pudo confirmar la transacción.");
              }

              // Éxito → redirigir
              header("Location: /views/usuarios/login.php");
              exit;
            } catch (Throwable $e) {
              sqlsrv_rollback($conn);
              $mensaje = $e->getMessage();
            }
          }
        }
      }
    }
  }

  // Cerrar conexión si no se redirigió
  sqlsrv_close($conn);
  ?>
  <!DOCTYPE html>
  <html lang="es">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - EcoAbrigo</title>
    <link rel="stylesheet" href="style.css">
  </head>

  <body class="body-register">

    <?php if (!empty($mensaje)): ?>
      <div class="mensaje visible" id="mensaje"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div>
      <script>
        const msg = document.getElementById('mensaje');
        setTimeout(() => msg.classList.remove('visible'), 3000);
        setTimeout(() => msg.remove(), 3500);
      </script>
    <?php endif; ?>

    <a href="/" class="volver">⮌</a>

    <main class="login">
      <section class="login-box">
        <h2>Crear Cuenta</h2>

        <form class="formulario" action="registro.php" method="POST" autocomplete="off" novalidate>
          <label for="nombre">Nombre completo:</label>
          <input type="text" name="nombre" placeholder="Tu nombre completo" required
            value="<?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>">

          <label for="correo">Correo electrónico:</label>
          <input type="email" name="correo" placeholder="ejemplo@correo.com" required
            value="<?php echo htmlspecialchars($correo, ENT_QUOTES, 'UTF-8'); ?>">

          <label for="telefono">Teléfono:</label>
          <input type="tel" name="telefono" placeholder="Número de contacto" required
            value="<?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?>">

          <label for="password">Contraseña:</label>
          <input type="password" name="password" placeholder="Crea una contraseña" required>

          <label for="confirmar">Confirmar contraseña:</label>
          <input type="password" name="confirmar" placeholder="Repite tu contraseña" required>

          <button type="submit" name="accion" value="registrar">Registrarse</button>
        </form>

        <p>¿Ya tienes una cuenta? <a href="login.php" class="registrate">Inicia sesión aquí</a></p>
      </section>
    </main>

    <script>
      const msg = document.getElementById('mensaje');
      if (msg) setTimeout(() => msg.remove(), 3000);
    </script>
  </body>

  </html>