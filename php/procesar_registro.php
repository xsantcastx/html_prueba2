<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connection.php'; // Establishes $conn

$errors = [];
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve form data
    $nombre = $conn->real_escape_string(trim($_POST['nombre']));
    $apellidos = $conn->real_escape_string(trim($_POST['apellidos']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $telefono = $conn->real_escape_string(trim($_POST['telefono']));
    $fecha_nacimiento = $conn->real_escape_string(trim($_POST['fecha_nacimiento']));
    $direccion = $conn->real_escape_string(trim($_POST['direccion']));
    $sexo = $conn->real_escape_string(trim($_POST['sexo']));
    $usuario = $conn->real_escape_string(trim($_POST['usuario']));
    $password = $_POST['password']; 
    $confirm_password = $_POST['confirm_password'];

    // --- Validation ---
    if (empty($nombre)) $errors[] = "El nombre es obligatorio.";
    if (empty($apellidos)) $errors[] = "Los apellidos son obligatorios.";
    if (empty($email)) {
        $errors[] = "El email es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El formato del email no es válido.";
    }
    if (empty($telefono)) $errors[] = "El teléfono es obligatorio.";
    if (empty($fecha_nacimiento)) $errors[] = "La fecha de nacimiento es obligatoria.";
    // Basic date validation (can be more robust)
    if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $fecha_nacimiento)) {
        if(!empty($fecha_nacimiento)) $errors[] = "El formato de la fecha de nacimiento no es válido (YYYY-MM-DD).";
    }
    if (empty($usuario)) $errors[] = "El nombre de usuario es obligatorio.";
    if (empty($password)) $errors[] = "La contraseña es obligatoria.";
    if ($password !== $confirm_password) $errors[] = "Las contraseñas no coinciden.";

    // Check if email or username already exists
    if (empty($errors)) {
        $stmt_check = $conn->prepare("SELECT idUser FROM users_data WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            $errors[] = "El email ya está registrado.";
        }
        $stmt_check->close();

        $stmt_check_user = $conn->prepare("SELECT idLogin FROM users_login WHERE usuario = ?");
        $stmt_check_user->bind_param("s", $usuario);
        $stmt_check_user->execute();
        $stmt_check_user->store_result();
        if ($stmt_check_user->num_rows > 0) {
            $errors[] = "El nombre de usuario ya existe.";
        }
        $stmt_check_user->close();
    }

    
    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Insert into users_data
            $stmt_data = $conn->prepare("INSERT INTO users_data (nombre, apellidos, email, telefono, fecha_nacimiento, direccion, sexo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_data->bind_param("sssssss", $nombre, $apellidos, $email, $telefono, $fecha_nacimiento, $direccion, $sexo);
            $stmt_data->execute();
            $idUser = $stmt_data->insert_id; 
            $stmt_data->close();

            if ($idUser) {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $rol = 'user'; 

                
                $stmt_login = $conn->prepare("INSERT INTO users_login (idUser, usuario, password, rol) VALUES (?, ?, ?, ?)");
                $stmt_login->bind_param("isss", $idUser, $usuario, $hashed_password, $rol);
                $stmt_login->execute();
                $stmt_login->close();

                $conn->commit();
                $_SESSION['registration_success'] = "¡Registro completado con éxito! Ahora puedes iniciar sesión.";
                header("Location: ../login.php");
                exit;
            } else {
                throw new Exception("Error al crear el usuario en users_data.");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error en el registro: " . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $_SESSION['registration_errors'] = $errors;
        
        $_SESSION['form_data'] = $_POST;
        header("Location: ../registro.php");
        exit;
    }
} else {
    
    header("Location: ../registro.php");
    exit;
}

$conn->close();
?>
