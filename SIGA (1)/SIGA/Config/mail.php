<?php
// Configuración SMTP para el envío de correos del sistema (recuperación de contraseña, etc.)
// Para Gmail: activa la verificación en 2 pasos en tu cuenta y genera una
// "Contraseña de aplicación" en https://myaccount.google.com/apppasswords
// NO uses la contraseña normal de la cuenta de Gmail: Google la rechaza para SMTP.

return [
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'encryption' => 'tls', // 'tls' (puerto 587) o 'ssl' (puerto 465)
    'username'   => 'adsosoft@gmail.com', // Correo de Gmail remitente, ej: siga.portal@gmail.com
    'password'   => 'jmrr olhw ssfk szlc', // Contraseña de aplicación de 16 caracteres (sin espacios)
    'from_email' => '', // Normalmente igual a 'username'
    'from_name'  => 'SIGA Portal',
];
