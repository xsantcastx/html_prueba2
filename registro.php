<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
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
        <h1>Registro de Nuevo Usuario</h1>
    </header>

    <main>
        <?php
        if (isset($_SESSION['registration_errors'])) {
            echo '<div class="message-div error">';
            echo '<strong>Errores en el registro:</strong><ul>';
            foreach ($_SESSION['registration_errors'] as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul></div>';
            unset($_SESSION['registration_errors']);
            // Optionally, keep form data to repopulate
            $form_data = $_SESSION['form_data'] ?? [];
            unset($_SESSION['form_data']);
        }
        ?>
        <form action="php/procesar_registro.php" method="POST">
            <h2>Datos Personales</h2>
            <div>
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($form_data['nombre'] ?? ''); ?>">
            </div>
            <div>
                <label for="apellidos">Apellidos:</label>
                <input type="text" id="apellidos" name="apellidos" required value="<?php echo htmlspecialchars($form_data['apellidos'] ?? ''); ?>">
            </div>
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
            </div>
            <div>
                <label for="telefono">Teléfono:</label>
                <input type="text" id="telefono" name="telefono" required value="<?php echo htmlspecialchars($form_data['telefono'] ?? ''); ?>">
            </div>
            <div>
                <label for="fecha_nacimiento">Fecha de Nacimiento:</label>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required value="<?php echo htmlspecialchars($form_data['fecha_nacimiento'] ?? ''); ?>">
            </div>
            <div>
                <label for="direccion">Dirección:</label>
                <textarea id="direccion" name="direccion"><?php echo htmlspecialchars($form_data['direccion'] ?? ''); ?></textarea>
            </div>
            <div>
                <label for="sexo">Sexo:</label>
                <select id="sexo" name="sexo">
                    <option value="masculino" <?php echo (isset($form_data['sexo']) && $form_data['sexo'] === 'masculino') ? 'selected' : ''; ?>>Masculino</option>
                    <option value="femenino" <?php echo (isset($form_data['sexo']) && $form_data['sexo'] === 'femenino') ? 'selected' : ''; ?>>Femenino</option>
                    <option value="otro" <?php echo (isset($form_data['sexo']) && $form_data['sexo'] === 'otro') ? 'selected' : ''; ?>>Otro</option>
                </select>
            </div>

            <h2>Datos de Inicio de Sesión</h2>
            <div>
                <label for="usuario">Nombre de Usuario:</label>
                <input type="text" id="usuario" name="usuario" required value="<?php echo htmlspecialchars($form_data['usuario'] ?? ''); ?>">
            </div>
            <div>
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div>
                <label for="confirm_password">Confirmar Contraseña:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <div>
                <button type="submit">Registrarse</button>
            </div>
        </form>
        <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a>.</p>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Mi Sitio Web. Todos los derechos reservados.</p>
    </footer>
    </div>
</body>
</html>
