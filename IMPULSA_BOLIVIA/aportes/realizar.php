<?php
require_once __DIR__ . '/../includes/autenticacion.php';
$u = requiere_rol('colaborador');
actualizar_estados_campanas();
$id = (int)(get('id') ?: post('id'));

$st = db()->prepare('SELECT p.*, c.nombre AS categoria, c.icono FROM proyectos p JOIN categorias c ON c.id_categoria=p.id_categoria WHERE p.id_proyecto=?');
$st->execute([$id]);
$p = $st->fetch();
if (!$p || $p['estado'] !== 'activo' || $p['fecha_fin'] < date('Y-m-d')) {
    flash('warning', 'Esa campaña no está disponible para recibir aportes.');
    redirigir($p ? 'proyectos/detalle.php?id=' . $id : 'proyectos/index.php');
}
$faltante = round($p['meta_financiamiento'] - $p['total_recaudado'], 2);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $monto = validar_monto(post('monto'));
    if ($monto === null) $error = 'Escribe un monto válido en bolivianos (por ejemplo 100 o 150,50).';
    elseif ($monto < APORTE_MINIMO) $error = 'El aporte mínimo es de ' . formato_bs_corto(APORTE_MINIMO) . '.';
    elseif ($monto > $faltante) $error = 'El monto supera lo que falta para la meta (' . formato_bs($faltante) . ').';
    else {
        $_SESSION['aporte_pendiente'] = ['id' => $id, 'monto' => $monto, 'hora' => time()];
        redirigir('aportes/confirmar.php');
    }
}
$sugeridos = array_values(array_filter([20, 50, 100, 200, 500], fn($m) => $m <= $faltante));
$titulo_pagina = 'Apoyar proyecto'; $pagina_activa = 'explorar';
require __DIR__ . '/../includes/header.php';
?>
<section class="seccion">
  <div class="container" style="max-width:720px">
    <a class="small" href="<?= url('proyectos/detalle.php?id=' . $id) ?>"><i class="fa-solid fa-arrow-left me-1"></i>Volver al proyecto</a>
    <div class="tarjeta-form mt-3">
      <h1 class="h3">Apoyar «<?= e($p['nombre']) ?>»</h1>
      <p class="text-muted">Faltan <strong><?= formato_bs($faltante) ?></strong> para alcanzar la meta de <?= formato_bs($p['meta_financiamiento']) ?>.</p>
      <div class="alert alert-warning small"><i class="fa-solid fa-flask me-1"></i><strong>Aporte simulado.</strong> No se cobra dinero ni se piden datos bancarios o de tarjeta.</div>
      <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
      <form method="post" class="needs-validation" novalidate>
        <?= csrf_campo() ?><input type="hidden" name="id" value="<?= $id ?>">
        <fieldset class="mb-3"><legend class="form-label">Elige un monto</legend>
          <div class="chips-monto d-flex flex-wrap gap-2">
            <?php foreach ($sugeridos as $m): ?><span><input type="radio" name="monto_rapido" id="m<?= $m ?>" value="<?= $m ?>"><label for="m<?= $m ?>">Bs <?= $m ?></label></span><?php endforeach; ?>
          </div>
        </fieldset>
        <div class="mb-3"><label class="form-label" for="monto_personalizado">O escribe otro monto (Bs)</label>
          <input class="form-control" id="monto_personalizado" name="monto" inputmode="decimal" required pattern="\d{1,9}([.,]\d{1,2})?" placeholder="Ej. 150">
          <div class="form-text">Mínimo <?= formato_bs_corto(APORTE_MINIMO) ?> · máximo <?= formato_bs($faltante) ?></div>
          <div class="invalid-feedback">Escribe un monto válido.</div></div>
        <p class="mb-3">Tu aporte: <strong id="resumen-monto">Bs 0,00</strong></p>
        <button class="btn btn-oro btn-lg w-100" type="submit">Continuar</button>
      </form>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
