document.addEventListener("DOMContentLoaded", function () {
  const negocio = [39.808385289253145, -0.1450980783133377]; // Tu dirección
  const map = L.map('map').setView(negocio, 13);

  // Carga base de mapa
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
  L.marker(negocio).addTo(map).bindPopup("Nuestra ubicación").openPopup();

  // Función para calcular distancia entre dos puntos
  function calcularDistancia(lat1, lon1, lat2, lon2) {
    const R = 6371; // radio terrestre en km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a =
      Math.sin(dLat / 2) ** 2 +
      Math.cos(lat1 * Math.PI / 180) *
      Math.cos(lat2 * Math.PI / 180) *
      Math.sin(dLon / 2) ** 2;

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  }

  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      position => {
        const visitante = [position.coords.latitude, position.coords.longitude];

        // Marcador del visitante
        L.marker(visitante).addTo(map).bindPopup("Tu ubicación").openPopup();

        // Línea entre ambos puntos
        const linea = L.polyline([negocio, visitante], { color: 'blue' }).addTo(map);
        map.fitBounds(linea.getBounds());

        // Distancia calculada
        const distancia = calcularDistancia(
          negocio[0], negocio[1],
          visitante[0], visitante[1]
        );

        document.getElementById("info-ruta").textContent =
          `Distancia aproximada en línea recta: ${distancia.toFixed(2)} km`;

        // Enlace a Google Maps con la ruta
        const enlace = `https://www.google.com/maps/dir/${visitante[0]},${visitante[1]}/${negocio[0]},${negocio[1]}`;
        document.getElementById("abrir-google").onclick = () => {
          window.open(enlace, '_blank');
        };
      },
      () => {
        alert("No se pudo obtener tu ubicación.");
      }
    );
  } else {
    alert("Tu navegador no permite obtener geolocalización.");
  }
});
