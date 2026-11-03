<?php
require_once __DIR__ . '/../includes/autenticacion.php';
actualizar_estados_campanas();
$pdo = db();
$id = (int)get('id');
$usuario = usuario_actual();

$st = $pdo->prepare("SELECT p.*, c.nombre AS categoria, c.icono, u.nombre_completo AS emprendedor, u.descripcion AS emp_descripcion,
  u.ciudad AS emp_ciudad, u.departamento AS emp_depto, u.fecha_registro AS emp_desde
  FROM proyectos p JOIN categorias c ON c.id_categoria=p.id_categoria JOIN usuarios u ON u.id_usuario=p.id_usuario WHERE p.id_proyecto=?");
$st->execute([$id]);
$p = $st->fetch();

$es_dueno = $p && $usuario && (int)$usuario['id_usuario'] === (int)$p['id_usuario'];
$es_admin = $usuario && $usuario['rol'] === 'administrador';
$publico = $p && in_array($p['estado'], ['activo', 'financiado', 'finalizado'], true);
if (!$p || (!$publico && !$es_dueno && !$es_admin)) {
    http_response_code(404);
    $titulo_pagina = 'Proyecto no encontrado'; $pagina_activa = 'explorar';
    require __DIR__ . '/../includes/header.php';
    echo '<section class="seccion"><div class="container"><div class="vacio"><i class="fa-solid fa-circle-question d-block"></i><h1 class="h4">No encontramos ese proyecto</h1><p>Puede que no exista o que todavía no esté publicado.</p><a class="btn btn-azul" href="' . url('proyectos/index.php') . '">Explorar proyectos</a></div></div></section>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

// Acciones del formulario: favorito y comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $u = requiere_login();
    $accion = post('accion');
    if ($accion === 'favorito' && $u['rol'] === 'colaborador') {
        $ya = $pdo->prepare('SELECT id_favorito FROM favoritos WHERE id_usuario=? AND id_proyecto=?'); $ya->execute([$u['id_usuario'], $id]);
        if ($f = $ya->fetch()) { $pdo->prepare('DELETE FROM favoritos WHERE id_favorito=?')->execute([$f['id_favorito']]); flash('success', 'Quitaste el proyecto de tus favoritos.'); }
        else { $pdo->prepare('INSERT INTO favoritos (id_usuario, id_proyecto) VALUES (?,?)')->execute([$u['id_usuario'], $id]); flash('success', 'Guardaste el proyecto en tus favoritos.'); }
    } elseif ($accion === 'comentar') {
        $texto = post('contenido');
        if (mb_strlen($texto) < 3 || mb_strlen($texto) > 800) flash('danger', 'Tu comentario debe tener entre 3 y 800 caracteres.');
        elseif (!$publico) flash('danger', 'Este proyecto todavía no admite comentarios.');
        else {
            $pdo->prepare("INSERT INTO comentarios (id_usuario, id_proyecto, contenido, estado) VALUES (?,?,?, 'pendiente')")->execute([$u['id_usuario'], $id, $texto]);
            flash('success', 'Tu comentario fue enviado y se publicará cuando el administrador lo revise.');
        }
    }
    redirigir('proyectos/detalle.php?id=' . $id . '#comentarios');
}

$est = estado_efectivo($p);
$pct = porcentaje($p['total_recaudado'], $p['meta_financiamiento']);
$faltante = max(0, $p['meta_financiamiento'] - $p['total_recaudado']);

$pres = $pdo->prepare("SELECT pp.*, COALESCE((SELECT SUM(e.monto_ejecutado) FROM ejecucion_presupuesto e WHERE e.id_presupuesto=pp.id_presupuesto),0) AS ejecutado
  FROM presupuesto_proyecto pp WHERE pp.id_proyecto=? AND pp.tipo='planificado' ORDER BY pp.monto DESC");
$pres->execute([$id]); $presupuesto = $pres->fetchAll();
$total_plan = array_sum(array_column($presupuesto, 'monto'));
$total_real = array_sum(array_column($presupuesto, 'ejecutado'));
$por_concepto = [];
foreach ($presupuesto as $r) $por_concepto[CONCEPTOS[$r['concepto']] ?? 'Otros'] = ($por_concepto[CONCEPTOS[$r['concepto']] ?? 'Otros'] ?? 0) + (float)$r['monto'];

$s = $pdo->prepare('SELECT * FROM actualizaciones WHERE id_proyecto=? ORDER BY fecha_publicacion DESC'); $s->execute([$id]); $actualizaciones = $s->fetchAll();
$s = $pdo->prepare("SELECT cm.contenido, cm.fecha, u.nombre_completo FROM comentarios cm JOIN usuarios u ON u.id_usuario=cm.id_usuario WHERE cm.id_proyecto=? AND cm.estado='aprobado' ORDER BY cm.fecha DESC"); $s->execute([$id]); $comentarios = $s->fetchAll();
$s = $pdo->prepare('SELECT ruta FROM imagenes_proyecto WHERE id_proyecto=? ORDER BY orden'); $s->execute([$id]); $galeria = $s->fetchAll(PDO::FETCH_COLUMN);
$es_favorito = false;
if ($usuario && $usuario['rol'] === 'colaborador') { $s = $pdo->prepare('SELECT 1 FROM favoritos WHERE id_usuario=? AND id_proyecto=?'); $s->execute([$usuario['id_usuario'], $id]); $es_favorito = (bool)$s->fetch(); }
$img = imagen_proyecto_url($p);
$video_ok = $p['video_url'] && preg_match('#^https?://#i', $p['video_url']);

$titulo_pagina = $p['nombre']; $pagina_activa = 'explorar';
$usar_chartjs = !empty($por_concepto);
if ($usar_chartjs) {
    ob_start(); ?>
<script>
new Chart(document.getElementById('graficoPresupuesto'), {
  type: 'doughnut',
  data: { labels: <?= json_encode(array_keys($por_concepto), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>,
    datasets: [{ data: <?= json_encode(array_values($por_concepto)) ?>, backgroundColor: ['#1E5BFF','#F2A900','#E8590C','#12805C','#0A1A44','#7B1E5B','#2A9BD6','#8B94AD'], borderWidth: 2 }] },
  options: { plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: function (c) { return ' ' + c.label + ': Bs ' + c.parsed.toLocaleString('es-BO', { minimumFractionDigits: 2 }); } } } } }
});
</script>
<?php $js_inline = ob_get_clean(); }
require __DIR__ . '/../includes/header.php';
?>
<section class="cabecera-pagina">
  <div class="container">
    <nav aria-label="Ruta"><ol class="breadcrumb mb-2 small"><li class="breadcrumb-item"><a class="text-white-50" href="<?= url('proyectos/index.php') ?>">Proyectos</a></li><li class="breadcrumb-item active text-white" aria-current="page"><?= e($p['categoria']) ?></li></ol></nav>
    <h1><?= e($p['nombre']) ?></h1>
    <p class="mt-2"><i class="fa-solid fa-location-dot me-1"></i><?= e($p['ciudad']) ?>, <?= e($p['departamento']) ?> · <i class="fa-solid fa-user ms-2 me-1"></i><?= e($p['emprendedor']) ?> · <?= badge_estado($est) ?></p>
  </div>
</section>
<section class="seccion pt-4">
  <div class="container">
    <?php if (!$publico): ?><div class="alert alert-warning"><strong>Vista privada.</strong> Este proyecto está en estado «<?= e(etiqueta_estado($p['estado'])[0]) ?>» y no aparece en el catálogo público.</div><?php endif; ?>
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="detalle-portada mb-3">
          <?php if ($img): ?><img src="<?= e($img) ?>" alt="Fotografía principal de <?= e($p['nombre']) ?>"><?php else: ?><span class="img-vacia cat-<?= (int)$p['id_categoria'] ?>"><i class="fa-solid fa-<?= e($p['icono']) ?>"></i></span><?php endif; ?>
        </div>
        <?php if ($galeria): ?><div class="row g-2 mb-4"><?php foreach ($galeria as $r): if (!file_exists(UPLOAD_DIR . 'proyectos/' . basename($r))) continue; ?><div class="col-4 col-md-3"><img class="img-fluid rounded" src="<?= e(url('uploads/proyectos/' . rawurlencode(basename($r)))) ?>" alt="Fotografía adicional del proyecto" loading="lazy"></div><?php endforeach; ?></div><?php endif; ?>

        <div class="bloque"><h2>Sobre el proyecto</h2><p><?= nl2br(e($p['descripcion'])) ?></p>
          <?php if ($video_ok): ?><a href="<?= e($p['video_url']) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-circle-play me-1"></i>Ver video del proyecto</a><?php endif; ?></div>
        <div class="row g-3">
          <div class="col-md-6"><div class="bloque h-100"><h2><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>Problema</h2><p class="mb-0"><?= nl2br(e($p['problema'])) ?></p></div></div>
          <div class="col-md-6"><div class="bloque h-100"><h2><i class="fa-solid fa-bullseye text-primary me-2"></i>Objetivo</h2><p class="mb-0"><?= nl2br(e($p['objetivo'])) ?></p></div></div>
        </div>

        <div class="bloque">
          <h2>Presupuesto y uso de los recursos</h2>
          <?php if ($presupuesto): ?>
          <div class="row g-4 align-items-center">
            <div class="col-md-5"><canvas id="graficoPresupuesto" height="240" role="img" aria-label="Distribución del presupuesto por concepto"></canvas></div>
            <div class="col-md-7"><div class="table-responsive"><table class="table tabla-limpia align-middle mb-0">
              <thead><tr><th>Concepto</th><th class="text-end">Planificado</th><th class="text-end">Gasto real</th></tr></thead>
              <tbody><?php foreach ($presupuesto as $r): ?><tr><td><strong><?= e(CONCEPTOS[$r['concepto']] ?? 'Otros') ?></strong><br><span class="small text-muted"><?= e($r['descripcion']) ?></span></td><td class="text-end"><?= formato_bs_corto($r['monto']) ?><br><span class="small text-muted"><?= $total_plan > 0 ? number_format($r['monto'] / $total_plan * 100, 0) : 0 ?>%</span></td><td class="text-end"><?= $r['ejecutado'] > 0 ? formato_bs_corto($r['ejecutado']) : '—' ?></td></tr><?php endforeach; ?></tbody>
              <tfoot><tr><th>Total</th><th class="text-end"><?= formato_bs_corto($total_plan) ?></th><th class="text-end"><?= $total_real > 0 ? formato_bs_corto($total_real) : '—' ?></th></tr></tfoot>
            </table></div></div>
          </div>
          <?php else: ?><p class="text-muted mb-0">El emprendedor aún no cargó el detalle del presupuesto.</p><?php endif; ?>
          <?php if ($p['explicacion_recursos']): ?><p class="mt-3 mb-0"><?= nl2br(e($p['explicacion_recursos'])) ?></p><?php endif; ?>
        </div>

        <div class="bloque"><h2>Actualizaciones del emprendedor</h2>
          <?php if ($actualizaciones): ?><div class="linea-tiempo"><?php foreach ($actualizaciones as $a): ?><article><h3 class="h6 mb-0"><?= e($a['titulo']) ?></h3><p class="small text-muted mb-1"><?= fecha_hora($a['fecha_publicacion']) ?></p><p class="mb-0"><?= nl2br(e($a['contenido'])) ?></p></article><?php endforeach; ?></div>
          <?php else: ?><p class="text-muted mb-0">Todavía no hay novedades. Cuando el emprendedor publique avances, aparecerán aquí.</p><?php endif; ?></div>

        <div class="bloque" id="comentarios"><h2>Comentarios y preguntas</h2>
          <?php foreach ($comentarios as $c): ?><div class="border-bottom pb-2 mb-2"><strong><?= e($c['nombre_completo']) ?></strong> <span class="small text-muted"><?= fecha_hora($c['fecha']) ?></span><p class="mb-0"><?= nl2br(e($c['contenido'])) ?></p></div><?php endforeach; ?>
          <?php if (!$comentarios): ?><p class="text-muted">Aún no hay comentarios publicados.</p><?php endif; ?>
          <?php if ($publico): ?>
            <?php if ($usuario): ?>
            <form method="post" class="mt-3 needs-validation" novalidate><?= csrf_campo() ?><input type="hidden" name="accion" value="comentar">
              <label class="form-label" for="contenido">Escribe un comentario o pregunta</label>
              <textarea class="form-control mb-2" id="contenido" name="contenido" rows="3" minlength="3" maxlength="800" required></textarea>
              <div class="d-flex justify-content-between align-items-center"><span class="small text-muted">Los comentarios se publican después de una revisión.</span><button class="btn btn-azul" type="submit">Enviar comentario</button></div>
            </form>
            <?php else: ?><p class="mb-0"><a href="<?= url('login.php?next=' . urlencode('proyectos/detalle.php?id=' . $id)) ?>">Inicia sesión</a> para comentar.</p><?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

      <aside class="col-lg-4">
        <div class="caja-aporte position-sticky" style="top:90px">
          <div class="recaudado"><?= formato_bs($p['total_recaudado']) ?></div>
          <p class="text-muted mb-2">recaudados de <?= formato_bs($p['meta_financiamiento']) ?></p>
          <div class="barra-progreso<?= $pct >= 100 ? ' completa' : '' ?> mb-2" role="progressbar" aria-valuenow="<?= min(100, (int)$pct) ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Avance del financiamiento"><span style="width:<?= min(100, $pct) ?>%"></span></div>
          <div class="row text-center my-3 g-2">
            <div class="col-4"><strong class="d-block fs-5"><?= number_format($pct, 0) ?>%</strong><span class="small text-muted">logrado</span></div>
            <div class="col-4"><strong class="d-block fs-5"><?= (int)$p['num_colaboradores'] ?></strong><span class="small text-muted">colaboradores</span></div>
            <div class="col-4"><strong class="d-block fs-5"><?= $est === 'activo' ? dias_restantes($p['fecha_fin']) : 0 ?></strong><span class="small text-muted">días restantes</span></div>
          </div>
          <?php if ($est === 'activo'): $dest = 'aportes/realizar.php?id=' . $id; ?>
            <?php if (!$usuario): ?><a class="btn btn-oro btn-lg w-100" href="<?= url('login.php?next=' . urlencode($dest)) ?>"><i class="fa-solid fa-hand-holding-heart me-1"></i>Apoyar este proyecto</a>
            <?php elseif ($usuario['rol'] === 'colaborador'): ?><a class="btn btn-oro btn-lg w-100" href="<?= url($dest) ?>"><i class="fa-solid fa-hand-holding-heart me-1"></i>Apoyar este proyecto</a>
            <?php else: ?><button class="btn btn-secondary btn-lg w-100" disabled>Solo los colaboradores pueden aportar</button><?php endif; ?>
            <p class="small text-muted mt-2 mb-0">Aporte simulado. Faltan <?= formato_bs_corto($faltante) ?> para la meta.</p>
          <?php elseif ($est === 'financiado'): ?><div class="alert alert-success mb-0"><i class="fa-solid fa-circle-check me-1"></i>¡Meta alcanzada! Esta campaña ya fue financiada.</div>
          <?php else: ?><div class="alert alert-secondary mb-0">Esta campaña ya no recibe aportes (<?= e(mb_strtolower(etiqueta_estado($est)[0])) ?>).</div><?php endif; ?>
          <?php if ($usuario && $usuario['rol'] === 'colaborador'): ?>
          <form method="post" class="mt-2"><?= csrf_campo() ?><input type="hidden" name="accion" value="favorito"><button class="btn btn-outline-azul w-100" type="submit"><i class="fa-<?= $es_favorito ? 'solid' : 'regular' ?> fa-heart me-1"></i><?= $es_favorito ? 'Quitar de favoritos' : 'Guardar en favoritos' ?></button></form>
          <?php endif; ?>
          <hr>
          <dl class="small mb-0">
            <dt>Inicio de la campaña</dt><dd><?= fecha_corta($p['fecha_inicio']) ?></dd>
            <dt>Fecha límite</dt><dd><?= fecha_corta($p['fecha_fin']) ?></dd>
            <dt>Categoría</dt><dd class="mb-0"><?= e($p['categoria']) ?></dd>
          </dl>
        </div>
        <div class="bloque mt-3"><h2 class="h6">Sobre el emprendedor</h2><p class="mb-1"><strong><?= e($p['emprendedor']) ?></strong></p><p class="small text-muted mb-2"><?= e($p['emp_ciudad']) ?>, <?= e($p['emp_depto']) ?> · miembro desde <?= date('Y', strtotime($p['emp_desde'])) ?></p><p class="small mb-0"><?= nl2br(e($p['emp_descripcion'] ?? '')) ?></p></div>
      </aside>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
