<?php
/*
Plugin Name: Descargador Asdrubalis
Plugin URI: https://carlos.sanchezdonate.com/curiosidades/descargar-imagenes-carpetas
Description: Descarga Archivos desde URLs especificadas en el backend, respetando la estructura de directorios original sin el dominio.
Version: 1.0
Author: Asdrubal SEO SL
Author URI: https://carlos.sanchezdonate.com/
Text Domain: descargador-asdrubalis
Domain Path: /languages
*/

// Hook para añadir un menú al panel de administración
add_action('admin_menu', 'image_downloader_menu');

function image_downloader_menu() {
    add_menu_page(
        __('Descargador Asdrubalis', 'descargador-asdrubalis'), 
        __('Descargador Asdrubalis', 'descargador-asdrubalis'), 
        'manage_options', 
        'image-downloader', 
        'image_downloader_admin_page'
    );
}

// La página de administración
function image_downloader_admin_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Descargador de Archivos', 'descargador-asdrubalis'); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field('download_images', 'image_downloader_nonce'); ?>
            <textarea name="image_urls" style="width: 100%; height: 100px;"></textarea><br>
            <input type="submit" value="<?php echo esc_attr__('Descargar Archivos', 'descargador-asdrubalis'); ?>" class="button button-primary">
        </form>
    </div>
    <?php
    if (!empty($_POST['image_urls']) && check_admin_referer('download_images', 'image_downloader_nonce')) {
        handle_image_download();
    }
}

// Función para manejar la descarga de Archivos
function handle_image_download() {
    $downloaded_files = [];
    $urls = explode("\n", sanitize_textarea_field($_POST['image_urls']));
    foreach ($urls as $url) {
        $url = trim($url);
        if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($url);
            $path = $parsedUrl['path'];
            $localPath = ABSPATH . $path;

            if (!file_exists(dirname($localPath))) {
                wp_mkdir_p(dirname($localPath));
            }
            file_put_contents($localPath, file_get_contents($url));
            $downloaded_files[] = $localPath; // Registrar el archivo descargado
        }
    }

    // Guardar el registro de descargas en la opción de WordPress
    update_option('recent_downloads', json_encode($downloaded_files));
    echo '<div class="updated"><p>' . esc_html__('Descarga completada.', 'descargador-asdrubalis') . '</p></div>';
}

add_action('admin_menu', 'add_undo_page');

function add_undo_page() {
    add_submenu_page(
        'image-downloader',
        __('Deshacer Descargas', 'descargador-asdrubalis'),
        __('Deshacer', 'descargador-asdrubalis'),
        'manage_options',
        'undo-downloads',
        'undo_downloads_page'
    );
}

function undo_downloads_page() {
    $downloads = json_decode(get_option('recent_downloads', '[]'), true);
    echo '<div class="wrap"><h1>' . esc_html__('Deshacer Descargas', 'descargador-asdrubalis') . '</h1><ul>';
    foreach ($downloads as $file) {
        echo '<li>' . esc_html($file) . ' <a href="' . esc_url(add_query_arg('undo', urlencode($file), menu_page_url('undo-downloads', false))) . '">' . esc_html__('Deshacer', 'descargador-asdrubalis') . '</a></li>';
    }
    echo '</ul></div>';

    if (!empty($_GET['undo']) && current_user_can('manage_options')) {
        $file_to_undo = sanitize_text_field(urldecode($_GET['undo']));
        if (file_exists($file_to_undo)) {
            unlink($file_to_undo); // Elimina el archivo
            // Actualiza la lista de descargas
            $new_downloads = array_filter($downloads, function ($f) use ($file_to_undo) { return $f !== $file_to_undo; });
            update_option('recent_downloads', json_encode($new_downloads));
            echo '<div class="updated"><p>' . esc_html__('Archivo eliminado: ', 'descargador-asdrubalis') . esc_html($file_to_undo) . '</p></div>';
        }
    }
}

?>
