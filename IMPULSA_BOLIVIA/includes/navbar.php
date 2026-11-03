<?php
$u = usuario_actual();
$activa = $pagina_activa ?? '';
$publicar = ($u && $u['rol'] === 'emprendedor' && existe_ruta('proyectos/publicar.php')) ? 'proyectos/publicar.php' : (!$u ? 'registro.php?tipo=emprendedor' : '');
$enlaces = [
    'inicio' => ['Inicio', 'index.php'],
    'explorar' => ['Explorar proyectos', 'proyectos/index.php'],
    'como' => ['Cómo funciona', 'index.php#como-funciona'],
];
if ($publicar !== '') $enlaces['publicar'] = ['Publicar proyecto', $publicar];
?>
<nav class="navbar navbar-expand-lg barra-principal sticky-top" aria-label="Menú principal">
  <div class="container">
    <a class="navbar-brand marca" href="<?= url('index.php') ?>">
      <svg class="marca-logo" viewBox="0 0 24 24" width="30" height="30" aria-hidden="true"><path d="M8 2h8v4h4v4h4v4h-4v4h-4v4H8v-4H4v-4H0v-4h4V6h4z" fill="#F2A900"/><circle cx="12" cy="12" r="3.2" fill="#0A1A44"/></svg>
      <span>IMPULSA <b>BOLIVIA</b></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal" aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menú">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="menuPrincipal">
      <ul class="navbar-nav mx-lg-auto mb-2 mb-lg-0">
        <?php foreach ($enlaces as $clave => [$texto, $ruta]): ?>
        <li class="nav-item"><a class="nav-link <?= $activa === $clave ? 'active' : '' ?>" href="<?= url($ruta) ?>"><?= e($texto) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="d-flex align-items-lg-center gap-2 flex-column flex-lg-row">
        <?php if ($u): ?>
          <div class="dropdown">
            <button class="btn btn-usuario dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fa-solid fa-circle-user me-1"></i><?= e(explode(' ', $u['nombre_completo'])[0]) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><span class="dropdown-item-text small text-muted text-capitalize"><?= e($u['rol']) ?></span></li>
              <li><a class="dropdown-item" href="<?= url(ruta_panel($u['rol'])) ?>"><i class="fa-solid fa-gauge me-2"></i>Mi panel</a></li>
              <?php if ($u['rol'] === 'colaborador' && existe_ruta('aportes/historial.php')): ?>
              <li><a class="dropdown-item" href="<?= url('aportes/historial.php') ?>"><i class="fa-solid fa-receipt me-2"></i>Mis aportes</a></li>
              <?php endif; ?>
              <?php if (existe_ruta('notificaciones/index.php')): ?>
              <li><a class="dropdown-item" href="<?= url('notificaciones/index.php') ?>"><i class="fa-solid fa-bell me-2"></i>Notificaciones</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= url('logout.php') ?>"><i class="fa-solid fa-right-from-bracket me-2"></i>Cerrar sesión</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a class="btn btn-outline-light btn-sm px-3" href="<?= url('login.php') ?>">Iniciar sesión</a>
          <a class="btn btn-oro btn-sm px-3" href="<?= url('registro.php') ?>">Registrarse</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
