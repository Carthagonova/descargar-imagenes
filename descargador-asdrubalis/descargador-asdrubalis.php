<?php
/*
Plugin Name: Descargador Asdrubalis
Plugin URI: https://carlos.sanchezdonate.com/curiosidades/descargar-imagenes-carpetas
Description: Descarga imágenes desde URLs especificadas en el backend, respetando la estructura de directorios original sin el dominio.
Version: 1.0
Author: Asdrubal SEO SL
Author URI: https://carlos.sanchezdonate.com/
*/

// Hook para añadir un menú al panel de administración
add_action('admin_menu', 'image_downloader_menu');

function image_downloader_menu() {
    add_menu_page('Descargador Asdrubalis', 'Descargador Asdrubalis', 'manage_options', 'image-downloader', 'image_downloader_admin_page');
}

// La página de administración
function image_downloader_admin_page() {
    ?>
    <div class="wrap">
        <h1>Descargador de Imágenes</h1>
        <form method="post" action="">
            <textarea name="image_urls" style="width: 100%; height: 100px;"></textarea><br>
            <input type="submit" value="Descargar Imágenes" class="button button-primary">
        </form>
    </div>
    <?php
    if (!empty($_POST['image_urls'])) {
        handle_image_download();
    }
}

// Función para manejar la descarga de imágenes
function handle_image_download() {
    $downloaded_files = [];
    $urls = explode("\n", $_POST['image_urls']);
    foreach ($urls as $url) {
        $url = trim($url);
        if (!empty($url)) {
            $parsedUrl = parse_url($url);
            $path = $parsedUrl['path'];
            $localPath = ABSPATH . $path;

            if (!file_exists(dirname($localPath))) {
                mkdir(dirname($localPath), 0777, true);
            }
            file_put_contents($localPath, file_get_contents($url));
            $downloaded_files[] = $localPath; // Registrar el archivo descargado
        }
    }

    // Guardar el registro de descargas en la opción de WordPress
    update_option('recent_downloads', json_encode($downloaded_files));
    echo '<div class="updated"><p>Descarga completada.</p></div>';
}

add_action('admin_menu', 'add_undo_page');

function add_undo_page() {
    add_submenu_page('image-downloader', 'Deshacer Descargas', 'Deshacer', 'manage_options', 'undo-downloads', 'undo_downloads_page');
}

function undo_downloads_page() {
    $downloads = json_decode(get_option('recent_downloads', '[]'), true);
    echo '<div class="wrap"><h1>Deshacer Descargas</h1><ul>';
    foreach ($downloads as $file) {
        echo '<li>' . $file . ' <a href="?page=undo-downloads&undo=' . urlencode($file) . '">Deshacer</a></li>';
    }
    echo '</ul></div>';

    if (!empty($_GET['undo'])) {
        $file_to_undo = $_GET['undo'];
        if (file_exists($file_to_undo)) {
            unlink($file_to_undo); // Elimina el archivo
            // Actualiza la lista de descargas
            $new_downloads = array_filter($downloads, function ($f) use ($file_to_undo) { return $f !== $file_to_undo; });
            update_option('recent_downloads', json_encode($new_downloads));
            echo '<div class="updated"><p>Archivo eliminado: ' . $file_to_undo . '</p></div>';
        }
    }
}

?>
