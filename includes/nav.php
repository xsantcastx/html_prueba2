<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Determine the current page
$current_page = basename($_SERVER['PHP_SELF']);

// Default navigation for visitors
$nav_links = [
    'index.php' => 'Inicio',
    'noticias.php' => 'Noticias',
    'registro.php' => 'Registro',
    'login.php' => 'Login'
];

// Check if user is logged in
if (isset($_SESSION['idUser'])) {
    // User is logged in
    if (isset($_SESSION['rol'])) {
        if ($_SESSION['rol'] === 'admin') {
            // Admin navigation
            $nav_links = [
                'index.php' => 'Inicio',
                'noticias.php' => 'Noticias',
                'usuarios-administracion.php' => 'Gestión Usuarios',
                'citas-administracion.php' => 'Gestión Citas',
                'noticias-administracion.php' => 'Gestión Noticias',
                'perfil.php' => 'Perfil',
                'php/logout.php' => 'Cerrar Sesión' 
            ];
        } elseif ($_SESSION['rol'] === 'user') {
            // User navigation
            $nav_links = [
                'index.php' => 'Inicio',
                'noticias.php' => 'Noticias',
                'citaciones.php' => 'Mis Citas',
                'perfil.php' => 'Perfil',
                'php/logout.php' => 'Cerrar Sesión'
            ];
        }
    }
}
?>
<nav>
    <ul>
        <?php
        foreach ($nav_links as $link => $label) {
            $active_class = ($current_page == $link) ? 'class="active"' : '';
            echo "<li><a href=\"$link\" $active_class>$label</a></li>";
        }
        ?>
    </ul>
</nav>
<hr> 
