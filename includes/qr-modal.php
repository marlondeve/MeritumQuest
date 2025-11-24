<!-- Modal para mostrar QR Code -->
<div id="qrModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="modal-game max-w-3xl w-full bounce-in">
        <div class="modal-header">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <img src="<?php echo APP_URL; ?>/assets/avatar/12.png" alt="QR Code" style="width: 50px; height: auto; filter: drop-shadow(0 3px 6px rgba(0,0,0,0.2));">
                    <h2 class="modal-title">CÓDIGO QR</h2>
                </div>
                <button onclick="closeQRModal()" class="text-white hover:opacity-80 transition-opacity" style="font-size: 28px; font-weight: 700;">
                    ✕
                </button>
            </div>
        </div>
        
        <div style="padding: 32px;">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                <!-- QR Code a la izquierda -->
                <div class="text-center">
                    <div id="qrCodeContainer" style="background: white; padding: 20px; border-radius: 20px; border: 2px solid var(--gray-200); display: inline-block; margin-bottom: 16px;">
                        <!-- QR se generará aquí -->
                    </div>
                    <button onclick="downloadQR()" class="btn-game btn-blue" style="width: 100%; padding: 12px;">
                        📥 DESCARGAR QR
                    </button>
                </div>
                
                <!-- Información del código a la derecha -->
                <div>
                    <div class="card-game" style="background: var(--pastel-blue); border-color: var(--duo-blue); padding: 24px;">
                        <div class="mb-4">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                                📝 TÍTULO
                            </label>
                            <p id="qrTitle" style="font-size: 18px; font-weight: 900; color: var(--gray-900);"></p>
                        </div>
                        
                        <div class="mb-4">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                                🔑 CÓDIGO
                            </label>
                            <div class="flex items-center space-x-2">
                                <input type="text" id="qrCode" readonly 
                                    style="flex: 1; font-family: monospace; font-size: 20px; font-weight: 900; color: var(--duo-blue); padding: 12px; background: white; border: 2px solid var(--duo-blue); border-radius: 12px; text-align: center;">
                                <button onclick="copyQRCode()" class="btn-game btn-yellow" style="padding: 12px 20px; white-space: nowrap;">
                                    📋 COPIAR
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                                📊 ESCANEOS
                            </label>
                            <p id="qrScanCount" style="font-size: 24px; font-weight: 900; color: var(--duo-blue);">0</p>
                        </div>
                        
                        <div style="margin-top: 16px; padding: 12px; background: white; border-radius: 12px; border: 2px solid var(--gray-200);">
                            <p style="font-size: 12px; font-weight: 600; color: var(--gray-700); line-height: 1.5;">
                                💡 <strong>Tip:</strong> Comparte este código QR para que otros puedan acceder fácilmente al contenido escaneándolo con su dispositivo móvil.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    let currentQRData = null;
    let qrCodeInstance = null;
    
    function openQRModal(entityType, entityId) {
        const modal = document.getElementById('qrModal');
        const container = document.getElementById('qrCodeContainer');
        
        // Limpiar QR anterior
        container.innerHTML = '';
        if (qrCodeInstance) {
            qrCodeInstance = null;
        }
        
        // Mostrar modal
        modal.classList.remove('hidden');
        
        // Cargar datos del QR
        fetch(`../api/get-qr.php?type=${entityType}&id=${entityId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentQRData = data;
                    
                    // Actualizar información
                    document.getElementById('qrTitle').textContent = data.title;
                    document.getElementById('qrCode').value = data.code;
                    document.getElementById('qrScanCount').textContent = data.scan_count || 0;
                    
                    // Generar QR con la URL completa
                    qrCodeInstance = new QRCode(container, {
                        text: data.qr_code || data.qr_url || data.code,
                        width: 250,
                        height: 250,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo generar el código QR',
                        confirmButtonColor: '#1CB0F6'
                    });
                    closeQRModal();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al cargar el código QR',
                    confirmButtonColor: '#1CB0F6'
                });
                closeQRModal();
            });
    }
    
    function closeQRModal() {
        document.getElementById('qrModal').classList.add('hidden');
        currentQRData = null;
    }
    
    function downloadQR() {
        if (!qrCodeInstance) return;
        
        const canvas = document.querySelector('#qrCodeContainer canvas');
        if (canvas) {
            const link = document.createElement('a');
            link.download = `QR-${currentQRData.code}.png`;
            link.href = canvas.toDataURL();
            link.click();
            
            Swal.fire({
                icon: 'success',
                title: '✅ ¡Descargado!',
                text: 'Código QR descargado correctamente',
                confirmButtonColor: '#1CB0F6',
                timer: 2000,
                showConfirmButton: false
            });
        }
    }
    
    function copyQRCode() {
        const codeInput = document.getElementById('qrCode');
        codeInput.select();
        codeInput.setSelectionRange(0, 99999); // Para móviles
        
        navigator.clipboard.writeText(codeInput.value);
    }
    
    // Cerrar modal al hacer click fuera
    document.getElementById('qrModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeQRModal();
        }
    });
</script>

