// Comportamientos generales de IMPULSA BOLIVIA
(function () {
  'use strict';

  // Monto de aporte: los botones rápidos rellenan el campo personalizado
  var campoMonto = document.getElementById('monto_personalizado');
  document.querySelectorAll('.chips-monto input[type="radio"]').forEach(function (r) {
    r.addEventListener('change', function () { if (campoMonto) campoMonto.value = r.value; actualizarResumen(); });
  });
  if (campoMonto) {
    campoMonto.addEventListener('input', function () {
      document.querySelectorAll('.chips-monto input[type="radio"]').forEach(function (r) { r.checked = (r.value === campoMonto.value); });
      actualizarResumen();
    });
  }
  function actualizarResumen() {
    var salida = document.getElementById('resumen-monto');
    if (!salida || !campoMonto) return;
    var v = parseFloat(String(campoMonto.value).replace(',', '.'));
    salida.textContent = isNaN(v) ? 'Bs 0,00' : 'Bs ' + v.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // Confirmación antes de acciones delicadas
  document.querySelectorAll('[data-confirmar]').forEach(function (el) {
    el.addEventListener('click', function (ev) { if (!window.confirm(el.getAttribute('data-confirmar'))) ev.preventDefault(); });
  });

  // Tooltips de Bootstrap
  if (window.bootstrap) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (t) { new bootstrap.Tooltip(t); });
  }
})();
