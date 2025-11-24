# MeritumQ - Sistema de Gestión de Quizzes y Talleres

Sistema moderno para crear y gestionar quizzes, talleres y generar códigos QR con roles de administrador y miembro.

## Tecnologías Utilizadas

- **PHP** - Backend
- **MySQL** - Base de datos
- **HTML/CSS** - Estructura y estilos
- **JavaScript** - Interactividad
- **Tailwind CSS** - Framework CSS moderno
- **SweetAlert2** - Alertas elegantes
- **QRCode.js** - Generación de códigos QR

## Características

### Gestión de Usuarios
- Sistema de autenticación (login/registro)
- Roles: Administrador y Miembro
- Los administradores pueden gestionar todos los usuarios
- Los miembros solo pueden gestionar sus propios recursos

### Quizzes
- Crear, editar y eliminar quizzes
- Asignar puntos por pregunta
- Configurar tiempo por pregunta
- Generación automática de códigos QR

### Talleres
- Crear, editar y eliminar talleres
- Configurar fechas de disponibilidad
- Límite de participantes
- Estado activo/inactivo
- Generación automática de códigos QR

### Códigos QR
- Generación automática al crear quizzes/talleres
- Visualización de códigos QR
- Descarga de códigos QR en formato PNG
- Copiar códigos al portapapeles
- Contador de escaneos

## Instalación

1. **Configurar Base de Datos**
   - Crear la base de datos `meritumquest` en MySQL
   - Ejecutar el archivo `estructura` para crear las tablas

2. **Configurar Conexión**
   - Editar `config/database.php` con tus credenciales de MySQL:
   ```php
   define('DB_HOST', '5.183.11.230');
   define('DB_NAME', 'meritumquest');
   define('DB_USER', 'root');
   define('DB_PASS', 'Platino5.');
   ```

3. **Configurar URL de la Aplicación**
   - Editar `config/config.php` y ajustar `APP_URL` según tu configuración:
   ```php
   define('APP_URL', 'http://localhost/MeritumQ');
   ```

4. **Crear Usuario Administrador**
   - Ejecutar en MySQL:
   ```sql
   INSERT INTO users (username, email, password_hash, full_name, role, is_active)
   VALUES ('admin', 'admin@example.com', '$2y$10$...', 'Administrador', 'admin', 1);
   ```
   - O usar el formulario de registro y luego cambiar el rol manualmente

## Estructura del Proyecto

```
MeritumQ/
├── api/                    # Endpoints API
│   ├── delete-quiz.php
│   ├── delete-workshop.php
│   └── delete-user.php
├── auth/                   # Autenticación
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── config/                 # Configuración
│   ├── config.php
│   └── database.php
├── dashboard/              # Panel principal
│   ├── index.php
│   ├── quizzes.php
│   ├── workshops.php
│   ├── qr-codes.php
│   └── users.php
├── includes/               # Componentes reutilizables
│   ├── header.php
│   └── sidebar.php
└── estructura              # Script SQL de base de datos
```

## Uso

1. **Iniciar Sesión**
   - Acceder a `/auth/login.php`
   - O registrarse en `/auth/register.php`

2. **Crear Quiz**
   - Ir a "Mis Quizzes" en el dashboard
   - Click en "Crear Quiz"
   - Completar formulario y guardar

3. **Crear Taller**
   - Ir a "Mis Talleres" en el dashboard
   - Click en "Crear Taller"
   - Completar formulario y guardar

4. **Generar Código QR**
   - Ir a "Códigos QR"
   - O generar desde la lista de quizzes/talleres
   - Descargar o copiar el código

## Permisos

### Administrador
- Gestionar todos los usuarios
- Ver y gestionar todos los quizzes y talleres
- Generar códigos QR para cualquier recurso

### Miembro
- Crear y gestionar sus propios quizzes y talleres
- Generar códigos QR para sus recursos
- Ver solo sus propios recursos

## Seguridad

- Contraseñas almacenadas con `password_hash()` de PHP
- Validación de permisos en cada acción
- Sanitización de entradas
- Protección contra SQL Injection con PDO
- Sesiones seguras

## Licencia

Este proyecto es de uso libre para fines educativos y comerciales.
