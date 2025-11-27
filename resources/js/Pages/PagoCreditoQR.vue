<template>
  <div :class="['app-wrapper', currentTheme, fontSizeClass, { 'high-contrast': highContrast, 'theme-night': isNightMode }]">
    <!-- Botón de accesibilidad -->
    <button class="accessibility-toggle" @click="toggleAccessibilityPanel">
      Accesibilidad
    </button>

    <!-- Panel de accesibilidad -->
    <div :class="['accessibility-panel', { active: accessibilityPanelOpen }]">
      <h3>Configuración de Accesibilidad</h3>
      
      <div class="control-group">
        <label>Tema:</label>
        <select v-model="currentTheme">
          <option value="theme-adults">Adultos</option>
          <option value="theme-young">Jóvenes</option>
          <option value="theme-kids">Niños</option>
        </select>
      </div>

      <div class="control-group">
        <label>Tamaño de fuente: {{ fontSizeLabel }}</label>
        <input 
          type="range" 
          v-model.number="fontSize" 
          min="12" 
          max="24" 
          step="2"
          style="width: 100%;"
        >
      </div>

      <div class="control-group">
        <label>Alto contraste:</label>
        <button @click="highContrast = !highContrast">
          {{ highContrast ? 'Desactivar' : 'Activar' }}
        </button>
      </div>

      <div class="control-group">
        <label>Modo Día/Noche:</label>
        <button @click="toggleNightMode">
          {{ isNightMode ? 'Modo Día' : 'Modo Noche' }}
        </button>
      </div>

      <div class="control-group">
        <label>
          <input type="checkbox" v-model="autoNightMode" style="margin-right: 8px;">
          Automático (7pm-7am)
        </label>
      </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar">
      <div class="container navbar-content">
        <a :href="getAppUrl('/')" class="navbar-logo">ELYTA</a>
        
        <ul class="navbar-menu">
          <li><a :href="getAppUrl('/')" class="navbar-link">Inicio</a></li>
          <li><a :href="getAppUrl('/catalogo')" class="navbar-link">Productos</a></li>
          <li><a href="/#categorias" class="navbar-link">Categorías</a></li>
          <li><a href="/#contacto" class="navbar-link">Contacto</a></li>
        </ul>

        <div class="navbar-controls">
          <template v-if="usuario">
            <span style="color: var(--color-text); margin-right: 1rem;">Bienvenido, {{ usuario.nombre }}</span>
          </template>
        </div>
      </div>
    </nav>

    <!-- Contenido principal -->
    <section class="section" style="background-color: var(--color-bg-alt); min-height: 80vh; padding: 4rem 0;">
      <div class="container">
        <div style="max-width: 600px; margin: 0 auto;">
          <!-- Header -->
          <div style="text-align: center; margin-bottom: 3rem;">
            <h2 class="section-title" style="margin-bottom: 0.5rem;">
              Pago a Crédito con <span class="highlight">QR</span>
            </h2>
            <p class="section-subtitle" style="margin: 0;">
              Escanea el código QR con tu app bancaria
            </p>
          </div>

          <!-- Estado del pago -->
          <div v-if="estadoPago === 'procesando'" style="background: var(--color-bg); border-radius: 16px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; margin-bottom: 2rem;">
            <div class="loading" style="margin: 0 auto 1rem;"></div>
            <h3 style="color: var(--color-primary); margin-bottom: 1rem;">Generando código QR...</h3>
            <p style="color: var(--color-text-light);">Espere un momento por favor</p>
          </div>

          <!-- QR generado -->
          <div v-else-if="estadoPago === 'pendiente'" style="background: var(--color-bg); border-radius: 16px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; margin-bottom: 2rem;">
            <!-- Información del pago -->
            <div style="background: var(--color-bg-alt); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
              <h3 style="color: var(--color-primary); margin-bottom: 1rem;">
                Pago de Cuota
              </h3>
              <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span>Venta #{{ pagoInfo.venta_id }}</span>
              </div>
              <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span>Monto a pagar:</span>
                <strong style="color: var(--color-primary); font-size: 1.5rem;">Bs. {{ parseFloat(pagoInfo.monto).toFixed(2) }}</strong>
              </div>
              <div style="display: flex; justify-content: space-between; font-size: 0.875rem; color: var(--color-text-light);">
                <span>Número de pago:</span>
                <span>{{ pagoInfo.payment_number }}</span>
              </div>
            </div>

            <!-- QR Code -->
            <div v-if="pagoInfo.qr_image" style="background: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);">
              <img :src="pagoInfo.qr_image" alt="Código QR" style="max-width: 300px; width: 100%; height: auto;">
            </div>

            <!-- Instrucciones -->
            <div style="text-align: left; background: #f0f8ff; padding: 1.5rem; border-radius: 12px; border-left: 4px solid var(--color-primary); margin-bottom: 2rem;">
              <h4 style="color: var(--color-primary); margin-bottom: 1rem;">
                Instrucciones de Pago
              </h4>
              <ol style="text-align: left; padding-left: 1.5rem; margin: 0;">
                <li>Abre tu aplicación bancaria</li>
                <li>Busca la opción "Pagar con QR"</li>
                <li>Escanea este código QR</li>
                <li>Confirma el pago de Bs. {{ parseFloat(pagoInfo.monto).toFixed(2) }}</li>
                <li>Espera la confirmación</li>
              </ol>
            </div>

            <!-- Estado de verificación -->
            <div v-if="verificandoPago" style="background: #fff3cd; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #ffc107;">
              <div style="display: flex; align-items: center; justify-content: center;">
                <div class="loading-small" style="margin-right: 0.5rem;"></div>
                <span>Verificando pago...</span>
              </div>
            </div>

            <!-- Botones -->
            <div style="display: flex; gap: 1rem; justify-content: center;">
              <button @click="verificarPagoManual" class="btn btn-primary" :disabled="verificandoPago">
                Verificar Pago
              </button>
              <button @click="cancelarPago" class="btn btn-secondary">
                Cancelar
              </button>
            </div>
          </div>

          <!-- Pago exitoso -->
          <div v-else-if="estadoPago === 'completado'" style="background: var(--color-bg); border-radius: 16px; padding: 3rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center;">
            <div style="width: 80px; height: 80px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem;">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </div>
            <h3 style="color: #10b981; margin-bottom: 1rem; font-size: 1.75rem;">¡Pago Registrado!</h3>
            <p style="color: var(--color-text); margin-bottom: 2rem;">Tu pago ha sido registrado exitosamente</p>
            <button @click="volverACompras" class="btn btn-primary">
              Ver mis compras
            </button>
          </div>

          <!-- Error -->
          <div v-else-if="estadoPago === 'error'" style="background: var(--color-bg); border-radius: 16px; padding: 3rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center;">
            <div style="width: 80px; height: 80px; background: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem;">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </div>
            <h3 style="color: #ef4444; margin-bottom: 1rem; font-size: 1.75rem;">Error en el pago</h3>
            <p style="color: var(--color-text); margin-bottom: 2rem;">{{ mensajeError }}</p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
              <button @click="reintentar" class="btn btn-primary">Reintentar</button>
              <button @click="volverACompras" class="btn btn-secondary">Volver a compras</button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <div class="container">
        <p style="text-align: center; color: #6b7280;">&copy; 2025 ELYTA. Todos los derechos reservados.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useApi } from '../composables/useApi';

const { apiFetch, getAppUrl } = useApi();

// Estados de accesibilidad
const accessibilityPanelOpen = ref(false);
const currentTheme = ref('theme-adults');
const fontSize = ref(16);
const highContrast = ref(false);
const isNightMode = ref(false);
const autoNightMode = ref(false);

// Usuario
const usuario = ref(null);

// Estados del pago
const estadoPago = ref('procesando'); // procesando, pendiente, completado, error
const pagoInfo = ref({
  venta_id: null,
  monto: 0,
  payment_number: '',
  qr_image: null
});
const verificandoPago = ref(false);
const mensajeError = ref('');
let verificacionInterval = null;

// Computed
const fontSizeClass = computed(() => `font-size-${fontSize.value}`);
const fontSizeLabel = computed(() => `${fontSize.value}px`);

// Cargar usuario
const usuarioData = localStorage.getItem('user');
if (usuarioData) {
  try {
    usuario.value = JSON.parse(usuarioData);
  } catch (error) {
    console.error('Error al parsear usuario:', error);
  }
}

// Métodos de accesibilidad
const toggleAccessibilityPanel = () => {
  accessibilityPanelOpen.value = !accessibilityPanelOpen.value;
};

const toggleNightMode = () => {
  isNightMode.value = !isNightMode.value;
  localStorage.setItem('nightMode', isNightMode.value);
};

// Generar QR para pago a crédito
const generarQRCredito = async () => {
  try {
    const token = localStorage.getItem('token');
    const pagoData = localStorage.getItem('pago_credito_pendiente');
    
    if (!pagoData || !usuario.value) {
      mensajeError.value = 'No se encontró información del pago';
      estadoPago.value = 'error';
      return;
    }

    const { venta_id, monto } = JSON.parse(pagoData);
    
    const response = await apiFetch('/api/generar-qr-credito', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        venta_id: venta_id,
        monto: parseFloat(monto),
        cliente_id: usuario.value.id,
        cliente_nombre: `${usuario.value.nombre} ${usuario.value.apellido || ''}`.trim(),
        cliente_ci: usuario.value.ci || '',
        cliente_telefono: usuario.value.telefono || '',
        cliente_email: usuario.value.correo || ''
      })
    });

    const data = await response.json();

    if (response.ok && data.success) {
      pagoInfo.value = {
        venta_id: venta_id,
        monto: monto,
        payment_number: data.payment_number || 'N/A',
        qr_image: data.qr_image || null,
        pago_id: data.pago_id
      };
      
      estadoPago.value = 'pendiente';
      
      // Iniciar verificación automática cada 5 segundos
      verificacionInterval = setInterval(verificarPagoAutomatico, 5000);
      
    } else {
      mensajeError.value = data.error || 'Error al generar el código QR';
      estadoPago.value = 'error';
    }
  } catch (error) {
    console.error('Error al generar QR:', error);
    mensajeError.value = 'Error de conexión al generar el código QR';
    estadoPago.value = 'error';
  }
};

// Verificar pago automáticamente
const verificarPagoAutomatico = async () => {
  if (!pagoInfo.value.pago_id) return;
  
  try {
    const token = localStorage.getItem('token');
    const response = await apiFetch(`/api/verificar-pago/${pagoInfo.value.pago_id}`, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });

    const data = await response.json();

    if (data.success && data.pagado) {
      clearInterval(verificacionInterval);
      estadoPago.value = 'completado';
      localStorage.removeItem('pago_credito_pendiente');
    }
  } catch (error) {
    console.error('Error al verificar pago:', error);
  }
};

// Verificar pago manualmente
const verificarPagoManual = async () => {
  verificandoPago.value = true;
  await verificarPagoAutomatico();
  
  setTimeout(() => {
    if (estadoPago.value !== 'completado') {
      alert('El pago aún no se ha confirmado. Por favor, intenta nuevamente en unos momentos.');
    }
    verificandoPago.value = false;
  }, 2000);
};

// Cancelar y volver
const cancelarPago = () => {
  if (confirm('¿Estás seguro de cancelar este pago?')) {
    if (verificacionInterval) {
      clearInterval(verificacionInterval);
    }
    window.location.href = getAppUrl('/mis-compras');
  }
};

const volverACompras = () => {
  if (verificacionInterval) {
    clearInterval(verificacionInterval);
  }
  window.location.href = getAppUrl('/mis-compras');
};

const reintentar = () => {
  estadoPago.value = 'procesando';
  generarQRCredito();
};

// Lifecycle
onMounted(async () => {
  // Cargar preferencias
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme) currentTheme.value = savedTheme;

  const savedFontSize = localStorage.getItem('fontSize');
  if (savedFontSize) fontSize.value = parseInt(savedFontSize);

  const savedHighContrast = localStorage.getItem('highContrast');
  if (savedHighContrast) highContrast.value = savedHighContrast === 'true';

  const savedNightMode = localStorage.getItem('nightMode');
  if (savedNightMode) isNightMode.value = savedNightMode === 'true';

  // Generar QR
  await generarQRCredito();
});

onUnmounted(() => {
  if (verificacionInterval) {
    clearInterval(verificacionInterval);
  }
});
</script>

<style scoped>
.loading {
  width: 50px;
  height: 50px;
  border: 5px solid #f3f3f3;
  border-top: 5px solid var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.loading-small {
  width: 20px;
  height: 20px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
