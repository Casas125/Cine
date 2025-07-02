// Redirigir a login
document.getElementById('usuario')?.addEventListener('click', () => {
    window.location.href = '/cine/sesion.html';
});

// Redirigir a historial
document.getElementById('historial')?.addEventListener('click', () => {
    window.location.href = '/cine/historial.html';
});

// Función para habilitar flechas en cada carrusel
function activarCarrusel(carrusel) {
    const izquierda = carrusel.querySelector('.izquierda');
    const derecha = carrusel.querySelector('.derecha');
    const contenedor = carrusel.querySelector('.contenedor-peliculas');

    izquierda?.addEventListener('click', () => {
        contenedor.scrollBy({ left: -200, behavior: 'smooth' });
    });

    derecha?.addEventListener('click', () => {
        contenedor.scrollBy({ left: 200, behavior: 'smooth' });
    });
}

// Activar carruseles (todos los que existan)
document.querySelectorAll('.carrusel').forEach(carrusel => {
    activarCarrusel(carrusel);
});

// Clic en película
document.querySelectorAll('.pelicula')?.forEach(pelicula => {
    pelicula.addEventListener('click', () => {
        const peliculaInfo = {
            id: pelicula.getAttribute('data-id'),
            titulo: pelicula.getAttribute('alt'),
            imagen: pelicula.getAttribute('src')
        };
        localStorage.setItem('peliculaDetalle', JSON.stringify(peliculaInfo));
        window.location.href = '/cine/detalle_p.html';
    });
});

// Mostrar detalle de película
const detallePelicula = document.getElementById('detallePelicula');
if (detallePelicula) {
    const pelicula = JSON.parse(localStorage.getItem('peliculaDetalle'));
    detallePelicula.innerHTML = `
        <h2>${pelicula.titulo}</h2>
        <img src="${pelicula.imagen}" alt="${pelicula.titulo}" style="width: 300px;">
        <p>Película muy interesante. ¡No te la pierdas!</p>
        <button id="comprarBoletos">Comprar Boletos</button>
    `;
    document.getElementById('comprarBoletos').addEventListener('click', () => {
        localStorage.setItem('peliculaSeleccionada', JSON.stringify(pelicula));
        window.location.href = '/cine/compra.html';
    });
}

// Compra de boletos
const tituloCompra = document.getElementById('peliculaSeleccionada');
const confirmarCompra = document.getElementById('confirmarCompra');
const horarios = document.querySelectorAll('.horario');
const asientosGrid = document.getElementById('asientosGrid');

if (tituloCompra && confirmarCompra && horarios.length > 0 && asientosGrid) {
    let pelicula = JSON.parse(localStorage.getItem('peliculaSeleccionada'));
    let horarioSeleccionado = null;
    let asientoSeleccionado = null;

    tituloCompra.textContent = `Película: ${pelicula.titulo}`;

    horarios.forEach(boton => {
        boton.addEventListener('click', () => {
            horarios.forEach(b => b.classList.remove('seleccionado'));
            boton.classList.add('seleccionado');
            horarioSeleccionado = boton.textContent;
            habilitarConfirmar();
        });
    });

    for (let i = 1; i <= 20; i++) {
        let asiento = document.createElement('div');
        asiento.classList.add('asiento');
        asiento.textContent = i;
        asiento.addEventListener('click', () => {
            document.querySelectorAll('.asiento').forEach(a => a.classList.remove('seleccionado'));
            asiento.classList.add('seleccionado');
            asientoSeleccionado = i;
            habilitarConfirmar();
        });
        asientosGrid.appendChild(asiento);
    }

    function habilitarConfirmar() {
        if (horarioSeleccionado && asientoSeleccionado) {
            confirmarCompra.disabled = false;
        }
    }

    confirmarCompra.addEventListener('click', () => {
        let compras = JSON.parse(localStorage.getItem('comprasRealizadas')) || [];
        compras.push({
            pelicula: pelicula.titulo,
            horario: horarioSeleccionado,
            asiento: asientoSeleccionado
        });
        localStorage.setItem('comprasRealizadas', JSON.stringify(compras));

        alert(`Compra confirmada: ${pelicula.titulo} - ${horarioSeleccionado} - Asiento ${asientoSeleccionado}`);
        window.location.href = '/cine/index.html';
    });
}

// Historial de compras
const historialCompras = document.getElementById('historialCompras');
if (historialCompras) {
    const compras = JSON.parse(localStorage.getItem('comprasRealizadas')) || [];
    compras.forEach(compra => {
        const div = document.createElement('div');
        div.innerHTML = `<p>${compra.pelicula} - ${compra.horario} - Asiento ${compra.asiento}</p>`;
        historialCompras.appendChild(div);
    });
}

// Registro de usuario
document.getElementById('formRegistro')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const nombre = document.getElementById('nombre').value;
    const correo = document.getElementById('correo').value;
    const contrasena = document.getElementById('contrasena').value;

    localStorage.setItem('usuario', JSON.stringify({ nombre, correo, contrasena }));
    alert('Registro exitoso. Inicia sesión.');
    window.location.href = '/cine/sesion.html';
});

// Inicio de sesión
document.getElementById('formLogin')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const correoLogin = document.getElementById('correoLogin').value;
    const contrasenaLogin = document.getElementById('contrasenaLogin').value;

    const usuario = JSON.parse(localStorage.getItem('usuario'));
    if (usuario && usuario.correo === correoLogin && usuario.contrasena === contrasenaLogin) {
        alert('Inicio de sesión exitoso');
        window.location.href = '/cine/index.html';
    } else {
        alert('Correo o contraseña incorrectos');
    }
});
