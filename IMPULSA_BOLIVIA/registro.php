<?php
require_once __DIR__ . '/includes/autenticacion.php';
if (usuario_actual()) redirigir(ruta_panel(usuario_actual()['rol']));

$errores = [];
$d = ['tipo' => get('tipo') === 'emprendedor' ? 'emprendedor' : 'colaborador', 'nombre' => '', 'ci' => '', 'correo' => '', 'telefono' => '', 'departamento' => '', 'ciudad' => '', 'direccion' => '', 'descripcion' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $d['tipo'] = post('tipo') === 'emprendedor' ? 'emprendedor' : 'colaborador';
    foreach (['nombre', 'ci', 'telefono', 'departamento', 'ciudad', 'direccion', 'descripcion'] as $c) $d[$c] = post($c);
    $d['correo'] = mb_strtolower(post('correo'));
    $clave = (string)($_POST['password'] ?? '');
    $clave2 = (string)($_POST['password2'] ?? '');
    $emp = $d['tipo'] === 'emprendedor';

    if (mb_strlen($d['nombre']) < 5 || mb_strlen($d['nombre']) > 120 || !preg_match('/^[\p{L}\s\.\'-]+$/u', $d['nombre'])) $errores['nombre'] = 'Escribe tu nombre completo (solo letras).';
    if (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL) || mb_strlen($d['correo']) > 120) $errores['correo'] = 'Escribe un correo electrónico válido.';
    if (!validar_telefono($d['telefono'])) $errores['telefono'] = 'Escribe un teléfono boliviano válido (7 u 8 dígitos).';
    if (!in_array($d['departamento'], DEPARTAMENTOS, true)) $errores['departamento'] = 'Selecciona tu departamento.';
    if (mb_strlen($d['ciudad']) < 2 || mb_strlen($d['ciudad']) > 60) $errores['ciudad'] = 'Escribe tu ciudad.';
    if ($msg = validar_password($clave)) $errores['password'] = $msg;
    elseif ($clave !== $clave2) $errores['password2'] = 'Las contraseñas no coinciden.';
    if ($emp) {
        if (!validar_ci($d['ci'])) $errores['ci'] = 'Escribe tu cédula de identidad (5 a 10 dígitos, con complemento si lo tienes).';
        if (mb_strlen($d['direccion']) < 5 || mb_strlen($d['direccion']) > 200) $errores['direccion'] = 'Escribe tu dirección.';
        if (mb_strlen($d['descripcion']) < 20 || mb_strlen($d['descripcion']) > 1000) $errores['descripcion'] = 'Cuéntanos quién eres en 20 a 1000 caracteres.';
    }
    if (!$errores) {
        $pdo = db();
        $st = $pdo->prepare('SELECT 1 FROM usuarios WHERE correo=?'); $st->execute([$d['correo']]);
        if ($st->fetch()) $errores['correo'] = 'Ya existe una cuenta con ese correo.';
        if ($emp) { $st = $pdo->prepare('SELECT 1 FROM usuarios WHERE ci=?'); $st->execute([$d['ci']]); if ($st->fetch()) $errores['ci'] = 'Esa cédula ya está registrada.'; }
    }
    if (!$errores) {
        $pdo->prepare('INSERT INTO usuarios (nombre_completo, ci, correo, telefono, password, rol, departamento, ciudad, direccion, descripcion) VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute([$d['nombre'], $emp ? $d['ci'] : null, $d['correo'], preg_replace('/[\s\-]/', '', $d['telefono']), password_hash($clave, PASSWORD_DEFAULT), $d['tipo'], $d['departamento'], $d['ciudad'], $emp ? $d['direccion'] : null, $emp ? $d['descripcion'] : null]);
        $id = (int)$pdo->lastInsertId();
        crear_notificacion($id, 'Registro exitoso', 'Tu cuenta de ' . $d['tipo'] . ' fue creada. ¡Bienvenido a ' . APP_NOMBRE . '!');
        registrar_accion($id, 'registro', $d['tipo']);
        iniciar_sesion_usuario(['id_usuario' => $id]);
        flash('success', 'Tu cuenta fue creada. ¡Bienvenido!');
        redirigir($emp ? 'index.php' : 'proyectos/index.php');
    }
}
function err(array $errores, string $c): string { return isset($errores[$c]) ? '<div class="text-danger small mt-1">' . e($errores[$c]) . '</div>' : ''; }

$titulo_pagina = 'Crear cuenta';
require __DIR__ . '/includes/header.php';
?>
<section class="seccion">
  <div class="container" style="max-width:760px">
    <div class="tarjeta-form">
      <h1 class="h3 mb-1">Crea tu cuenta</h1>
      <p class="text-muted">Elige cómo quieres participar. Puedes cambiar de rol creando otra cuenta.</p>
      <?php if ($errores): ?><div class="alert alert-danger" role="alert">Revisa los campos marcados e inténtalo de nuevo.</div><?php endif; ?>
      <form method="post" class="needs-validation" novalidate>
        <?= csrf_campo() ?>
        <div class="row g-3 selector-tipo mb-4">
          <div class="col-sm-6"><input type="radio" name="tipo" id="t-colab" value="colaborador" <?= $d['tipo'] === 'colaborador' ? 'checked' : '' ?>><label for="t-colab"><i class="fa-solid fa-hand-holding-heart d-block mb-1"></i><strong>Quiero apoyar proyectos</strong><span class="d-block small text-muted">Colaborador: aporta a campañas.</span></label></div>
          <div class="col-sm-6"><input type="radio" name="tipo" id="t-emp" value="emprendedor" <?= $d['tipo'] === 'emprendedor' ? 'checked' : '' ?>><label for="t-emp"><i class="fa-solid fa-lightbulb d-block mb-1"></i><strong>Tengo un proyecto</strong><span class="d-block small text-muted">Emprendedor: publica y recibe aportes.</span></label></div>
        </div>
        <div class="row g-3">
          <div class="col-12"><label class="form-label" for="nombre">Nombre completo</label><input class="form-control" id="nombre" name="nombre" value="<?= e($d['nombre']) ?>" required minlength="5" maxlength="120" autocomplete="name"><?= err($errores, 'nombre') ?></div>
          <div class="col-md-6"><label class="form-label" for="correo">Correo electrónico</label><input class="form-control" type="email" id="correo" name="correo" value="<?= e($d['correo']) ?>" required autocomplete="email"><?= err($errores, 'correo') ?></div>
          <div class="col-md-6"><label class="form-label" for="telefono">Teléfono</label><input class="form-control" type="tel" id="telefono" name="telefono" value="<?= e($d['telefono']) ?>" required placeholder="71234567" autocomplete="tel"><?= err($errores, 'telefono') ?></div>
          <div class="col-md-6"><label class="form-label" for="departamento">Departamento</label>
            <select class="form-select" id="departamento" name="departamento" required><option value="">Selecciona…</option>
              <?php foreach (DEPARTAMENTOS as $dep): ?><option <?= $d['departamento'] === $dep ? 'selected' : '' ?>><?= e($dep) ?></option><?php endforeach; ?></select><?= err($errores, 'departamento') ?></div>
          <div class="col-md-6"><label class="form-label" for="ciudad">Ciudad</label><input class="form-control" id="ciudad" name="ciudad" value="<?= e($d['ciudad']) ?>" required maxlength="60"><?= err($errores, 'ciudad') ?></div>
        </div>
        <div id="campos-emprendedor" class="row g-3 mt-0 <?= $d['tipo'] === 'emprendedor' ? '' : 'd-none' ?>">
          <div class="col-md-5"><label class="form-label" for="ci">Cédula de identidad</label><input class="form-control" id="ci" name="ci" value="<?= e($d['ci']) ?>" data-req-emprendedor placeholder="1234567" maxlength="14"><?= err($errores, 'ci') ?></div>
          <div class="col-md-7"><label class="form-label" for="direccion">Dirección</label><input class="form-control" id="direccion" name="direccion" value="<?= e($d['direccion']) ?>" data-req-emprendedor maxlength="200"><?= err($errores, 'direccion') ?></div>
          <div class="col-12"><label class="form-label" for="descripcion">Descripción personal</label><textarea class="form-control" id="descripcion" name="descripcion" rows="3" data-req-emprendedor maxlength="1000" placeholder="¿Quién eres y a qué te dedicas?"><?= e($d['descripcion']) ?></textarea><?= err($errores, 'descripcion') ?></div>
        </div>
        <div class="row g-3 mt-0">
          <div class="col-md-6"><label class="form-label" for="password">Contraseña</label>
            <div class="input-group"><input class="form-control" type="password" id="password" name="password" required minlength="8" autocomplete="new-password"><button class="btn btn-outline-secondary" type="button" data-ver-password="password" aria-label="Mostrar contraseña"><i class="fa-solid fa-eye"></i></button></div>
            <div class="fuerza mt-2"><span></span></div><div class="form-text">Mínimo 8 caracteres, con letras y números.</div><?= err($errores, 'password') ?></div>
          <div class="col-md-6"><label class="form-label" for="password2">Repite la contraseña</label><input class="form-control" type="password" id="password2" name="password2" required autocomplete="new-password"><?= err($errores, 'password2') ?></div>
        </div>
        <button class="btn btn-azul w-100 py-2 mt-4" type="submit">Crear mi cuenta</button>
      </form>
      <p class="small text-center mt-3 mb-0">¿Ya tienes cuenta? <a href="<?= url('login.php') ?>">Inicia sesión</a></p>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
