<?php
require_once __DIR__ . '/includes/autenticacion.php';
cerrar_sesion();
session_start();
flash('success', 'Cerraste sesión correctamente.');
redirigir('index.php');
