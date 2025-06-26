<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in, otherwise redirect to login page
if (!isset($_SESSION['idUser'])) {
    header("Location: login.php");
    exit;
}

require_once 'php/db_connection.php';

$idUser = $_SESSION['idUser'];
$userData = null;
$update_errors = [];
$update_success = '';

// Fetch current user data
$stmt = $conn->prepare("SELECT ud.nombre, ud.apellidos, ud.email, ud.telefono, ud.fecha_nacimiento, ud.direccion, ud.sexo, ul.usuario
                        FROM users_data ud
                        JOIN users_login ul ON ud.idUser = ul.idUser
                        WHERE ud.idUser = ?");
if ($stmt) {
    $stmt->bind_param("i", $idUser);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $userData = $result->fetch_assoc();
    } else {
        
        $update_errors[] = "Error: No se pudieron cargar los datos del usuario.";
    }
    $stmt->close();
} else {
    $update_errors[] = "Error al preparar la consulta para cargar datos: " . $conn->error;
}


// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Sanitize and retrieve form data for profile update
    $nombre = $conn->real_escape_string(trim($_POST['nombre']));
    $apellidos = $conn->real_escape_string(trim($_POST['apellidos']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $telefono = $conn->real_escape_string(trim($_POST['telefono']));
    $fecha_nacimiento = $conn->real_escape_string(trim($_POST['fecha_nacimiento']));
    $direccion = $conn->real_escape_string(trim($_POST['direccion']));
    $sexo = $conn->real_escape_string(trim($_POST['sexo']));

    // Validation for profile update
    if (empty($nombre)) $update_errors[] = "El nombre es obligatorio.";
    if (empty($apellidos)) $update_errors[] = "Los apellidos son obligatorios.";
    if (empty($email)) {
        $update_errors[] = "El email es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $update_errors[] = "El formato del email no es válido.";
    }
    if (empty($telefono)) $update_errors[] = "El teléfono es obligatorio.";
    if (empty($fecha_nacimiento)) $update_errors[] = "La fecha de nacimiento es obligatoria.";
    if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $fecha_nacimiento)) {
        if(!empty($fecha_nacimiento)) $update_errors[] = "El formato de la fecha de nacimiento no es válido (YYYY-MM-DD).";
    }

    // Check if email already exists for another user
    if (empty($update_errors) && $email !== $userData['email']) {
        $stmt_check_email = $conn->prepare("SELECT idUser FROM users_data WHERE email = ? AND idUser != ?");
        $stmt_check_email->bind_param("si", $email, $idUser);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();
        if ($stmt_check_email->num_rows > 0) {
            $update_errors[] = "El email ya está registrado por otro usuario.";
        }
        $stmt_check_email->close();
    }

    if (empty($update_errors)) {
        $stmt_update = $conn->prepare("UPDATE users_data SET nombre = ?, apellidos = ?, email = ?, telefono = ?, fecha_nacimiento = ?, direccion = ?, sexo = ? WHERE idUser = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("sssssssi", $nombre, $apellidos, $email, $telefono, $fecha_nacimiento, $direccion, $sexo, $idUser);
            if ($stmt_update->execute()) {
                $update_success = "Datos personales actualizados correctamente.";
                // Refresh userData
                $userData['nombre'] = $nombre;
                $_SESSION['nombre'] = $nombre; // Update session name if changed
                $userData['apellidos'] = $apellidos;
                $userData['email'] = $email;
                $userData['telefono'] = $telefono;
                $userData['fecha_nacimiento'] = $fecha_nacimiento;
                $userData['direccion'] = $direccion;
                $userData['sexo'] = $sexo;
            } else {
                $update_errors[] = "Error al actualizar los datos personales: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $update_errors[] = "Error al preparar la actualización de datos: " . $conn->error;
        }
    }
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    if (empty($current_password)) $update_errors[] = "La contraseña actual es obligatoria.";
    if (empty($new_password)) $update_errors[] = "La nueva contraseña es obligatoria.";
    if ($new_password !== $confirm_new_password) $update_errors[] = "Las nuevas contraseñas no coinciden.";

    if (empty($update_errors)) {
        // Fetch current password hash
        $stmt_pass = $conn->prepare("SELECT password FROM users_login WHERE idUser = ?");
        if ($stmt_pass) {
            $stmt_pass->bind_param("i", $idUser);
            $stmt_pass->execute();
            $result_pass = $stmt_pass->get_result();
            if ($user_login_data = $result_pass->fetch_assoc()) {
                if (password_verify($current_password, $user_login_data['password'])) {
                    // Current password is correct, hash and update new password
                    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt_update_pass = $conn->prepare("UPDATE users_login SET password = ? WHERE idUser = ?");
                    if ($stmt_update_pass) {
                        $stmt_update_pass->bind_param("si", $hashed_new_password, $idUser);
                        if ($stmt_update_pass->execute()) {
                            $update_success = "Contraseña actualizada correctamente.";
                        } else {
                            $update_errors[] = "Error al actualizar la contraseña: " . $stmt_update_pass->error;
                        }
                        $stmt_update_pass->close();
                    } else {
                         $update_errors[] = "Error al preparar la actualización de contraseña: " . $conn->error;
                    }
                } else {
                    $update_errors[] = "La contraseña actual es incorrecta.";
                }
            }
            $stmt_pass->close();
        } else {
            $update_errors[] = "Error al verificar la contraseña actual: " . $conn->error;
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="wrapper">
    <?php include 'includes/nav.php'; ?>

    <header>
        <h1>Mi Perfil</h1>
    </header>

    <main>
        <?php
        if (!empty($update_errors)) {
            echo '<div class="message-div error">';
            echo '<strong>Errores:</strong><ul>';
            foreach ($update_errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul></div>';
        }
        if (!empty($update_success)) {
            echo '<div class="message-div success">';
            echo htmlspecialchars($update_success);
            echo '</div>';
        }
        ?>

        <?php if ($userData): ?>
            <section id="personal-data">
                <h2>Datos Personales</h2>
                <p><strong>Nombre de Usuario:</strong> <?php echo htmlspecialchars($userData['usuario']); ?> (No se puede cambiar)</p>

                <form action="perfil.php" method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    <div>
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($userData['nombre']); ?>" required>
                    </div>
                    <div>
                        <label for="apellidos">Apellidos:</label>
                        <input type="text" id="apellidos" name="apellidos" value="<?php echo htmlspecialchars($userData['apellidos']); ?>" required>
                    </div>
                    <div>
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                    </div>
                    <div>
                        <label for="telefono">Teléfono:</label>
                        <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($userData['telefono']); ?>" required>
                    </div>
                    <div>
                        <label for="fecha_nacimiento">Fecha de Nacimiento:</label>
                        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo htmlspecialchars($userData['fecha_nacimiento']); ?>" required>
                    </div>
                    <div>
                        <label for="direccion">Dirección:</label>
                        <textarea id="direccion" name="direccion"><?php echo htmlspecialchars($userData['direccion']); ?></textarea>
                    </div>
                    <div>
                        <label for="sexo">Sexo:</label>
                        <select id="sexo" name="sexo">
                            <option value="masculino" <?php echo ($userData['sexo'] === 'masculino') ? 'selected' : ''; ?>>Masculino</option>
                            <option value="femenino" <?php echo ($userData['sexo'] === 'femenino') ? 'selected' : ''; ?>>Femenino</option>
                            <option value="otro" <?php echo ($userData['sexo'] === 'otro') ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit">Actualizar Datos Personales</button>
                    </div>
                </form>
            </section>

            <hr>

            <section id="change-password">
                <h2>Cambiar Contraseña</h2>
                <form action="perfil.php" method="POST">
                    <input type="hidden" name="change_password" value="1">
                    <div>
                        <label for="current_password">Contraseña Actual:</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div>
                        <label for="new_password">Nueva Contraseña:</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div>
                        <label for="confirm_new_password">Confirmar Nueva Contraseña:</label>
                        <input type="password" id="confirm_new_password" name="confirm_new_password" required>
                    </div>
                    <div>
                        <button type="submit">Cambiar Contraseña</button>
                    </div>
                </form>
            </section>
        <?php else: ?>
            <p>No se pudieron cargar los datos del perfil. Por favor, inténtalo de nuevo más tarde o contacta con el administrador.</p>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Mi Sitio Web. Todos los derechos reservados.</p>
    </footer>
    </div>
</body>
</html>
