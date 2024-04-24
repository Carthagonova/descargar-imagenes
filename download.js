document.getElementById('downloadButton').addEventListener('click', function() {
    fetch('download.php')
        .then(response => response.text())
        .then(data => {
            alert("Descarga completada. Verifica los archivos en el directorio local.");
        })
        .catch(error => {
            console.error('Error durante la descarga:', error);
            alert("Error al descargar las imágenes.");
        });
});
