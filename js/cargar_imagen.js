// document.addEventListener("DOMContentLoaded", function () {
//     fetch("../data/galeria.json")
//         .then(response => response.json())
//         .then(imagenes => {
//             const galeriaContainer = document.getElementById("contenedor-imagenes");
//             if (!galeriaContainer) {
//                 console.error("Error: No se encontró el contenedor de imágenes.");
//                 return;
//             }
//             galeriaContainer.innerHTML = "";
//             imagenes.forEach(imagen => {
//                 const imageElement = `
//                     <a href="${imagen.url}" data-lightbox="galeria" data-title="${imagen.titulo}">
//                         <img src="${imagen.url}" alt="${imagen.titulo}">
//                     </a>
//                 `;
//                 galeriaContainer.innerHTML += imageElement;
//                 const image = document.createElement("img");
//                 image.src = imagen.url;
//                 image.alt = imagen.titulo;
//                 image.classList.add("fade-in");
//             });
//             console.log("Imágenes cargadas correctamente.");
//         })
//         .catch(error => console.error("Error al cargar las imágenes:", error));
// });

// function filtrar(categoria) {
//     const imagenes = document.querySelectorAll('#contenedor-imagenes a');
//     imagenes.forEach(a => {
//         const titulo = a.querySelector('img').alt.toLowerCase();
//         a.style.display = titulo.includes(categoria.toLowerCase()) ? "block" : "none";
//     });
// }
document.addEventListener("DOMContentLoaded", function () {
  fetch("../data/galeria.json")
    .then(res => res.json())
    .then(imagenes => {
      const carrusel = document.getElementById("carrusel-imagenes");
      const miniaturasContainer = document.getElementById("miniaturas");

      let index = 0;
      let startX = 0;
      let isDragging = false;
      const total = imagenes.length;

      // Cargar imágenes del carrusel
      imagenes.forEach(img => {
        const imagen = document.createElement("img");
        imagen.src = img.url;
        imagen.alt = img.titulo;
        carrusel.appendChild(imagen);
      });

      // Cargar miniaturas
      imagenes.forEach((img, i) => {
        const mini = document.createElement("img");
        mini.src = img.url;
        mini.alt = img.titulo;
        mini.classList.add("miniatura");
        mini.addEventListener("click", () => {
          index = i;
          updateCarrusel();
        });
        miniaturasContainer.appendChild(mini);
      });

      const updateCarrusel = () => {
        carrusel.style.transform = `translateX(-${index * 100}%)`;
      };

      // Reproducción automática cada 5 segundos
      setInterval(() => {
        index = (index + 1) % total;
        updateCarrusel();
      }, 5000);

      // Botones de navegación
      document.getElementById("prev").addEventListener("click", () => {
        index = (index - 1 + total) % total;
        updateCarrusel();
      });

      document.getElementById("next").addEventListener("click", () => {
        index = (index + 1) % total;
        updateCarrusel();
      });

      // Gesto táctil para móviles
      carrusel.addEventListener("touchstart", e => {
        startX = e.touches[0].clientX;
        isDragging = true;
      });

      carrusel.addEventListener("touchmove", e => {
        if (!isDragging) return;
        const currentX = e.touches[0].clientX;
        const deltaX = currentX - startX;

        if (Math.abs(deltaX) > 50) {
          if (deltaX < 0) {
            index = (index + 1) % total;
          } else {
            index = (index - 1 + total) % total;
          }
          updateCarrusel();
          isDragging = false;
        }
      });

      carrusel.addEventListener("touchend", () => {
        isDragging = false;
      });
    });
});
