// Validación en el navegador (la validación definitiva siempre se hace en el servidor)
(function () {
  'use strict';

  // Bootstrap: marcar campos inválidos al enviar
  document.querySelectorAll('form.needs-validation').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!form.checkValidity()) { ev.preventDefault(); ev.stopPropagation(); }
      form.classList.add('was-validated');
    });
  });

  // Mostrar u ocultar contraseña
  document.querySelectorAll('[data-ver-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var campo = document.getElementById(btn.getAttribute('data-ver-password'));
      var oculto = campo.type === 'password';
      campo.type = oculto ? 'text' : 'password';
      btn.querySelector('i').className = oculto ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
      btn.setAttribute('aria-label', oculto ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
  });

  // Indicador de fuerza de la contraseña
  var pass = document.getElementById('password');
  var barra = document.querySelector('.fuerza span');
  if (pass && barra) {
    pass.addEventListener('input', function () {
      var v = pass.value, p = 0;
      if (v.length >= 8) p++; if (/[A-Z]/.test(v)) p++; if (/[a-z]/.test(v)) p++; if (/\d/.test(v)) p++; if (/[^A-Za-z0-9]/.test(v)) p++;
      barra.style.width = (p * 20) + '%';
      barra.style.background = p <= 2 ? '#D6336C' : (p === 3 ? '#F2A900' : '#12805C');
    });
  }

  // Confirmar contraseña
  var conf = document.getElementById('password2');
  if (pass && conf) {
    var comparar = function () { conf.setCustomValidity(conf.value && conf.value !== pass.value ? 'Las contraseñas no coinciden' : ''); };
    pass.addEventListener('input', comparar); conf.addEventListener('input', comparar);
  }

  // Registro: campos exclusivos del emprendedor
  var tipos = document.querySelectorAll('input[name="tipo"]');
  var extra = document.getElementById('campos-emprendedor');
  if (tipos.length && extra) {
    var alternar = function () {
      var emp = document.querySelector('input[name="tipo"]:checked').value === 'emprendedor';
      extra.classList.toggle('d-none', !emp);
      extra.querySelectorAll('[data-req-emprendedor]').forEach(function (c) { c.required = emp; });
    };
    tipos.forEach(function (t) { t.addEventListener('change', alternar); });
    alternar();
  }
})();
