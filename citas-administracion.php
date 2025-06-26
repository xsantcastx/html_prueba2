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
$all_users_list = []; // For dropdown
$selected_user_citas = [];
$selected_userId = null;
$selected_userName = '';


// --- Fetch all users for the dropdown selector ---
$stmt_users = $conn->prepare("SELECT ud.idUser, ud.nombre, ud.apellidos, ul.usuario FROM users_data ud JOIN users_login ul ON ud.idUser = ul.idUser ORDER BY ud.apellidos, ud.nombre");
if ($stmt_users) {
    $stmt_users->execute();
    $result_users = $stmt_users->get_result();
    while ($user = $result_users->fetch_assoc()) {
        $all_users_list[] = $user;
    }
    $stmt_users->close();
} else {
    $errors[] = "Error al cargar la lista de usuarios: " . $conn->error;
}


// --- Handle User Selection (GET request) ---
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['idUser_select'])) {
    $selected_userId = intval($_GET['idUser_select']);
    // Validate $selected_userId exists
    $stmt_check_user = $conn->prepare("SELECT nombre, apellidos FROM users_data WHERE idUser = ?");
    if ($stmt_check_user) {
        $stmt_check_user->bind_param("i", $selected_userId);
        $stmt_check_user->execute();
        $result_user_check = $stmt_check_user->get_result();
        if ($user_details = $result_user_check->fetch_assoc()) {
            $selected_userName = htmlspecialchars($user_details['nombre'] . ' ' . $user_details['apellidos']);
        } else {
            $errors[] = "Usuario seleccionado no válido.";
            $selected_userId = null; // Reset if invalid
        }
        $stmt_check_user->close();
    } else {
        $errors[] = "Error al verificar usuario: " . $conn->error;
        $selected_userId = null;
    }
}


// --- CRUD Operations for Citas (POST requests, require $selected_userId) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['selected_userId_form'])) {
    $selected_userId = intval($_POST['selected_userId_form']);
    // Re-fetch user name for display after POST
    $stmt_get_name = $conn->prepare("SELECT nombre, apellidos FROM users_data WHERE idUser = ?");
    if ($stmt_get_name) {
        $stmt_get_name->bind_param("i", $selected_userId);
        $stmt_get_name->execute();
        $res_name = $stmt_get_name->get_result();
        if($details = $res_name->fetch_assoc()){ $selected_userName = htmlspecialchars($details['nombre'] . ' ' . $details['apellidos']);}
        $stmt_get_name->close();
    }


    // Create Cita for Selected User
    if (isset($_POST['create_cita_admin'])) {
        $fecha_cita_str = trim($_POST['fecha_cita']);
        $hora_cita_str = trim($_POST['hora_cita']);
        $motivo_cita = $conn->real_escape_string(trim($_POST['motivo_cita']));

        if (empty($fecha_cita_str) || empty($hora_cita_str) || empty($motivo_cita)) {
            $errors[] = "Fecha, hora y motivo son obligatorios para crear la cita.";
        } else {
            $fecha_cita_dt_str = $fecha_cita_str . ' ' . $hora_cita_str . ':00';
            // Admin might be able to create past citas for record keeping, or you might enforce future only.
            // For this example, let's allow any date for admin.
            $fecha_cita_sql = (new DateTime($fecha_cita_dt_str))->format('Y-m-d H:i:s');

            $stmt = $conn->prepare("INSERT INTO citas (idUser, fecha_cita, motivo_cita) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iss", $selected_userId, $fecha_cita_sql, $motivo_cita);
                if ($stmt->execute()) {
                    $success_message = "Cita creada para " . $selected_userName . ".";
                } else {
                    $errors[] = "Error al crear la cita: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $errors[] = "Error al preparar la creación de cita: " . $conn->error;
            }
        }
    }

    // Update Cita for Selected User
    if (isset($_POST['update_cita_admin'])) {
        $idCita_update = intval($_POST['idCita_update']);
        $fecha_cita_update_str = trim($_POST['fecha_cita_update']);
        $hora_cita_update_str = trim($_POST['hora_cita_update']);
        $motivo_cita_update = $conn->real_escape_string(trim($_POST['motivo_cita_update']));

        if (empty($fecha_cita_update_str) || empty($hora_cita_update_str) || empty($motivo_cita_update)) {
            $errors[] = "Fecha, hora y motivo son obligatorios para actualizar la cita.";
        } else {
            $fecha_cita_update_sql = (new DateTime($fecha_cita_update_str . ' ' . $hora_cita_update_str . ':00'))->format('Y-m-d H:i:s');
            $stmt_update = $conn->prepare("UPDATE citas SET fecha_cita = ?, motivo_cita = ? WHERE idCita = ? AND idUser = ?");
            if ($stmt_update) {
                $stmt_update->bind_param("ssii", $fecha_cita_update_sql, $motivo_cita_update, $idCita_update, $selected_userId);
                if ($stmt_update->execute()) {
                    $success_message = "Cita ID " . $idCita_update . " actualizada para " . $selected_userName . ".";
                } else {
                    $errors[] = "Error al actualizar la cita: " . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                $errors[] = "Error al preparar la actualización de cita: " . $conn->error;
            }
        }
    }

    // Delete Cita for Selected User
    if (isset($_POST['delete_cita_admin'])) {
        $idCita_delete = intval($_POST['idCita_delete']);
        $stmt_delete = $conn->prepare("DELETE FROM citas WHERE idCita = ? AND idUser = ?");
        if ($stmt_delete) {
            $stmt_delete->bind_param("ii", $idCita_delete, $selected_userId);
            if ($stmt_delete->execute()) {
                $success_message = "Cita ID " . $idCita_delete . " eliminada para " . $selected_userName . ".";
            } else {
                $errors[] = "Error al eliminar la cita: " . $stmt_delete->error;
            }
            $stmt_delete->close();
        } else {
            $errors[] = "Error al preparar la eliminación de cita: " . $conn->error;
        }
    }
}


// --- If a user is selected, fetch their citas ---
if ($selected_userId !== null) {
    $stmt_citas = $conn->prepare("SELECT idCita, fecha_cita, motivo_cita FROM citas WHERE idUser = ? ORDER BY fecha_cita ASC");
    if ($stmt_citas) {
        $stmt_citas->bind_param("i", $selected_userId);
        $stmt_citas->execute();
        $result_citas = $stmt_citas->get_result();
        while ($row = $result_citas->fetch_assoc()) {
            $selected_user_citas[] = $row;
        }
        $stmt_citas->close();
    } else {
        $errors[] = "Error al cargar las citas del usuario seleccionado: " . $conn->error;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Citas</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-section, .user-citas-section { margin-bottom: 20px; padding: 15px; border: 1px solid #eee; background-color: #f9f9f9; }
        .cita-item-admin { background-color: #fff; border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
        .cita-item-admin h4 {margin-top:0;}
        .hidden { display: none; }
        .action-buttons form { display: inline-block; margin-right: 5px; }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <header>
        <h1>Administración de Citas</h1>
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

        <section class="form-section">
            <h2>Seleccionar Usuario</h2>
            <form action="citas-administracion.php" method="GET">
                <label for="idUser_select">Usuario:</label>
                <select name="idUser_select" id="idUser_select" onchange="this.form.submit()">
                    <option value="">-- Seleccione un Usuario --</option>
                    <?php foreach ($all_users_list as $user_item): ?>
                        <option value="<?php echo $user_item['idUser']; ?>" <?php echo ($selected_userId == $user_item['idUser']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user_item['apellidos'] . ', ' . $user_item['nombre'] . ' (' . $user_item['usuario'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit">Ver Citas del Usuario</button></noscript>
            </form>
        </section>

        <?php if ($selected_userId !== null): ?>
            <hr>
            <h2>Gestionar Citas para: <?php echo $selected_userName; ?></h2>

            <!-- Crear Nueva Cita para Usuario Seleccionado -->
            <section class="form-section">
                <h3>Crear Nueva Cita</h3>
                <form action="citas-administracion.php" method="POST">
                    <input type="hidden" name="create_cita_admin" value="1">
                    <input type="hidden" name="selected_userId_form" value="<?php echo $selected_userId; ?>">
                    <div>
                        <label for="fecha_cita">Fecha:</label>
                        <input type="date" id="fecha_cita" name="fecha_cita" required>
                    </div>
                    <div>
                        <label for="hora_cita">Hora:</label>
                        <input type="time" id="hora_cita" name="hora_cita" required>
                    </div>
                    <div>
                        <label for="motivo_cita">Motivo:</label>
                        <textarea id="motivo_cita" name="motivo_cita" rows="3" required></textarea>
                    </div>
                    <div><button type="submit">Crear Cita</button></div>
                </form>
            </section>

            <!-- Lista de Citas del Usuario Seleccionado -->
            <section class="user-citas-section">
                <h3>Citas Programadas</h3>
                <?php if (empty($selected_user_citas)): ?>
                    <p>Este usuario no tiene citas programadas.</p>
                <?php else: ?>
                    <?php foreach ($selected_user_citas as $cita):
                        $fecha_cita_obj = new DateTime($cita['fecha_cita']);
                    ?>
                        <div class="cita-item-admin">
                            <h4>Motivo: <?php echo htmlspecialchars($cita['motivo_cita']); ?></h4>
                            <p><strong>Fecha y Hora:</strong> <?php echo $fecha_cita_obj->format('d/m/Y H:i'); ?> (ID Cita: <?php echo $cita['idCita']; ?>)</p>
                            <div class="action-buttons">
                                <button onclick="toggleAdminUpdateForm('<?php echo $cita['idCita']; ?>')">Modificar</button>
                                <form action="citas-administracion.php" method="POST" onsubmit="return confirm('¿Eliminar esta cita?');">
                                    <input type="hidden" name="delete_cita_admin" value="1">
                                    <input type="hidden" name="selected_userId_form" value="<?php echo $selected_userId; ?>">
                                    <input type="hidden" name="idCita_delete" value="<?php echo $cita['idCita']; ?>">
                                    <button type="submit">Eliminar</button>
                                </form>
                            </div>
                            <!-- Formulario de Modificación (oculto) -->
                            <div id="admin-update-form-<?php echo $cita['idCita']; ?>" class="form-section hidden" style="margin-top:10px; background-color: #eef;">
                                <h5>Modificar Cita ID: <?php echo $cita['idCita']; ?></h5>
                                <form action="citas-administracion.php" method="POST">
                                    <input type="hidden" name="update_cita_admin" value="1">
                                    <input type="hidden" name="selected_userId_form" value="<?php echo $selected_userId; ?>">
                                    <input type="hidden" name="idCita_update" value="<?php echo $cita['idCita']; ?>">
                                    <div>
                                        <label>Nueva Fecha:</label>
                                        <input type="date" name="fecha_cita_update" value="<?php echo $fecha_cita_obj->format('Y-m-d'); ?>" required>
                                    </div>
                                    <div>
                                        <label>Nueva Hora:</label>
                                        <input type="time" name="hora_cita_update" value="<?php echo $fecha_cita_obj->format('H:i'); ?>" required>
                                    </div>
                                    <div>
                                        <label>Nuevo Motivo:</label>
                                        <textarea name="motivo_cita_update" rows="2" required><?php echo htmlspecialchars($cita['motivo_cita']); ?></textarea>
                                    </div>
                                    <div>
                                        <button type="submit">Guardar Cambios</button>
                                        <button type="button" onclick="toggleAdminUpdateForm('<?php echo $cita['idCita']; ?>')">Cancelar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Mi Sitio Web. Todos los derechos reservados.</p>
    </footer>

    <script>
        function toggleAdminUpdateForm(idCita) {
            const form = document.getElementById('admin-update-form-' + idCita);
            if (form) {
                form.classList.toggle('hidden');
            }
        }
    </script>
</body>
</html>
