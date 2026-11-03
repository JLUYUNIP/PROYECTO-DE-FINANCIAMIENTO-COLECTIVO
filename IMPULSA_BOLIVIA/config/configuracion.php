<?php
// Configuración general de IMPULSA BOLIVIA
define('APP_NOMBRE', 'IMPULSA BOLIVIA');
define('APP_ESLOGAN', 'Juntos impulsamos los sueños que transforman Bolivia');

// Carpeta del proyecto dentro de htdocs. Si la renombras, cambia este valor.
define('BASE_URL', '/IMPULSA_BOLIVIA');

// Base de datos (valores por defecto de XAMPP)
define('DB_HOST', 'localhost');
define('DB_NAME', 'impulsa_bolivia');
define('DB_USER', 'root');
define('DB_PASS', '');

define('RAIZ', dirname(__DIR__));
define('UPLOAD_DIR', RAIZ . '/uploads/');
define('MONEDA', 'Bs');
define('APORTE_MINIMO', 10);
define('SESION_MAX_INACTIVIDAD', 7200); // 2 horas

date_default_timezone_set('America/La_Paz');

const DEPARTAMENTOS = ['Beni','Chuquisaca','Cochabamba','La Paz','Oruro','Pando','Potosí','Santa Cruz','Tarija'];

const CONCEPTOS = [
    'maquinaria' => 'Maquinaria', 'materia_prima' => 'Materia prima', 'equipamiento' => 'Equipamiento',
    'infraestructura' => 'Infraestructura', 'mano_de_obra' => 'Mano de obra', 'transporte' => 'Transporte',
    'publicidad' => 'Publicidad', 'otros' => 'Otros',
];
