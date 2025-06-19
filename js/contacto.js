document.addEventListener("DOMContentLoaded", function () {
  const negocio = [39.808385289253145, -0.1450980783133377]; // Moncófar
  const map = L.map('map').setView(negocio, 13);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
  L.marker(negocio).addTo(map).bindPopup("Nuestra ubicación").openPopup();

  function calcularDistancia(lat1, lon1, lat2, lon2) {
    const R = 6371; // km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) ** 2 +
              Math.cos(lat1 * Math.PI / 180) *
              Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon / 2) ** 2;
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  }

  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(position => {
      const visitante = [position.coords.latitude, position.coords.longitude];
      
      L.marker(visitante).addTo(map).bindPopup("Tu ubicación").openPopup();
      
      // Dibujamos la línea recta
      const linea = L.polyline([negocio, visitante], { color: 'blue' }).addTo(map);
      map.fitBounds(linea.getBounds());

      // Calculamos la distancia
      const distancia = calcularDistancia(
        negocio[0], negocio[1],
        visitante[0], visitante[1]
      );
      
      const info = document.getElementById("info-ruta");
      info.textContent = `Distancia aproximada en línea recta: ${distancia.toFixed(2)} km`;

      // Abrir Google Maps
      const enlace = `https://www.google.com/maps/dir/${visitante[0]},${visitante[1]}/${negocio[0]},${negocio[1]}`;
      document.getElementById("abrir-google").onclick = () => {
        window.open(enlace, '_blank');
      };
    }, () => {
      alert("No se pudo obtener tu ubicación.");
    });
  } else {
    alert("Tu navegador no permite obtener la geolocalización.");
  }
});
