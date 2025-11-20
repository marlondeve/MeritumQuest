<?php
$current_page = basename($_SERVER['PHP_SELF']);
$is_admin = isAdmin();
$current_user = getCurrentUser();
?>
<aside class="sidebar-game w-64 min-h-screen fixed left-0 top-0 z-40 flex flex-col">
    <!-- Logo Section -->
    <div class="logo-container">
        <a href="<?php echo APP_URL; ?>/dashboard/index.php" class="block">
            <div class="logo-icon">
                <span class="emoji-sticker">🎓</span>
            </div>
            <div class="logo-text"><?php echo APP_NAME; ?></div>
        </a>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="flex-1 py-6 overflow-y-auto">
        <a href="<?php echo APP_URL; ?>/dashboard/index.php" 
           class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
            <div class="nav-icon">
                <span style="font-size: 24px;">🏠</span>
            </div>
            <span>Dashboard</span>
        </a>
        
        <a href="<?php echo APP_URL; ?>/dashboard/quizzes.php" 
           class="nav-link <?php echo $current_page === 'quizzes.php' || $current_page === 'quiz-view.php' ? 'active' : ''; ?>">
            <div class="nav-icon">
                <span style="font-size: 24px;">❓</span>
            </div>
            <span>Mis Quizzes</span>
        </a>
        
        <a href="<?php echo APP_URL; ?>/dashboard/workshops.php" 
           class="nav-link <?php echo $current_page === 'workshops.php' || $current_page === 'workshop-view.php' ? 'active' : ''; ?>">
            <div class="nav-icon">
                <span style="font-size: 24px;">📚</span>
            </div>
            <span>Mis Talleres</span>
        </a>
        
        
        <?php if ($is_admin): ?>
            <div class="px-4 pt-6 mt-4 border-t-2 border-gray-200">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 px-2">⚙️ Administración</p>
                <a href="<?php echo APP_URL; ?>/dashboard/users.php" 
                   class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>" style="margin-top: 0;">
                    <div class="nav-icon">
                        <span style="font-size: 24px;">👥</span>
                    </div>
                    <span>Usuarios</span>
                </a>
            </div>
        <?php endif; ?>
    </nav>
    
    <!-- User Info at Bottom -->
    <div class="p-4">
        <div class="user-card">
            <div class="flex items-center">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($current_user['username'], 0, 1)); ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-gray-900 truncate"><?php echo htmlspecialchars($current_user['full_name'] ?? $current_user['username']); ?></p>
                    <p class="text-xs font-semibold text-gray-700">
                        <?php echo $is_admin ? '👑 Admin' : '⭐ Miembro'; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</aside>

