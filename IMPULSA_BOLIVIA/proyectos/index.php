<?php
require_once __DIR__ . '/../includes/autenticacion.php';
actualizar_estados_campanas();
$pdo = db();

$q = get('q'); $cat = (int)get('categoria'); $dep = get('departamento'); $ciudad = get('ciudad');
$estado = get('estado'); $avance = get('avance'); $orden = get('orden') ?: 'recientes';
$pagina = max(1, (int)get('pagina')); $por_pagina = 9;

$where = ["p.estado IN ('activo','financiado','finalizado')"]; $par = [];
if ($q !== '') { $like = '%' . addcslashes($q, '%_\\') . '%'; $where[] = '(p.nombre LIKE ? OR p.resumen LIKE ? OR p.descripcion LIKE ?)'; array_push($par, $like, $like, $like); }
if ($cat > 0) { $where[] = 'p.id_categoria = ?'; $par[] = $cat; }
if (in_array($dep, DEPARTAMENTOS, true)) { $where[] = 'p.departamento = ?'; $par[] = $dep; } else { $dep = ''; }
if ($ciudad !== '') { $where[] = 'p.ciudad = ?'; $par[] = $ciudad; }
if (in_array($estado, ['activo', 'financiado', 'finalizado'], true)) { $where[] = 'p.estado = ?'; $par[] = $estado; } else { $estado = ''; }
$rangos = ['0-25' => [0, 25], '25-50' => [25, 50], '50-75' => [50, 75], '75-100' => [75, 1000000]];
if (isset($rangos[$avance])) { $where[] = '(p.total_recaudado / p.meta_financiamiento * 100) >= ? AND (p.total_recaudado / p.meta_financiamiento * 100) < ?'; array_push($par, ...$rangos[$avance]); } else { $avance = ''; }
$orden_sql = 'p.fecha_creacion DESC, p.id_proyecto DESC';
if ($orden === 'finalizar') { $where[] = "p.estado='activo' AND p.fecha_fin >= CURDATE()"; $orden_sql = 'p.fecha_fin ASC'; }
elseif ($orden === 'avance') { $orden_sql = '(p.total_recaudado / p.meta_financiamiento) DESC'; }
else { $orden = 'recientes'; }

$cond = implode(' AND ', $where);
$st = $pdo->prepare("SELECT COUNT(*) FROM proyectos p WHERE $cond"); $st->execute($par);
$total = (int)$st->fetchColumn();
$paginas = max(1, (int)ceil($total / $por_pagina)); $pagina = min($pagina, $paginas);
$offset = ($pagina - 1) * $por_pagina;
$st = $pdo->prepare("SELECT p.*, c.nombre AS categoria, c.icono, u.nombre_completo AS emprendedor
  FROM proyectos p JOIN categorias c ON c.id_categoria=p.id_categoria JOIN usuarios u ON u.id_usuario=p.id_usuario
  WHERE $cond ORDER BY $orden_sql LIMIT $por_pagina OFFSET $offset");
$st->execute($par);
$proyectos = $st->fetchAll();

$categorias = $pdo->query("SELECT id_categoria, nombre FROM categorias WHERE estado='activo' ORDER BY nombre")->fetchAll();
$ciudades = $pdo->query("SELECT DISTINCT ciudad FROM proyectos WHERE estado IN ('activo','financiado','finalizado') ORDER BY ciudad")->fetchAll(PDO::FETCH_COLUMN);
$filtros = array_filter(['q' => $q, 'categoria' => $cat ?: '', 'departamento' => $dep, 'ciudad' => $ciudad, 'estado' => $estado, 'avance' => $avance, 'orden' => $orden !== 'recientes' ? $orden : '']);

$titulo_pagina = 'Explorar proyectos'; $pagina_activa = 'explorar';
require __DIR__ . '/../includes/header.php';
?>
<section class="cabecera-pagina">
  <div class="container"><h1>Explorar proyectos</h1><p>Encuentra emprendimientos bolivianos y apoya los que más te inspiren.</p></div>
</section>
<section class="seccion pt-4">
  <div class="container">
    <form class="filtros mb-4" method="get" role="search" aria-label="Filtrar proyectos">
      <div class="row g-3">
        <div class="col-lg-4"><label class="form-label" for="q">Buscar</label><input class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Nombre o palabra clave"></div>
        <div class="col-6 col-lg-2"><label class="form-label" for="categoria">Categoría</label><select class="form-select" id="categoria" name="categoria"><option value="">Todas</option><?php foreach ($categorias as $c): ?><option value="<?= (int)$c['id_categoria'] ?>" <?= $cat === (int)$c['id_categoria'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="col-6 col-lg-2"><label class="form-label" for="departamento">Departamento</label><select class="form-select" id="departamento" name="departamento"><option value="">Todos</option><?php foreach (DEPARTAMENTOS as $d): ?><option <?= $dep === $d ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?></select></div>
        <div class="col-6 col-lg-2"><label class="form-label" for="ciudad">Ciudad</label><select class="form-select" id="ciudad" name="ciudad"><option value="">Todas</option><?php foreach ($ciudades as $c): ?><option <?= $ciudad === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
        <div class="col-6 col-lg-2"><label class="form-label" for="estado">Estado</label><select class="form-select" id="estado" name="estado"><option value="">Todos</option><option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activas</option><option value="financiado" <?= $estado === 'financiado' ? 'selected' : '' ?>>Financiadas</option><option value="finalizado" <?= $estado === 'finalizado' ? 'selected' : '' ?>>Finalizadas</option></select></div>
        <div class="col-6 col-lg-3"><label class="form-label" for="avance">Avance</label><select class="form-select" id="avance" name="avance"><option value="">Cualquiera</option><?php foreach (['0-25' => 'Menos del 25%', '25-50' => '25% a 50%', '50-75' => '50% a 75%', '75-100' => '75% o más'] as $k => $v): ?><option value="<?= $k ?>" <?= $avance === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
        <div class="col-6 col-lg-3"><label class="form-label" for="orden">Ordenar por</label><select class="form-select" id="orden" name="orden"><option value="recientes" <?= $orden === 'recientes' ? 'selected' : '' ?>>Más recientes</option><option value="finalizar" <?= $orden === 'finalizar' ? 'selected' : '' ?>>Próximos a finalizar</option><option value="avance" <?= $orden === 'avance' ? 'selected' : '' ?>>Mayor avance</option></select></div>
        <div class="col-12 col-lg-6 d-flex align-items-end gap-2 justify-content-lg-end"><a class="btn btn-outline-secondary" href="<?= url('proyectos/index.php') ?>">Limpiar</a><button class="btn btn-azul px-4" type="submit"><i class="fa-solid fa-magnifying-glass me-1"></i>Buscar</button></div>
      </div>
    </form>
    <p class="text-muted" aria-live="polite"><?= $total ?> <?= $total === 1 ? 'proyecto encontrado' : 'proyectos encontrados' ?></p>
    <?php if ($proyectos): ?>
      <div class="row g-4">
        <?php foreach ($proyectos as $p): ?><div class="col-md-6 col-lg-4"><?php include __DIR__ . '/../includes/tarjeta_proyecto.php'; ?></div><?php endforeach; ?>
      </div>
      <?php if ($paginas > 1): ?>
      <nav class="mt-5" aria-label="Paginación"><ul class="pagination justify-content-center flex-wrap">
        <?php for ($i = 1; $i <= $paginas; $i++): ?>
          <li class="page-item <?= $i === $pagina ? 'active' : '' ?>"><a class="page-link" href="?<?= e(http_build_query($filtros + ['pagina' => $i])) ?>"><?= $i ?></a></li>
        <?php endfor; ?>
      </ul></nav>
      <?php endif; ?>
    <?php else: ?>
      <div class="vacio"><i class="fa-solid fa-magnifying-glass d-block"></i><p class="mb-2">No encontramos proyectos con esos filtros.</p><a class="btn btn-outline-azul" href="<?= url('proyectos/index.php') ?>">Ver todos los proyectos</a></div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
