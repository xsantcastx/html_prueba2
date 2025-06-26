<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página de Inicio</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="wrapper">
    <?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    include 'includes/nav.php';
    ?>

    <header>
        <h1>Bienvenido a Nuestro Sitio Web</h1>
        <p>Tu fuente confiable de información, noticias y servicios digitales</p>
    </header>

    <main>
        <?php
        if (isset($_SESSION['login_success'])) {
            echo '<div class="message-div success">';
            echo htmlspecialchars($_SESSION['login_success']);
            echo '</div>';
            unset($_SESSION['login_success']);
        }
        if (isset($_GET['status']) && $_GET['status'] === 'loggedout') {
            echo '<div class="message-div success">';
            echo 'Has cerrado sesión exitosamente.';
            echo '</div>';
        }
        ?>

        <section id="about">
            <h2>Sobre Nosotros</h2>
            <p>Somos una plataforma creada por y para desarrolladores web. Aquí podrás acceder a noticias, gestionar citas y administrar tus datos de forma segura.</p>
            <p>Nuestra misión es facilitar el desarrollo y la gestión de información en línea, conectando usuarios con herramientas prácticas y actualizadas.</p>
            <img src="images/placeholder_team.jpg" alt="Equipo de desarrollo" style="width:100%;max-width:600px; border-radius: 8px; margin-top: 15px;">
        </section>

        <section id="services">
            <h2>Nuestros Servicios</h2>
            <p>Te ofrecemos las siguientes funcionalidades para aprovechar al máximo el sitio:</p>
            <div class="service-links">
                <a class="btn-link" href="noticias.php">📰 Ver Últimas Noticias</a>
                <a class="btn-link" href="registro.php">📝 Crear una Cuenta</a>
                <a class="btn-link" href="login.php">🔐 Iniciar Sesión</a>
            </div>
        </section>

        <section id="contact">
            <h2>Contacto</h2>
            <p>¿Tienes dudas o sugerencias? Estamos aquí para ayudarte.</p>
            <p><strong>Email:</strong> <a href="mailto:xsantcastx@outlook.com">xsantcastx@outlook.com</a></p>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Mi Sitio Web. Todos los derechos reservados.</p>
    </footer>   
    </div>
</body>
</html>
