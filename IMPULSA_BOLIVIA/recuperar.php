<?php
require_once __DIR__ . '/includes/autenticacion.php';
// Flujo de recuperación preparado para correo. En el prototipo (XAMPP sin servidor de correo)
// el enlace se muestra en pantalla cuando MODO_DEMO_CORREO es true.
const MODO_DEMO_CORREO = true;
$enlace = null; $enviado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $correo = mb_strtolower(post('correo'));
    if (filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $st = db()->prepare("SELECT id_usuario FROM usuarios WHERE correo=? AND estado='activo'");
        $st->execute([$correo]);
        if ($u = $st->fetch()) {
            $token = bin2hex(random_bytes(32));
            db()->prepare('UPDATE recuperacion_password SET usado=1 WHERE id_usuario=?')->execute([$u['id_usuario']]);
            db()->prepare('INSERT INTO recuperacion_password (id_usuario, token_hash, fecha_expiracion) VALUES (?,?,DATE_ADD(NOW(), INTERVAL 30 MINUTE))')
                ->execute([$u['id_usuario'], hash('sha256', $token)]);
            registrar_accion((int)$u['id_usuario'], 'solicitud_recuperacion');
            // Aquí se enviaría el correo con mail() o PHPMailer: BASE + /restablecer.php?token=...
            if (MODO_DEMO_CORREO) $enlace = url('restablecer.php?token=' . $token);
        }
    }
    $enviado = true; // misma respuesta exista o no el correo, para no revelar cuentas
}
$titulo_pagina = 'Recuperar contraseña';
require __DIR__ . '/includes/header.php';
?>
<section class="seccion"><div class="container" style="max-width:480px"><div class="tarjeta-form">
  <h1 class="h3 mb-1">Recuperar contraseña</h1>
  <?php if ($enviado): ?>
    <div class="alert alert-success mt-3">Si el correo está registrado, recibirás un enlace para crear una nueva contraseña. El enlace vence en 30 minutos.</div>
    <?php if ($enlace): ?><div class="alert alert-warning small"><strong>Modo demostración:</strong> como no hay servidor de correo, usa este enlace.<br><a href="<?= e($enlace) ?>">Restablecer mi contraseña</a></div><?php endif; ?>
  <?php else: ?>
    <p class="text-muted">Escribe el correo de tu cuenta y te enviaremos un enlace.</p>
    <form method="post" class="needs-validation" novalidate><?= csrf_campo() ?>
      <div class="mb-3"><label class="form-label" for="correo">Correo electrónico</label><input class="form-control" type="email" id="correo" name="correo" required><div class="invalid-feedback">Escribe un correo válido.</div></div>
      <button class="btn btn-azul w-100" type="submit">Enviar enlace</button>
    </form>
  <?php endif; ?>
  <p class="small text-center mt-3 mb-0"><a href="<?= url('login.php') ?>">Volver a iniciar sesión</a></p>
</div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
