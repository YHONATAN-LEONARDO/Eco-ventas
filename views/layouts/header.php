<?php
if (!isset($_SESSION)) {
  session_start();
}

$auth = $_SESSION['login'] ?? false;

// Detectar si estamos en el index.php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<header class="<?php echo ($currentPage === 'index.php') ? 'with-video' : 'no-video'; ?>">
  <nav class="navegacion">
    <div class="menu-toggle" id="menu-toggle">
      <span></span>
      <span></span>
      <span></span>
    </div>

    <div class="enlace-uno">
      <a href="/" class="especial">
        <img class="logo" src="public/img/logo.png" alt="Logo EcoAbrigo">
      </a>
      <a href="/">Inicio</a>
      <a href="hombre.php?genero=1">Hombre</a>
      <a href="mujer.php?genero=0">Mujer</a>
      <a href="acerca.php">Nosotros</a>
      <a href="contacto.php">Contáctanos</a>
      <a class="carrito" href="carrito.php"><ion-icon name="cart-outline"></ion-icon></a>
    </div>

    <div class="enlace-dos enlace-uno">
      <?php if ($auth): ?>
        <a href="/views/usuarios/cerrar-sesion.php">Cerrar Sesión</a>
        <a class="carrito" href="/perfil.php"><ion-icon name="person-outline"></ion-icon></a>
      <?php else: ?>
        <a href="/views/usuarios/login.php">Iniciar Sesión</a>
        <a href="/views/usuarios/registro.php">Registrarse</a>
      <?php endif; ?>
    </div>
  </nav>

  <?php if ($currentPage === 'index.php'): ?>
    <div class="video-container">
      <video autoplay muted loop playsinline>
        <source src="/public/video/sis2.mp4" type="video/mp4">
      </video>

      <div class="dentro-v">
        <!-- <img class="logo" src="public/img/logo.png" alt="Logo EcoAbrigo"> -->
        <p>Nos dedicamos a ropa sostenible y responsable en <span>EcoAbrigos </span>siempre.</p>
      </div>
    </div>
  <?php endif; ?>
</header>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const menuToggle = document.getElementById("menu-toggle");
  const menu = document.querySelector(".enlace-uno");
  const loginMenu = document.querySelector(".enlace-dos");

  const toggleMenu = () => {
    menu.classList.toggle("active");
    loginMenu.classList.toggle("active"); // mostrar login/registro
    menuToggle.classList.toggle("open");
  };

  menuToggle.addEventListener("click", toggleMenu);
});

</script>

<style>
/* HEADER GENERAL */
header {
  position: relative;
  width: 100%;
}
header.with-video { height: 75rem; }
header.no-video { height: auto; }

/* VIDEO */
.video-container {
  position: relative;
  width: 100%;
  height: 75rem;
}
.video-container video {
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  object-fit: cover;
  z-index: -10;
}

/* TEXTO SOBRE EL VIDEO */
.dentro-v {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  color: #fff;
  z-index: 1;
}
.dentro-v p { font-size: 2.2rem; }

/* NAV */
nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem 6rem;
  background: rgba(0,0,0,0.2);
  backdrop-filter: blur(0.5rem);
  position: fixed;
  top: 0; left: 0;
  width: 100%;
  z-index: 100;
}

/* LOGO */
.logo { width: 12rem; }

/* ENLACES */
nav div a {
  position: relative;
  color: #fff;
  text-decoration: none;
  margin-left: 2.5rem;
  font-weight: 500;
  transition: all 0.3s ease;
}
nav div a::after {
  content: "";
  position: absolute;
  left: 0; bottom: -0.5rem;
  width: 0%;
  height: 0.2rem;
  background-color: #fff;
  transition: width 0.3s ease;
}
nav div a:hover::after { width: 100%; }

/* RESPONSIVE: HAMBURGUESA */
@media (max-width: 768px) {
  .menu-toggle {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    width: 30px; height: 25px;
    cursor: pointer;
    margin-bottom: 10px;
    z-index: 2000;
  }
  .menu-toggle span {
    display: block;
    height: 3px; width: 100%;
    background-color: white;
    border-radius: 2px;
    transition: all 0.3s ease;
  }
  .menu-toggle.open span:nth-child(1) {
    transform: rotate(45deg) translate(5px,5px);
  }
  .menu-toggle.open span:nth-child(2) { opacity: 0; }
  .menu-toggle.open span:nth-child(3) {
    transform: rotate(-45deg) translate(5px,-5px);
  }
  .enlace-uno {
    display: none;
    flex-direction: column;
    width: 100%;
    margin-top: 10px;
  }
  .enlace-uno.active { display: flex; }
}
</style>
