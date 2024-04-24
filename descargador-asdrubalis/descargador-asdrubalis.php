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
        <h1><?php echo esc_html__('Descargador de Archivos', 'descargador-asdrubalis'); ?> Asdrubal SEO SL</h1>
		<form id="asdruform" method="post" action="">
			<?php wp_nonce_field('download_images', 'image_downloader_nonce'); ?>
			<p>Incluye aqui la URL de todos los elementos que deseas descargar:</p>
			<ol>
				<li>Una URL por linea</li>
				<li>Si no seleccionas ninguna opción posterior se respetará la estructura de la URL a la hora de la descarga</li>
				<li>A menos que se especifique, las imágenes no serán sobreescritas en caso de coincidir</li>
			</ol>
			<textarea name="image_urls" style="width: 100%; height: 100px;"></textarea><br>
			<div id="folder-selector">
				<input type="text" name="custom_folder" id="folder-path" placeholder="<?php echo esc_attr__('Ruta de la Carpeta (Opcional)', 'descargador-asdrubalis'); ?>" style="width: 80%;">
				<button type="button" id="select-folder-btn" class="button"><?php echo esc_html__('Seleccionar Carpeta', 'descargador-asdrubalis'); ?></button>
			</div>
			<br>

			<label>
				<input type="checkbox" name="flatten_folders" value="1"> 
				<?php echo esc_html__('Guardar todos los archivos directamente en la carpeta especificada, sin mantener la estructura de directorios.', 'descargador-asdrubalis'); ?>
			</label><br>
            <label>
                <input type="checkbox" name="appear_in_media_library" value="1">
                <?php echo esc_html__('Aparecer en la biblioteca de medios', 'descargador-asdrubalis'); ?>
            </label><br>
			<label>
				<input type="checkbox" name="overwrite_files" value="1">
				<b>Sobrescribir</b> archivos existentes. <b>¡Cuidado! Esta acción no se puede deshacer.</b>
			</label><br>
			<details>
				<summary class="desplegable-advanced" title="Haz click para opciones avanzadas">Avanzado</summary>
				<div class="advanced-box">
					<div>
						<label for="user_agent">User-Agent (opcional):</label>
						<input type="text" name="user_agent" id="user_agent" placeholder="Mozilla/5.0 (Windows NT 10.0; Win64; x64)" style="width: 100%;">
					</div>
					<div>
						<label for="download_delay">Retardo entre descargas (en segundos, opcional):</label>
						<input type="number" name="download_delay" placeholder="0" id="download_delay" min="0" style="width: 100%;">
					</div>
					<div>
						<label for="max_file_size">Tamaño máximo de un archivo:</label>
						<div class="max_size_asdr">
							<input type="number" name="max_file_size" id="max_file_size" min="0" style="width: 80%;">
							<select name="max_file_size_unit" id="max_file_size_unit" style="width: 20%;">
								<option value="1024">KB</option>
								<option value="1048576" selected>MB</option>
								<option value="1073741824">GB</option>
							</select>
						</div>
					</div>
					<div>
						<label for="max_total_size">Tamaño máximo total de descarga:</label>
						<div class="max_size_asdr">
							<input type="number" name="max_total_size" id="max_total_size" min="0" style="width: 80%;">
							<select name="max_total_size_unit" id="max_total_size_unit" style="width: 20%;">
								<option value="1024">KB</option>
								<option value="1048576" selected>MB</option>
								<option value="1073741824">GB</option>
							</select>
						</div>
					</div>
				</div>
			</details>
			<input type="submit" value="<?php echo esc_attr__('Descargar Archivos', 'descargador-asdrubalis'); ?>" class="button button-primary">	
		</form>



    </div>
    <?php
    if (!empty($_POST['image_urls']) && check_admin_referer('download_images', 'image_downloader_nonce')) {
        handle_image_download();
    }
}

function handle_image_download() {
    $downloaded_files = [];
    $urls = explode("\n", sanitize_textarea_field($_POST['image_urls']));
    $appearInMediaLibrary = isset($_POST['appear_in_media_library']) && $_POST['appear_in_media_library'] == '1';
    $custom_folder = sanitize_textarea_field($_POST['custom_folder']);
    $flatten_folders = isset($_POST['flatten_folders']) && $_POST['flatten_folders'] == '1';
    $overwrite_files = isset($_POST['overwrite_files']) && $_POST['overwrite_files'] == '1';
    $userAgent = sanitize_text_field($_POST['user_agent']) ?: 'Mozilla/5.0 (WordPress/DescargadorAsdrubalis; +https://carlos.sanchezdonate.com)';
    $downloadDelay = intval($_POST['download_delay']);
    $maxFileSize = intval($_POST['max_file_size']) * intval($_POST['max_file_size_unit']);
    $maxTotalSize = intval($_POST['max_total_size']) * intval($_POST['max_total_size_unit']);
    $totalSize = 0;

    foreach ($urls as $url) {
        $url = trim($url);
        if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($url);
            $path = $parsedUrl['path'];

            if ($flatten_folders && !empty($custom_folder)) {
                $filename = basename($path);
                $path = '/' . trim($custom_folder, '/') . '/' . $filename;
            } elseif (!empty($custom_folder)) {
                $path = '/' . trim($custom_folder, '/') . $path;
            }

            $localPath = ABSPATH . $path;

            // Crear directorio si no existe
             if (!file_exists(dirname($localPath))) {
                wp_mkdir_p(dirname($localPath));
            }

            $fileContents = file_get_contents($url, false, $context);

            if ($fileContents !== false && (!$maxFileSize || strlen($fileContents) <= $maxFileSize)) {
                if ($overwrite_files || !file_exists($localPath)) {
                    if (!$maxTotalSize || ($totalSize + strlen($fileContents) <= $maxTotalSize)) {
                        file_put_contents($localPath, $fileContents);
                        if ($appearInMediaLibrary && wp_upload_dir()['basedir'] === dirname($localPath)) {
                            // Registrar en la biblioteca de medios si está en uploads y seleccionado
                            $file_array = ['tmp_name' => $localPath, 'name' => basename($localPath)];
                            $attachment_id = media_handle_sideload($file_array, 0);
                            if (!is_wp_error($attachment_id)) {
                                $downloaded_files[] = wp_get_attachment_url($attachment_id);
                            }
                        } else {
                            $downloaded_files[] = $localPath;
                        }
                        $totalSize += strlen($fileContents); // Sumar al total
                        if ($downloadDelay) sleep($downloadDelay); // Pausar entre descargas
                    } else {
                        echo '<div class="notice notice-warning"><p>' . esc_html__('No se completó la descarga de todos los archivos debido al límite total de tamaño de descarga.', 'descargador-asdrubalis') . '</p></div>';
                        break;
                    }
                }
            } else {
                echo '<div class="notice notice-error"><p>' . sprintf(esc_html__('Archivo "%s" no descargado: excede el tamaño máximo permitido por archivo o es inaccesible.', 'descargador-asdrubalis'), $url) . '</p></div>';
            }
        }
    }

    // Guardar el registro de descargas en la opción de WordPress
    update_option('recent_downloads', json_encode($downloaded_files));
    echo '<div class="updated"><p>' . esc_html__('Descarga completada con éxito.', 'descargador-asdrubalis') . '</p></div>';
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

add_action('wp_ajax_get_directory_contents', 'get_directory_contents');

function get_directory_contents() {
    check_ajax_referer('image_downloader_nonce', 'security');

    $path = sanitize_text_field($_POST['path']);
    $fullPath = ABSPATH . untrailingslashit($path);

    if (!current_user_can('manage_options') || !is_dir($fullPath)) {
        wp_send_json_error(array('message' => 'No se pudo acceder al directorio.'));
    }

    $directories = array();
    $dir = new DirectoryIterator($fullPath);
    foreach ($dir as $fileinfo) {
        if ($fileinfo->isDir() && !$fileinfo->isDot()) {
            $directories[] = $fileinfo->getFilename();
        }
    }

    wp_send_json_success($directories);
}


// Mp encriptar
function image_downloader_scripts() {
    wp_enqueue_script('folder-selector-js', plugin_dir_url(__FILE__) . 'js/folder-selector.js', array(), '1.0', true);
    wp_localize_script('folder-selector-js', 'imageDownloader', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('image_downloader_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'image_downloader_scripts');

function image_downloader_styles() {
    // Asegúrate de que el path a tu archivo CSS es correcto
    wp_enqueue_style('image-downloader-css', plugin_dir_url(__FILE__) . 'css/style.css', array(), '1.0', 'all');
}

add_action('admin_enqueue_scripts', 'image_downloader_styles');




?>
