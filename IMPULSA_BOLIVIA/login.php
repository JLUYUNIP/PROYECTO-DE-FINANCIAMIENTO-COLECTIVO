<?php
require_once __DIR__ . '/includes/autenticacion.php';
if (usuario_actual()) redirigir(ruta_panel(usuario_actual()['rol']));

$next = ruta_interna_segura(get('next') ?: post('next'));
$error = null; $correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $correo = mb_strtolower(post('correo'));
    $clave = (string)($_POST['password'] ?? '');

    if (($_SESSION['bloqueo_hasta'] ?? 0) > time()) {
        $error = 'Demasiados intentos fallidos. Espera unos minutos antes de volver a intentar.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL) || $clave === '') {
        $error = 'Escribe tu correo y tu contraseña.';
    } else {
        $st = db()->prepare('SELECT * FROM usuarios WHERE correo = ?');
        $st->execute([$correo]);
        $u = $st->fetch();
        if ($u && password_verify($clave, $u['password']) && $u['estado'] === 'activo') {
            unset($_SESSION['intentos'], $_SESSION['bloqueo_hasta']);
            if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE usuarios SET password=? WHERE id_usuario=?')->execute([password_hash($clave, PASSWORD_DEFAULT), $u['id_usuario']]);
            }
            iniciar_sesion_usuario($u);
            registrar_accion((int)$u['id_usuario'], 'inicio_sesion');
            flash('success', 'Bienvenido, ' . explode(' ', $u['nombre_completo'])[0] . '.');
            $destino = $next !== '' ? $next : ruta_panel($u['rol']);
            redirigir(existe_ruta(strtok($destino, '?#')) ? $destino : 'index.php');
        }
        $_SESSION['intentos'] = ($_SESSION['intentos'] ?? 0) + 1;
        if ($_SESSION['intentos'] >= 5) { $_SESSION['bloqueo_hasta'] = time() + 300; $_SESSION['intentos'] = 0; }
        $error = ($u && $u['estado'] !== 'activo') ? 'Tu cuenta está desactivada. Contacta al administrador.' : 'Correo o contraseña incorrectos.';
    }
}

$titulo_pagina = 'Iniciar sesión';
require __DIR__ . '/includes/header.php';
?>
<section class="seccion">
  <div class="container" style="max-width:480px">
    <div class="tarjeta-form">
      <h1 class="h3 mb-1">Iniciar sesión</h1>
      <p class="text-muted">Entra para publicar proyectos o apoyar a otros emprendedores.</p>
      <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
      <form method="post" class="needs-validation" novalidate>
        <?= csrf_campo() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <div class="mb-3">
          <label class="form-label" for="correo">Correo electrónico</label>
          <input class="form-control" type="email" id="correo" name="correo" value="<?= e($correo) ?>" required autocomplete="email">
          <div class="invalid-feedback">Escribe un correo válido.</div>
        </div>
        <div class="mb-2">
          <label class="form-label" for="clave">Contraseña</label>
          <div class="input-group">
            <input class="form-control" type="password" id="clave" name="password" required autocomplete="current-password">
            <button class="btn btn-outline-secondary" type="button" data-ver-password="clave" aria-label="Mostrar contraseña"><i class="fa-solid fa-eye"></i></button>
          </div>
          <div class="invalid-feedback">Escribe tu contraseña.</div>
        </div>
        <p class="text-end small"><a href="<?= url('recuperar.php') ?>">¿Olvidaste tu contraseña?</a></p>
        <button class="btn btn-azul w-100 py-2" type="submit">Entrar</button>
      </form>
      <hr>
      <p class="small mb-0 text-center">¿Aún no tienes cuenta? <a href="<?= url('registro.php') ?>">Regístrate gratis</a></p>
    </div>
    <div class="alert alert-warning mt-3 small">
      <strong>Cuentas de prueba</strong><br>
      Administrador: admin@impulsabolivia.bo / Admin123*<br>
      Emprendedor: mquispe@demo.bo / Emprende123*<br>
      Colaborador: asalvatierra@demo.bo / Colabora123*
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
