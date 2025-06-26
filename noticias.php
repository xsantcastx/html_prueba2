<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'php/db_connection.php';

$noticias_list = [];
$errors = [];

$sql = "SELECT n.idNoticia, n.titulo, n.imagen, n.texto, n.fecha, ud.nombre as autor_nombre, ud.apellidos as autor_apellidos
        FROM noticias n
        JOIN users_data ud ON n.idUser = ud.idUser
        ORDER BY n.fecha DESC, n.idNoticia DESC";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $noticias_list[] = $row;
    }
} else {
    $errors[] = "Error al cargar las noticias: " . $conn->error;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noticias</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .noticia-article {
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .noticia-article:last-child {
            border-bottom: none;
        }
        .noticia-article img {
            max-width: 100%;
            height: auto;
            margin-bottom: 10px;
            border-radius: 5px;
        }
         .noticia-meta {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <header>
        <h1>Últimas Noticias</h1>
    </header>

    <main>
        <?php
        if (!empty($errors)) {
            echo '<div style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px;"><strong>Errores:</strong><ul>';
            foreach ($errors as $error) echo '<li>' . htmlspecialchars($error) . '</li>';
            echo '</ul></div>';
        }
        ?>

        <?php if (empty($noticias_list) && empty($errors)): ?>
            <p>No hay noticias publicadas por el momento.</p>
        <?php else: ?>
            <?php foreach ($noticias_list as $noticia): ?>
                <article class="noticia-article">
                    <h2><?php echo htmlspecialchars($noticia['titulo']); ?></h2>
                    <p class="noticia-meta">
                        Publicado por: <?php echo htmlspecialchars($noticia['autor_nombre'] . ' ' . $noticia['autor_apellidos']); ?> |
                        Fecha: <?php echo date('d/m/Y', strtotime($noticia['fecha'])); ?>
                    </p>
                    <?php if (!empty($noticia['imagen']) && file_exists($noticia['imagen'])): ?>
                        <img src="<?php echo htmlspecialchars($noticia['imagen']); ?>" alt="<?php echo htmlspecialchars($noticia['titulo']); ?>">
                    <?php else: ?>
                        <!-- Optional: placeholder if image is missing but record exists -->
                        <!-- <img src="images/placeholder_news_default.jpg" alt="Imagen no disponible"> -->
                    <?php endif; ?>
                    <div>
                        <?php
                        // nl2br to respect line breaks in the text, and htmlspecialchars for security
                        echo nl2br(htmlspecialchars($noticia['texto']));
                        ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Mi Sitio Web. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
