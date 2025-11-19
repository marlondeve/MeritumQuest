<?php
$current_user = getCurrentUser();
$is_admin = isAdmin();
?>
<header class="header-game sticky top-0 z-50 ml-64">
    <div class="container mx-auto">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <h2 class="page-title">
                    <?php
                    $current_page = basename($_SERVER['PHP_SELF']);
                    $page_emojis = [
                        'index.php' => '🎯',
                        'quizzes.php' => '❓',
                        'workshops.php' => '📚',
                        'qr-codes.php' => '🔳',
                        'users.php' => '👥',
                        'quiz-view.php' => '📝',
                        'workshop-view.php' => '📖'
                    ];
                    $page_titles = [
                        'index.php' => 'Dashboard',
                        'quizzes.php' => 'Mis Quizzes',
                        'workshops.php' => 'Mis Talleres',
                        'qr-codes.php' => 'Códigos QR',
                        'users.php' => 'Gestión de Usuarios',
                        'quiz-view.php' => 'Detalle del Quiz',
                        'workshop-view.php' => 'Detalle del Taller'
                    ];
                    echo ($page_emojis[$current_page] ?? '📊') . ' ' . ($page_titles[$current_page] ?? 'Dashboard');
                    ?>
                </h2>
            </div>
            
            <div class="flex items-center space-x-3">
                <a href="<?php echo APP_URL; ?>/auth/logout.php" class="btn-game btn-red" style="padding: 12px 20px; font-size: 13px;">
                    <span>👋</span> SALIR
                </a>
            </div>
        </div>
    </div>
</header>

