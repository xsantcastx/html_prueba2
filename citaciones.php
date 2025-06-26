<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['idUser']) || $_SESSION['rol'] !== 'user') {
    
    header("Location: login.php");
    exit;
}

require_once 'php/db_connection.php';
$idUser = $_SESSION['idUser'];
$errors = [];
$success_message = '';
$user_citas = [];


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_cita'])) {
    $fecha_cita_str = trim($_POST['fecha_cita']);
    $hora_cita_str = trim($_POST['hora_cita']);
    $motivo_cita = $conn->real_escape_string(trim($_POST['motivo_cita']));

    if (empty($fecha_cita_str)) $errors[] = "La fecha de la cita es obligatoria.";
    if (empty($hora_cita_str)) $errors[] = "La hora de la cita es obligatoria.";
    if (empty($motivo_cita)) $errors[] = "El motivo de la cita es obligatorio.";

    if (empty($errors)) {
        $fecha_cita_dt_str = $fecha_cita_str . ' ' . $hora_cita_str . ':00';
        $fecha_cita_dt = new DateTime($fecha_cita_dt_str);
        $now = new DateTime();

        if ($fecha_cita_dt <= $now) {
            $errors[] = "La fecha y hora de la cita deben ser en el futuro.";
        } else {
            $fecha_cita_sql = $fecha_cita_dt->format('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO citas (idUser, fecha_cita, motivo_cita) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iss", $idUser, $fecha_cita_sql, $motivo_cita);
                if ($stmt->execute()) {
                    $success_message = "Cita solicitada correctamente.";
                } else {
                    $errors[] = "Error al solicitar la cita: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $errors[] = "Error al preparar la solicitud de cita: " . $conn->error;
            }
        }
    }
}

// Update Cita
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_cita'])) {
    $idCita_update = intval($_POST['idCita']);
    $fecha_cita_update_str = trim($_POST['fecha_cita_update']);
    $hora_cita_update_str = trim($_POST['hora_cita_update']);
    $motivo_cita_update = $conn->real_escape_string(trim($_POST['motivo_cita_update']));

    if (empty($fecha_cita_update_str)) $errors[] = "La nueva fecha de la cita es obligatoria.";
    if (empty($hora_cita_update_str)) $errors[] = "La nueva hora de la cita es obligatoria.";
    if (empty($motivo_cita_update)) $errors[] = "El nuevo motivo de la cita es obligatorio.";

    if (empty($errors)) {
        $fecha_cita_update_dt_str = $fecha_cita_update_str . ' ' . $hora_cita_update_str . ':00';
        $fecha_cita_update_dt = new DateTime($fecha_cita_update_dt_str);
        $now = new DateTime();

        if ($fecha_cita_update_dt <= $now) {
            $errors[] = "La nueva fecha y hora de la cita deben ser en el futuro.";
        } else {
            // Verify the cita belongs to the user and is in the future before updating
            $stmt_check = $conn->prepare("SELECT fecha_cita FROM citas WHERE idCita = ? AND idUser = ?");
            $stmt_check->bind_param("ii", $idCita_update, $idUser);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            if ($cita_check = $result_check->fetch_assoc()) {
                $current_cita_dt = new DateTime($cita_check['fecha_cita']);
                if ($current_cita_dt <= $now) {
                    $errors[] = "No se puede modificar una cita que ya ha pasado.";
                } else {
                    $fecha_cita_update_sql = $fecha_cita_update_dt->format('Y-m-d H:i:s');
                    $stmt_update = $conn->prepare("UPDATE citas SET fecha_cita = ?, motivo_cita = ? WHERE idCita = ? AND idUser = ?");
                    if ($stmt_update) {
                        $stmt_update->bind_param("ssii", $fecha_cita_update_sql, $motivo_cita_update, $idCita_update, $idUser);
                        if ($stmt_update->execute()) {
                            $success_message = "Cita actualizada correctamente.";
                        } else {
                            $errors[] = "Error al actualizar la cita: " . $stmt_update->error;
                        }
                        $stmt_update->close();
                    } else {
                        $errors[] = "Error al preparar la actualización de cita: " . $conn->error;
                    }
                }
            } else {
                $errors[] = "Cita no encontrada o no pertenece al usuario.";
            }
            $stmt_check->close();
        }
    }
}


// Delete Cita
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_cita'])) {
    $idCita_delete = intval($_POST['idCita']);
    $now = new DateTime();

    
    $stmt_check = $conn->prepare("SELECT fecha_cita FROM citas WHERE idCita = ? AND idUser = ?");
    $stmt_check->bind_param("ii", $idCita_delete, $idUser);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($cita_check = $result_check->fetch_assoc()) {
        $current_cita_dt = new DateTime($cita_check['fecha_cita']);
        if ($current_cita_dt <= $now) {
            $errors[] = "No se puede eliminar una cita que ya ha pasado.";
        } else {
            $stmt_delete = $conn->prepare("DELETE FROM citas WHERE idCita = ? AND idUser = ?");
            if ($stmt_delete) {
                $stmt_delete->bind_param("ii", $idCita_delete, $idUser);
                if ($stmt_delete->execute()) {
                    $success_message = "Cita eliminada correctamente.";
                } else {
                    $errors[] = "Error al eliminar la cita: " . $stmt_delete->error;
                }
                $stmt_delete->close();
            } else {
                $errors[] = "Error al preparar la eliminación de cita: " . $conn->error;
            }
        }
    } else {
        $errors[] = "Cita no encontrada o no pertenece al usuario.";
    }
    $stmt_check->close();
}


// --- Fetch User's Citas ---
$stmt_citas = $conn->prepare("SELECT idCita, fecha_cita, motivo_cita FROM citas WHERE idUser = ? ORDER BY fecha_cita ASC");
if ($stmt_citas) {
    $stmt_citas->bind_param("i", $idUser);
    $stmt_citas->execute();
    $result_citas = $stmt_citas->get_result();
    while ($row = $result_citas->fetch_assoc()) {
        $user_citas[] = $row;
    }
    $stmt_citas->close();
} else {
    $errors[] = "Error al cargar tus citas: " . $conn->error;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Citaciones</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .cita-item { background-color: #f9f9f9; border: 1px solid #eee; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
        .cita-item h3 { margin-top: 0; }
        .cita-actions button { margin-right: 5px; font-size: 0.9em; padding: 5px 10px;}
        .form-update-cita { margin-top: 10px; padding:10px; background-color: #eef; border-radius: 4px;}
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="wrapper">
    <?php include 'includes/nav.php'; ?>

    <header>
        <h1>Mis Citaciones</h1>
    </header>

    <main>
        <?php
        if (!empty($errors)) {
            echo '<div class="message-div error">';
            echo '<strong>Errores:</strong><ul>';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul></div>';
        }
        if (!empty($success_message)) {
            echo '<div class="message-div success">';
            echo htmlspecialchars($success_message);
            echo '</div>';
        }
        ?>

        <section id="request-cita" class="form-section">
            <h2>Solicitar Nueva Cita</h2>
            <form action="citaciones.php" method="POST">
                <input type="hidden" name="request_cita" value="1">
                <div>
                    <label for="fecha_cita">Fecha de la Cita:</label>
                    <input type="date" id="fecha_cita" name="fecha_cita" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div>
                    <label for="hora_cita">Hora de la Cita:</label>
                    <input type="time" id="hora_cita" name="hora_cita" required>
                </div>
                <div>
                    <label for="motivo_cita">Motivo de la Cita:</label>
                    <textarea id="motivo_cita" name="motivo_cita" rows="3" required></textarea>
                </div>
                <div>
                    <button type="submit">Solicitar Cita</button>
                </div>
            </form>
        </section>

        <hr>

        <section id="my-citas">
            <h2>Mis Citas Programadas</h2>
            <?php if (empty($user_citas)): ?>
                <p>No tienes citas programadas.</p>
            <?php else: ?>
                <?php foreach ($user_citas as $cita):
                    $fecha_cita_obj = new DateTime($cita['fecha_cita']);
                    $is_past_cita = $fecha_cita_obj < new DateTime();
                ?>
                    <div class="cita-item <?php echo $is_past_cita ? 'past-cita' : 'future-cita'; ?>">
                        <h3>Motivo: <?php echo htmlspecialchars($cita['motivo_cita']); ?></h3>
                        <p><strong>Fecha y Hora:</strong> <?php echo $fecha_cita_obj->format('d/m/Y H:i'); ?></p>

                        <?php if (!$is_past_cita): ?>
                            <div class="cita-actions">
                                <button onclick="toggleUpdateForm('<?php echo $cita['idCita']; ?>')">Modificar</button>
                                <form action="citaciones.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="delete_cita" value="1">
                                    <input type="hidden" name="idCita" value="<?php echo $cita['idCita']; ?>">
                                    <button type="submit" onclick="return confirm('¿Estás seguro de que quieres eliminar esta cita?');">Eliminar</button>
                                </form>
                            </div>

                            <!-- Formulario de Modificación (oculto por defecto) -->
                            <div id="update-form-<?php echo $cita['idCita']; ?>" class="form-update-cita hidden">
                                <h4>Modificar Cita</h4>
                                <form action="citaciones.php" method="POST">
                                    <input type="hidden" name="update_cita" value="1">
                                    <input type="hidden" name="idCita" value="<?php echo $cita['idCita']; ?>">
                                    <div>
                                        <label for="fecha_cita_update_<?php echo $cita['idCita']; ?>">Nueva Fecha:</label>
                                        <input type="date" id="fecha_cita_update_<?php echo $cita['idCita']; ?>" name="fecha_cita_update" value="<?php echo $fecha_cita_obj->format('Y-m-d'); ?>" required min="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div>
                                        <label for="hora_cita_update_<?php echo $cita['idCita']; ?>">Nueva Hora:</label>
                                        <input type="time" id="hora_cita_update_<?php echo $cita['idCita']; ?>" name="hora_cita_update" value="<?php echo $fecha_cita_obj->format('H:i'); ?>" required>
                                    </div>
                                    <div>
                                        <label for="motivo_cita_update_<?php echo $cita['idCita']; ?>">Nuevo Motivo:</label>
                                        <textarea id="motivo_cita_update_<?php echo $cita['idCita']; ?>" name="motivo_cita_update" rows="2" required><?php echo htmlspecialchars($cita['motivo_cita']); ?></textarea>
                                    </div>
                                    <div>
                                        <button type="submit">Guardar Cambios</button>
                                        <button type="button" onclick="toggleUpdateForm('<?php echo $cita['idCita']; ?>')">Cancelar</button>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <p><small><em>Esta cita ya ha pasado.</em></small></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Mi Sitio Web. Todos los derechos reservados.</p>
    </footer>

    <script>
        function toggleUpdateForm(idCita) {
            const form = document.getElementById('update-form-' + idCita);
            if (form) {
                form.classList.toggle('hidden');
            }
        }
    </script>
    </div> 
</body>
</html>
