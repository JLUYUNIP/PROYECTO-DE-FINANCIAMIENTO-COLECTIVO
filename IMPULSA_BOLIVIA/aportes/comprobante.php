<?php
require_once __DIR__ . '/../includes/autenticacion.php';
$u = requiere_login();
$st = db()->prepare("SELECT a.*, p.nombre AS proyecto, p.id_usuario AS id_emprendedor, ue.nombre_completo AS emprendedor, uc.nombre_completo AS colaborador, uc.correo
  FROM aportes a JOIN proyectos p ON p.id_proyecto=a.id_proyecto JOIN usuarios ue ON ue.id_usuario=p.id_usuario JOIN usuarios uc ON uc.id_usuario=a.id_usuario
  WHERE a.codigo_comprobante=?");
$st->execute([get('codigo')]);
$a = $st->fetch();
$permitido = $a && ($u['rol'] === 'administrador' || (int)$a['id_usuario'] === (int)$u['id_usuario'] || (int)$a['id_emprendedor'] === (int)$u['id_usuario']);
if (!$permitido) { http_response_code(404); flash('danger', 'No encontramos ese comprobante.'); redirigir(ruta_panel($u['rol'])); }
$titulo_pagina = 'Comprobante ' . $a['codigo_comprobante'];
require __DIR__ . '/../includes/header.php';
?>
<section class="seccion"><div class="container">
  <div class="comprobante">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div><h1 class="h4 mb-0">Comprobante de aporte</h1><span class="text-muted small"><?= e(APP_NOMBRE) ?></span></div>
      <span class="sello">DEMOSTRATIVO</span>
    </div>
    <dl class="row mb-0">
      <dt class="col-5">Código</dt><dd class="col-7"><code><?= e($a['codigo_comprobante']) ?></code></dd>
      <dt class="col-5">Fecha</dt><dd class="col-7"><?= fecha_hora($a['fecha_aporte']) ?></dd>
      <dt class="col-5">Colaborador</dt><dd class="col-7"><?= e($a['colaborador']) ?></dd>
      <dt class="col-5">Proyecto</dt><dd class="col-7"><?= e($a['proyecto']) ?></dd>
      <dt class="col-5">Emprendedor</dt><dd class="col-7"><?= e($a['emprendedor']) ?></dd>
      <dt class="col-5">Tipo</dt><dd class="col-7 text-capitalize"><?= e($a['tipo_aporte']) ?></dd>
      <dt class="col-5">Estado</dt><dd class="col-7"><?= badge_estado($a['estado']) ?></dd>
      <dt class="col-5 fs-5">Monto</dt><dd class="col-7 fs-4 fw-bold"><?= formato_bs($a['monto']) ?></dd>
    </dl>
    <hr><p class="small text-muted mb-0">Este comprobante no tiene validez fiscal ni contable. Corresponde a un aporte simulado de un prototipo universitario.</p>
  </div>
  <div class="text-center mt-4 no-imprimir d-flex justify-content-center gap-2 flex-wrap">
    <button class="btn btn-azul" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Imprimir o guardar PDF</button>
    <?php if ($u['rol'] === 'colaborador'): ?><a class="btn btn-outline-azul" href="<?= url('aportes/historial.php') ?>">Ver mi historial</a><?php endif; ?>
    <a class="btn btn-outline-secondary" href="<?= url('proyectos/index.php') ?>">Seguir explorando</a>
  </div>
</div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
