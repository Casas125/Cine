// Validación para registro
const formRegistro = document.getElementById('formRegistro');
if (formRegistro) {
    formRegistro.addEventListener('submit', function (e) {
        e.preventDefault();

        const nombre = document.getElementById('nombre').value.trim();
        const correo = document.getElementById('correo').value.trim();
        const contrasena = document.getElementById('contrasena').value.trim();

        if (nombre === '' || correo === '' || contrasena === '') {
            alert('Por favor, completa todos los campos.');
        } else {
            alert('Registro exitoso.');
            // Aquí podrías redirigir o guardar los datos.
            formRegistro.reset();
        }
    });
}

// Validación para login
const formLogin = document.getElementById('formLogin');
if (formLogin) {
    formLogin.addEventListener('submit', function (e) {
        e.preventDefault();

        const correo = document.getElementById('correoLogin').value.trim();
        const contrasena = document.getElementById('contrasenaLogin').value.trim();

        if (correo === '' || contrasena === '') {
            alert('Por favor, completa todos los campos.');
        } else {
            alert('Inicio de sesión exitoso.');
            // Aquí podrías redirigir a otra página.
            formLogin.reset();
        }
    });
}
