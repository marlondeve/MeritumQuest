<?php
require_once '../config/config.php';
requireAuth();

$current_user = getCurrentUser();
$is_admin = isAdmin();
$db = getDB();

// Listar talleres (optimizado - solo campos necesarios)
$query = $is_admin 
    ? "SELECT w.id, w.code, w.title, w.description, w.is_active, w.created_at, u.username, u.full_name FROM workshops w LEFT JOIN users u ON w.created_by = u.id ORDER BY w.created_at DESC LIMIT 100"
    : "SELECT w.id, w.code, w.title, w.description, w.is_active, w.created_at, u.username, u.full_name FROM workshops w LEFT JOIN users u ON w.created_by = u.id WHERE w.created_by = ? ORDER BY w.created_at DESC LIMIT 100";
$stmt = $db->prepare($query);
if ($is_admin) {
    $stmt->execute();
} else {
    $stmt->execute([$current_user['id']]);
}
$workshops = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Talleres - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/gamified.css">
    <?php include '../includes/qr-modal.php'; ?>
</head>
<body style="background: #FAFAFA;">
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="ml-64 pt-4 p-8">
        <div class="container mx-auto">
            <div class="mb-6 slide-up">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <img src="<?php echo APP_URL; ?>/assets/avatar/15.png" alt="Talleres" style="width: 60px; height: auto; filter: drop-shadow(0 3px 6px rgba(0,0,0,0.1));">
                        <h1 style="font-size: 32px; font-weight: 900; color: var(--gray-900);">Mis Talleres</h1>
                    </div>
                    <button onclick="openWorkshopModal()" class="btn-game btn-green">
                        ➕ CREAR TALLER
                    </button>
                </div>
                <div class="card-game" style="background: var(--pastel-green); border-color: var(--duo-green); padding: 16px; margin-bottom: 16px;">
                    <div class="flex items-start space-x-3">
                        <img src="<?php echo APP_URL; ?>/assets/avatar/16.png" alt="Info" style="width: 50px; height: auto; flex-shrink: 0;">
                        <div>
                            <p style="font-size: 14px; font-weight: 600; color: var(--gray-700); line-height: 1.6;">
                                <strong>¿Qué es esta sección?</strong><br>
                                Gestiona tus talleres educativos aquí. Organiza eventos con fechas de inicio y fin, establece límites de participantes, y controla la disponibilidad. Los talleres te permiten estructurar actividades educativas más complejas que los quizzes.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-game slide-up">
                <?php if (empty($workshops)): ?>
                    <div class="text-center py-12">
                        <div class="emoji-sticker" style="font-size: 80px; margin-bottom: 16px;">📭</div>
                        <p style="font-size: 20px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px;">No hay talleres aún</p>
                        <p style="font-size: 16px; font-weight: 600; color: var(--gray-500); margin-bottom: 24px;">¡Organiza tu primer taller!</p>
                        <button onclick="openWorkshopModal()" class="btn-game btn-green">
                            ✨ CREAR PRIMER TALLER
                        </button>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto modern-table rounded-xl">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Código</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Título</th>
                                    <?php if ($is_admin): ?>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Creado por</th>
                                    <?php endif; ?>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($workshops as $index => $workshop): ?>
                                <tr class="slide-in-right" style="animation-delay: <?php echo $index * 0.05; ?>s;">
                                    <td class="px-6 py-4">
                                        <span class="font-mono text-sm font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent"><?php echo htmlspecialchars($workshop['code']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-gray-800"><?php echo htmlspecialchars($workshop['title']); ?></td>
                                    <?php if ($is_admin): ?>
                                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($workshop['full_name'] ?? $workshop['username']); ?></td>
                                    <?php endif; ?>
                                    <td class="px-6 py-4">
                                        <span class="badge-modern <?php echo $workshop['is_active'] ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white' : 'bg-gradient-to-r from-red-500 to-rose-500 text-white'; ?>">
                                            <?php echo $workshop['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo date('d/m/Y H:i', strtotime($workshop['created_at'])); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            <a href="workshop-view.php?id=<?php echo $workshop['id']; ?>" class="w-9 h-9 flex items-center justify-center rounded-lg bg-purple-100 text-purple-600 hover:bg-purple-200 hover:scale-110 transition-all duration-300 icon-hover" title="Ver detalles">
                                                <i class="fas fa-eye text-sm"></i>
                                            </a>
                                            <button onclick="openQRModal('workshop', <?php echo $workshop['id']; ?>)" class="w-9 h-9 flex items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 hover:bg-emerald-200 hover:scale-110 transition-all duration-300 icon-hover" title="Ver QR" style="box-shadow: 0 2px 0 rgba(0,0,0,0.1);">
                                                <i class="fas fa-qrcode text-sm"></i>
                                            </button>
                                            <button onclick="editWorkshop(<?php echo $workshop['id']; ?>)" class="w-9 h-9 flex items-center justify-center rounded-lg bg-purple-100 text-purple-600 hover:bg-purple-200 hover:scale-110 transition-all duration-300 icon-hover" title="Editar">
                                                <i class="fas fa-edit text-sm"></i>
                                            </button>
                                            <button onclick="deleteWorkshop(<?php echo $workshop['id']; ?>)" class="w-9 h-9 flex items-center justify-center rounded-lg bg-red-100 text-red-600 hover:bg-red-200 hover:scale-110 transition-all duration-300 icon-hover" title="Eliminar">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal para Crear/Editar Taller -->
    <div id="workshopModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="modal-game max-w-2xl w-full max-h-[90vh] overflow-y-auto bounce-in">
            <div class="modal-header" style="background: linear-gradient(135deg, #58CC02 0%, #89E219 100%); border-bottom-color: #46A302;">
                <div class="flex items-center justify-between">
                    <h2 id="modalTitle" class="modal-title flex items-center">
                        <span style="font-size: 28px; margin-right: 12px;">📚</span>
                        <span id="modalTitleText">CREAR TALLER</span>
                    </h2>
                    <button onclick="closeWorkshopModal()" class="text-white hover:opacity-80 transition-opacity" style="font-size: 28px; font-weight: 700;">
                        ✕
                    </button>
                </div>
            </div>
            
            <form id="workshopForm" style="padding: 32px;">
                <input type="hidden" id="workshop_id" name="workshop_id" value="0">
                <input type="hidden" id="code" name="code" value="">
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                        ✏️ Título *
                    </label>
                    <input type="text" id="title" name="title" required class="input-game" placeholder="Ej: Taller de Programación">
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                        📝 Descripción
                    </label>
                    <textarea id="description" name="description" rows="4" class="input-game" placeholder="Describe tu taller..."></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4" style="margin-bottom: 24px;">
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                            📅 Desde
                        </label>
                        <input type="datetime-local" id="available_from" name="available_from" class="input-game">
                    </div>
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                            📅 Hasta
                        </label>
                        <input type="datetime-local" id="available_to" name="available_to" class="input-game">
                    </div>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                        👥 Máximo de Participantes
                    </label>
                    <input type="number" id="max_participants" name="max_participants" min="1" class="input-game" placeholder="Ej: 30">
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; padding: 16px; background: var(--pastel-green); border: 2px solid var(--duo-green); border-radius: 16px; cursor: pointer; font-weight: 700;">
                        <input type="checkbox" id="is_active" name="is_active" value="1" checked style="width: 20px; height: 20px; margin-right: 12px;">
                        <span style="font-size: 16px;">✅ Taller activo</span>
                    </label>
                </div>
                
                <div style="display: flex; gap: 16px; margin-top: 32px;">
                    <button type="submit" class="flex-1 btn-game btn-green">
                        💾 GUARDAR TALLER
                    </button>
                    <button type="button" onclick="closeWorkshopModal()" style="flex: 1; background: var(--gray-200); color: var(--gray-700); padding: 14px 24px; border-radius: 16px; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.15s ease;">
                        CANCELAR
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openWorkshopModal(workshopId = null) {
            const modal = document.getElementById('workshopModal');
            const form = document.getElementById('workshopForm');
            const title = document.getElementById('modalTitle');
            
            // Resetear formulario
            form.reset();
            document.getElementById('workshop_id').value = '0';
            document.getElementById('is_active').checked = true;
            
            if (workshopId) {
                title.innerHTML = '<i class="fas fa-edit mr-2"></i>Editar Taller';
                loadWorkshopData(workshopId);
            } else {
                title.innerHTML = '<i class="fas fa-plus mr-2"></i>Crear Nuevo Taller';
            }
            
            modal.classList.remove('hidden');
        }
        
        function closeWorkshopModal() {
            document.getElementById('workshopModal').classList.add('hidden');
        }
        
        async function loadWorkshopData(workshopId) {
            try {
                const response = await fetch(`../api/get-workshop.php?id=${workshopId}`);
                const result = await response.json();
                
                if (result.success) {
                    const workshop = result.workshop;
                    document.getElementById('workshop_id').value = workshop.id;
                    document.getElementById('code').value = workshop.code;
                    document.getElementById('title').value = workshop.title;
                    document.getElementById('description').value = workshop.description || '';
                    document.getElementById('max_participants').value = workshop.max_participants || '';
                    document.getElementById('is_active').checked = workshop.is_active == 1;
                    
                    if (workshop.available_from) {
                        const fromDate = new Date(workshop.available_from);
                        document.getElementById('available_from').value = fromDate.toISOString().slice(0, 16);
                    }
                    if (workshop.available_to) {
                        const toDate = new Date(workshop.available_to);
                        document.getElementById('available_to').value = toDate.toISOString().slice(0, 16);
                    }
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cargar los datos del taller',
                    confirmButtonColor: '#9333ea'
                });
            }
        }
        
        async function editWorkshop(id) {
            openWorkshopModal(id);
        }
        
        document.getElementById('workshopForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('workshop_id', document.getElementById('workshop_id').value);
            
            try {
                const response = await fetch('../api/save-workshop.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const message = result.code 
                        ? `${result.message}\n\nCódigo generado: ${result.code}`
                        : result.message;
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        html: message.replace(/\n/g, '<br>'),
                        confirmButtonColor: '#9333ea',
                        timer: 3000,
                        showConfirmButton: true
                    }).then(() => {
                        closeWorkshopModal();
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message,
                        confirmButtonColor: '#9333ea'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al guardar el taller',
                    confirmButtonColor: '#9333ea'
                });
            }
        });
        
        // Cerrar modal al hacer click fuera
        document.getElementById('workshopModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeWorkshopModal();
            }
        });
        
        async function deleteWorkshop(id) {
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
                const response = await fetch('../api/delete-workshop.php?id=' + id, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        text: 'Taller eliminado correctamente',
                        confirmButtonColor: '#9333ea',
                        timer: 1500,
                        showConfirmButton: false
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