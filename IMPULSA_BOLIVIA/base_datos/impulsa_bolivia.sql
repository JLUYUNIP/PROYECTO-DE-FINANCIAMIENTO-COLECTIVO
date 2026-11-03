-- IMPULSA BOLIVIA - Base de datos (MySQL / MariaDB, XAMPP + phpMyAdmin)
-- Importar desde phpMyAdmin > Importar. Elimina y vuelve a crear la base impulsa_bolivia.

DROP DATABASE IF EXISTS impulsa_bolivia;
CREATE DATABASE impulsa_bolivia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE impulsa_bolivia;
SET NAMES utf8mb4;

CREATE TABLE usuarios (
  id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre_completo VARCHAR(120) NOT NULL,
  ci VARCHAR(20) NULL,
  correo VARCHAR(120) NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  password VARCHAR(255) NOT NULL,
  rol ENUM('administrador','emprendedor','colaborador') NOT NULL DEFAULT 'colaborador',
  departamento VARCHAR(30) NOT NULL,
  ciudad VARCHAR(60) NOT NULL,
  direccion VARCHAR(200) NULL,
  descripcion TEXT NULL,
  foto VARCHAR(255) NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_usuarios_correo (correo),
  UNIQUE KEY uq_usuarios_ci (ci),
  KEY idx_usuarios_rol (rol),
  KEY idx_usuarios_depto (departamento)
) ENGINE=InnoDB;

CREATE TABLE categorias (
  id_categoria INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL,
  descripcion VARCHAR(255) NULL,
  icono VARCHAR(40) NOT NULL DEFAULT 'lightbulb',
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB;

CREATE TABLE proyectos (
  id_proyecto INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NOT NULL,
  id_categoria INT UNSIGNED NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  resumen VARCHAR(220) NOT NULL,
  descripcion TEXT NOT NULL,
  problema TEXT NOT NULL,
  objetivo TEXT NOT NULL,
  departamento VARCHAR(30) NOT NULL,
  ciudad VARCHAR(60) NOT NULL,
  direccion VARCHAR(200) NULL,
  meta_financiamiento DECIMAL(12,2) NOT NULL,
  total_recaudado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  num_colaboradores INT UNSIGNED NOT NULL DEFAULT 0,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  imagen_principal VARCHAR(255) NULL,
  video_url VARCHAR(255) NULL,
  explicacion_recursos TEXT NULL,
  estado ENUM('pendiente','correccion','rechazado','activo','financiado','finalizado','suspendido','cancelado') NOT NULL DEFAULT 'pendiente',
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_proyectos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
  CONSTRAINT fk_proyectos_categoria FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria),
  CONSTRAINT ck_proyectos_meta CHECK (meta_financiamiento > 0),
  CONSTRAINT ck_proyectos_recaudado CHECK (total_recaudado >= 0),
  CONSTRAINT ck_proyectos_fechas CHECK (fecha_fin >= fecha_inicio),
  KEY idx_proyectos_estado (estado),
  KEY idx_proyectos_depto (departamento),
  KEY idx_proyectos_fin (fecha_fin),
  KEY idx_proyectos_usuario (id_usuario),
  KEY idx_proyectos_categoria (id_categoria)
) ENGINE=InnoDB;

CREATE TABLE imagenes_proyecto (
  id_imagen INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_proyecto INT UNSIGNED NOT NULL,
  ruta VARCHAR(255) NOT NULL,
  orden TINYINT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_imagenes_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyectos(id_proyecto) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE presupuesto_proyecto (
  id_presupuesto INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_proyecto INT UNSIGNED NOT NULL,
  concepto ENUM('maquinaria','materia_prima','equipamiento','infraestructura','mano_de_obra','transporte','publicidad','otros') NOT NULL,
  descripcion VARCHAR(255) NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  tipo ENUM('planificado','real') NOT NULL DEFAULT 'planificado',
  CONSTRAINT fk_presupuesto_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyectos(id_proyecto) ON DELETE CASCADE,
  CONSTRAINT ck_presupuesto_monto CHECK (monto >= 0),
  KEY idx_presupuesto_proyecto (id_proyecto)
) ENGINE=InnoDB;

CREATE TABLE ejecucion_presupuesto (
  id_ejecucion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_presupuesto INT UNSIGNED NOT NULL,
  monto_ejecutado DECIMAL(12,2) NOT NULL,
  descripcion VARCHAR(255) NOT NULL,
  fecha DATE NOT NULL,
  CONSTRAINT fk_ejecucion_presupuesto FOREIGN KEY (id_presupuesto) REFERENCES presupuesto_proyecto(id_presupuesto) ON DELETE CASCADE,
  CONSTRAINT ck_ejecucion_monto CHECK (monto_ejecutado >= 0)
) ENGINE=InnoDB;

CREATE TABLE campanas (
  id_campana INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_proyecto INT UNSIGNED NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  meta DECIMAL(12,2) NOT NULL,
  estado ENUM('activa','financiada','finalizada','suspendida','cancelada') NOT NULL DEFAULT 'activa',
  UNIQUE KEY uq_campanas_proyecto (id_proyecto),
  CONSTRAINT fk_campanas_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyectos(id_proyecto) ON DELETE CASCADE,
  CONSTRAINT ck_campanas_meta CHECK (meta > 0)
) ENGINE=InnoDB;

CREATE TABLE aportes (
  id_aporte INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NOT NULL,
  id_proyecto INT UNSIGNED NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  fecha_aporte DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado ENUM('pendiente','confirmado','anulado') NOT NULL DEFAULT 'pendiente',
  codigo_comprobante VARCHAR(30) NOT NULL,
  tipo_aporte ENUM('simulado','real') NOT NULL DEFAULT 'simulado',
  UNIQUE KEY uq_aportes_codigo (codigo_comprobante),
  CONSTRAINT fk_aportes_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
  CONSTRAINT fk_aportes_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyectos(id_proyecto),
  CONSTRAINT ck_aportes_monto CHECK (monto > 0),
  KEY idx_aportes_usuario (id_usuario),
  KEY idx_aportes_proyecto (id_proyecto),
  KEY idx_aportes_fecha (fecha_aporte)
) ENGINE=InnoDB;

CREATE TABLE actualizaciones (
  id_actualizacion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_proyecto INT UNSIGNED NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  contenido TEXT NOT NULL,
  imagen VARCHAR(255) NULL,
  fecha_publicacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_actualizaciones_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyectos(id_proyecto) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE comentarios (
  id_comentario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NOT NULL,
  id_proyecto INT UNSIGNED NOT NULL,
  contenido VARCHAR(800) NOT NULL,
  estado ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_comentarios_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
  CONSTRAINT fk_comentarios_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyectos(id_proyecto) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE favoritos (
  id_favorito INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NOT NULL,
  id_proyecto INT UNSIGNED NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_favoritos (id_usuario, id_proyecto),
  CONSTRAINT fk_favoritos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  CONSTRAINT fk_favoritos_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyectos(id_proyecto) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notificaciones (
  id_notificacion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  mensaje VARCHAR(400) NOT NULL,
  leida TINYINT(1) NOT NULL DEFAULT 0,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notificaciones_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  KEY idx_notificaciones_usuario (id_usuario, leida)
) ENGINE=InnoDB;

CREATE TABLE reportes (
  id_reporte INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NOT NULL,
  tipo VARCHAR(60) NOT NULL,
  descripcion VARCHAR(255) NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reportes_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

CREATE TABLE historial_estados (
  id_historial INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_proyecto INT UNSIGNED NOT NULL,
  id_administrador INT UNSIGNED NOT NULL,
  estado_anterior VARCHAR(20) NOT NULL,
  estado_nuevo VARCHAR(20) NOT NULL,
  observacion VARCHAR(400) NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_historial_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyectos(id_proyecto) ON DELETE CASCADE,
  CONSTRAINT fk_historial_admin FOREIGN KEY (id_administrador) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

CREATE TABLE recuperacion_password (
  id_recuperacion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  fecha_expiracion DATETIME NOT NULL,
  usado TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_recuperacion_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  KEY idx_recuperacion_token (token_hash)
) ENGINE=InnoDB;

CREATE TABLE registro_acciones (
  id_registro INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NULL,
  accion VARCHAR(80) NOT NULL,
  detalle VARCHAR(400) NULL,
  ip VARCHAR(45) NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_registro_usuario (id_usuario)
) ENGINE=InnoDB;

CREATE TABLE configuracion (
  clave VARCHAR(60) PRIMARY KEY,
  valor VARCHAR(255) NOT NULL
) ENGINE=InnoDB;


INSERT INTO configuracion (clave, valor) VALUES ('aporte_minimo','10'),('aporte_maximo','50000'),('moneda','Bs'),('modo_aportes','simulado');

INSERT INTO categorias (id_categoria,nombre,descripcion,icono) VALUES
(1,'Agricultura','Cultivos, huertos y producción agrícola','seedling'),
(2,'Ganadería','Cría de animales y acuicultura','cow'),
(3,'Alimentación','Transformación y venta de alimentos','utensils'),
(4,'Tecnología','Software, dispositivos e innovación','microchip'),
(5,'Educación','Formación y capacitación','graduation-cap'),
(6,'Artesanía','Productos hechos a mano','palette'),
(7,'Comercio','Tiendas y distribución','store'),
(8,'Manufactura','Talleres y producción industrial','industry'),
(9,'Turismo','Servicios turísticos y hospedaje','mountain-sun'),
(10,'Medio ambiente','Reciclaje y energías limpias','recycle'),
(11,'Otros','Otros emprendimientos productivos','lightbulb');

INSERT INTO usuarios (id_usuario,nombre_completo,ci,correo,telefono,password,rol,departamento,ciudad,direccion,descripcion) VALUES
(1,'Administrador General','1000001','admin@impulsabolivia.bo','70000001','$2y$10$EQcwTBOLJWNwHVBmbcN/auzeBxV1rcf2OwW5lkvca/OxueNHQSq82','administrador','La Paz','La Paz','Av. Camacho 1485','Administrador de la plataforma.'),
(2,'María Fernanda Quispe Mamani','6543210','mquispe@demo.bo','71234501','$2y$10$spxncv.1JZoN9oQVEfw3kuGWnH.vTKHsUSGvdszi8WqRXf8XI/fO.','emprendedor','Oruro','Salinas de Garci Mendoza','Calle Bolívar s/n','Productora de quinua y presidenta de una asociación de mujeres tejedoras.'),
(3,'Carlos Alberto Choque Villca','7123456','cchoque@demo.bo','72345602','$2y$10$spxncv.1JZoN9oQVEfw3kuGWnH.vTKHsUSGvdszi8WqRXf8XI/fO.','emprendedor','La Paz','Caranavi','Zona Villa Imperial, km 4','Caficultor de tercera generación con 6 hectáreas en los Yungas.'),
(4,'Lucía Rojas Antelo','8234567','lrojas@demo.bo','73456703','$2y$10$spxncv.1JZoN9oQVEfw3kuGWnH.vTKHsUSGvdszi8WqRXf8XI/fO.','emprendedor','Santa Cruz','Santa Cruz de la Sierra','Barrio Equipetrol, calle 5','Ingeniera de sistemas enfocada en tecnología para el agro.'),
(5,'Jhonny Mamani Condori','5987654','jmamani@demo.bo','74567804','$2y$10$spxncv.1JZoN9oQVEfw3kuGWnH.vTKHsUSGvdszi8WqRXf8XI/fO.','emprendedor','Potosí','Uyuni','Av. Ferroviaria 120','Guía de turismo y gestor de emprendimientos comunitarios en el Salar.'),
(6,'Andrea Salvatierra Peña',NULL,'asalvatierra@demo.bo','75678905','$2y$10$MeP36CvjaTHbalKm034lTOwYz7oN6RFs45ShQXcs6UsDk4BFk9yDO','colaborador','Cochabamba','Cochabamba',NULL,NULL),
(7,'Diego Vargas Rocha',NULL,'dvargas@demo.bo','76789006','$2y$10$MeP36CvjaTHbalKm034lTOwYz7oN6RFs45ShQXcs6UsDk4BFk9yDO','colaborador','Santa Cruz','Santa Cruz de la Sierra',NULL,NULL),
(8,'Valeria Gutiérrez Soliz',NULL,'vgutierrez@demo.bo','77890107','$2y$10$MeP36CvjaTHbalKm034lTOwYz7oN6RFs45ShQXcs6UsDk4BFk9yDO','colaborador','Tarija','Tarija',NULL,NULL),
(9,'Rodrigo Flores Arce',NULL,'rflores@demo.bo','78901208','$2y$10$MeP36CvjaTHbalKm034lTOwYz7oN6RFs45ShQXcs6UsDk4BFk9yDO','colaborador','La Paz','La Paz',NULL,NULL),
(10,'Camila Terrazas Ibáñez',NULL,'cterrazas@demo.bo','79012309','$2y$10$MeP36CvjaTHbalKm034lTOwYz7oN6RFs45ShQXcs6UsDk4BFk9yDO','colaborador','Chuquisaca','Sucre',NULL,NULL);

INSERT INTO proyectos (id_proyecto,id_usuario,id_categoria,nombre,resumen,descripcion,problema,objetivo,departamento,ciudad,meta_financiamiento,fecha_inicio,fecha_fin,explicacion_recursos,estado) VALUES
(1,2,1,'Quinua Real Orgánica: planta de lavado y empaque','Una planta comunitaria para lavar, seleccionar y empacar quinua real con certificación orgánica.','Somos 38 familias productoras de quinua real en el Salar de Uyuni y Salinas de Garci Mendoza. Hoy vendemos el grano sin procesar a intermediarios. Con una planta propia de lavado, selección y empaque podremos vender producto terminado directamente a tiendas y exportadores.','Vendemos la quinua a granel a precios bajos porque no tenemos dónde lavarla ni empacarla, y los intermediarios se quedan con la mayor parte del valor.','Instalar una planta de procesamiento con capacidad para 20 toneladas al mes y lograr la certificación orgánica en el primer año.','Oruro','Salinas de Garci Mendoza',45000.00,DATE_SUB(CURDATE(), INTERVAL 40 DAY),DATE_ADD(CURDATE(), INTERVAL 24 DAY),'Los fondos se destinarán a los rubros del presupuesto detallado. Cada gasto será respaldado con facturas y reportado en las actualizaciones del proyecto.','activo'),
(2,3,3,'Café de altura de Caranavi: secadora solar','Secadoras solares para mejorar la calidad del café de especialidad y vender directo a tostadores.','Cultivamos café arábica a 1.600 msnm. El secado al aire libre sobre el suelo arruina lotes cuando llueve. Con secadoras solares tipo invernadero controlamos humedad y temperatura y logramos puntajes de taza superiores a 84.','Perdemos entre 15% y 20% de la cosecha por mal secado y recibimos precios de café corriente.','Construir 4 secadoras solares y capacitar a 12 familias vecinas en beneficio húmedo y secado controlado.','La Paz','Caranavi',38000.00,DATE_SUB(CURDATE(), INTERVAL 35 DAY),DATE_ADD(CURDATE(), INTERVAL 6 DAY),'Los fondos se destinarán a los rubros del presupuesto detallado. Cada gasto será respaldado con facturas y reportado en las actualizaciones del proyecto.','activo'),
(3,2,6,'Hilos del Altiplano: taller de tejido en fibra de alpaca','Equipamos un taller de mujeres tejedoras para producir prendas de alpaca con diseño propio.','Un grupo de 14 mujeres de Challapata hila y teje fibra de alpaca desde hace años, pero solo vende por encargo y con herramientas manuales. Necesitamos telares, una máquina de cardado y capital para materia prima.','Las tejedoras producen pocas prendas por falta de maquinaria y dependen de ferias ocasionales.','Triplicar la producción mensual y abrir un canal de venta en línea y a tiendas de La Paz y Santa Cruz.','Oruro','Challapata',25000.00,DATE_SUB(CURDATE(), INTERVAL 28 DAY),DATE_ADD(CURDATE(), INTERVAL 45 DAY),'Los fondos se destinarán a los rubros del presupuesto detallado. Cada gasto será respaldado con facturas y reportado en las actualizaciones del proyecto.','activo'),
(4,4,4,'AgroSeñal: sensores de riego para pequeños productores','Sensores de humedad de bajo costo con alertas por mensaje de texto para optimizar el riego.','Desarrollamos un sensor de humedad de suelo con alertas por SMS que no necesita internet. Probamos un prototipo en 3 parcelas de hortalizas y redujimos el consumo de agua en un 30%. Buscamos financiar el lote inicial de 150 sensores.','El riego por costumbre desperdicia agua y encarece la producción de hortalizas en zonas con sequías recurrentes.','Producir y entregar 150 sensores a pequeños productores y medir el ahorro de agua durante una campaña agrícola.','Santa Cruz','Santa Cruz de la Sierra',60000.00,DATE_SUB(CURDATE(), INTERVAL 20 DAY),DATE_ADD(CURDATE(), INTERVAL 60 DAY),'Los fondos se destinarán a los rubros del presupuesto detallado. Cada gasto será respaldado con facturas y reportado en las actualizaciones del proyecto.','activo'),
(5,5,9,'Hospedaje comunitario de sal en el Salar de Uyuni','Seis habitaciones de bloques de sal administradas por familias de la comunidad.','Proponemos un hospedaje comunitario con seis habitaciones, comedor y energía solar, administrado por 9 familias. Los ingresos se reparten entre las familias y un fondo para la escuela comunitaria.','Gran parte del dinero del turismo se queda en agencias externas y la comunidad recibe muy poco.','Abrir el hospedaje para la temporada alta y generar empleo estable para al menos 15 personas de la comunidad.','Potosí','Uyuni',52000.00,DATE_SUB(CURDATE(), INTERVAL 30 DAY),DATE_ADD(CURDATE(), INTERVAL 12 DAY),'Los fondos se destinarán a los rubros del presupuesto detallado. Cada gasto será respaldado con facturas y reportado en las actualizaciones del proyecto.','activo'),
(6,4,2,'Piscifactoría de pacú en Trinidad','Estanques de pacú para abastecer mercados de Trinidad con pescado de cultivo.','Construiremos 6 estanques de 400 m2 para criar pacú. El pescado de cultivo da oferta estable durante todo el año y alivia la presión sobre la pesca de río en época de veda.','El mercado local depende de la pesca de río, con oferta irregular y precios altos en época de veda.','Producir 8 toneladas de pacú por ciclo y vender a restaurantes y mercados locales.','Beni','Trinidad',40000.00,DATE_SUB(CURDATE(), INTERVAL 10 DAY),DATE_ADD(CURDATE(), INTERVAL 70 DAY),'Los fondos se destinarán a los rubros del presupuesto detallado. Cada gasto será respaldado con facturas y reportado en las actualizaciones del proyecto.','activo'),
(7,3,10,'Reciclaje de PET: transformamos botellas en hojuelas','Una línea de lavado y trituración de botellas PET para venderlas a industrias de reciclaje.','Recolectamos botellas PET con 25 recicladores de base en Quillacollo. Con una trituradora y una lavadora industrial pasamos de vender botellas sucias a vender hojuela limpia, con mejor precio y menos residuos.','Los recicladores venden el plástico sin procesar a precios mínimos y gran parte termina en botaderos.','Procesar 6 toneladas de PET al mes y formalizar a los recicladores como asociación.','Cochabamba','Quillacollo',35000.00,DATE_SUB(CURDATE(), INTERVAL 25 DAY),DATE_ADD(CURDATE(), INTERVAL 30 DAY),'Los fondos se destinarán a los rubros del presupuesto detallado. Cada gasto será respaldado con facturas y reportado en las actualizaciones del proyecto.','activo'),
(8,5,3,'Cacao silvestre amazónico: chocolate de origen','Un pequeño taller para convertir cacao silvestre beniano en chocolate de origen.','Recolectores de la Amazonía beniana cosechan cacao silvestre. El proyecto financia un taller de fermentado, tostado y moldeado para vender barras de chocolate con identidad amazónica.','El cacao silvestre se vende como materia prima barata y no llega al consumidor final.','Lanzar tres variedades de chocolate de origen y vender en tiendas gourmet de las ciudades principales.','Beni','Riberalta',30000.00,DATE_SUB(CURDATE(), INTERVAL 45 DAY),DATE_ADD(CURDATE(), INTERVAL 5 DAY),'Los fondos se destinarán a los rubros del presupuesto detallado. Cada gasto será respaldado con facturas y reportado en las actualizaciones del proyecto.','financiado'),
(9,2,8,'Madera Tarijeña: taller de carpintería con diseño','Modernizamos un taller de carpintería para fabricar muebles de diseño con madera certificada.','Un taller familiar en Tarija quiere incorporar maquinaria segura y capacitación en diseño para fabricar mobiliario con madera de manejo forestal certificado.','El taller trabaja con herramientas antiguas, genera desperdicio y no cumple con las normas de seguridad.','Fabricar una línea de mobiliario de diseño y generar 6 empleos formales.','Tarija','Tarija',28000.00,DATE_SUB(CURDATE(), INTERVAL 0 DAY),DATE_ADD(CURDATE(), INTERVAL 60 DAY),'Los fondos se destinarán a los rubros del presupuesto detallado. Cada gasto será respaldado con facturas y reportado en las actualizaciones del proyecto.','pendiente');

INSERT INTO campanas (id_proyecto,fecha_inicio,fecha_fin,meta,estado) VALUES
(1,DATE_SUB(CURDATE(), INTERVAL 40 DAY),DATE_ADD(CURDATE(), INTERVAL 24 DAY),45000.00,'activa'),
(2,DATE_SUB(CURDATE(), INTERVAL 35 DAY),DATE_ADD(CURDATE(), INTERVAL 6 DAY),38000.00,'activa'),
(3,DATE_SUB(CURDATE(), INTERVAL 28 DAY),DATE_ADD(CURDATE(), INTERVAL 45 DAY),25000.00,'activa'),
(4,DATE_SUB(CURDATE(), INTERVAL 20 DAY),DATE_ADD(CURDATE(), INTERVAL 60 DAY),60000.00,'activa'),
(5,DATE_SUB(CURDATE(), INTERVAL 30 DAY),DATE_ADD(CURDATE(), INTERVAL 12 DAY),52000.00,'activa'),
(6,DATE_SUB(CURDATE(), INTERVAL 10 DAY),DATE_ADD(CURDATE(), INTERVAL 70 DAY),40000.00,'activa'),
(7,DATE_SUB(CURDATE(), INTERVAL 25 DAY),DATE_ADD(CURDATE(), INTERVAL 30 DAY),35000.00,'activa'),
(8,DATE_SUB(CURDATE(), INTERVAL 45 DAY),DATE_ADD(CURDATE(), INTERVAL 5 DAY),30000.00,'financiada');

INSERT INTO historial_estados (id_proyecto,id_administrador,estado_anterior,estado_nuevo,observacion) VALUES
(1,1,'pendiente','activo','Proyecto revisado y aprobado.'),
(2,1,'pendiente','activo','Proyecto revisado y aprobado.'),
(3,1,'pendiente','activo','Proyecto revisado y aprobado.'),
(4,1,'pendiente','activo','Proyecto revisado y aprobado.'),
(5,1,'pendiente','activo','Proyecto revisado y aprobado.'),
(6,1,'pendiente','activo','Proyecto revisado y aprobado.'),
(7,1,'pendiente','activo','Proyecto revisado y aprobado.'),
(8,1,'pendiente','activo','Proyecto revisado y aprobado.');

INSERT INTO presupuesto_proyecto (id_presupuesto,id_proyecto,concepto,descripcion,monto,tipo) VALUES
(1,1,'infraestructura','Galpón de 120 m2 con piso sanitario',13500.00,'planificado'),
(2,1,'maquinaria','Lavadora y escarificadora de quinua',15750.00,'planificado'),
(3,1,'equipamiento','Balanzas, tamices y empacadora',6750.00,'planificado'),
(4,1,'materia_prima','Bolsas y etiquetas certificadas',3600.00,'planificado'),
(5,1,'mano_de_obra','Capacitación y operarios iniciales',3600.00,'planificado'),
(6,1,'publicidad','Marca y catálogo para exportadores',1800.00,'planificado'),
(7,2,'infraestructura','Cuatro secadoras solares tipo invernadero',20900.00,'planificado'),
(8,2,'equipamiento','Higrómetros y mallas de secado',5700.00,'planificado'),
(9,2,'mano_de_obra','Capacitación en beneficio húmedo',4560.00,'planificado'),
(10,2,'transporte','Traslado de café a tostadores',3800.00,'planificado'),
(11,2,'publicidad','Fotografía y catálogo de microlotes',3040.00,'planificado'),
(12,3,'maquinaria','Dos telares y una cardadora',11250.00,'planificado'),
(13,3,'materia_prima','Fibra de alpaca para el primer ciclo',6250.00,'planificado'),
(14,3,'mano_de_obra','Capacitación en diseño y acabado',3750.00,'planificado'),
(15,3,'publicidad','Tienda en línea y fotografía',2500.00,'planificado'),
(16,3,'transporte','Envíos a ferias y tiendas',1250.00,'planificado'),
(17,4,'equipamiento','Componentes electrónicos de 150 sensores',27000.00,'planificado'),
(18,4,'mano_de_obra','Ensamblaje y calibración',12000.00,'planificado'),
(19,4,'infraestructura','Banco de pruebas en parcela',6000.00,'planificado'),
(20,4,'transporte','Entrega e instalación en campo',6000.00,'planificado'),
(21,4,'otros','Plan de datos SMS por un año',6000.00,'planificado'),
(22,4,'publicidad','Demostraciones a productores',3000.00,'planificado'),
(23,5,'infraestructura','Construcción de seis habitaciones y comedor',28600.00,'planificado'),
(24,5,'equipamiento','Sistema solar y camas',10400.00,'planificado'),
(25,5,'mano_de_obra','Formación en atención al turista',5200.00,'planificado'),
(26,5,'publicidad','Sitio web y alianzas',5200.00,'planificado'),
(27,5,'otros','Fondo para la escuela comunitaria',2600.00,'planificado'),
(28,6,'infraestructura','Excavación y revestimiento de seis estanques',20000.00,'planificado'),
(29,6,'materia_prima','Alevines y alimento balanceado',10000.00,'planificado'),
(30,6,'equipamiento','Bombas de agua y aireadores',6000.00,'planificado'),
(31,6,'mano_de_obra','Técnico acuicultor durante el ciclo',4000.00,'planificado'),
(32,7,'maquinaria','Trituradora y lavadora industrial',21000.00,'planificado'),
(33,7,'infraestructura','Adecuación del galpón y desagüe',5250.00,'planificado'),
(34,7,'transporte','Camioneta de recojo (alquiler)',3500.00,'planificado'),
(35,7,'mano_de_obra','Capacitación de recicladores',3500.00,'planificado'),
(36,7,'otros','Permisos ambientales',1750.00,'planificado'),
(37,8,'equipamiento','Tostadora, descascarilladora y moldes',15000.00,'planificado'),
(38,8,'infraestructura','Cámara de fermentado',6000.00,'planificado'),
(39,8,'materia_prima','Compra de cacao a recolectores',6000.00,'planificado'),
(40,8,'publicidad','Empaque y marca',3000.00,'planificado'),
(41,9,'maquinaria','Sierra, cepilladora y lijadora',14000.00,'planificado'),
(42,9,'materia_prima','Madera certificada primer lote',5600.00,'planificado'),
(43,9,'mano_de_obra','Capacitación en diseño',4200.00,'planificado'),
(44,9,'equipamiento','Equipos de seguridad',2800.00,'planificado'),
(45,9,'publicidad','Catálogo y fotografía',1400.00,'planificado');

INSERT INTO ejecucion_presupuesto (id_presupuesto,monto_ejecutado,descripcion,fecha) VALUES
(37,9800.00,'Compra de tostadora y descascarilladora',DATE_SUB(CURDATE(), INTERVAL 18 DAY)),
(38,5200.00,'Construcción de la cámara de fermentado',DATE_SUB(CURDATE(), INTERVAL 12 DAY)),
(39,2400.00,'Primera compra de cacao a 11 recolectores',DATE_SUB(CURDATE(), INTERVAL 4 DAY));

INSERT INTO aportes (id_usuario,id_proyecto,monto,fecha_aporte,estado,codigo_comprobante,tipo_aporte) VALUES
(7,1,4850.00,DATE_SUB(NOW(), INTERVAL 369 HOUR),'confirmado','IB-DEMO-000001','simulado'),
(8,1,4650.00,DATE_SUB(NOW(), INTERVAL 122 HOUR),'confirmado','IB-DEMO-000002','simulado'),
(9,1,50.00,DATE_SUB(NOW(), INTERVAL 452 HOUR),'confirmado','IB-DEMO-000003','simulado'),
(10,1,200.00,DATE_SUB(NOW(), INTERVAL 67 HOUR),'confirmado','IB-DEMO-000004','simulado'),
(6,1,13400.00,DATE_SUB(NOW(), INTERVAL 326 HOUR),'confirmado','IB-DEMO-000005','simulado'),
(7,1,50.00,DATE_SUB(NOW(), INTERVAL 523 HOUR),'confirmado','IB-DEMO-000006','simulado'),
(8,1,650.00,DATE_SUB(NOW(), INTERVAL 509 HOUR),'confirmado','IB-DEMO-000007','simulado'),
(9,1,550.00,DATE_SUB(NOW(), INTERVAL 480 HOUR),'confirmado','IB-DEMO-000008','simulado'),
(10,1,1650.00,DATE_SUB(NOW(), INTERVAL 410 HOUR),'confirmado','IB-DEMO-000009','simulado'),
(6,1,200.00,DATE_SUB(NOW(), INTERVAL 49 HOUR),'confirmado','IB-DEMO-000010','simulado'),
(7,1,1650.00,DATE_SUB(NOW(), INTERVAL 175 HOUR),'confirmado','IB-DEMO-000011','simulado'),
(8,2,1550.00,DATE_SUB(NOW(), INTERVAL 384 HOUR),'confirmado','IB-DEMO-000012','simulado'),
(9,2,8500.00,DATE_SUB(NOW(), INTERVAL 530 HOUR),'confirmado','IB-DEMO-000013','simulado'),
(10,2,1950.00,DATE_SUB(NOW(), INTERVAL 380 HOUR),'confirmado','IB-DEMO-000014','simulado'),
(6,2,3100.00,DATE_SUB(NOW(), INTERVAL 229 HOUR),'confirmado','IB-DEMO-000015','simulado'),
(7,2,1650.00,DATE_SUB(NOW(), INTERVAL 434 HOUR),'confirmado','IB-DEMO-000016','simulado'),
(8,2,5850.00,DATE_SUB(NOW(), INTERVAL 560 HOUR),'confirmado','IB-DEMO-000017','simulado'),
(9,2,1200.00,DATE_SUB(NOW(), INTERVAL 271 HOUR),'confirmado','IB-DEMO-000018','simulado'),
(10,2,2800.00,DATE_SUB(NOW(), INTERVAL 417 HOUR),'confirmado','IB-DEMO-000019','simulado'),
(6,2,3700.00,DATE_SUB(NOW(), INTERVAL 26 HOUR),'confirmado','IB-DEMO-000020','simulado'),
(7,2,450.00,DATE_SUB(NOW(), INTERVAL 459 HOUR),'confirmado','IB-DEMO-000021','simulado'),
(8,2,1550.00,DATE_SUB(NOW(), INTERVAL 315 HOUR),'confirmado','IB-DEMO-000022','simulado'),
(9,3,50.00,DATE_SUB(NOW(), INTERVAL 174 HOUR),'confirmado','IB-DEMO-000023','simulado'),
(10,3,200.00,DATE_SUB(NOW(), INTERVAL 63 HOUR),'confirmado','IB-DEMO-000024','simulado'),
(6,3,650.00,DATE_SUB(NOW(), INTERVAL 324 HOUR),'confirmado','IB-DEMO-000025','simulado'),
(7,3,2850.00,DATE_SUB(NOW(), INTERVAL 338 HOUR),'confirmado','IB-DEMO-000026','simulado'),
(8,3,1200.00,DATE_SUB(NOW(), INTERVAL 476 HOUR),'confirmado','IB-DEMO-000027','simulado'),
(9,3,3850.00,DATE_SUB(NOW(), INTERVAL 176 HOUR),'confirmado','IB-DEMO-000028','simulado'),
(10,3,1200.00,DATE_SUB(NOW(), INTERVAL 266 HOUR),'confirmado','IB-DEMO-000029','simulado'),
(10,4,400.00,DATE_SUB(NOW(), INTERVAL 25 HOUR),'confirmado','IB-DEMO-000030','simulado'),
(6,4,2200.00,DATE_SUB(NOW(), INTERVAL 375 HOUR),'confirmado','IB-DEMO-000031','simulado'),
(7,4,450.00,DATE_SUB(NOW(), INTERVAL 161 HOUR),'confirmado','IB-DEMO-000032','simulado'),
(8,4,400.00,DATE_SUB(NOW(), INTERVAL 182 HOUR),'confirmado','IB-DEMO-000033','simulado'),
(9,4,2900.00,DATE_SUB(NOW(), INTERVAL 414 HOUR),'confirmado','IB-DEMO-000034','simulado'),
(10,4,1650.00,DATE_SUB(NOW(), INTERVAL 133 HOUR),'confirmado','IB-DEMO-000035','simulado'),
(6,4,550.00,DATE_SUB(NOW(), INTERVAL 315 HOUR),'confirmado','IB-DEMO-000036','simulado'),
(7,4,1950.00,DATE_SUB(NOW(), INTERVAL 325 HOUR),'confirmado','IB-DEMO-000037','simulado'),
(8,4,6300.00,DATE_SUB(NOW(), INTERVAL 168 HOUR),'confirmado','IB-DEMO-000038','simulado'),
(6,5,1050.00,DATE_SUB(NOW(), INTERVAL 97 HOUR),'confirmado','IB-DEMO-000039','simulado'),
(7,5,8550.00,DATE_SUB(NOW(), INTERVAL 126 HOUR),'confirmado','IB-DEMO-000040','simulado'),
(8,5,1200.00,DATE_SUB(NOW(), INTERVAL 368 HOUR),'confirmado','IB-DEMO-000041','simulado'),
(9,5,3050.00,DATE_SUB(NOW(), INTERVAL 43 HOUR),'confirmado','IB-DEMO-000042','simulado'),
(10,5,1750.00,DATE_SUB(NOW(), INTERVAL 273 HOUR),'confirmado','IB-DEMO-000043','simulado'),
(6,5,4600.00,DATE_SUB(NOW(), INTERVAL 314 HOUR),'confirmado','IB-DEMO-000044','simulado'),
(7,5,9350.00,DATE_SUB(NOW(), INTERVAL 74 HOUR),'confirmado','IB-DEMO-000045','simulado'),
(8,5,800.00,DATE_SUB(NOW(), INTERVAL 186 HOUR),'confirmado','IB-DEMO-000046','simulado'),
(9,5,500.00,DATE_SUB(NOW(), INTERVAL 511 HOUR),'confirmado','IB-DEMO-000047','simulado'),
(10,5,2050.00,DATE_SUB(NOW(), INTERVAL 43 HOUR),'confirmado','IB-DEMO-000048','simulado'),
(6,5,3500.00,DATE_SUB(NOW(), INTERVAL 299 HOUR),'confirmado','IB-DEMO-000049','simulado'),
(7,6,850.00,DATE_SUB(NOW(), INTERVAL 210 HOUR),'confirmado','IB-DEMO-000050','simulado'),
(8,6,2100.00,DATE_SUB(NOW(), INTERVAL 84 HOUR),'confirmado','IB-DEMO-000051','simulado'),
(9,6,850.00,DATE_SUB(NOW(), INTERVAL 92 HOUR),'confirmado','IB-DEMO-000052','simulado'),
(10,6,200.00,DATE_SUB(NOW(), INTERVAL 81 HOUR),'confirmado','IB-DEMO-000053','simulado'),
(6,6,2000.00,DATE_SUB(NOW(), INTERVAL 115 HOUR),'confirmado','IB-DEMO-000054','simulado'),
(8,7,4100.00,DATE_SUB(NOW(), INTERVAL 327 HOUR),'confirmado','IB-DEMO-000055','simulado'),
(9,7,800.00,DATE_SUB(NOW(), INTERVAL 482 HOUR),'confirmado','IB-DEMO-000056','simulado'),
(10,7,150.00,DATE_SUB(NOW(), INTERVAL 337 HOUR),'confirmado','IB-DEMO-000057','simulado'),
(6,7,1350.00,DATE_SUB(NOW(), INTERVAL 99 HOUR),'confirmado','IB-DEMO-000058','simulado'),
(7,7,7800.00,DATE_SUB(NOW(), INTERVAL 64 HOUR),'confirmado','IB-DEMO-000059','simulado'),
(8,7,1950.00,DATE_SUB(NOW(), INTERVAL 223 HOUR),'confirmado','IB-DEMO-000060','simulado'),
(9,7,1450.00,DATE_SUB(NOW(), INTERVAL 588 HOUR),'confirmado','IB-DEMO-000061','simulado'),
(10,7,1000.00,DATE_SUB(NOW(), INTERVAL 229 HOUR),'confirmado','IB-DEMO-000062','simulado'),
(6,7,350.00,DATE_SUB(NOW(), INTERVAL 495 HOUR),'confirmado','IB-DEMO-000063','simulado'),
(7,7,300.00,DATE_SUB(NOW(), INTERVAL 256 HOUR),'confirmado','IB-DEMO-000064','simulado'),
(9,8,3550.00,DATE_SUB(NOW(), INTERVAL 576 HOUR),'confirmado','IB-DEMO-000065','simulado'),
(10,8,250.00,DATE_SUB(NOW(), INTERVAL 80 HOUR),'confirmado','IB-DEMO-000066','simulado'),
(6,8,2700.00,DATE_SUB(NOW(), INTERVAL 350 HOUR),'confirmado','IB-DEMO-000067','simulado'),
(7,8,2500.00,DATE_SUB(NOW(), INTERVAL 193 HOUR),'confirmado','IB-DEMO-000068','simulado'),
(8,8,1450.00,DATE_SUB(NOW(), INTERVAL 53 HOUR),'confirmado','IB-DEMO-000069','simulado'),
(9,8,450.00,DATE_SUB(NOW(), INTERVAL 251 HOUR),'confirmado','IB-DEMO-000070','simulado'),
(10,8,800.00,DATE_SUB(NOW(), INTERVAL 426 HOUR),'confirmado','IB-DEMO-000071','simulado'),
(6,8,2650.00,DATE_SUB(NOW(), INTERVAL 122 HOUR),'confirmado','IB-DEMO-000072','simulado'),
(7,8,10200.00,DATE_SUB(NOW(), INTERVAL 292 HOUR),'confirmado','IB-DEMO-000073','simulado'),
(8,8,4100.00,DATE_SUB(NOW(), INTERVAL 370 HOUR),'confirmado','IB-DEMO-000074','simulado'),
(9,8,1350.00,DATE_SUB(NOW(), INTERVAL 544 HOUR),'confirmado','IB-DEMO-000075','simulado'),
(9,1,300.00,DATE_SUB(NOW(), INTERVAL 30 HOUR),'anulado','IB-DEMO-000076','simulado'),
(10,1,150.00,DATE_SUB(NOW(), INTERVAL 3 HOUR),'pendiente','IB-DEMO-000077','simulado');

UPDATE proyectos p SET
 total_recaudado=(SELECT COALESCE(SUM(a.monto),0) FROM aportes a WHERE a.id_proyecto=p.id_proyecto AND a.estado='confirmado'),
 num_colaboradores=(SELECT COUNT(DISTINCT a.id_usuario) FROM aportes a WHERE a.id_proyecto=p.id_proyecto AND a.estado='confirmado');

INSERT INTO actualizaciones (id_proyecto,titulo,contenido,fecha_publicacion) VALUES
(1,'Visita técnica al terreno','Un ingeniero agrónomo revisó el terreno del galpón y confirmó que cumple las condiciones para la planta de lavado.',DATE_SUB(NOW(), INTERVAL 6 DAY)),
(2,'Primer lote de café en prueba de taza','El microlote de este año obtuvo 84 puntos en la prueba de taza. Con las secadoras solares podemos repetirlo en toda la cosecha.',DATE_SUB(NOW(), INTERVAL 4 DAY)),
(8,'Ya fermentamos el primer lote','Con el financiamiento completo instalamos la cámara de fermentado y comenzamos el primer lote de cacao.',DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5,'Los vecinos aprobaron el diseño','En asamblea comunitaria se aprobó el diseño de las seis habitaciones y el reglamento de uso.',DATE_SUB(NOW(), INTERVAL 8 DAY));
INSERT INTO comentarios (id_usuario,id_proyecto,contenido,estado) VALUES
(6,2,'¿El café se venderá también en tostadores de Cochabamba?','aprobado'),
(7,5,'Excelente iniciativa. ¿Habrá reservas en línea cuando abran?','aprobado'),
(8,1,'Me gustaría saber cuándo estará la certificación orgánica.','pendiente');
INSERT INTO favoritos (id_usuario,id_proyecto) VALUES (6,2),(6,5),(7,4),(8,1),(9,3);
INSERT INTO notificaciones (id_usuario,titulo,mensaje,leida) VALUES
(2,'Proyecto aprobado','Tu proyecto "Quinua Real Orgánica" fue aprobado y ya está visible en el catálogo.',0),
(3,'Tu campaña está por terminar','La campaña "Café de altura de Caranavi" termina en pocos días.',0),
(5,'Campaña financiada','"Cacao silvestre amazónico" alcanzó el 100% de su meta.',1),
(1,'Proyecto pendiente de revisión','Hay un proyecto nuevo esperando tu revisión: "Madera Tarijeña".',0);
