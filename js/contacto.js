document.addEventListener("DOMContentLoaded", function() {
    const negocio = [39.808385289253145, -0.1450980783133377]; // Example location (Madrid)
    
    const map = L.map('map').setView(negocio, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    L.marker(negocio).addTo(map).bindPopup("Nuestra ubicación").openPopup();

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            const visitante = [position.coords.latitude, position.coords.longitude];

            L.marker(visitante).addTo(map).bindPopup("Tu ubicación").openPopup();

            const route = L.polyline([negocio, visitante], { color: 'blue' }).addTo(map);
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
