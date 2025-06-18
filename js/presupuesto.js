document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById("presupuesto-form");
    const producto = document.getElementById("producto");
    const plazo = document.getElementById("plazo");
    const extras = document.querySelectorAll(".extra");
    const totalDisplay = document.getElementById("total");

    function validarCampo(campo, regex, maxLength) {
        const mensajeError = document.createElement("span");
        mensajeError.style.color = "red";
        mensajeError.style.fontSize = "12px";
        mensajeError.style.display = "block";
        campo.parentNode.appendChild(mensajeError);

        campo.addEventListener("input", function() {
            if (this.value.length > maxLength) {
                this.value = this.value.substring(0, maxLength);
            }

            if (regex.test(this.value)) {
                this.style.border = "2px solid green";
                mensajeError.textContent = "";
            } else {
                this.style.border = "2px solid red";
                mensajeError.textContent = "Formato incorrecto";
            }
        });
    }

    validarCampo(document.getElementById("nombre"), /^[A-Za-z]{1,15}$/, 15);
    validarCampo(document.getElementById("apellidos"), /^[A-Za-z ]{1,40}$/, 40);
    validarCampo(document.getElementById("telefono"), /^[0-9]{1,9}$/, 9);
    validarCampo(document.getElementById("email"), /^[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/, 50);

    function calcularPresupuesto() {
        let total = parseInt(producto.value) || 0;
        let descuento = (parseInt(plazo.value) < 30) ? 0.1 * total : 0;

        extras.forEach(extra => {
            if (extra.checked) total += parseInt(extra.value);
        });

        total -= descuento;
        totalDisplay.textContent = `Total: ${total}€`;
    }

    producto.addEventListener("change", calcularPresupuesto);
    plazo.addEventListener("input", calcularPresupuesto);
    extras.forEach(extra => extra.addEventListener("change", calcularPresupuesto));

    form.addEventListener("submit", function(event) {
        event.preventDefault();

        const nombre = document.getElementById("nombre");
        const apellidos = document.getElementById("apellidos");
        const telefono = document.getElementById("telefono");
        const email = document.getElementById("email");
        const condiciones = document.getElementById("condiciones");

        const campos = [nombre, apellidos, telefono, email];
        let valido = true;

        campos.forEach(campo => {
            if (campo.style.border === "2px solid red") {
                valido = false;
            }
        });

        if (!valido) {
            alert("Por favor, corrige los campos resaltados antes de enviar.");
            return;
        }

        if (!condiciones.checked) {
            alert("Debes aceptar las condiciones.");
            return;
        }

        alert("Formulario enviado correctamente.");
    });
});
