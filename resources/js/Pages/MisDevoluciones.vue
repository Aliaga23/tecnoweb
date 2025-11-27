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
            <span v-if="carritoCount > 0" style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; display: flex; align-items: center; justify-content: center; font-weight: bold;">{{ carritoCount }}</span>
          </a>
        </div>
      </div>
    </nav>

    <!-- Mis Devoluciones -->
    <section class="section" style="background-color: var(--color-bg-alt); min-height: 80vh; padding: 4rem 0;">
      <div class="container" style="max-width: 1400px;">
        <div style="margin-bottom: 3rem;">
          <h2 class="section-title" style="margin-bottom: 0.5rem;">Mis <span class="highlight">Devoluciones</span></h2>
          <p class="section-subtitle" style="margin: 0;">Historial de todas tus devoluciones</p>
        </div>

        <!-- Loading -->
        <div v-if="cargando" class="loading-container">
          <div class="loading"></div>
        </div>

        <!-- Lista de Devoluciones -->
        <div v-else-if="devoluciones.length > 0" style="display: flex; flex-direction: column; gap: 1.5rem;">
          <div v-for="devolucion in devoluciones" :key="devolucion.id" class="card" style="padding: 1.5rem;">
            <!-- Encabezado de la devolución -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 2px solid var(--color-border);">
              <div>
                <h3 style="font-size: 1.25rem; color: var(--color-primary); margin: 0 0 0.5rem 0;">
                  Devolución #{{ devolucion.id }}
                </h3>
                <p style="margin: 0; color: var(--color-text); font-size: 0.9rem;">
                  <strong>Fecha:</strong> {{ formatearFecha(devolucion.fecha_devolucion) }}
                </p>
                <p style="margin: 0.25rem 0 0 0; color: var(--color-text); font-size: 0.9rem;">
                  <strong>Motivo:</strong> {{ devolucion.motivo }}
                </p>
                <p style="margin: 0.25rem 0 0 0; color: var(--color-text); font-size: 0.9rem;">
                  <strong>Venta ID:</strong> #{{ devolucion.venta_id }}
                </p>
              </div>
            </div>

            <!-- Detalles de productos devueltos -->
            <div style="margin-bottom: 1rem;">
              <h4 style="font-size: 1rem; color: var(--color-text); margin: 0 0 1rem 0; font-weight: 600;">
                Productos devueltos:
              </h4>
              <div v-if="devolucion.detalles && devolucion.detalles.length > 0" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div v-for="detalle in devolucion.detalles" :key="detalle.id" 
                     style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: var(--color-bg); border-radius: 8px; border: 1px solid var(--color-border);">
                  <div style="flex: 1;">
                    <p style="margin: 0; font-weight: 600; color: var(--color-text);">
                      {{ detalle.producto ? detalle.producto.nombre : 'Producto' }}
                    </p>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem; color: var(--color-text-light);">
                      Cantidad devuelta: {{ detalle.cantidad }}
                    </p>
                  </div>
                </div>
              </div>
              <div v-else style="padding: 1rem; text-align: center; color: var(--color-text-light); background: var(--color-bg); border-radius: 8px;">
                No hay detalles disponibles
              </div>
            </div>
          </div>
        </div>

        <!-- Sin devoluciones -->
        <div v-else style="text-align: center; padding: 3rem;">
          <p style="font-size: 1.2rem; color: var(--color-text);">No tienes devoluciones registradas</p>
          <a :href="getAppUrl('/mis-compras')" class="btn btn-primary" style="margin-top: 1rem; display: inline-block; text-decoration: none;">
            Ver mis compras
          </a>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <div class="container footer-content">
        <div>
          <h3 class="footer-logo">MOTO<span>PARTS</span></h3>
          <p style="margin-top: 0.5rem;">Tu tienda de confianza para repuestos de motos</p>
        </div>
        <div>
          <h4>Contacto</h4>
          <p>Email: info@ELYTA.com</p>
          <p>Teléfono: +591 123 456 789</p>
        </div>
        <div>
          <h4>Enlaces</h4>
          <ul style="list-style: none; padding: 0;">
            <li><a :href="getAppUrl('/')">Inicio</a></li>
            <li><a :href="getAppUrl('/catalogo')">Productos</a></li>
            <li><a :href="getAppUrl('/mis-compras')">Compras</a></li>
            <li><a :href="getAppUrl('/mis-devoluciones')">Devoluciones</a></li>
          </ul>
        </div>
      </div>
      <div style="text-align: center; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
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
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { User, ShoppingCart } from 'lucide-vue-next';
import { useApi } from '../composables/useApi';

const { apiFetch, getAppUrl } = useApi();

// Estados de accesibilidad
const accessibilityPanelOpen = ref(false);
const currentTheme = ref('theme-adults');
const fontSize = ref(16);
const highContrast = ref(false);
const isNightMode = ref(false);
const autoNightMode = ref(false);

// Contador de visitas
const contadorVisitas = ref(0);

// Estados de usuario
const usuario = ref(null);
const userMenuOpen = ref(false);
const carrito = ref([]);

// Estados de devoluciones
const cargando = ref(true);
const devoluciones = ref([]);

// Verificar si hay usuario logueado
const usuarioData = localStorage.getItem('user');
if (usuarioData) {
  try {
    usuario.value = JSON.parse(usuarioData);
  } catch (error) {
    console.error('Error al parsear usuario:', error);
  }
}

// Cargar carrito
const carritoGuardado = localStorage.getItem('carrito');
if (carritoGuardado) {
  try {
    carrito.value = JSON.parse(carritoGuardado);
  } catch (error) {
    console.error('Error al parsear carrito:', error);
  }
}

// Computed
const fontSizeClass = computed(() => `font-size-${fontSize.value}`);
const fontSizeLabel = computed(() => `${fontSize.value}px`);

const carritoCount = computed(() => {
  return carrito.value.reduce((total, item) => total + item.cantidad, 0);
});

// Cargar devoluciones
const cargarDevoluciones = async () => {
  try {
    const token = localStorage.getItem('token');
    if (!token) {
      window.location.href = getAppUrl('/login');
      return;
    }

    const response = await apiFetch(`/api/mis-devoluciones`);
    const data = await response.json();
    if (response.ok) {
      devoluciones.value = data;
    } else {
      console.error('Error al cargar devoluciones:', data.error);
    }
  } catch (error) {
    console.error('Error al cargar devoluciones:', error);
  } finally {
    cargando.value = false;
  }
};

// Métodos de accesibilidad
const toggleAccessibilityPanel = () => {
  accessibilityPanelOpen.value = !accessibilityPanelOpen.value;
};

const toggleNightMode = () => {
  isNightMode.value = !isNightMode.value;
  localStorage.setItem('nightMode', isNightMode.value);
};

const checkAutoNightMode = () => {
  if (autoNightMode.value) {
    const hour = new Date().getHours();
    isNightMode.value = hour >= 19 || hour < 7;
  }
};

// Métodos de usuario
const toggleUserMenu = () => {
  userMenuOpen.value = !userMenuOpen.value;
};

const cerrarSesion = () => {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  usuario.value = null;
  window.location.reload();
};

// Utilidades
const formatearFecha = (fecha) => {
  if (!fecha) return 'N/A';
  const date = new Date(fecha);
  return date.toLocaleDateString('es-BO', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Watchers
watch(autoNightMode, (newValue) => {
  localStorage.setItem('autoNightMode', newValue);
  if (newValue) {
    checkAutoNightMode();
  }
});

watch(currentTheme, (newValue) => {
  localStorage.setItem('theme', newValue);
});

watch(fontSize, (newValue) => {
  localStorage.setItem('fontSize', newValue);
});

watch(highContrast, (newValue) => {
  localStorage.setItem('highContrast', newValue);
});

const registrarVisita = async () => {
  try {
    const response = await apiFetch('/api/visitas/mis-devoluciones', {
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

// Lifecycle
onMounted(async () => {
  await registrarVisita();
  
  // Cargar preferencias guardadas
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme) currentTheme.value = savedTheme;

  const savedFontSize = localStorage.getItem('fontSize');
  if (savedFontSize) fontSize.value = parseInt(savedFontSize);

  const savedHighContrast = localStorage.getItem('highContrast');
  if (savedHighContrast) highContrast.value = savedHighContrast === 'true';

  const savedNightMode = localStorage.getItem('nightMode');
  if (savedNightMode) isNightMode.value = savedNightMode === 'true';

  const savedAutoNightMode = localStorage.getItem('autoNightMode');
  if (savedAutoNightMode) {
    autoNightMode.value = savedAutoNightMode === 'true';
    if (autoNightMode.value) {
      checkAutoNightMode();
    }
  }

  cargarDevoluciones();

  // Verificar modo noche automático cada hora
  const nightModeInterval = setInterval(checkAutoNightMode, 3600000);
  
  onUnmounted(() => {
    clearInterval(nightModeInterval);
  });
});
</script>

<style scoped>
/* Los estilos ya están en el CSS global */
</style>
