<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connection.php'; // Establishes $conn

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $conn->real_escape_string(trim($_POST['usuario']));
    $password = $_POST['password'];

    if (empty($usuario)) {
        $errors[] = "El nombre de usuario es obligatorio.";
    }
    if (empty($password)) {
        $errors[] = "La contraseña es obligatoria.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT ul.idUser, ul.password, ul.rol, ud.nombre FROM users_login ul JOIN users_data ud ON ul.idUser = ud.idUser WHERE ul.usuario = ?");
        if (!$stmt) {
            $errors[] = "Error al preparar la consulta: " . $conn->error;
        } else {
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user_row = $result->fetch_assoc();
                if (password_verify($password, $user_row['password'])) {
                    // Password is correct, set session variables
                    $_SESSION['idUser'] = $user_row['idUser'];
                    $_SESSION['usuario'] = $usuario; // Or $user_row['usuario'] if you selected it
                    $_SESSION['nombre'] = $user_row['nombre']; // Store user's actual name
                    $_SESSION['rol'] = $user_row['rol'];

                    // Regenerate session ID for security
                    session_regenerate_id(true);

                    $_SESSION['login_success'] = "¡Inicio de sesión exitoso! Bienvenido, " . htmlspecialchars($user_row['nombre']) . ".";
                    header("Location: ../index.php");
                    exit;
                } else {
                    $errors[] = "Nombre de usuario o contraseña incorrectos.";
                }
            } else {
                $errors[] = "Nombre de usuario o contraseña incorrectos.";
            }
            $stmt->close();
        }
    }

    if (!empty($errors)) {
        $_SESSION['login_errors'] = $errors;
        $_SESSION['login_form_data'] = $_POST; // To repopulate username
        header("Location: ../login.php");
        exit;
    }
} else {
    header("Location: ../login.php");
    exit;
}

$conn->close();
?>
