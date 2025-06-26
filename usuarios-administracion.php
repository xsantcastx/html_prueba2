<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['idUser']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php?error=unauthorized");
    exit;
}

require_once 'php/db_connection.php';

$errors = [];
$success_message = '';
$all_users = []; // To store all users for listing

// --- Handle Admin Actions ---

// Create New User
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_user'])) {
    
    $nombre = $conn->real_escape_string(trim($_POST['nombre']));
    $apellidos = $conn->real_escape_string(trim($_POST['apellidos']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $telefono = $conn->real_escape_string(trim($_POST['telefono']));
    $fecha_nacimiento = $conn->real_escape_string(trim($_POST['fecha_nacimiento']));
    $direccion = $conn->real_escape_string(trim($_POST['direccion']));
    $sexo = $conn->real_escape_string(trim($_POST['sexo']));
    $usuario = $conn->real_escape_string(trim($_POST['usuario']));
    $password = $_POST['password_new']; // Will be hashed
    $rol_new = $conn->real_escape_string(trim($_POST['rol_new']));

    
    if (empty($nombre)) $errors[] = "El nombre es obligatorio.";
    if (empty($apellidos)) $errors[] = "Los apellidos son obligatorios.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email inválido o vacío.";
    if (empty($telefono)) $errors[] = "El teléfono es obligatorio.";
    if (empty($fecha_nacimiento) || !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $fecha_nacimiento)) $errors[] = "Fecha de nacimiento inválida.";
    if (empty($usuario)) $errors[] = "El nombre de usuario es obligatorio.";
    if (empty($password)) $errors[] = "La contraseña es obligatoria.";
    if (empty($rol_new) || !in_array($rol_new, ['user', 'admin'])) $errors[] = "Rol inválido.";

    
    if (empty($errors)) {
        $stmt_check = $conn->prepare("SELECT idUser FROM users_data WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) $errors[] = "El email ya está registrado.";
        $stmt_check->close();

        $stmt_check_user = $conn->prepare("SELECT idLogin FROM users_login WHERE usuario = ?");
        $stmt_check_user->bind_param("s", $usuario);
        $stmt_check_user->execute();
        if ($stmt_check_user->get_result()->num_rows > 0) $errors[] = "El nombre de usuario ya existe.";
        $stmt_check_user->close();
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $stmt_data = $conn->prepare("INSERT INTO users_data (nombre, apellidos, email, telefono, fecha_nacimiento, direccion, sexo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_data->bind_param("sssssss", $nombre, $apellidos, $email, $telefono, $fecha_nacimiento, $direccion, $sexo);
            $stmt_data->execute();
            $idUser_new = $stmt_data->insert_id;
            $stmt_data->close();

            if ($idUser_new) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_login = $conn->prepare("INSERT INTO users_login (idUser, usuario, password, rol) VALUES (?, ?, ?, ?)");
                $stmt_login->bind_param("isss", $idUser_new, $usuario, $hashed_password, $rol_new);
                $stmt_login->execute();
                $stmt_login->close();
                $conn->commit();
                $success_message = "Usuario '" . htmlspecialchars($usuario) . "' creado exitosamente.";
            } else {
                throw new Exception("Error al crear datos personales del usuario.");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error al crear usuario: " . $e->getMessage();
        }
    }
}

// Update Existing User
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    $idUser_edit = intval($_POST['idUser_edit']);
    $nombre_edit = $conn->real_escape_string(trim($_POST['nombre_edit']));
    $apellidos_edit = $conn->real_escape_string(trim($_POST['apellidos_edit']));
    $email_edit = $conn->real_escape_string(trim($_POST['email_edit']));
    $telefono_edit = $conn->real_escape_string(trim($_POST['telefono_edit']));
    $fecha_nacimiento_edit = $conn->real_escape_string(trim($_POST['fecha_nacimiento_edit']));
    $direccion_edit = $conn->real_escape_string(trim($_POST['direccion_edit']));
    $sexo_edit = $conn->real_escape_string(trim($_POST['sexo_edit']));
    $usuario_edit = $conn->real_escape_string(trim($_POST['usuario_edit']));
    $rol_edit = $conn->real_escape_string(trim($_POST['rol_edit']));
    $password_edit = $_POST['password_edit']; 

    
    if (empty($nombre_edit)) $errors[] = "El nombre (editado) es obligatorio.";
    
    if (empty($email_edit) || !filter_var($email_edit, FILTER_VALIDATE_EMAIL)) $errors[] = "Email (editado) inválido o vacío.";
    if (empty($usuario_edit)) $errors[] = "Nombre de usuario (editado) es obligatorio.";
    if (empty($rol_edit) || !in_array($rol_edit, ['user', 'admin'])) $errors[] = "Rol (editado) inválido.";
     if ($rol_edit === 'admin' && $idUser_edit === $_SESSION['idUser'] && $rol_edit !== $_SESSION['rol']) {
        
        $stmt_check_self_role = $conn->prepare("SELECT rol FROM users_login WHERE idUser = ?");
        $stmt_check_self_role->bind_param("i", $idUser_edit);
        $stmt_check_self_role->execute();
        $current_db_rol = $stmt_check_self_role->get_result()->fetch_assoc()['rol'];
        $stmt_check_self_role->close();
        if ($current_db_rol === 'admin' && $rol_edit === 'user'){
             $errors[] = "Un administrador no puede cambiar su propio rol a 'user'.";
        }
    }


    
    if (empty($errors)) {
        
        $stmt_check_email = $conn->prepare("SELECT idUser FROM users_data WHERE email = ? AND idUser != ?");
        $stmt_check_email->bind_param("si", $email_edit, $idUser_edit);
        $stmt_check_email->execute();
        if ($stmt_check_email->get_result()->num_rows > 0) $errors[] = "El email (editado) ya está registrado por otro usuario.";
        $stmt_check_email->close();

        
        $stmt_check_user = $conn->prepare("SELECT idLogin FROM users_login WHERE usuario = ? AND idUser != ?");
        $stmt_check_user->bind_param("si", $usuario_edit, $idUser_edit);
        $stmt_check_user->execute();
        if ($stmt_check_user->get_result()->num_rows > 0) $errors[] = "El nombre de usuario (editado) ya existe.";
        $stmt_check_user->close();
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $stmt_update_data = $conn->prepare("UPDATE users_data SET nombre = ?, apellidos = ?, email = ?, telefono = ?, fecha_nacimiento = ?, direccion = ?, sexo = ? WHERE idUser = ?");
            $stmt_update_data->bind_param("sssssssi", $nombre_edit, $apellidos_edit, $email_edit, $telefono_edit, $fecha_nacimiento_edit, $direccion_edit, $sexo_edit, $idUser_edit);
            $stmt_update_data->execute();
            $stmt_update_data->close();

            $sql_update_login = "UPDATE users_login SET usuario = ?, rol = ?";
            $params_login = [$usuario_edit, $rol_edit];
            $types_login = "ss";

            if (!empty($password_edit)) {
                $hashed_password_edit = password_hash($password_edit, PASSWORD_DEFAULT);
                $sql_update_login .= ", password = ?";
                $params_login[] = $hashed_password_edit;
                $types_login .= "s";
            }
            $sql_update_login .= " WHERE idUser = ?";
            $params_login[] = $idUser_edit;
            $types_login .= "i";

            $stmt_update_login = $conn->prepare($sql_update_login);
            $stmt_update_login->bind_param($types_login, ...$params_login);
            $stmt_update_login->execute();
            $stmt_update_login->close();

            $conn->commit();
            $success_message = "Usuario ID " . $idUser_edit . " actualizado exitosamente.";

            
            if ($idUser_edit == $_SESSION['idUser']) {
                 $_SESSION['usuario'] = $usuario_edit; 
                
            }

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error al actualizar usuario: " . $e->getMessage();
        }
    }
}

// Delete User
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $idUser_delete = intval($_POST['idUser_delete']);

    if ($idUser_delete === $_SESSION['idUser']) {
        $errors[] = "No puedes eliminar tu propia cuenta de administrador.";
    } else {
        

        $conn->begin_transaction();
        try {
            
            $stmt_delete = $conn->prepare("DELETE FROM users_data WHERE idUser = ?");
            $stmt_delete->bind_param("i", $idUser_delete);
            $stmt_delete->execute();

            if ($stmt_delete->affected_rows > 0) {
                $conn->commit();
                $success_message = "Usuario ID " . $idUser_delete . " y todos sus datos relacionados han sido eliminados exitosamente.";
            } else {
                
                $conn->rollback();
                $errors[] = "No se encontró el usuario a eliminar o ya fue eliminado (ID: " . $idUser_delete . ").";
            }
            $stmt_delete->close();
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            
            $errors[] = "Error al eliminar usuario: " . $e->getMessage();
        }
    }
}


$sql_fetch_users = "SELECT ud.idUser, ud.nombre, ud.apellidos, ud.email, ud.telefono, ud.fecha_nacimiento, ud.direccion, ud.sexo, ul.usuario, ul.rol
                    FROM users_data ud
                    JOIN users_login ul ON ud.idUser = ul.idUser
                    ORDER BY ud.apellidos, ud.nombre";
$result_users = $conn->query($sql_fetch_users);
if ($result_users) {
    while ($row = $result_users->fetch_assoc()) {
        $all_users[] = $row;
    }
} else {
    $errors[] = "Error al cargar la lista de usuarios: " . $conn->error;
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Usuarios</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .user-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .user-table th, .user-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .user-table th { background-color: #f2f2f2; }
        .user-table tr:nth-child(even) { background-color: #f9f9f9; }
        .action-buttons form { display: inline-block; margin-right: 5px; }
        .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 5px; }
        .close-button { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close-button:hover, .close-button:focus { color: black; text-decoration: none; cursor: pointer; }
        .form-section { margin-bottom: 20px; padding: 15px; border: 1px solid #eee; background-color: #f9f9f9; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'includes/nav.php'; ?>

    <header>
        <h1>Administración de Usuarios</h1>
    </header>

    <main>
        <?php
        if (!empty($errors)) {
            echo '<div class="message-div error"><strong>Errores:</strong><ul>';
            foreach ($errors as $error) echo '<li>' . htmlspecialchars($error) . '</li>';
            echo '</ul></div>';
        }
        if (!empty($success_message)) {
            echo '<div class="message-div success">' . htmlspecialchars($success_message) . '</div>';
        }
        ?>

        <!-- Crear Nuevo Usuario -->
        <section class="form-section">
            <h2>Crear Nuevo Usuario</h2>
            <form action="usuarios-administracion.php" method="POST">
                <input type="hidden" name="create_user" value="1">
                <div><label>Nombre: <input type="text" name="nombre" required></label></div>
                <div><label>Apellidos: <input type="text" name="apellidos" required></label></div>
                <div><label>Email: <input type="email" name="email" required></label></div>
                <div><label>Teléfono: <input type="text" name="telefono" required></label></div>
                <div><label>Fecha Nacimiento: <input type="date" name="fecha_nacimiento" required></label></div>
                <div><label>Dirección: <textarea name="direccion"></textarea></label></div>
                <div>
                    <label>Sexo:
                        <select name="sexo">
                            <option value="masculino">Masculino</option>
                            <option value="femenino">Femenino</option>
                            <option value="otro">Otro</option>
                        </select>
                    </label>
                </div>
                <div><label>Nombre de Usuario: <input type="text" name="usuario" required></label></div>
                <div><label>Contraseña: <input type="password" name="password_new" required></label></div>
                <div>
                    <label>Rol:
                        <select name="rol_new" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </label>
                </div>
                <div><button type="submit">Crear Usuario</button></div>
            </form>
        </section>

        <hr>

        
        <h2>Lista de Usuarios Existentes</h2>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Nombre Completo</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($all_users)): ?>
                    <?php foreach ($all_users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['idUser']); ?></td>
                            <td><?php echo htmlspecialchars($user['usuario']); ?></td>
                            <td><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellidos']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['telefono']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($user['rol'])); ?></td>
                            <td class="action-buttons">
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)">Modificar</button>
                                <?php if ($_SESSION['idUser'] != $user['idUser']): // Admin cannot delete themselves ?>
                                <form action="usuarios-administracion.php" method="POST" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este usuario? Esta acción es irreversible.');">
                                    <input type="hidden" name="delete_user" value="1">
                                    <input type="hidden" name="idUser_delete" value="<?php echo $user['idUser']; ?>">
                                    <button type="submit">Eliminar</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">No hay usuarios registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Modal para Modificar Usuario -->
        <div id="editUserModal" class="modal">
            <div class="modal-content">
                <span class="close-button" onclick="closeEditModal()">&times;</span>
                <h2>Modificar Usuario</h2>
                <form id="editUserForm" action="usuarios-administracion.php" method="POST">
                    <input type="hidden" name="update_user" value="1">
                    <input type="hidden" id="edit_idUser" name="idUser_edit">

                    <div><label>Nombre: <input type="text" id="edit_nombre" name="nombre_edit" required></label></div>
                    <div><label>Apellidos: <input type="text" id="edit_apellidos" name="apellidos_edit" required></label></div>
                    <div><label>Email: <input type="email" id="edit_email" name="email_edit" required></label></div>
                    <div><label>Teléfono: <input type="text" id="edit_telefono" name="telefono_edit" required></label></div>
                    <div><label>Fecha Nacimiento: <input type="date" id="edit_fecha_nacimiento" name="fecha_nacimiento_edit" required></label></div>
                    <div><label>Dirección: <textarea id="edit_direccion" name="direccion_edit"></textarea></label></div>
                    <div>
                        <label>Sexo:
                            <select id="edit_sexo" name="sexo_edit">
                                <option value="masculino">Masculino</option>
                                <option value="femenino">Femenino</option>
                                <option value="otro">Otro</option>
                            </select>
                        </label>
                    </div>
                    <div><label>Nombre de Usuario: <input type="text" id="edit_usuario" name="usuario_edit" required></label></div>
                    <div>
                        <label>Rol:
                            <select id="edit_rol" name="rol_edit" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </label>
                    </div>
                    <div><label>Nueva Contraseña (opcional): <input type="password" name="password_edit"></label>
                        <small>Dejar en blanco para no cambiar la contraseña.</small>
                    </div>
                    <div><button type="submit">Guardar Cambios</button></div>
                </form>
            </div>
        </div>

    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Mi Sitio Web. Todos los derechos reservados.</p>
    </footer>

    <script>
        const modal = document.getElementById('editUserModal');
        const form = document.getElementById('editUserForm');

        function openEditModal(userData) {
            form.reset(); 
            document.getElementById('edit_idUser').value = userData.idUser;
            document.getElementById('edit_nombre').value = userData.nombre;
            document.getElementById('edit_apellidos').value = userData.apellidos;
            document.getElementById('edit_email').value = userData.email;
            document.getElementById('edit_telefono').value = userData.telefono;
            document.getElementById('edit_fecha_nacimiento').value = userData.fecha_nacimiento;
            document.getElementById('edit_direccion').value = userData.direccion || '';
            document.getElementById('edit_sexo').value = userData.sexo;
            document.getElementById('edit_usuario').value = userData.usuario;
            document.getElementById('edit_rol').value = userData.rol;
            modal.style.display = "block";
        }

        function closeEditModal() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>
</div>
</body>
</html>
<?php $conn->close(); ?>
