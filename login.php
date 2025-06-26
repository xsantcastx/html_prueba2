<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
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
        <h1>Iniciar Sesión</h1>
    </header>

    <main>
        <?php
        if (isset($_SESSION['login_errors'])) {
            echo '<div class="message-div error">';
            echo '<strong>Errores en el inicio de sesión:</strong><ul>';
            foreach ($_SESSION['login_errors'] as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul></div>';
            unset($_SESSION['login_errors']);
            $login_form_data = $_SESSION['login_form_data'] ?? [];
            unset($_SESSION['login_form_data']);
        }

        if (isset($_SESSION['registration_success'])) {
            echo '<div class="message-div success">';
            echo htmlspecialchars($_SESSION['registration_success']);
            echo '</div>';
            unset($_SESSION['registration_success']);
        }
        ?>
        <form action="php/procesar_login.php" method="POST">
            <div>
                <label for="usuario">Nombre de Usuario:</label>
                <input type="text" id="usuario" name="usuario" required value="<?php echo htmlspecialchars($login_form_data['usuario'] ?? ''); ?>">
            </div>
            <div>
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div>
                <button type="submit">Iniciar Sesión</button>
            </div>
        </form>
        <p>¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a>.</p>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Mi Sitio Web. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
