<?php
require_once __DIR__ . '/../config/configuracion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/funciones.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('IMPULSASESS');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

// Cierre por inactividad
if (isset($_SESSION['uid'])) {
    if (time() - ($_SESSION['ultimo_acceso'] ?? 0) > SESION_MAX_INACTIVIDAD) {
        session_unset(); session_destroy(); session_start();
        flash('warning', 'Tu sesión se cerró por inactividad. Inicia sesión otra vez.');
    } else {
        $_SESSION['ultimo_acceso'] = time();
    }
}

/** Usuario autenticado (se vuelve a consultar para respetar cuentas desactivadas). */
function usuario_actual(): ?array
{
    static $usuario = false;
    if ($usuario !== false) return $usuario;
    $usuario = null;
    if (!empty($_SESSION['uid'])) {
        $st = db()->prepare("SELECT id_usuario, nombre_completo, correo, rol, departamento, ciudad FROM usuarios WHERE id_usuario=? AND estado='activo'");
        $st->execute([$_SESSION['uid']]);
        $usuario = $st->fetch() ?: null;
        if (!$usuario) { session_unset(); }
    }
    return $usuario;
}

function iniciar_sesion_usuario(array $u): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id_usuario'];
    $_SESSION['ultimo_acceso'] = time();
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function cerrar_sesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $c = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
    }
    session_destroy();
}

function ruta_panel(string $rol): string
{
    $ruta = ['administrador' => 'administrador/dashboard.php', 'emprendedor' => 'emprendedor/dashboard.php', 'colaborador' => 'colaborador/dashboard.php'][$rol] ?? 'index.php';
    return existe_ruta($ruta) ? $ruta : 'index.php';
}

function requiere_login(): array
{
    $u = usuario_actual();
    if (!$u) {
        flash('warning', 'Inicia sesión para continuar.');
        $destino = ltrim(substr($_SERVER['REQUEST_URI'] ?? '', strlen(BASE_URL)), '/');
        redirigir('login.php?next=' . urlencode($destino));
    }
    return $u;
}

function requiere_rol(string ...$roles): array
{
    $u = requiere_login();
    if (!in_array($u['rol'], $roles, true)) {
        http_response_code(403);
        flash('danger', 'No tienes permiso para entrar a esa sección.');
        redirigir(ruta_panel($u['rol']));
    }
    return $u;
}
