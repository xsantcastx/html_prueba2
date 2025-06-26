<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['idUser']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php?error=unauthorized");
    exit;
}

require_once 'php/db_connection.php'; // $conn
$adminId = $_SESSION['idUser'];

$errors = [];
$success_message = '';
$all_noticias = [];
$editing_noticia = null; // Store noticia data if in edit mode

// Define upload path
define('UPLOAD_DIR_NOTICIAS', 'uploads/noticias/');
if (!is_dir(UPLOAD_DIR_NOTICIAS)) {
    mkdir(UPLOAD_DIR_NOTICIAS, 0777, true); // Create if not exists
}

// --- Handle News Article CRUD ---

// Create or Update News Article
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['create_noticia']) || isset($_POST['update_noticia']))) {
    $titulo = $conn->real_escape_string(trim($_POST['titulo']));
    $texto = $conn->real_escape_string(trim($_POST['texto'])); // Text can be long
    $fecha = $conn->real_escape_string(trim($_POST['fecha'])); // Should be YYYY-MM-DD
    $imagen_path = ''; // Initialize image path

    // Validation
    if (empty($titulo)) $errors[] = "El título es obligatorio.";
    if (empty($texto)) $errors[] = "El texto de la noticia es obligatorio.";
    if (empty($fecha) || !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $fecha)) $errors[] = "Fecha inválida.";

    // Handle Image Upload
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
        $img_name = basename($_FILES['imagen']['name']);
        $img_tmp_name = $_FILES['imagen']['tmp_name'];
        $img_size = $_FILES['imagen']['size'];
        $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($img_ext, $allowed_ext)) {
            if ($img_size < 5000000) { // Max 5MB
                // Create a unique name for the image to avoid conflicts
                $imagen_path = UPLOAD_DIR_NOTICIAS . uniqid('noticia_', true) . '.' . $img_ext;
                if (!move_uploaded_file($img_tmp_name, $imagen_path)) {
                    $errors[] = "Error al mover el archivo de imagen subido.";
                    $imagen_path = ''; // Reset on error
                }
            } else {
                $errors[] = "El archivo de imagen es demasiado grande (max 5MB).";
            }
        } else {
            $errors[] = "Tipo de archivo de imagen no permitido (solo JPG, JPEG, PNG, GIF).";
        }
    } elseif (isset($_POST['update_noticia']) && !empty($_POST['current_imagen_path'])) {
        // Keep existing image if not uploading a new one during update
        $imagen_path = $_POST['current_imagen_path'];
    } elseif (!isset($_POST['update_noticia'])) { // Required for create if no update
         $errors[] = "La imagen es obligatoria para crear una nueva noticia.";
    }


    if (empty($errors)) {
        if (isset($_POST['create_noticia'])) {
            // Check for unique title on create
            $stmt_check_title = $conn->prepare("SELECT idNoticia FROM noticias WHERE titulo = ?");
            $stmt_check_title->bind_param("s", $titulo);
            $stmt_check_title->execute();
            if ($stmt_check_title->get_result()->num_rows > 0) {
                $errors[] = "Ya existe una noticia con este título.";
            }
            $stmt_check_title->close();

            if (empty($errors) && !empty($imagen_path)) {
                $stmt = $conn->prepare("INSERT INTO noticias (titulo, imagen, texto, fecha, idUser) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $titulo, $imagen_path, $texto, $fecha, $adminId);
                if ($stmt->execute()) {
                    $success_message = "Noticia creada exitosamente.";
                } else {
                    $errors[] = "Error al crear la noticia: " . $stmt->error;
                    if (!empty($imagen_path) && file_exists($imagen_path) && strpos($imagen_path, UPLOAD_DIR_NOTICIAS) === 0) unlink($imagen_path); // Delete uploaded image on DB error
                }
                $stmt->close();
            } elseif(empty($imagen_path) && empty($errors)) {
                 $errors[] = "La imagen es obligatoria para crear una nueva noticia.";
            }

        } elseif (isset($_POST['update_noticia'])) {
            $idNoticia_update = intval($_POST['idNoticia_update']);
            // Check for unique title on update (if changed)
            $stmt_check_title = $conn->prepare("SELECT idNoticia FROM noticias WHERE titulo = ? AND idNoticia != ?");
            $stmt_check_title->bind_param("si", $titulo, $idNoticia_update);
            $stmt_check_title->execute();
            if ($stmt_check_title->get_result()->num_rows > 0) {
                $errors[] = "Ya existe otra noticia con este título.";
            }
            $stmt_check_title->close();

            if (empty($errors)) {
                 // If a new image was uploaded and there's an old one, delete the old one.
                if ($imagen_path !== $_POST['current_imagen_path'] && !empty($_POST['current_imagen_path']) && file_exists($_POST['current_imagen_path'])) {
                     if (strpos($_POST['current_imagen_path'], UPLOAD_DIR_NOTICIAS) === 0) { // Security check
                        unlink($_POST['current_imagen_path']);
                     }
                }
                if(empty($imagen_path) && !empty($_POST['current_imagen_path'])) $imagen_path = $_POST['current_imagen_path'];


                if(empty($imagen_path)) { // Should not happen if logic is correct
                    $errors[] = "Error: La ruta de la imagen no puede estar vacía para la actualización.";
                } else {
                    $stmt = $conn->prepare("UPDATE noticias SET titulo = ?, imagen = ?, texto = ?, fecha = ?, idUser = ? WHERE idNoticia = ?");
                    $stmt->bind_param("ssssii", $titulo, $imagen_path, $texto, $fecha, $adminId, $idNoticia_update);
                    if ($stmt->execute()) {
                        $success_message = "Noticia actualizada exitosamente.";
                    } else {
                        $errors[] = "Error al actualizar la noticia: " . $stmt->error;
                         if ($imagen_path !== $_POST['current_imagen_path'] && !empty($imagen_path) && file_exists($imagen_path) && strpos($imagen_path, UPLOAD_DIR_NOTICIAS) === 0) unlink($imagen_path); // Delete newly uploaded image on DB error
                    }
                    $stmt->close();
                }
            }
        }
    }
     // If there were errors during create/update and a new image was uploaded, delete it.
    if (!empty($errors) && isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK && !empty($imagen_path) && file_exists($imagen_path) && strpos($imagen_path, UPLOAD_DIR_NOTICIAS) === 0) {
        if (isset($_POST['update_noticia']) && $imagen_path === $_POST['current_imagen_path']) {
            // Don't delete if it's the existing image during a failed update without new image upload
        } else {
            unlink($imagen_path);
        }
    }
}


// Delete News Article
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_noticia'])) {
    $idNoticia_delete = intval($_POST['idNoticia_delete']);

    // First, get the image path to delete the file
    $stmt_get_img = $conn->prepare("SELECT imagen FROM noticias WHERE idNoticia = ?");
    $stmt_get_img->bind_param("i", $idNoticia_delete);
    $stmt_get_img->execute();
    $img_result = $stmt_get_img->get_result();
    if ($img_row = $img_result->fetch_assoc()) {
        $image_to_delete = $img_row['imagen'];
    }
    $stmt_get_img->close();

    $stmt = $conn->prepare("DELETE FROM noticias WHERE idNoticia = ?");
    $stmt->bind_param("i", $idNoticia_delete);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $success_message = "Noticia eliminada exitosamente.";
            if (!empty($image_to_delete) && file_exists($image_to_delete) && strpos($image_to_delete, UPLOAD_DIR_NOTICIAS) === 0) {
                unlink($image_to_delete); // Delete the image file
            }
        } else {
            $errors[] = "No se encontró la noticia a eliminar.";
        }
    } else {
        $errors[] = "Error al eliminar la noticia: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch Noticia for Editing (GET request)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['edit_idNoticia'])) {
    $idNoticia_edit = intval($_GET['edit_idNoticia']);
    $stmt = $conn->prepare("SELECT idNoticia, titulo, imagen, texto, fecha FROM noticias WHERE idNoticia = ?");
    $stmt->bind_param("i", $idNoticia_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $editing_noticia = $result->fetch_assoc();
    } else {
        $errors[] = "Noticia para editar no encontrada.";
    }
    $stmt->close();
}


// --- Fetch All News Articles for Listing ---
$sql_fetch_noticias = "SELECT n.idNoticia, n.titulo, n.imagen, LEFT(n.texto, 100) as extracto_texto, n.fecha, ud.usuario as autor_usuario
                       FROM noticias n
                       JOIN users_data ud ON n.idUser = ud.idUser
                       ORDER BY n.fecha DESC, n.idNoticia DESC";
$result_noticias = $conn->query($sql_fetch_noticias);
if ($result_noticias) {
    while ($row = $result_noticias->fetch_assoc()) {
        $all_noticias[] = $row;
    }
} else {
    $errors[] = "Error al cargar la lista de noticias: " . $conn->error;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Noticias</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-section { margin-bottom: 20px; padding: 15px; border: 1px solid #eee; background-color: #f9f9f9; }
        .noticias-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .noticias-table th, .noticias-table td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top;}
        .noticias-table th { background-color: #f2f2f2; }
        .noticias-table img { max-width: 100px; max-height: 100px; object-fit: cover; }
        .action-buttons form, .action-buttons a { display: inline-block; margin-right: 5px; }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <header>
        <h1>Administración de Noticias</h1>
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

        <!-- Crear/Editar Noticia Form -->
        <section class="form-section">
            <h2><?php echo $editing_noticia ? 'Modificar Noticia' : 'Crear Nueva Noticia'; ?></h2>
            <form action="noticias-administracion.php" method="POST" enctype="multipart/form-data">
                <?php if ($editing_noticia): ?>
                    <input type="hidden" name="update_noticia" value="1">
                    <input type="hidden" name="idNoticia_update" value="<?php echo $editing_noticia['idNoticia']; ?>">
                    <input type="hidden" name="current_imagen_path" value="<?php echo htmlspecialchars($editing_noticia['imagen']); ?>">
                <?php else: ?>
                    <input type="hidden" name="create_noticia" value="1">
                <?php endif; ?>

                <div>
                    <label for="titulo">Título:</label>
                    <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($editing_noticia['titulo'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="fecha">Fecha de Publicación:</label>
                    <input type="date" id="fecha" name="fecha" value="<?php echo htmlspecialchars($editing_noticia['fecha'] ?? date('Y-m-d')); ?>" required>
                </div>
                <div>
                    <label for="imagen">Imagen:</label>
                    <input type="file" id="imagen" name="imagen" accept="image/png, image/jpeg, image/gif">
                    <?php if ($editing_noticia && !empty($editing_noticia['imagen'])): ?>
                        <p><small>Imagen actual: <img src="<?php echo htmlspecialchars($editing_noticia['imagen']); ?>" alt="Imagen actual" style="max-height: 50px; vertical-align: middle;"> (<?php echo basename($editing_noticia['imagen']); ?>) Dejar vacío para no cambiar.</small></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="texto">Texto de la Noticia:</label>
                    <textarea id="texto" name="texto" rows="10" required><?php echo htmlspecialchars($editing_noticia['texto'] ?? ''); ?></textarea>
                </div>
                <div>
                    <button type="submit"><?php echo $editing_noticia ? 'Guardar Cambios' : 'Crear Noticia'; ?></button>
                    <?php if ($editing_noticia): ?>
                        <a href="noticias-administracion.php">Cancelar Edición</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <hr>

        <!-- Lista de Noticias -->
        <h2>Noticias Existentes</h2>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imagen</th>
                    <th>Título</th>
                    <th>Extracto</th>
                    <th>Fecha</th>
                    <th>Autor</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($all_noticias)): ?>
                    <?php foreach ($all_noticias as $noticia): ?>
                        <tr>
                            <td><?php echo $noticia['idNoticia']; ?></td>
                            <td><img src="<?php echo htmlspecialchars($noticia['imagen']); ?>" alt="<?php echo htmlspecialchars($noticia['titulo']); ?>"></td>
                            <td><?php echo htmlspecialchars($noticia['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($noticia['extracto_texto']); ?>...</td>
                            <td><?php echo date('d/m/Y', strtotime($noticia['fecha'])); ?></td>
                            <td><?php echo htmlspecialchars($noticia['autor_usuario']); ?></td>
                            <td class="action-buttons">
                                <a href="noticias-administracion.php?edit_idNoticia=<?php echo $noticia['idNoticia']; ?>">Modificar</a>
                                <form action="noticias-administracion.php" method="POST" onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta noticia?');">
                                    <input type="hidden" name="delete_noticia" value="1">
                                    <input type="hidden" name="idNoticia_delete" value="<?php echo $noticia['idNoticia']; ?>">
                                    <button type="submit">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">No hay noticias creadas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Mi Sitio Web. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
