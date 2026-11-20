/**
 * ARCHIVO: login_app/assets/js/validaciones.js
 * ---------------------------------------------------------------------
 * Un único archivo JavaScript (vanilla) para los dos formularios:
 *   1) Verifica campos vacíos (incluye los que solo tienen espacios).
 *   2) Verifica que la contraseña y su confirmación coincidan (alta).
 *   3) Envía los datos al backend PHP con fetch() + async/await.
 *
 * Recordatorio: esta validación mejora la experiencia de usuario, pero
 * la seguridad real se repite en el servidor (carpeta /api).
 */

'use strict'; // Modo estricto: evita variables globales accidentales.

// Espera a que el HTML esté listo (el script se carga con "defer", por
// lo que el DOM ya existe, pero el listener deja el código más portable).
document.addEventListener('DOMContentLoaded', () => {

    // Detecta qué formulario existe en la página actual.
    const formLogin = document.getElementById('form-login');
    const formRegistro = document.getElementById('form-registro');

    // Zona donde se muestran mensajes al usuario.
    const cajaMensaje = document.getElementById('mensaje');

    // Longitud mínima de contraseña: se lee del atributo minlength del HTML
    // para no duplicar el valor (la fuente de verdad es config.php).
    const campoPassword = document.getElementById('password');
    const MIN_PASSWORD = campoPassword && campoPassword.minLength > 0 ? campoPassword.minLength : 0;

    /**
     * Muestra un mensaje en pantalla.
     * @param {string} texto - Contenido del mensaje.
     * @param {'error'|'exito'} tipo - Define el color.
     */
    function mostrarMensaje(texto, tipo) {
        // textContent (y no innerHTML) evita inyectar HTML ajeno.
        cajaMensaje.textContent = texto;
        cajaMensaje.className = `mensaje ${tipo} visible`;
    }

    /** Oculta el mensaje y limpia los marcadores de error de los inputs. */
    function limpiarEstado(formulario) {
        cajaMensaje.textContent = '';
        cajaMensaje.className = 'mensaje';
        formulario.querySelectorAll('input').forEach((input) => input.classList.remove('invalido'));
    }

    /**
     * Busca campos vacíos (trim() descarta los que solo tienen espacios).
     * Las contraseñas NO se recortan: un espacio puede ser parte de la clave,
     * pero igualmente no pueden estar completamente vacías.
     * @returns {HTMLInputElement|null} El primer campo vacío o null.
     */
    function buscarCampoVacio(formulario) {
        const campos = formulario.querySelectorAll('input[required]');
        for (const campo of campos) {
            const valor = campo.type === 'password' ? campo.value : campo.value.trim();
            if (valor === '') {
                return campo;
            }
        }
        return null;
    }

    /**
     * Envía los datos como JSON al endpoint indicado.
     * @param {string} url - Ruta del endpoint PHP.
     * @param {object} datos - Objeto a serializar.
     * @returns {Promise<{estado:number, cuerpo:object}>}
     */
    async function enviarJson(url, datos) {
        const respuesta = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': datos.csrf_token, // El servidor acepta cabecera o cuerpo.
            },
            body: JSON.stringify(datos),
            credentials: 'same-origin', // Envía la cookie de sesión al mismo sitio.
        });

        // Si el servidor devolviera algo que no es JSON, se evita romper el flujo.
        let cuerpo = {};
        try {
            cuerpo = await respuesta.json();
        } catch {
            cuerpo = { ok: false, mensaje: 'Respuesta inesperada del servidor.' };
        }
        return { estado: respuesta.status, cuerpo };
    }

    /**
     * Manejador común: valida, deshabilita el botón, envía y procesa la respuesta.
     * @param {SubmitEvent} evento
     * @param {HTMLFormElement} formulario
     * @param {string} url - Endpoint destino.
     * @param {function(): object} armarDatos - Construye el JSON a enviar.
     * @param {function(object): void} alExito - Qué hacer si el servidor acepta.
     */
    async function manejarEnvio(evento, formulario, url, armarDatos, alExito) {
        // Frena el envío tradicional (que recargaría la página).
        evento.preventDefault();
        limpiarEstado(formulario);

        // 1) Verificación de campos vacíos.
        const vacio = buscarCampoVacio(formulario);
        if (vacio) {
            vacio.classList.add('invalido');
            vacio.focus();
            mostrarMensaje('Completá todos los campos obligatorios.', 'error');
            return;
        }

        // 2) Verificaciones propias del formulario de alta.
        if (formulario === formRegistro) {
            const pass = document.getElementById('password');
            const confirmar = document.getElementById('confirmar');

            // Largo mínimo.
            if (MIN_PASSWORD && pass.value.length < MIN_PASSWORD) {
                pass.classList.add('invalido');
                pass.focus();
                mostrarMensaje(`La contraseña debe tener al menos ${MIN_PASSWORD} caracteres.`, 'error');
                return;
            }

            // Coincidencia entre contraseña y confirmación.
            if (pass.value !== confirmar.value) {
                confirmar.classList.add('invalido');
                confirmar.focus();
                mostrarMensaje('Las contraseñas no coinciden.', 'error');
                return;
            }
        }

        // 3) Envío al servidor. El botón se bloquea para evitar dobles clics.
        const boton = formulario.querySelector('button[type="submit"]');
        boton.disabled = true;

        try {
            const { cuerpo } = await enviarJson(url, armarDatos());

            if (cuerpo.ok) {
                alExito(cuerpo);
            } else {
                mostrarMensaje(cuerpo.mensaje || 'No se pudo completar la operación.', 'error');
                boton.disabled = false;
            }
        } catch (error) {
            // Falla de red (servidor apagado, sin conexión, etc.).
            mostrarMensaje('No se pudo conectar con el servidor. Verificá que Apache esté activo.', 'error');
            boton.disabled = false;
        }
    }

    // ---------------------------- LOGIN ----------------------------
    if (formLogin) {
        formLogin.addEventListener('submit', (evento) => {
            manejarEnvio(
                evento,
                formLogin,
                'api/login.php',
                // Datos que viajan al backend.
                () => ({
                    csrf_token: document.getElementById('csrf_token').value,
                    correo: document.getElementById('correo').value.trim(),
                    password: document.getElementById('password').value,
                }),
                // Éxito: ir a la pantalla de bienvenida.
                (cuerpo) => {
                    window.location.href = cuerpo.redirigir || 'bienvenida.php';
                }
            );
        });
    }

    // ---------------------------- REGISTRO ----------------------------
    if (formRegistro) {
        formRegistro.addEventListener('submit', (evento) => {
            manejarEnvio(
                evento,
                formRegistro,
                'api/registro.php',
                () => ({
                    csrf_token: document.getElementById('csrf_token').value,
                    nombre: document.getElementById('nombre').value.trim(),
                    apellido: document.getElementById('apellido').value.trim(),
                    correo: document.getElementById('correo').value.trim(),
                    password: document.getElementById('password').value,
                    confirmar: document.getElementById('confirmar').value,
                }),
                // Éxito: vuelve al login mostrando el aviso de alta correcta.
                () => {
                    mostrarMensaje('Usuario creado correctamente. Redirigiendo al inicio de sesión...', 'exito');
                    setTimeout(() => {
                        window.location.href = 'index.php?registro=ok';
                    }, 1200);
                }
            );
        });
    }

    // Quita el marcado de error apenas el usuario vuelve a escribir.
    document.querySelectorAll('input').forEach((input) => {
        input.addEventListener('input', () => input.classList.remove('invalido'));
    });
});
