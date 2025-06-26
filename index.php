<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página de Inicio</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    include 'includes/nav.php';
    ?>

    <header>
        <h1>Bienvenido a Nuestro Sitio Web</h1>
        <p>Su fuente de información y servicios.</p>
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
            <p>Somos una empresa ficticia dedicada a ofrecer soluciones innovadoras. Nuestro equipo de programadores web está aquí para ayudarle.</p>
            <img src="images/placeholder_team.jpg" alt="Equipo de desarrollo" style="width:100%;max-width:600px;">
        </section>

        <section id="services">
            <h2>Nuestros Servicios</h2>
            <p>Ofrecemos una variedad de servicios para satisfacer sus necesidades. Desde desarrollo web hasta consultoría.</p>
            <ul>
                <li><a href="noticias.php">Últimas Noticias</a></li>
                <li><a href="registro.php">Regístrese</a></li>
                <li><a href="login.php">Iniciar Sesión</a></li>
            </ul>
        </section>

        <section id="contact">
            <h2>Contacto</h2>
            <p>Póngase en contacto con nosotros para más información.</p>
            <p>Email: info@example.com</p>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Mi Sitio Web. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
