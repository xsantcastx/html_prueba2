document.addEventListener("DOMContentLoaded", function() {
    const negocio = [39.808385289253145, -0.1450980783133377]; // Example location (Madrid)
    
    const map = L.map('map').setView(negocio, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    L.marker(negocio).addTo(map).bindPopup("Nuestra ubicación").openPopup();

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            const visitante = [position.coords.latitude, position.coords.longitude];

            L.marker(visitante).addTo(map).bindPopup("Tu ubicación").openPopup();
            fetch('https://api.openrouteservice.org/v2/directions/driving-car/geojson', {
            method: 'POST',
            headers: {
                'Authorization': 'TU_API_KEY_AQUI',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                coordinates: [
                [negocio[1], negocio[0]], // Longitud, Latitud
                [visitante[1], visitante[0]]
                ]
            })
            })
            .then(res => res.json())
            .then(data => {
            const ruta = L.geoJSON(data, {
                style: { color: 'blue', weight: 4 }
            }).addTo(map);
            map.fitBounds(ruta.getBounds());
            })
            .catch(err => console.error("Error al obtener la ruta:", err));

            map.fitBounds(route.getBounds());
        }, () => console.error("No se pudo obtener la ubicación"));
    }
    navigator.permissions.query({ name: "geolocation" }).then(result => {
    if (result.state === "denied") {
        alert("Geolocation is blocked. Please enable it in browser settings.");
    } else {
        navigator.geolocation.getCurrentPosition(
            position => {
                console.log("User location:", position.coords);
            },
            error => {
                console.error("Geolocation error:", error.message);
            }
        );
    }
});

});
