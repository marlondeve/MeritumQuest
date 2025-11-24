# MeritumQuest - Sistema de Quizzes Interactivos

Sistema completo para crear y gestionar quizzes interactivos con soporte para eventos en vivo y modo taller autónomo.

## Características Principales

### 🎯 Panel de Administrador
- Crear y gestionar quizzes
- Añadir preguntas con multimedia (imagen, video, audio)
- Configurar tiempo por pregunta
- Modo examen o modo juego
- Analíticas completas con gráficas
- Exportación a CSV

### 👥 Interfaz del Estudiante
- Acceso rápido por código o QR
- Pantalla de espera para eventos en vivo
- Interfaz intuitiva para responder preguntas
- Feedback inmediato (opcional)
- Visualización de resultados y ranking

### 📺 Pantalla del Presentador
- Vista para proyección en clase
- Contador de participantes conectados
- Gráficas de resultados por pregunta
- Ranking en tiempo real
- Control de avance de preguntas

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior (o MariaDB 10.2+)
- Servidor web (Apache/Nginx)
- Extensiones PHP: PDO, JSON, mbstring

## Instalación

1. **Clonar o descargar el proyecto** en tu servidor web (ej: `htdocs` en XAMPP)

2. **Configurar la base de datos:**
   - Ejecutar el archivo `estructura` en MySQL para crear las tablas
   - Actualizar las credenciales en `config.php`:
   ```php
   define('DB_HOST', 'tu_host');
   define('DB_NAME', 'tu_base_de_datos');
   define('DB_USER', 'tu_usuario');
   define('DB_PASS', 'tu_contraseña');
   ```

3. **Permisos de escritura:**
   - Asegurar que los directorios `uploads/` y `cache/` tengan permisos de escritura

4. **Acceder al sistema:**
   - Panel Admin: `http://localhost/MeritumQuest/admin/`
   - Interfaz Estudiante: `http://localhost/MeritumQuest/student/`
   - Pantalla Presentador: Se genera automáticamente al iniciar una sesión

## Estructura del Proyecto

```
MeritumQuest/
├── admin/              # Panel de administración
│   ├── index.php      # Lista y gestión de quizzes
│   ├── admin.js       # Lógica del panel admin
│   ├── analytics.php  # Página de analíticas
│   └── analytics.js   # Lógica de analíticas
├── api/               # APIs REST
│   ├── quizzes.php    # CRUD de quizzes
│   ├── questions.php  # CRUD de preguntas
│   ├── sessions.php   # Gestión de sesiones
│   ├── attempts.php   # Intentos y respuestas
│   └── analytics.php  # Estadísticas
├── student/           # Interfaz del estudiante
│   ├── index.php     # Pantalla principal
│   └── student.js    # Lógica del estudiante
├── presenter/         # Pantalla del presentador
│   ├── index.php     # Vista de proyección
│   └── presenter.js  # Lógica del presentador
├── config.php        # Configuración y conexión BD
├── estructura        # Script SQL de creación de BD
└── README.md         # Este archivo
```

## Uso del Sistema

### Crear un Quiz

1. Accede al panel de administrador
2. Haz clic en "Crear Nuevo Quiz"
3. Completa el formulario:
   - Título y descripción
   - Puntos por pregunta
   - Configuración de tiempo
4. Guarda el quiz

### Añadir Preguntas

1. Haz clic en "Preguntas" en el quiz deseado
2. Agrega una nueva pregunta:
   - Texto de la pregunta
   - Opcional: imagen, video o audio
   - Tiempo límite (opcional)
   - Múltiples respuestas (si aplica)
   - Opciones de respuesta (mínimo 2)
   - Marca las opciones correctas
   - Explicación (opcional)

### Iniciar una Sesión

1. En el panel admin, haz clic en "Iniciar" en un quiz
2. Selecciona el modo:
   - **Evento en Vivo**: Requiere control del presentador
   - **Modo Taller**: Autónomo, los estudiantes pueden hacerlo cuando quieran
3. Se generará un código de sesión
4. Abre la pantalla del presentador (para modo live)
5. Comparte el código con los estudiantes

### Participar en un Quiz

1. El estudiante ingresa el código de sesión
2. Ingresa su nombre
3. Responde las preguntas
4. Al finalizar, ve sus resultados y ranking (si está habilitado)

## Modos de Operación

### Modo Evento en Vivo
- El presentador controla el avance de preguntas
- Los estudiantes esperan en pantalla de espera
- Resultados se muestran después de cada pregunta
- Ranking en tiempo real

### Modo Taller
- Los estudiantes pueden empezar cuando quieran
- No requiere presentador conectado
- Configurable:
  - Fechas de disponibilidad
  - Límite de intentos
  - Feedback inmediato o al final
  - Ranking público o privado

## Tecnologías Utilizadas

- **Backend**: PHP (sin frameworks)
- **Base de Datos**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework CSS**: Tailwind CSS
- **Framework JS**: Alpine.js
- **Gráficas**: Chart.js
- **Alertas**: SweetAlert2
- **Comunicación**: AJAX (JSON)

## Notas Importantes

- El sistema usa cache JSON para mejorar el rendimiento
- Las sesiones se pueden cerrar manualmente desde el presentador
- Los rankings se calculan automáticamente al finalizar intentos
- Las analíticas se actualizan en tiempo real

## Soporte

Para problemas o preguntas, revisa:
- Los logs del servidor web
- Los logs de PHP
- La consola del navegador (F12)

## Licencia

Este proyecto es de código abierto y está disponible para uso educativo y comercial.


