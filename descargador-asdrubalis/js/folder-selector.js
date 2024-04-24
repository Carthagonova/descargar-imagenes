document.addEventListener('DOMContentLoaded', function() {
    const selectFolderBtn = document.getElementById('select-folder-btn');
    const folderPathInput = document.getElementById('folder-path');
    const folderList = document.createElement('div');
    folderList.className = 'folder-list';
    folderList.style.display = 'none';  // Asegúrate de que el navegador comience oculto

    // Botón para cerrar el navegador de carpetas
    const closeButton = document.createElement('button');
    closeButton.innerHTML = '<span class="dashicons dashicons-no"></span>'; // Usar Dashicon para el botón
    closeButton.className = 'close-button';
    closeButton.onclick = function(event) {
        event.stopPropagation();  // Previene que el evento click se propague al documento
        folderList.style.display = 'none';
    };

    document.body.appendChild(folderList);
    folderList.appendChild(closeButton);

    selectFolderBtn.addEventListener('click', function(event) {
        event.stopPropagation();  // Previene que el evento click inicial se propague y cierre inmediatamente el diálogo
        folderList.style.display = 'block'; // Mostrar el navegador
        openFolderDialog('/');
    });

    // Función para manejar el cierre del navegador de carpetas cuando se hace clic fuera de él
    document.addEventListener('click', function() {
        if (folderList.style.display === 'block') {
            folderList.style.display = 'none';
        }
    });

    function openFolderDialog(path) {
        fetch(imageDownloader.ajaxurl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=get_directory_contents&path=' + encodeURIComponent(path) + '&security=' + imageDownloader.nonce
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayFolders(data.data, path);
            } else {
                alert(data.data.message);
            }
        });
    }

    function displayFolders(folders, currentPath) {
        folderList.innerHTML = '';
        folderList.appendChild(closeButton);  // Asegurar que el botón de cerrar siempre esté presente
        folders.forEach(folder => {
            const folderItem = document.createElement('div');
            folderItem.textContent = folder;
            folderItem.className = 'folder-item';
            folderItem.addEventListener('click', (event) => {
                event.stopPropagation();  // Previene que el evento click se propague y cierre el diálogo
                const newPath = currentPath + folder + '/';
                folderPathInput.value = newPath;
                openFolderDialog(newPath);
            });
            folderList.appendChild(folderItem);
        });
    }
});
