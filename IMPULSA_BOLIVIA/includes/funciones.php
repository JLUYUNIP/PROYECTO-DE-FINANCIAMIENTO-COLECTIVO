<?php
// ---------- Utilidades generales ----------
function e($valor): string { return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $ruta = ''): string { return BASE_URL . '/' . ltrim($ruta, '/'); }
function existe_ruta(string $ruta): bool { return file_exists(RAIZ . '/' . ltrim($ruta, '/')); }

function redirigir(string $ruta): void
{
    header('Location: ' . (str_starts_with($ruta, 'http') ? $ruta : url($ruta)));
    exit;
}

function post(string $campo): string { return trim((string)($_POST[$campo] ?? '')); }
function get(string $campo): string { return trim((string)($_GET[$campo] ?? '')); }

// Evita redirecciones abiertas: solo rutas internas.
function ruta_interna_segura(string $ruta, string $defecto = ''): string
{
    $ruta = ltrim($ruta, '/');
    if ($ruta === '' || str_contains($ruta, '://') || str_contains($ruta, '..') || str_starts_with($ruta, '\\')) return $defecto;
    return $ruta;
}

// ---------- Mensajes flash ----------
function flash(string $tipo, string $mensaje): void { $_SESSION['flash'][] = ['tipo' => $tipo, 'msg' => $mensaje]; }
function obtener_flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

// ---------- CSRF ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_campo(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_verificar(): void
{
    $enviado = $_POST['csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals($_SESSION['csrf'] ?? '', $enviado)) {
        http_response_code(419);
        exit('La sesión del formulario expiró. Vuelve atrás, recarga la página e inténtalo de nuevo.');
    }
}

// ---------- Formato ----------
function formato_bs($monto): string { return MONEDA . ' ' . number_format((float)$monto, 2, ',', '.'); }
function formato_bs_corto($monto): string { return MONEDA . ' ' . number_format((float)$monto, 0, ',', '.'); }
function fecha_corta(?string $f): string { return $f ? date('d/m/Y', strtotime($f)) : '—'; }
function fecha_hora(?string $f): string { return $f ? date('d/m/Y H:i', strtotime($f)) : '—'; }

function porcentaje($recaudado, $meta): float
{
    return $meta > 0 ? round(((float)$recaudado / (float)$meta) * 100, 1) : 0.0;
}
function dias_restantes(string $fecha_fin): int
{
    $hoy = new DateTime('today');
    $fin = new DateTime($fecha_fin);
    return $fin < $hoy ? 0 : (int)$hoy->diff($fin)->days;
}

// ---------- Estados ----------
function estado_efectivo(array $p): string
{
    if ($p['estado'] === 'activo' && $p['fecha_fin'] < date('Y-m-d')) return 'finalizado';
    return $p['estado'];
}
function etiqueta_estado(string $estado): array
{
    $mapa = [
        'pendiente'  => ['Pendiente de aprobación', 'warning'], 'correccion' => ['Requiere correcciones', 'info'],
        'rechazado'  => ['Rechazado', 'danger'], 'activo' => ['Activa', 'primary'],
        'financiado' => ['Financiada', 'success'], 'finalizado' => ['Finalizada', 'secondary'],
        'suspendido' => ['Suspendida', 'dark'], 'cancelado' => ['Cancelada', 'dark'],
        'confirmado' => ['Confirmado', 'success'], 'anulado' => ['Anulado', 'danger'],
    ];
    return $mapa[$estado] ?? [ucfirst($estado), 'secondary'];
}
function badge_estado(string $estado): string
{
    [$texto, $clase] = etiqueta_estado($estado);
    return '<span class="badge text-bg-' . $clase . '">' . e($texto) . '</span>';
}

// Imagen del proyecto: devuelve URL o null (se muestra un fondo con icono).
function imagen_proyecto_url(array $p): ?string
{
    if (!empty($p['imagen_principal']) && file_exists(UPLOAD_DIR . 'proyectos/' . basename($p['imagen_principal']))) {
        return url('uploads/proyectos/' . rawurlencode(basename($p['imagen_principal'])));
    }
    return null;
}

// ---------- Mantenimiento de campañas ----------
function actualizar_estados_campanas(): void
{
    $pdo = db();
    $pdo->exec("UPDATE proyectos SET estado='finalizado' WHERE estado='activo' AND fecha_fin < CURDATE()");
    $pdo->exec("UPDATE campanas c JOIN proyectos p ON p.id_proyecto=c.id_proyecto SET c.estado='finalizada' WHERE c.estado='activa' AND p.estado='finalizado'");
}

// ---------- Notificaciones y registro de acciones ----------
function crear_notificacion(int $id_usuario, string $titulo, string $mensaje): void
{
    db()->prepare('INSERT INTO notificaciones (id_usuario, titulo, mensaje) VALUES (?,?,?)')
        ->execute([$id_usuario, mb_substr($titulo, 0, 150), mb_substr($mensaje, 0, 400)]);
}
function registrar_accion(?int $id_usuario, string $accion, string $detalle = ''): void
{
    db()->prepare('INSERT INTO registro_acciones (id_usuario, accion, detalle, ip) VALUES (?,?,?,?)')
        ->execute([$id_usuario, mb_substr($accion, 0, 80), mb_substr($detalle, 0, 400), $_SERVER['REMOTE_ADDR'] ?? null]);
}

// ---------- Validaciones ----------
function validar_password(string $p): ?string
{
    if (strlen($p) < 8) return 'La contraseña debe tener al menos 8 caracteres.';
    if (!preg_match('/[A-Za-z]/', $p) || !preg_match('/\d/', $p)) return 'La contraseña debe combinar letras y números.';
    return null;
}
function validar_telefono(string $t): bool { return (bool)preg_match('/^(\+?591)?[0-9]{7,8}$/', preg_replace('/[\s\-]/', '', $t)); }
function validar_ci(string $ci): bool { return (bool)preg_match('/^[0-9]{5,10}(-?[0-9A-Za-z]{1,3})?$/', $ci); }
function validar_fecha(string $f): bool { $d = DateTime::createFromFormat('Y-m-d', $f); return $d && $d->format('Y-m-d') === $f; }
function validar_monto(string $m): ?float
{
    $m = str_replace(',', '.', trim($m));
    return preg_match('/^\d{1,9}(\.\d{1,2})?$/', $m) ? (float)$m : null;
}

// ---------- Subida de imágenes (se usa al publicar proyectos) ----------
function subir_imagen(array $archivo, string $subcarpeta): array
{
    if ($archivo['error'] === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($archivo['error'] !== UPLOAD_ERR_OK) return [null, 'No se pudo subir la imagen.'];
    if ($archivo['size'] > 3 * 1024 * 1024) return [null, 'La imagen no puede pesar más de 3 MB.'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext || @getimagesize($archivo['tmp_name']) === false) return [null, 'Solo se permiten imágenes JPG, PNG o WEBP.'];
    $nombre = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($archivo['tmp_name'], UPLOAD_DIR . $subcarpeta . '/' . $nombre)) return [null, 'No se pudo guardar la imagen.'];
    return [$nombre, null];
}
