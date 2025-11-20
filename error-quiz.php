<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/gamified.css">
</head>
<body style="background: #FAFAFA; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px;">
    <div class="card-game text-center slide-up" style="max-width: 500px;">
        <img src="<?php echo APP_URL; ?>/assets/avatar/14.png" alt="Error" style="width: 120px; height: auto; margin: 0 auto 24px; display: block; filter: drop-shadow(0 5px 10px rgba(0,0,0,0.1));">
        <h1 style="font-size: 32px; font-weight: 900; color: var(--gray-900); margin-bottom: 16px;">
            ⚠️ Quiz no encontrado
        </h1>
        <p style="font-size: 18px; font-weight: 600; color: var(--gray-700); margin-bottom: 32px;">
            <?php echo isset($error) ? htmlspecialchars($error) : 'El código del quiz no es válido o no existe.'; ?>
        </p>
        <a href="index.php" class="btn-game btn-blue" style="padding: 16px 32px; font-size: 16px;">
            🏠 Volver al Inicio
        </a>
    </div>
</body>
</html>

