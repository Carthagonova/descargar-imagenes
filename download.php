<?php
$urls = [
    "https://www.dominio.com/wp-content/uploads/2023/06/ejemplo-300x193.png?x44721",
    "https://www.dominio.com/wp-content/uploads/2023/06/ejemplgo-300x193.png?x44721",
];

foreach ($urls as $url) {
    if (strpos($url, '/wp-content/uploads/') !== false) {
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'];
        $localPath = 'directorio' . $path;

        // Crea directorio si no existe
        if (!file_exists(dirname($localPath))) {
            mkdir(dirname($localPath), 0777, true);
        }

        // Descarga y guarda la imagen
        file_put_contents($localPath, file_get_contents($url));
    }
}

?>