document.addEventListener("DOMContentLoaded", function () {
    fetch("../data/galeria.json")
        .then(response => response.json())
        .then(imagenes => {
            const galeriaContainer = document.getElementById("contenedor-imagenes");
            if (!galeriaContainer) {
                console.error("Error: No se encontró el contenedor de imágenes.");
                return;
            }

            // Limpia el contenido antes de agregar nuevas imágenes
            galeriaContainer.innerHTML = "";

            imagenes.forEach(imagen => {
                const imageElement = `
                    <a href="${imagen.url}" data-lightbox="galeria" data-title="${imagen.titulo}">
                        <img src="${imagen.url}" alt="${imagen.titulo}">
                    </a>
                `;
                galeriaContainer.innerHTML += imageElement;
            });

            console.log("Imágenes cargadas correctamente.");
        })
        .catch(error => console.error("Error al cargar las imágenes:", error));
});
