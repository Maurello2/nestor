<?php
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/JWTHelper.php';
require_once __DIR__ . '/../Models/Mailer.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new Usuario();
    }

    public function showHome() {
        $filePath = __DIR__ . '/../index.html';
        if (file_exists($filePath)) {
            readfile($filePath);
        } else {
            echo "Página de inicio no encontrada.";
        }
    }

    public function showLogin() {
        $filePath = __DIR__ . '/../Views/login.html';
        if (file_exists($filePath)) {
            readfile($filePath);
        } else {
            echo "Login page not found.";
        }
    }

    public function showRecovery() {
        $filePath = __DIR__ . '/../Views/recovery.html';
        if (file_exists($filePath)) {
            readfile($filePath);
        } else {
            echo "Recovery page not found.";
        }
    }

    public function showResetPassword() {
        // Sirve una página con el formulario de restablecimiento de contraseña
        $token = $_GET['token'] ?? '';
        if (empty($token) || !JWTHelper::decode($token)) {
            echo "<h3>El enlace de recuperación es inválido o ha expirado.</h3><a href='./login'>Volver al Inicio de Sesión</a>";
            return;
        }

        // Genera un formulario simple y elegante de restablecimiento de contraseña, con estilo similar a la página de recuperación
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Restablecer Contraseña - SIGA</title>
            <link rel="stylesheet" href="./Views/css/style.css">
            <link rel="stylesheet" href="./Views/css/login.css">
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="./Views/css/vendor/all.min.css">
            <link rel="stylesheet" href="./Views/css/vendor/alertify.min.css">
            <link rel="stylesheet" href="./Views/css/vendor/alertify-bootstrap.min.css">
        </head>
        <body class="login-body">
            <div class="login-container">
                <div class="login-card">
                    <h2 style="text-align: center;">Nueva Contraseña</h2>
                    <p class="subtitle" style="text-align: center;">Ingrese su nueva contraseña de acceso</p>
                    <form id="resetForm">
                        <input type="hidden" id="resetToken" value="<?php echo htmlspecialchars($token); ?>">
                        <div class="form-group">
                            <label>NUEVA CONTRASEÑA</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="newPassword" placeholder="••••••••" required minlength="4">
                            </div>
                        </div>
                        <button type="submit" class="btn-login" style="margin-top: 20px;">Actualizar Contraseña</button>
                    </form>
                </div>
            </div>
            <script src="./Views/js/vendor/jquery-3.6.0.min.js"></script>
            <script src="./Views/js/vendor/alertify.min.js"></script>
            <script>
                $('#resetForm').on('submit', function(e) {
                    e.preventDefault();
                    const password = $('#newPassword').val();
                    const token = $('#resetToken').val();
                    
                    $.post('./api/reset-password', { token: token, password: password }, function(response) {
                        if (response.status === 'success') {
                            alertify.success('Contraseña actualizada con éxito. Redirigiendo...');
                            setTimeout(() => { window.location.href = './login'; }, 2000);
                        } else {
                            alertify.error(response.message || 'Error al restablecer la contraseña');
                        }
                    }, 'json').fail(function() {
                        alertify.error('Error del servidor');
                    });
                });
            </script>
        </body>
        </html>
        <?php
    }

    public function showDashboard() {
        if (!isset($_SESSION['user'])) {
            header('Location: ./login?expired=1');
            exit;
        }

        $user = $_SESSION['user'];
        $filePath = __DIR__ . '/../Views/dashboard.html';

        if (file_exists($filePath)) {
            $html = file_get_contents($filePath);
            
            // Inyecta el nombre, la foto y el rol dinámicos del usuario
            $html = str_replace('Dr. Julian Vance', htmlspecialchars($user['nombre_usuario']), $html);
            $html = str_replace('Superadministrador', htmlspecialchars($user['Tipo_usuario']), $html);
            
            $avatar = !empty($user['foto_usuario']) ? $user['foto_usuario'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['nombre_usuario']) . '&background=0D8ABC&color=fff';
            $html = str_replace('https://ui-avatars.com/api/?name=Julian+Vance&background=0D8ABC&color=fff', $avatar, $html);
            
            echo $html;
        } else {
            echo "Dashboard view not found.";
        }
    }

    public function servePage($params) {
        if (!isset($_SESSION['user'])) {
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax) {
                // Petición AJAX interna del dashboard: el JS detecta el 401 y redirige al login
                http_response_code(401);
                echo "<div class='error-container'><i class='fas fa-exclamation-triangle'></i><p>Acceso no autorizado. Inicie sesión de nuevo.</p></div>";
            } else {
                // Navegación directa a una página protegida: envía al login con el mensaje
                header('Location: ./login?expired=1');
            }
            exit;
        }
        
        $page = $params['page'];
        $page = basename($page); // Evita el ataque de path traversal (recorrido de directorios)

        $filePath = __DIR__ . '/../Views/' . $page . '.html';
        if (file_exists($filePath)) {
            readfile($filePath);
        } else {
            http_response_code(404);
            echo "<div class='error-container'><i class='fas fa-exclamation-triangle'></i><p>Página no encontrada: " . htmlspecialchars($page) . "</p></div>";
        }
    }

    public function login() {
        header('Content-Type: application/json');

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? ''; // Rol enviado desde la pestaña (estudiante, docente, administrador)

        if (empty($username) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Por favor ingrese todos los campos']);
            return;
        }

        try {
            // Intenta consultar por doc_usuario O mail_usuario
            $user = $this->userModel->getByDoc($username);
            if (!$user) {
                $user = $this->userModel->getByEmail($username);
            }

            if (!$user) {
                echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
                return;
            }

            // Verifica el hash de la contraseña usando MD5
            if ($user['clave_usuario'] !== md5($password)) {
                echo json_encode(['status' => 'error', 'message' => 'Contraseña incorrecta']);
                return;
            }

            // Verifica si el estado del usuario está activado
            if ($user['estado_usuario'] !== 'Activado') {
                echo json_encode(['status' => 'error', 'message' => 'El usuario se encuentra inactivo']);
                return;
            }

            // Actualiza el último inicio de sesión
            $this->userModel->updateLogin($user['doc_usuario']);

            // Genera el token JWT
            $payload = [
                'doc_usuario' => $user['doc_usuario'],
                'Tipo_usuario' => $user['Tipo_usuario'],
                'nombre_usuario' => $user['nombre_usuario'],
                'exp' => time() + 3600 // Expiración de 1 hora
            ];
            $token = JWTHelper::encode($payload);

            // Almacena en la sesión
            $_SESSION['jwt'] = $token;
            $_SESSION['user'] = $user;

            echo json_encode([
                'status' => 'success',
                'token' => $token,
                'user' => [
                    'doc_usuario' => $user['doc_usuario'],
                    'nombre_usuario' => $user['nombre_usuario'],
                    'Tipo_usuario' => $user['Tipo_usuario'],
                    'foto_usuario' => $user['foto_usuario']
                ]
            ]);
        } catch (\PDOException $e) {
            error_log('Login DB error: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'No se pudo conectar con la base de datos. Intente más tarde.']);
        }
    }

    public function logout() {
        unset($_SESSION['jwt']);
        unset($_SESSION['user']);
        session_destroy();
        header('Location: ./login');
        exit;
    }

    /**
     * Procesa la solicitud de recuperación de contraseña.
     *
     * Flujo:
     * 1. Valida que se haya enviado un correo electrónico.
     * 2. Verifica que el correo pertenece a un usuario registrado.
     * 3. Genera un token JWT firmado con expiración de 15 minutos.
     * 4. GUARDA el token en la tabla `token_recuperacion` de la BD.
     *    Esto permite validar uso único y revocar el token tras su uso.
     * 5. Construye el enlace de restablecimiento con el token embebido.
     * 6. Registra el correo en `sent_emails.log` (modo simulación).
     *
     * @return void  Responde con JSON {status, message, debug_link}.
     */
    public function recovery() {
        header('Content-Type: application/json');

        // --- Paso 1: Validar que llegó el campo email ---
        $email = $_POST['email'] ?? '';
        if (empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Por favor ingrese su correo electrónico']);
            return;
        }

        try {
            // --- Paso 2: Buscar el usuario por correo electrónico ---
            $user = $this->userModel->getByEmail($email);
            if (!$user) {
                echo json_encode(['status' => 'error', 'message' => 'No se encontró ningún usuario con ese correo electrónico']);
                return;
            }

            // --- Paso 3: Generar el token JWT de recuperación ---
            // El payload incluye el documento del usuario, la acción y una expiración
            // de 900 segundos (15 minutos). La firma HS256 impide su manipulación.
            $expiracion = time() + 900; // 15 minutos desde ahora
            $payload = [
                'doc_usuario' => $user['doc_usuario'],
                'action'      => 'password_recovery',
                'exp'         => $expiracion
            ];
            $token = JWTHelper::encode($payload);

            // --- Paso 4: Persistir el token en la base de datos ---
            // Se guarda en la tabla `token_recuperacion` vinculada a `usuario`
            // mediante clave foránea. El campo `usado` inicia en 0 (no usado).
            // Si el usuario ya tenía un token previo, se elimina antes de insertar.
            $fechaExpiracion = date('Y-m-d H:i:s', $expiracion);
            $this->userModel->saveRecoveryToken($user['doc_usuario'], $token, $fechaExpiracion);

            // --- Paso 5: Construir el enlace de restablecimiento ---
            $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $resetLink = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/SIGA/reset-password?token=' . $token;

            // --- Paso 6: Simular el envío del correo y registrar en log ---
            $subject = 'Recuperación de Contraseña - SIGA Portal';
            $message = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>
                    <h2 style='color: #0D8ABC; text-align: center;'>Restablecimiento de Contraseña</h2>
                    <p>Hola <strong>{$user['nombre_usuario']}</strong>,</p>
                    <p>Hemos recibido una solicitud para restablecer la contraseña de su cuenta en el Portal de SIGA.</p>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='{$resetLink}' style='background-color: #0D8ABC; color: white; padding: 12px 24px;
                           text-decoration: none; border-radius: 5px; font-weight: bold;'>Restablecer Contraseña</a>
                    </p>
                    <p>Este enlace expirará en <strong>15 minutos</strong>.</p>
                    <p>Si no realizó esta solicitud, puede ignorar este correo de forma segura.</p>
                    <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='font-size: 0.8em; color: #777; text-align: center;'>© 2026 Colegio Centro de Instrucción Sistematizada C.E.I.S</p>
                </div>
            </body>
            </html>";

            // --- Paso 7: Enviar el correo real por SMTP (o simular si no hay configuración) ---
            $logFile = __DIR__ . '/../sent_emails.log';
            try {
                Mailer::send($email, $user['nombre_usuario'], $subject, $message);

                $logEntry = '[' . date('Y-m-d H:i:s') . "] TO: $email | SUBJECT: $subject | ENVIADO: SI\n" .
                            "TOKEN_BD: Guardado | EXPIRA: $fechaExpiracion\n" .
                            "---------------------------------------------\n\n";
                file_put_contents($logFile, $logEntry, FILE_APPEND);

                echo json_encode([
                    'status'  => 'success',
                    'message' => 'Se han enviado las instrucciones a su correo electrónico.'
                ]);
            } catch (\Exception $mailError) {
                // Sin credenciales SMTP configuradas (Config/mail.php) u otro fallo de envío:
                // se conserva el token válido en BD y se registra el intento en el log,
                // devolviendo el enlace para pruebas en desarrollo.
                error_log('Mail send error: ' . $mailError->getMessage());

                $logEntry = '[' . date('Y-m-d H:i:s') . "] TO: $email | SUBJECT: $subject | ENVIADO: NO ({$mailError->getMessage()})\n" .
                            "BODY:\n$message\n" .
                            "TOKEN_BD: Guardado | EXPIRA: $fechaExpiracion\n" .
                            "---------------------------------------------\n\n";
                file_put_contents($logFile, $logEntry, FILE_APPEND);

                echo json_encode([
                    'status'     => 'success',
                    'message'    => 'Instrucciones generadas. (Simulación: no hay SMTP configurado, enlace guardado en sent_emails.log)',
                    'debug_link' => $resetLink
                ]);
            }

        } catch (\PDOException $e) {
            error_log('Recovery DB error: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'No se pudo conectar con la base de datos. Intente más tarde.']);
        }
    }

    /**
     * Restablece la contraseña del usuario usando el token de recuperación.
     *
     * Flujo de validación (doble capa de seguridad):
     * 1. Verifica que el token JWT sea criptográficamente válido y no haya
     *    expirado (validación en memoria, sin consultar la BD).
     * 2. Consulta la tabla `token_recuperacion` en la BD para verificar que:
     *    a) El token existe como registro.
     *    b) El campo `usado` sea 0 (no se ha utilizado antes).
     *    c) La fecha `expiracion` sea mayor al momento actual.
     * 3. Si ambas validaciones pasan, actualiza la contraseña con hash MD5.
     * 4. Marca el token como 'usado' (usado = 1) para impedir reutilización,
     *    incluso si el JWT todavía no ha caducado.
     *
     * @return void  Responde con JSON {status, message}.
     */
    public function resetPassword() {
        header('Content-Type: application/json');

        // --- Validar que los parámetros POST llegaron completos ---
        $token       = $_POST['token']    ?? '';
        $newPassword = $_POST['password'] ?? '';

        if (empty($token) || empty($newPassword)) {
            echo json_encode(['status' => 'error', 'message' => 'Parámetros incompletos']);
            return;
        }

        // --- Capa 1: Validación criptográfica del JWT ---
        // Verifica la firma HS256 y que el campo `exp` no haya vencido.
        $decoded = JWTHelper::decode($token);
        if (!$decoded || ($decoded['action'] ?? '') !== 'password_recovery') {
            echo json_encode(['status' => 'error', 'message' => 'El enlace ha expirado o es inválido']);
            return;
        }

        try {
            // --- Capa 2: Validación en base de datos (uso único) ---
            // Busca el registro activo del token. Si findValidToken() retorna false,
            // significa que el token ya fue usado o fue eliminado manualmente.
            $tokenRecord = $this->userModel->findValidToken($token);
            if (!$tokenRecord) {
                echo json_encode(['status' => 'error', 'message' => 'El enlace ya fue utilizado o ha expirado. Solicite uno nuevo.']);
                return;
            }

            // --- Actualizar la contraseña en la tabla `usuario` ---
            $doc = $decoded['doc_usuario'];
            if ($this->userModel->updatePassword($doc, $newPassword)) {

                // --- Invalidar el token para impedir reutilización ---
                // Marca el campo `usado` = 1 en la tabla `token_recuperacion`.
                $this->userModel->invalidateToken($token);

                echo json_encode(['status' => 'success', 'message' => 'Contraseña actualizada correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar la contraseña']);
            }

        } catch (\PDOException $e) {
            error_log('Reset password DB error: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'No se pudo conectar con la base de datos. Intente más tarde.']);
        }
    }

    public static function checkApiAuth() {
        // Autentica la solicitud a la API usando la cabecera o la sesión
        $token = '';
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $token = $matches[1];
            }
        } elseif (isset($_SESSION['jwt'])) {
            $token = $_SESSION['jwt'];
        }

        if (empty($token)) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'No autorizado: Falta token de acceso']);
            exit;
        }

        $decoded = JWTHelper::decode($token);
        if (!$decoded) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'No autorizado: Token inválido o expirado']);
            exit;
        }

        return $decoded;
    }
}
