<?php
// Espera $p: fila de proyectos con categoria, icono y emprendedor.
$est = estado_efectivo($p);
$pct = porcentaje($p['total_recaudado'], $p['meta_financiamiento']);
$img = imagen_proyecto_url($p);
$dias = dias_restantes($p['fecha_fin']);
?>
<article class="tarjeta-proyecto h-100">
  <a class="tarjeta-img" href="<?= url('proyectos/detalle.php?id=' . (int)$p['id_proyecto']) ?>" tabindex="-1" aria-hidden="true">
    <?php if ($img): ?>
      <img src="<?= e($img) ?>" alt="" loading="lazy">
    <?php else: ?>
      <span class="img-vacia cat-<?= (int)$p['id_categoria'] ?>"><i class="fa-solid fa-<?= e($p['icono']) ?>"></i></span>
    <?php endif; ?>
    <span class="tarjeta-estado"><?= badge_estado($est) ?></span>
  </a>
  <div class="tarjeta-cuerpo">
    <p class="tarjeta-cat mb-1"><i class="fa-solid fa-<?= e($p['icono']) ?> me-1"></i><?= e($p['categoria']) ?></p>
    <h3 class="tarjeta-titulo"><a href="<?= url('proyectos/detalle.php?id=' . (int)$p['id_proyecto']) ?>"><?= e($p['nombre']) ?></a></h3>
    <p class="tarjeta-resumen"><?= e($p['resumen']) ?></p>
    <p class="tarjeta-meta mb-2"><i class="fa-solid fa-location-dot me-1"></i><?= e($p['ciudad']) ?>, <?= e($p['departamento']) ?><br><i class="fa-solid fa-user me-1"></i><?= e($p['emprendedor']) ?></p>
    <div class="barra-progreso<?= $pct >= 100 ? ' completa' : '' ?>" role="progressbar" aria-valuenow="<?= min(100, (int)$pct) ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Avance del financiamiento">
      <span style="width:<?= min(100, $pct) ?>%"></span>
    </div>
    <div class="d-flex justify-content-between align-items-baseline mt-2">
      <strong class="monto"><?= formato_bs_corto($p['total_recaudado']) ?></strong>
      <span class="pct"><?= number_format($pct, 0) ?>%</span>
    </div>
    <p class="small text-muted mb-3">de <?= formato_bs_corto($p['meta_financiamiento']) ?> · <?= (int)$p['num_colaboradores'] ?> colaboradores ·
      <?= $est === 'activo' ? ($dias . ($dias === 1 ? ' día' : ' días') . ' restantes') : 'Termina el ' . fecha_corta($p['fecha_fin']) ?></p>
    <a class="btn btn-azul w-100" href="<?= url('proyectos/detalle.php?id=' . (int)$p['id_proyecto']) ?>">Ver proyecto</a>
  </div>
</article>
