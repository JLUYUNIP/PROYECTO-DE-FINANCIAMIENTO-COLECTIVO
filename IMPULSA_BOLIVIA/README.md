# IMPULSA BOLIVIA — Etapa 1 (base, portada, catálogo, cuentas y aportes)

Plataforma de financiamiento colectivo para emprendedores bolivianos.
Prototipo universitario de Ingeniería Económica y Financiera. Los aportes son **simulados**.

## Requisitos
- XAMPP con Apache y MySQL (PHP 8.1 o superior)
- Internet en el navegador (Bootstrap, Font Awesome, Chart.js y las fuentes se cargan por CDN)

## Instalación en XAMPP
1. Copia la carpeta `IMPULSA_BOLIVIA` dentro de `C:\xampp\htdocs\` (en Linux/Mac: `/opt/lampp/htdocs/`).
2. Abre el Panel de XAMPP y enciende **Apache** y **MySQL**.
3. Entra a http://localhost/phpmyadmin → pestaña **Importar** → elige `base_datos/impulsa_bolivia.sql` → **Continuar**.
   (El archivo borra y vuelve a crear la base `impulsa_bolivia`.)
4. Abre http://localhost/IMPULSA_BOLIVIA/
5. Si tu carpeta tiene otro nombre, cambia `BASE_URL` en `config/configuracion.php`.
   Si tu MySQL tiene contraseña, cambia `DB_PASS` en el mismo archivo.

## Abrir en Visual Studio Code
Abre la carpeta `IMPULSA_BOLIVIA` con *File > Open Folder*. Se recomienda la extensión "PHP Intelephense".
Puedes trabajar directamente en `htdocs` o copiar los cambios allí.

## Usuarios de prueba
| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@impulsabolivia.bo | Admin123* |
| Emprendedor | mquispe@demo.bo | Emprende123* |
| Emprendedor | cchoque@demo.bo | Emprende123* |
| Colaborador | asalvatierra@demo.bo | Colabora123* |
| Colaborador | dvargas@demo.bo | Colabora123* |

## Cómo probar lo que incluye esta etapa
1. **Portada** (`/`): indicadores reales desde MySQL, campaña destacada, proyectos, cómo funciona, FAQ.
2. **Registro** (`/registro.php`): elige emprendedor o colaborador; prueba errores (correo repetido, contraseña débil).
3. **Inicio de sesión** (`/login.php`): usa las cuentas de prueba; 5 intentos fallidos bloquean 5 minutos.
4. **Recuperar contraseña** (`/recuperar.php`): genera un enlace de un solo uso (en modo demostración se muestra en pantalla).
5. **Catálogo** (`/proyectos/`): busca, filtra por categoría, departamento, ciudad, estado y avance; ordena por «Próximos a finalizar».
6. **Detalle** (`/proyectos/detalle.php?id=2`): presupuesto con gráfico, actualizaciones, comentarios (quedan pendientes de moderación) y favoritos (solo colaboradores).
7. **Aporte simulado**: entra como colaborador → proyecto → *Apoyar este proyecto* → monto → confirmar. Mira cómo cambian el total, el porcentaje y los colaboradores; abre el comprobante.
8. **Historial** (`/aportes/historial.php`): aportes confirmados, pendientes y anulados del colaborador.
9. **Reglas**: un proyecto financiado, vencido o pendiente no acepta aportes; un emprendedor no puede aportar; no se puede superar la meta.

## Seguridad incluida
Consultas preparadas con PDO, `password_hash`, tokens CSRF, escape de salida contra XSS, sesiones con cookie HttpOnly y regeneración de ID,
control de acceso por rol, transacción con bloqueo de fila en los aportes, validación de imágenes y bloqueo de PHP en `uploads/`.

## Qué viene en las siguientes etapas
- Etapa 2: panel del emprendedor y del colaborador, publicar/editar proyectos con fotos, presupuesto y ejecución, actualizaciones, perfil, cambio de contraseña, notificaciones.
- Etapa 3: panel de administración (aprobar, rechazar o pedir correcciones, usuarios, categorías, aportes, moderación), reportes con gráficos y exportación CSV.
