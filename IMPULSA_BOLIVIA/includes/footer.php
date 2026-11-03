</main>
<?php if (empty($es_panel)): ?>
<div class="franja-aguayo" aria-hidden="true"></div>
<footer class="pie">
  <div class="container py-5">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="marca text-white mb-2"><svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true"><path d="M8 2h8v4h4v4h4v4h-4v4h-4v4H8v-4H4v-4H0v-4h4V6h4z" fill="#F2A900"/><circle cx="12" cy="12" r="3.2" fill="#0A1A44"/></svg> <span>IMPULSA <b>BOLIVIA</b></span></div>
        <p class="small mb-0"><?= e(APP_ESLOGAN) ?>.</p>
        <p class="small mt-3 mb-0 opacity-75">Prototipo universitario de Ingeniería Económica y Financiera. Los aportes son simulados: no se cobra dinero ni se piden datos bancarios.</p>
      </div>
      <div class="col-6 col-lg-2">
        <h2 class="h6 text-white">Plataforma</h2>
        <ul class="list-unstyled small">
          <li><a href="<?= url('proyectos/index.php') ?>">Explorar proyectos</a></li>
          <li><a href="<?= url('index.php#como-funciona') ?>">Cómo funciona</a></li>
          <li><a href="<?= url('index.php#preguntas') ?>">Preguntas frecuentes</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h2 class="h6 text-white">Cuenta</h2>
        <ul class="list-unstyled small">
          <li><a href="<?= url('login.php') ?>">Iniciar sesión</a></li>
          <li><a href="<?= url('registro.php?tipo=emprendedor') ?>">Soy emprendedor</a></li>
          <li><a href="<?= url('registro.php?tipo=colaborador') ?>">Quiero apoyar</a></li>
        </ul>
      </div>
      <div class="col-lg-4">
        <h2 class="h6 text-white">Contacto</h2>
        <ul class="list-unstyled small">
          <li><i class="fa-solid fa-envelope me-2"></i>contacto@impulsabolivia.bo</li>
          <li><i class="fa-solid fa-phone me-2"></i>+591 2 000 0000</li>
          <li><i class="fa-solid fa-location-dot me-2"></i>La Paz, Bolivia</li>
        </ul>
        <p class="small opacity-75 mb-0">Datos de contacto de demostración.</p>
      </div>
    </div>
  </div>
  <div class="pie-legal"><div class="container small py-3">© <?= date('Y') ?> IMPULSA BOLIVIA · Proyecto académico</div></div>
</footer>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($usar_chartjs)): ?><script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script><?php endif; ?>
<script src="<?= url('assets/js/validaciones.js') ?>"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
<?= $js_inline ?? '' ?>
</body>
</html>
