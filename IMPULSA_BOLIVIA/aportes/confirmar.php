<?php
require_once __DIR__ . '/../includes/autenticacion.php';
$u = requiere_rol('colaborador');
$pend = $_SESSION['aporte_pendiente'] ?? null;
if (!$pend || time() - $pend['hora'] > 900) {
    unset($_SESSION['aporte_pendiente']);
    flash('warning', 'Tu aporte no está listo para confirmar. Elige el monto otra vez.');
    redirigir('proyectos/index.php');
}
$id = (int)$pend['id']; $monto = round((float)$pend['monto'], 2);
$pdo = db();
$st = $pdo->prepare('SELECT p.nombre, p.ciudad, p.departamento, u.nombre_completo AS emprendedor FROM proyectos p JOIN usuarios u ON u.id_usuario=p.id_usuario WHERE p.id_proyecto=?');
$st->execute([$id]); $info = $st->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    if (post('accion') === 'cancelar') { unset($_SESSION['aporte_pendiente']); redirigir('proyectos/detalle.php?id=' . $id); }

    $codigo = 'IB-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    $financiado = false;
    try {
        $pdo->beginTransaction();
        $s = $pdo->prepare('SELECT * FROM proyectos WHERE id_proyecto=? FOR UPDATE'); $s->execute([$id]); $p = $s->fetch();
        if (!$p || $p['estado'] !== 'activo' || $p['fecha_fin'] < date('Y-m-d')) throw new RuntimeException('La campaña ya no recibe aportes.');
        $faltante = round($p['meta_financiamiento'] - $p['total_recaudado'], 2);
        if ($monto <= 0 || $monto > $faltante) throw new RuntimeException('El monto supera lo que falta para la meta. Elige un monto menor.');

        $pdo->prepare("INSERT INTO aportes (id_usuario, id_proyecto, monto, estado, codigo_comprobante, tipo_aporte) VALUES (?,?,?, 'confirmado', ?, 'simulado')")
            ->execute([$u['id_usuario'], $id, $monto, $codigo]);
        $pdo->prepare("UPDATE proyectos SET total_recaudado = total_recaudado + ?, num_colaboradores = (SELECT COUNT(DISTINCT a.id_usuario) FROM aportes a WHERE a.id_proyecto=? AND a.estado='confirmado') WHERE id_proyecto=?")
            ->execute([$monto, $id, $id]);
        if (round($p['total_recaudado'] + $monto, 2) >= round($p['meta_financiamiento'], 2)) {
            $financiado = true;
            $pdo->prepare("UPDATE proyectos SET estado='financiado' WHERE id_proyecto=?")->execute([$id]);
            $pdo->prepare("UPDATE campanas SET estado='financiada' WHERE id_proyecto=?")->execute([$id]);
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        unset($_SESSION['aporte_pendiente']);
        flash('danger', $ex instanceof RuntimeException ? $ex->getMessage() : 'No se pudo registrar el aporte. Inténtalo de nuevo.');
        redirigir('proyectos/detalle.php?id=' . $id);
    }
    unset($_SESSION['aporte_pendiente']);
    crear_notificacion((int)$u['id_usuario'], 'Aporte realizado', 'Tu aporte simulado de ' . formato_bs($monto) . ' a «' . $info['nombre'] . '» fue confirmado. Comprobante ' . $codigo . '.');
    $d = $pdo->prepare('SELECT id_usuario FROM proyectos WHERE id_proyecto=?'); $d->execute([$id]); $dueno = (int)$d->fetchColumn();
    crear_notificacion($dueno, 'Nuevo aporte recibido', $u['nombre_completo'] . ' aportó ' . formato_bs($monto) . ' a «' . $info['nombre'] . '».');
    if ($financiado) crear_notificacion($dueno, 'Campaña financiada', '«' . $info['nombre'] . '» alcanzó el 100% de su meta.');
    registrar_accion((int)$u['id_usuario'], 'aporte', $codigo . ' · ' . $monto);
    flash('success', $financiado ? '¡Tu aporte completó la meta del proyecto!' : 'Tu aporte fue registrado. ¡Gracias por impulsar este proyecto!');
    redirigir('aportes/comprobante.php?codigo=' . urlencode($codigo));
}
$titulo_pagina = 'Confirmar aporte'; $pagina_activa = 'explorar';
require __DIR__ . '/../includes/header.php';
?>
<section class="seccion"><div class="container" style="max-width:600px"><div class="tarjeta-form">
  <h1 class="h3 mb-3">Confirma tu aporte</h1>
  <dl class="row mb-3">
    <dt class="col-5">Proyecto</dt><dd class="col-7"><?= e($info['nombre']) ?></dd>
    <dt class="col-5">Emprendedor</dt><dd class="col-7"><?= e($info['emprendedor']) ?></dd>
    <dt class="col-5">Ubicación</dt><dd class="col-7"><?= e($info['ciudad']) ?>, <?= e($info['departamento']) ?></dd>
    <dt class="col-5">Colaborador</dt><dd class="col-7"><?= e($u['nombre_completo']) ?></dd>
    <dt class="col-5">Tipo de aporte</dt><dd class="col-7">Simulado</dd>
    <dt class="col-5 fs-5">Monto</dt><dd class="col-7 fs-4 fw-bold text-primary"><?= formato_bs($monto) ?></dd>
  </dl>
  <div class="alert alert-warning small">Al confirmar se registra un aporte demostrativo. No se realiza ningún cobro real.</div>
  <form method="post" class="d-flex gap-2"><?= csrf_campo() ?>
    <button class="btn btn-outline-secondary flex-fill" name="accion" value="cancelar" type="submit">Cancelar</button>
    <button class="btn btn-oro flex-fill" name="accion" value="confirmar" type="submit"><i class="fa-solid fa-check me-1"></i>Confirmar aporte</button>
  </form>
</div></div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
