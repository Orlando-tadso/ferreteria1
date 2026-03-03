<?php
/**
 * MIDDLEWARE - Autenticación y Autorización
 */

/**
 * Validar sesión con token JWT
 */
function validarTokenJWT() {
    $headers = apache_request_headers();
    $token = null;
    
    // Obtener token del header Authorization
    if (isset($headers['Authorization'])) {
        $parts = explode(' ', $headers['Authorization']);
        if (count($parts) === 2 && $parts[0] === 'Bearer') {
            $token = $parts[1];
        }
    }
    
    // Si no hay token en header, intentar desde cookie
    if (!$token && isset($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];
    }
    
    if (!$token) {
        responder(false, null, 'Token no proporcionado', 401);
    }
    
    try {
        $decoded = verificarJWT($token);
        return $decoded;
    } catch (Exception $e) {
        responder(false, null, 'Token inválido o expirado', 401);
    }
}

/**
 * Crear JWT
 */
function crearJWT($usuario_id, $usuario_rol) {
    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    
    $payload = [
        'usuario_id' => $usuario_id,
        'usuario_rol' => $usuario_rol,
        'iat' => time(),
        'exp' => time() + SESSION_TIMEOUT
    ];
    
    $payload_encoded = base64_encode(json_encode($payload));
    
    $signature = base64_encode(hash_hmac('sha256', "$header.$payload_encoded", JWT_SECRET, true));
    
    return "$header.$payload_encoded.$signature";
}

/**
 * Verificar JWT
 */
function verificarJWT($token) {
    $parts = explode('.', $token);
    
    if (count($parts) !== 3) {
        throw new Exception('Estructura de token inválida');
    }
    
    list($header, $payload, $signature) = $parts;
    
    // Verificar firma
    $expected_signature = base64_encode(
        hash_hmac('sha256', "$header.$payload", JWT_SECRET, true)
    );
    
    if ($signature !== $expected_signature) {
        throw new Exception('Firma de token inválida');
    }
    
    // Decodificar payload
    $payload_decoded = json_decode(base64_decode($payload), true);
    
    // Verificar expiración
    if ($payload_decoded['exp'] < time()) {
        throw new Exception('Token expirado');
    }
    
    return $payload_decoded;
}

/**
 * Requerir autenticación
 */
function requerirAutenticacion() {
    return validarTokenJWT();
}

/**
 * Requerir rol de administrador
 */
function requerirAdmin() {
    $usuario = requerirAutenticacion();
    
    if ($usuario['usuario_rol'] !== 'admin') {
        responder(false, null, 'Permisos insuficientes', 403);
    }
    
    return $usuario;
}

/**
 * Requerir rol específico
 */
function requerirRol($rol_requerido) {
    $usuario = requerirAutenticacion();
    
    if ($usuario['usuario_rol'] !== $rol_requerido) {
        responder(false, null, 'Permisos insuficientes', 403);
    }
    
    return $usuario;
}

/**
 * Obtener usuario actual desde token
 */
function obtenerUsuarioActual() {
    try {
        return requerirAutenticacion();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Validar datos requeridos
 */
function validarDatos($datos, $campos_requeridos) {
    $faltantes = [];
    
    foreach ($campos_requeridos as $campo) {
        if (!isset($datos[$campo]) || trim($datos[$campo]) === '') {
            $faltantes[] = $campo;
        }
    }
    
    if (!empty($faltantes)) {
        responder(false, null, 'Campos requeridos: ' . implode(', ', $faltantes), 400);
    }
}

/**
 * Sanitizar entrada
 */
function sanitizar($valor) {
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar URL
 */
function validarURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

?>
