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
        </ul>

        <div class="navbar-controls">
          <template v-if="usuario">
            <span style="color: var(--color-text); margin-right: 1rem;">Bienvenido, {{ usuario.nombre }}</span>
            <div class="user-menu-container">
              <button @click="toggleUserMenu" class="navbar-icon" title="Mi cuenta">
                <User :size="24" />
              </button>
              <div v-if="userMenuOpen" class="user-menu">
                <a :href="getAppUrl('/mis-cotizaciones')" class="user-menu-item">Mis cotizaciones</a>
                <a :href="getAppUrl('/mis-compras')" class="user-menu-item">Mis compras</a>
                <a :href="getAppUrl('/mis-devoluciones')" class="user-menu-item">Mis devoluciones</a>
                <button @click="cerrarSesion" class="user-menu-item">Cerrar sesión</button>
              </div>
            </div>
          </template>
          <template v-else>
            <a :href="getAppUrl('/register')" class="btn btn-secondary">Registrarse</a>
            <a :href="getAppUrl('/login')" class="btn btn-primary">Ingresar</a>
          </template>
          <a :href="getAppUrl('/cart')" class="navbar-icon" title="Carrito" style="position: relative;">
            <ShoppingCart :size="24" />
          </a>
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
              Pago con <span class="highlight">QR</span>
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
              <h3 style="color: var(--color-primary); margin-bottom: 1rem; display: flex; align-items: center; justify-content: center;">
                <CreditCard :size="24" style="margin-right: 0.5rem;" />
                Resumen del Pago
              </h3>
              <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span>Total:</span>
                <strong style="color: var(--color-primary); font-size: 1.25rem;">Bs. {{ parseFloat(pagoInfo.total).toFixed(2) }}</strong>
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
              <h4 style="color: var(--color-primary); margin-bottom: 1rem; display: flex; align-items: center;">
                <Smartphone :size="20" style="margin-right: 0.5rem;" />
                Instrucciones de Pago
              </h4>
              <ol style="text-align: left; padding-left: 1.5rem; margin: 0;">
                <li>Abre tu aplicación bancaria (BancaMovil, BNB, BMSC, etc.)</li>
                <li>Busca la opción "Pagar con QR" o "Escanear QR"</li>
                <li>Escanea este código QR con tu teléfono</li>
                <li><strong>Confirma el pago</strong></li>
                <li>Espera la confirmación en esta página</li>
              </ol>
            </div>

            <!-- Estado de verificación -->
            <div style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; color: var(--color-text-light); margin-bottom: 2rem;">
              <div class="loading" style="width: 20px; height: 20px;"></div>
              <span>Esperando confirmación del pago...</span>
            </div>
          </div>

          <!-- Pago exitoso -->
          <div v-else-if="estadoPago === 'completado'" style="background: var(--color-bg); border-radius: 16px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; margin-bottom: 2rem;">
            <div style="background: #dcfce7; color: #16a34a; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
              <CheckCircle :size="48" style="margin-bottom: 1rem;" />
              <h3 style="margin-bottom: 0.5rem;">¡Pago Exitoso!</h3>
              <p style="margin: 0;">Tu compra ha sido procesada correctamente</p>
            </div>
            
            <!-- Botón de WhatsApp para coordinar entrega -->
            <div style="background: #f0fdf4; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; border-left: 4px solid #10b981;">
              <h4 style="color: #059669; margin-bottom: 1rem;">Coordina tu entrega</h4>
              <p style="margin-bottom: 1rem; color: var(--color-text);">Contáctanos por WhatsApp para coordinar la entrega de tu pedido</p>
              <a href="https://wa.link/q4dkbj" target="_blank" class="btn" style="background: #25D366; color: white; display: inline-flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                Contactar por WhatsApp
              </a>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: center;">
              <a :href="getAppUrl('/mis-compras')" class="btn btn-primary">Ver mis compras</a>
              <a :href="getAppUrl('/catalogo')" class="btn btn-secondary">Seguir comprando</a>
            </div>
          </div>

          <!-- Error -->
          <div v-else-if="estadoPago === 'error'" style="background: var(--color-bg); border-radius: 16px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; margin-bottom: 2rem;">
            <div style="background: #fef2f2; color: #dc2626; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
              <AlertCircle :size="48" style="margin-bottom: 1rem;" />
              <h3 style="margin-bottom: 0.5rem;">Error en el Pago</h3>
              <p style="margin: 0;">{{ errorMessage }}</p>
            </div>
            <div style="display: flex; gap: 1rem; justify-content: center;">
              <button @click="reintentar" class="btn btn-primary">Reintentar</button>
              <a :href="getAppUrl('/cart')" class="btn btn-secondary">Volver al carrito</a>
            </div>
          </div>

          <!-- Botones de acción -->
          <div v-if="estadoPago === 'pendiente'" style="display: flex; gap: 1rem; justify-content: center;">
            <button @click="verificarEstado" class="btn btn-secondary">
              <RefreshCw :size="20" style="margin-right: 0.5rem;" />
              Verificar Estado
            </button>
            <a :href="getAppUrl('/cart')" class="btn btn-outline">Cancelar</a>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <div class="container">
        <h3 class="footer-title">ELYTA</h3>
        <p class="footer-text">Tu mejor opción en repuestos para motos</p>
        <div class="footer-social">
          <a href="#">Facebook</a>
          <a href="#">Instagram</a>
          <a href="#">WhatsApp</a>
        </div>
        <p style="color: #6b7280; font-size: 14px;">&copy; 2025 ELYTA. Todos los derechos reservados.</p>
        
        <div class="footer-counter">
          Visitas en esta página: <strong>{{ contadorVisitas }}</strong>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch, onUnmounted } from 'vue';
import { ShoppingCart, User, CreditCard, Smartphone, CheckCircle, AlertCircle, RefreshCw } from 'lucide-vue-next';
import { useApi } from '../composables/useApi';

const { apiFetch, getAppUrl } = useApi();

// Props
const props = defineProps({
  carrito: Array,
  total: Number
});

// Estados
const estadoPago = ref('procesando'); // procesando, pendiente, completado, error
const pagoInfo = ref({});
const errorMessage = ref('');
const usuario = ref(null);
const userMenuOpen = ref(false);
const contadorVisitas = ref(0);
const intervalVerificacion = ref(null);

// Verificar si hay usuario logueado
const usuarioData = localStorage.getItem('user');
if (usuarioData) {
  try {
    usuario.value = JSON.parse(usuarioData);
  } catch (error) {
    console.error('Error al parsear usuario:', error);
  }
}

const cerrarSesion = () => {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  usuario.value = null;
  window.location.reload();
};

const toggleUserMenu = () => {
  userMenuOpen.value = !userMenuOpen.value;
};

const generarQR = async () => {
  try {
    const token = localStorage.getItem('token');
    if (!token) {
      window.location.href = getAppUrl('/login');
      return;
    }

    // Obtener carrito actual
    const carritoActual = JSON.parse(localStorage.getItem('carrito') || '[]');
    if (carritoActual.length === 0) {
      errorMessage.value = 'El carrito está vacío';
      estadoPago.value = 'error';
      return;
    }

    // Calcular total
    const totalCalculado = carritoActual.reduce((sum, item) => 
      sum + (item.cantidad * item.costo_unitario), 0
    );

    // Obtener cotizacion_id si existe
    const cotizacionId = localStorage.getItem('cotizacion_id');
    
    // Obtener venta_pendiente_id si existe (pago de compra pendiente)
    const ventaPendienteId = localStorage.getItem('venta_pendiente_id');

    const payload = {
      cliente_id: usuario.value.id,
      productos: carritoActual,
      total: totalCalculado,
      cliente_nombre: `${usuario.value.nombre} ${usuario.value.apellido}`,
      cliente_ci: usuario.value.ci,
      cliente_telefono: usuario.value.telefono,
      cliente_email: usuario.value.correo,
      cotizacion_id: cotizacionId ? parseInt(cotizacionId) : null,
      venta_id: ventaPendienteId ? parseInt(ventaPendienteId) : null
    };

    const response = await apiFetch('/api/generar-qr', {
      method: 'POST',
      body: JSON.stringify(payload)
    });

    const data = await response.json();

    if (data.success) {
      pagoInfo.value = data;
      estadoPago.value = 'pendiente';
      
      // Limpiar carrito, cotizacion_id y venta_pendiente_id después de generar QR
      localStorage.removeItem('carrito');
      localStorage.removeItem('cotizacion_id');
      localStorage.removeItem('venta_pendiente_id');
      
      // Iniciar verificación automática cada 5 segundos
      intervalVerificacion.value = setInterval(verificarEstado, 5000);
    } else {
      errorMessage.value = data.error || 'Error al generar QR';
      estadoPago.value = 'error';
    }

  } catch (error) {
    console.error('Error al generar QR:', error);
    errorMessage.value = 'Error de conexión';
    estadoPago.value = 'error';
  }
};

const verificarEstado = async () => {
  try {
    if (!pagoInfo.value.pago_id) return;

    const response = await apiFetch(`/api/pago-estado/${pagoInfo.value.pago_id}`);
    const data = await response.json();

    if (data.estado === 'pagada') {
      estadoPago.value = 'completado';
      if (intervalVerificacion.value) {
        clearInterval(intervalVerificacion.value);
      }
    } else if (data.estado === 'fallido') {
      errorMessage.value = 'El pago falló. Intenta nuevamente.';
      estadoPago.value = 'error';
      if (intervalVerificacion.value) {
        clearInterval(intervalVerificacion.value);
      }
    }
    // Si está pendiente, no hacer nada, seguir esperando

  } catch (error) {
    console.error('Error al verificar estado:', error);
  }
};

const reintentar = () => {
  estadoPago.value = 'procesando';
  errorMessage.value = '';
  pagoInfo.value = {};
  generarQR();
};

// Registrar visita
const registrarVisita = async () => {
  try {
    const response = await apiFetch('/api/visitas/pago-qr', {
      method: 'POST'
    });
    const data = await response.json();
    if (data.success) {
      contadorVisitas.value = data.visitas;
    }
  } catch (error) {
    console.error('Error al registrar visita:', error);
  }
};

// Accesibilidad
const accessibilityPanelOpen = ref(false);
const currentTheme = ref(localStorage.getItem('theme') || 'theme-adults');
const fontSize = ref(parseInt(localStorage.getItem('fontSize')) || 16);
const highContrast = ref(localStorage.getItem('highContrast') === 'true');
const manualNightMode = ref(localStorage.getItem('manualNightMode') === 'true');
const autoNightMode = ref(localStorage.getItem('autoNightMode') !== 'false');

const fontSizeLabel = computed(() => {
  if (fontSize.value <= 14) return 'Pequeño';
  if (fontSize.value <= 18) return 'Normal';
  if (fontSize.value <= 22) return 'Grande';
  return 'Extra Grande';
});

const fontSizeClass = computed(() => {
  if (fontSize.value <= 14) return 'font-small';
  if (fontSize.value <= 18) return '';
  if (fontSize.value <= 22) return 'font-large';
  return 'font-xlarge';
});

const isNightMode = computed(() => {
  if (!autoNightMode.value) {
    return manualNightMode.value;
  }
  const hour = new Date().getHours();
  return hour >= 19 || hour < 7;
});

const toggleAccessibilityPanel = () => {
  accessibilityPanelOpen.value = !accessibilityPanelOpen.value;
};

const toggleNightMode = () => {
  manualNightMode.value = !manualNightMode.value;
  autoNightMode.value = false;
  localStorage.setItem('manualNightMode', manualNightMode.value);
  localStorage.setItem('autoNightMode', 'false');
};

watch(currentTheme, (newTheme) => {
  localStorage.setItem('theme', newTheme);
});

watch(fontSize, (newSize) => {
  localStorage.setItem('fontSize', newSize);
  document.documentElement.style.setProperty('--font-size-base', `${newSize}px`);
});

watch(highContrast, (newValue) => {
  localStorage.setItem('highContrast', newValue);
});

watch(autoNightMode, (newValue) => {
  localStorage.setItem('autoNightMode', newValue);
  if (newValue) {
    manualNightMode.value = false;
    localStorage.setItem('manualNightMode', 'false');
  }
});

onMounted(async () => {
  document.documentElement.style.setProperty('--font-size-base', `${fontSize.value}px`);
  
  // Verificar autenticación
  if (!usuario.value) {
    window.location.href = getAppUrl('/login');
    return;
  }
  
  // Registrar visita
  await registrarVisita();
  
  // Generar QR automáticamente
  await generarQR();
});

onUnmounted(() => {
  if (intervalVerificacion.value) {
    clearInterval(intervalVerificacion.value);
  }
});
</script>