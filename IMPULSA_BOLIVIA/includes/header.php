<?php
// Variables opcionales antes de incluir: $titulo_pagina, $pagina_activa, $body_clase, $es_panel
$titulo_pagina = isset($titulo_pagina) ? $titulo_pagina . ' | ' . APP_NOMBRE : APP_NOMBRE . ' - ' . APP_ESLOGAN;
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo_pagina) ?></title>
<meta name="description" content="Plataforma de financiamiento colectivo para emprendedores bolivianos. Prototipo universitario con aportes simulados.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="<?= url('assets/css/estilos.css') ?>" rel="stylesheet">
<?php if (!empty($es_panel)): ?><link href="<?= url('assets/css/dashboard.css') ?>" rel="stylesheet"><?php endif; ?>
</head>
<body class="<?= e($body_clase ?? '') ?>">
<a class="visually-hidden-focusable salto" href="#contenido">Saltar al contenido</a>
<?php require __DIR__ . '/navbar.php'; ?>
<div class="franja-aguayo" aria-hidden="true"></div>
<?php foreach (obtener_flashes() as $f): ?>
<div class="container mt-3">
  <div class="alert alert-<?= e($f['tipo']) ?> alert-dismissible fade show mb-0" role="alert">
    <?= e($f['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
  </div>
</div>
<?php endforeach; ?>
<main id="contenido">
