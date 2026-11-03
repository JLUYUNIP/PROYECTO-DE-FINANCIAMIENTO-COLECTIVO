<?php
require_once __DIR__ . '/includes/autenticacion.php';
actualizar_estados_campanas();
$pdo = db();

$stats = $pdo->query("SELECT
  (SELECT COUNT(*) FROM proyectos WHERE estado IN ('activo','financiado','finalizado')) AS publicados,
  (SELECT COUNT(*) FROM proyectos WHERE estado='financiado') AS financiados,
  (SELECT COALESCE(SUM(monto),0) FROM aportes WHERE estado='confirmado') AS total_aportes,
  (SELECT COUNT(*) FROM usuarios WHERE rol='emprendedor' AND estado='activo') AS emprendedores")->fetch();

$destacados = $pdo->query("SELECT p.*, c.nombre AS categoria, c.icono, u.nombre_completo AS emprendedor
  FROM proyectos p JOIN categorias c ON c.id_categoria=p.id_categoria JOIN usuarios u ON u.id_usuario=p.id_usuario
  WHERE p.estado IN ('activo','financiado')
  ORDER BY (p.estado='activo') DESC, (p.total_recaudado/p.meta_financiamiento) DESC LIMIT 7")->fetchAll();
$hero = $destacados ? array_shift($destacados) : null;

$actual = usuario_actual();
$ruta_publicar = ($actual && $actual['rol'] === 'emprendedor' && existe_ruta('proyectos/publicar.php')) ? 'proyectos/publicar.php' : 'registro.php?tipo=emprendedor';
$titulo_pagina = null; $pagina_activa = 'inicio';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-7">
        <h1>Haz realidad tus ideas. Impulsa el futuro de Bolivia</h1>
        <p class="lead my-4">Conectamos emprendedores con personas que creen en sus ideas y desean contribuir al crecimiento de Bolivia.</p>
        <div class="d-flex flex-wrap gap-3">
          <a class="btn btn-oro btn-lg" href="<?= url('proyectos/index.php') ?>">Explorar proyectos</a>
          <a class="btn btn-outline-light btn-lg" href="<?= url($ruta_publicar) ?>">Publicar mi proyecto</a>
        </div>
        <p class="small mt-4 mb-0" style="color:#9FB0DE"><i class="fa-solid fa-flask me-1"></i>Prototipo universitario: los aportes son simulados y no se cobra dinero real.</p>
      </div>
      <?php if ($hero): $pct = porcentaje($hero['total_recaudado'], $hero['meta_financiamiento']); ?>
      <div class="col-lg-5">
        <div class="hero-destacado">
          <span class="hero-etiqueta"><i class="fa-solid fa-fire me-1"></i>Campaña destacada</span>
          <h2><?= e($hero['nombre']) ?></h2>
          <p class="small text-muted mb-3"><i class="fa-solid fa-location-dot me-1"></i><?= e($hero['ciudad']) ?>, <?= e($hero['departamento']) ?> · <?= e($hero['emprendedor']) ?></p>
          <div class="barra-progreso<?= $pct >= 100 ? ' completa' : '' ?>"><span style="width:<?= min(100, $pct) ?>%"></span></div>
          <div class="d-flex justify-content-between mt-2">
            <strong class="monto"><?= formato_bs_corto($hero['total_recaudado']) ?></strong>
            <span class="pct"><?= number_format($pct, 0) ?>% de <?= formato_bs_corto($hero['meta_financiamiento']) ?></span>
          </div>
          <p class="small text-muted mb-3"><?= (int)$hero['num_colaboradores'] ?> colaboradores · <?= $hero['estado'] === 'activo' ? dias_restantes($hero['fecha_fin']) . ' días restantes' : 'Meta alcanzada' ?></p>
          <a class="btn btn-azul w-100" href="<?= url('proyectos/detalle.php?id=' . (int)$hero['id_proyecto']) ?>">Ver proyecto</a>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="indicadores" aria-label="Indicadores generales">
  <div class="container">
    <div class="row g-3">
      <div class="col-6 col-lg-3"><div class="indicador"><i class="fa-solid fa-rocket"></i><div class="num"><?= (int)$stats['publicados'] ?></div><div class="rot">Proyectos publicados</div></div></div>
      <div class="col-6 col-lg-3"><div class="indicador"><i class="fa-solid fa-circle-check"></i><div class="num"><?= (int)$stats['financiados'] ?></div><div class="rot">Proyectos financiados</div></div></div>
      <div class="col-6 col-lg-3"><div class="indicador"><i class="fa-solid fa-coins"></i><div class="num"><?= formato_bs_corto($stats['total_aportes']) ?></div><div class="rot">Total de aportes</div></div></div>
      <div class="col-6 col-lg-3"><div class="indicador"><i class="fa-solid fa-users"></i><div class="num"><?= (int)$stats['emprendedores'] ?></div><div class="rot">Emprendedores registrados</div></div></div>
    </div>
  </div>
</section>

<section class="seccion">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div><h2 class="titulo-seccion">Proyectos destacados</h2><p class="sub-seccion mb-0">Ideas productivas de todo el país que ya están recibiendo apoyo.</p></div>
      <a class="btn btn-outline-azul" href="<?= url('proyectos/index.php') ?>">Ver todos los proyectos</a>
    </div>
    <?php if ($destacados): ?>
    <div class="row g-4">
      <?php foreach ($destacados as $p): ?><div class="col-md-6 col-lg-4"><?php include __DIR__ . '/includes/tarjeta_proyecto.php'; ?></div><?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="vacio"><i class="fa-solid fa-seedling d-block"></i><p class="mb-0">Todavía no hay proyectos publicados. ¡Sé el primero en publicar el tuyo!</p></div>
    <?php endif; ?>
  </div>
</section>

<section class="seccion seccion-blanca" id="como-funciona">
  <div class="container">
    <h2 class="titulo-seccion">¿Cómo funciona?</h2>
    <p class="sub-seccion mb-4">Dos caminos, una misma comunidad. Elige el tuyo.</p>
    <div class="row g-4">
      <div class="col-lg-6"><div class="panel-pasos">
        <h3 class="h5 mb-2"><i class="fa-solid fa-lightbulb text-warning me-2"></i>Si eres emprendedor</h3>
        <div class="paso"><div class="paso-num">1</div><div><h3>Crea tu cuenta</h3><p>Regístrate con tus datos y tu cédula de identidad.</p></div></div>
        <div class="paso"><div class="paso-num">2</div><div><h3>Publica tu proyecto</h3><p>Cuenta el problema, tu meta en bolivianos y el presupuesto detallado.</p></div></div>
        <div class="paso"><div class="paso-num">3</div><div><h3>Espera la aprobación</h3><p>Un administrador revisa que la información sea completa y clara.</p></div></div>
        <div class="paso"><div class="paso-num">4</div><div><h3>Recibe aportes y rinde cuentas</h3><p>Publica avances y reporta cómo usas cada boliviano.</p></div></div>
      </div></div>
      <div class="col-lg-6"><div class="panel-pasos">
        <h3 class="h5 mb-2"><i class="fa-solid fa-hand-holding-heart text-primary me-2"></i>Si quieres apoyar</h3>
        <div class="paso"><div class="paso-num">1</div><div><h3>Explora proyectos</h3><p>Filtra por categoría, departamento o avance de la campaña.</p></div></div>
        <div class="paso"><div class="paso-num">2</div><div><h3>Elige un proyecto</h3><p>Revisa el presupuesto, las novedades y a quién estás apoyando.</p></div></div>
        <div class="paso"><div class="paso-num">3</div><div><h3>Haz tu aporte</h3><p>Escoge un monto y confirma. En este prototipo el aporte es simulado.</p></div></div>
        <div class="paso"><div class="paso-num">4</div><div><h3>Sigue el avance</h3><p>Recibe notificaciones y descarga tu comprobante.</p></div></div>
      </div></div>
    </div>
  </div>
</section>

<section class="seccion">
  <div class="container">
    <h2 class="titulo-seccion">Lo que dicen nuestros usuarios</h2>
    <p class="sub-seccion mb-4"><span class="etiqueta-demo">Testimonios demostrativos</span> Personas y opiniones ficticias para ilustrar la plataforma.</p>
    <div class="row g-4">
      <div class="col-md-4"><figure class="testimonio mb-0"><blockquote class="mb-3">“Pude mostrar mi proyecto de café con presupuesto y fotos. Eso generó confianza en quienes me apoyaron.”</blockquote><footer>Carlos, caficultor · Caranavi <span class="etiqueta-demo">Demo</span></footer></figure></div>
      <div class="col-md-4"><figure class="testimonio mb-0"><blockquote class="mb-3">“Me gusta saber en qué se usa mi aporte. Las actualizaciones del emprendedor me dan tranquilidad.”</blockquote><footer>Andrea, colaboradora · Cochabamba <span class="etiqueta-demo">Demo</span></footer></figure></div>
      <div class="col-md-4"><figure class="testimonio mb-0"><blockquote class="mb-3">“Antes dependíamos de intermediarios. Aquí presentamos el proyecto de la comunidad directamente.”</blockquote><footer>Jhonny, gestor comunitario · Uyuni <span class="etiqueta-demo">Demo</span></footer></figure></div>
    </div>
  </div>
</section>

<section class="seccion seccion-blanca" id="preguntas">
  <div class="container" style="max-width:860px">
    <h2 class="titulo-seccion mb-4">Preguntas frecuentes</h2>
    <div class="accordion" id="faq">
      <?php
      $faq = [
        ['¿Se cobra dinero real en la plataforma?', 'No. Esta es una versión demostrativa: los aportes son simulados, no se piden datos de tarjetas ni cuentas bancarias. La arquitectura queda lista para integrar una pasarela de pagos autorizada.'],
        ['¿Quién puede publicar un proyecto?', 'Cualquier persona registrada como emprendedor. Cada proyecto pasa por una revisión del administrador antes de aparecer en el catálogo.'],
        ['¿Qué pasa si un proyecto no llega a su meta?', 'La campaña finaliza en la fecha límite. En esta versión el estado queda como finalizada y el administrador puede revisar el caso.'],
        ['¿Cómo sé que el dinero se usa bien?', 'Cada proyecto muestra su presupuesto planificado y el gasto real que reporta el emprendedor, además de actualizaciones con fecha.'],
        ['¿Puedo apoyar más de un proyecto?', 'Sí. Puedes apoyar todos los proyectos que quieras y consultar tu historial y comprobantes desde tu panel.'],
      ];
      foreach ($faq as $i => [$preg, $resp]): ?>
      <div class="accordion-item">
        <h3 class="accordion-header"><button class="accordion-button <?= $i ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>" aria-expanded="<?= $i ? 'false' : 'true' ?>"><?= e($preg) ?></button></h3>
        <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i ? '' : 'show' ?>" data-bs-parent="#faq"><div class="accordion-body"><?= e($resp) ?></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="seccion">
  <div class="container">
    <div class="cta d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
      <div><h2 class="h3 mb-1">¿Tienes una idea que merece crecer?</h2><p class="mb-0" style="color:#CBD5F5"><?= e(APP_ESLOGAN) ?>.</p></div>
      <a class="btn btn-oro btn-lg" href="<?= url('registro.php') ?>">Crear mi cuenta</a>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
