<?php
require_once '../config/config.php';
requireAdmin(); // Solo administradores

$db = getDB();
$action = $_GET['action'] ?? 'list';

if ($action === 'create' || $action === 'edit') {
    $user_id = $_GET['id'] ?? null;
    $user = null;
    
    if ($user_id) {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            header('Location: users.php');
            exit;
        }
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $full_name = sanitize($_POST['full_name'] ?? '');
        $role = sanitize($_POST['role'] ?? 'member');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($username) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Usuario y email son requeridos']);
            exit;
        }
        
        if ($user_id) {
            // Actualizar
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, password_hash = ?, full_name = ?, role = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$username, $email, $password_hash, $full_name, $role, $is_active, $user_id]);
            } else {
                $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$username, $email, $full_name, $role, $is_active, $user_id]);
            }
            echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);
        } else {
            // Crear
            if (empty($password)) {
                echo json_encode(['success' => false, 'message' => 'La contraseña es requerida para nuevos usuarios']);
                exit;
            }
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password_hash, $full_name, $role, $is_active]);
            echo json_encode(['success' => true, 'message' => 'Usuario creado correctamente']);
        }
        exit;
    }
    
    // Mostrar formulario
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $user_id ? 'Editar' : 'Crear'; ?> Usuario - <?php echo APP_NAME; ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/gamified.css">
    </head>
    <body style="background: #FAFAFA;">
        <?php include '../includes/header.php'; ?>
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="ml-64 pt-4 p-8">
            <div class="container mx-auto max-w-4xl">
                <div class="bg-white rounded-xl shadow-md p-8">
                    <h1 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-<?php echo $user_id ? 'edit' : 'user-plus'; ?> mr-2"></i>
                        <?php echo $user_id ? 'Editar Usuario' : 'Crear Nuevo Usuario'; ?>
                    </h1>
                    
                    <form id="userForm" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Usuario *</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Contraseña <?php echo $user_id ? '(dejar vacío para mantener)' : '*'; ?></label>
                                <input type="password" name="password" <?php echo $user_id ? '' : 'required'; ?> minlength="6"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Rol *</label>
                                <select name="role" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    <option value="member" <?php echo ($user['role'] ?? 'member') === 'member' ? 'selected' : ''; ?>>Miembro</option>
                                    <option value="admin" <?php echo ($user['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="is_active" value="1" <?php echo ($user['is_active'] ?? 1) ? 'checked' : ''; ?>
                                    class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                <span class="text-sm font-medium text-gray-700">Usuario activo</span>
                            </label>
                        </div>
                        
                        <div class="flex space-x-4">
                            <button type="submit" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Guardar
                            </button>
                            <a href="users.php" class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 transition-colors">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
        
        <script>
            document.getElementById('userForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                const response = await fetch('users.php?action=<?php echo $action; ?><?php echo $user_id ? '&id=' . $user_id : ''; ?>', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: result.message,
                        confirmButtonColor: '#9333ea'
                    }).then(() => {
                        window.location.href = 'users.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message,
                        confirmButtonColor: '#9333ea'
                    });
                }
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Listar usuarios
$stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestión de Usuarios - <?php echo APP_NAME; ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/gamified.css">
    </head>
<body style="background: #FAFAFA;">
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="ml-64 pt-4 p-8">
        <div class="container mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Gestión de Usuarios</h1>
                <a href="users.php?action=create" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-user-plus mr-2"></i>Crear Usuario
                </a>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Último Login</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-800"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($user['full_name'] ?? '-'); ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <?php echo $user['role'] === 'admin' ? 'Administrador' : 'Miembro'; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $user['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Nunca'; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" class="text-purple-600 hover:text-purple-800 mr-3" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="#" onclick="deleteUser(<?php echo $user['id']; ?>)" class="text-red-600 hover:text-red-800" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        async function deleteUser(id) {
            const result = await Swal.fire({
                title: '¿Estás seguro?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            });
            
            if (result.isConfirmed) {
                const response = await fetch('../api/delete-user.php?id=' + id, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        text: 'Usuario eliminado correctamente',
                        confirmButtonColor: '#9333ea'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al eliminar',
                        confirmButtonColor: '#9333ea'
                    });
                }
            }
        }
    </script>
</body>
</html>
