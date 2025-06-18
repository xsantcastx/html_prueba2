document.addEventListener("DOMContentLoaded", function() {
    fetch("../data/noticias.json")
        .then(response => response.json())
        .then(noticias => {
            const noticiasContainer = document.getElementById("contenedor-noticias");
            noticiasContainer.innerHTML = ""; // Asegura que está vacío antes de llenarlo
            noticias.forEach(noticia => {
                noticiasContainer.innerHTML += `
                    <div class="noticia">
                        <h3>${noticia.titulo}</h3>
                        <p>${noticia.descripcion}</p>
                    </div>
                `;
            });
        })
        .catch(error => console.error("Error al cargar noticias:", error));
});
