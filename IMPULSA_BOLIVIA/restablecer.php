<?php
require_once __DIR__ . '/includes/autenticacion.php';
$token = get('token') ?: post('token');
$fila = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $st = db()->prepare('SELECT * FROM recuperacion_password WHERE token_hash=? AND usado=0 AND fecha_expiracion > NOW()');
    $st->execute([hash('sha256', $token)]);
    $fila = $st->fetch();
}
$error = null;
if ($fila && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $clave = (string)($_POST['password'] ?? ''); $clave2 = (string)($_POST['password2'] ?? '');
    if ($msg = validar_password($clave)) $error = $msg;
    elseif ($clave !== $clave2) $error = 'Las contraseñas no coinciden.';
    else {
        $pdo = db(); $pdo->beginTransaction();
        $pdo->prepare('UPDATE usuarios SET password=? WHERE id_usuario=?')->execute([password_hash($clave, PASSWORD_DEFAULT), $fila['id_usuario']]);
        $pdo->prepare('UPDATE recuperacion_password SET usado=1 WHERE id_recuperacion=?')->execute([$fila['id_recuperacion']]);
        $pdo->commit();
        registrar_accion((int)$fila['id_usuario'], 'cambio_password_recuperacion');
        flash('success', 'Tu contraseña fue actualizada. Ya puedes iniciar sesión.');
        redirigir('login.php');
    }
}
$titulo_pagina = 'Nueva contraseña';
require __DIR__ . '/includes/header.php';
?>
<section class="seccion"><div class="container" style="max-width:480px"><div class="tarjeta-form">
<?php if (!$fila): ?>
  <h1 class="h3">Enlace no válido</h1><p class="text-muted">El enlace venció o ya fue usado. Solicita uno nuevo.</p><a class="btn btn-azul" href="<?= url('recuperar.php') ?>">Solicitar otro enlace</a>
<?php else: ?>
  <h1 class="h3 mb-3">Crea una nueva contraseña</h1>
  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="needs-validation" novalidate><?= csrf_campo() ?><input type="hidden" name="token" value="<?= e($token) ?>">
    <div class="mb-3"><label class="form-label" for="password">Nueva contraseña</label><input class="form-control" type="password" id="password" name="password" required minlength="8" autocomplete="new-password"><div class="fuerza mt-2"><span></span></div></div>
    <div class="mb-3"><label class="form-label" for="password2">Repite la contraseña</label><input class="form-control" type="password" id="password2" name="password2" required autocomplete="new-password"></div>
    <button class="btn btn-azul w-100" type="submit">Guardar contraseña</button>
  </form>
<?php endif; ?>
</div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
