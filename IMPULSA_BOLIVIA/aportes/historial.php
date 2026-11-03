<?php
require_once __DIR__ . '/../includes/autenticacion.php';
$u = requiere_rol('colaborador');
$st = db()->prepare("SELECT a.*, p.nombre AS proyecto FROM aportes a JOIN proyectos p ON p.id_proyecto=a.id_proyecto WHERE a.id_usuario=? ORDER BY a.fecha_aporte DESC LIMIT 300");
$st->execute([$u['id_usuario']]);
$aportes = $st->fetchAll();
$total = array_sum(array_map(fn($a) => $a['estado'] === 'confirmado' ? (float)$a['monto'] : 0, $aportes));
$titulo_pagina = 'Mis aportes';
require __DIR__ . '/../includes/header.php';
?>
<section class="cabecera-pagina"><div class="container"><h1>Mis aportes</h1><p>Total confirmado: <strong><?= formato_bs($total) ?></strong></p></div></section>
<section class="seccion pt-4"><div class="container">
<?php if ($aportes): ?>
  <div class="bloque p-0"><div class="table-responsive"><table class="table tabla-limpia align-middle mb-0">
    <thead><tr><th>Fecha</th><th>Proyecto</th><th class="text-end">Monto</th><th>Estado</th><th>Tipo</th><th><span class="visually-hidden">Comprobante</span></th></tr></thead>
    <tbody><?php foreach ($aportes as $a): ?><tr>
      <td><?= fecha_hora($a['fecha_aporte']) ?></td>
      <td><a href="<?= url('proyectos/detalle.php?id=' . (int)$a['id_proyecto']) ?>"><?= e($a['proyecto']) ?></a></td>
      <td class="text-end"><?= formato_bs($a['monto']) ?></td><td><?= badge_estado($a['estado']) ?></td><td class="text-capitalize"><?= e($a['tipo_aporte']) ?></td>
      <td><a class="btn btn-sm btn-outline-azul" href="<?= url('aportes/comprobante.php?codigo=' . urlencode($a['codigo_comprobante'])) ?>">Comprobante</a></td>
    </tr><?php endforeach; ?></tbody>
  </table></div></div>
<?php else: ?>
  <div class="vacio"><i class="fa-solid fa-hand-holding-heart d-block"></i><p>Aún no has hecho aportes.</p><a class="btn btn-azul" href="<?= url('proyectos/index.php') ?>">Explorar proyectos</a></div>
<?php endif; ?>
</div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
