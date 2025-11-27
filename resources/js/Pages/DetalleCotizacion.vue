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

    <!-- Contenido principal -->
    <section class="section" style="background-color: var(--color-bg-alt); min-height: 80vh; padding: 4rem 0;">
      <div class="container">
        <!-- Header -->
        <div style="margin-bottom: 3rem; display: flex; justify-content: space-between; align-items: center;">
          <div>
            <h2 class="section-title" style="margin-bottom: 0.5rem;">
              Detalle de <span class="highlight">Cotización #{{ cotizacion.id }}</span>
            </h2>
            <p class="section-subtitle" style="margin: 0;">
              {{ formatearFecha(cotizacion.fecha_cotizacion) }}
            </p>
          </div>
          <a :href="getAppUrl('/mis-cotizaciones')" class="btn btn-secondary">
            <ArrowLeft :size="20" style="margin-right: 0.5rem;" />
            Volver
          </a>
        </div>

        <div v-if="cargando" class="loading-container">
          <div class="loading"></div>
        </div>

        <div v-else-if="error" style="text-align: center; padding: 4rem 2rem; background: var(--color-bg); border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
          <div style="background: #fef2f2; color: #dc2626; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
            {{ error }}
          </div>
          <a :href="getAppUrl('/mis-cotizaciones')" class="btn btn-primary">Volver a Mis Cotizaciones</a>
        </div>

        <div v-else class="grid grid-cols-1" style="gap: 2rem;">
          <!-- Información de la cotización -->
          <div style="background: var(--color-bg); border-radius: 16px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="font-size: 1.5rem; color: var(--color-primary); margin-bottom: 1.5rem; font-weight: 600; display: flex; align-items: center;">
              <FileText :size="24" style="margin-right: 0.75rem;" />
              Información General
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3" style="gap: 1.5rem;">
              <div>
                <p style="color: var(--color-text-light); font-size: 0.875rem; margin-bottom: 0.25rem;">Cliente</p>
                <p style="font-weight: 600; color: var(--color-text);">{{ cotizacion.cliente }}</p>
              </div>
              <div>
                <p style="color: var(--color-text-light); font-size: 0.875rem; margin-bottom: 0.25rem;">Fecha</p>
                <p style="font-weight: 600; color: var(--color-text);">{{ formatearFecha(cotizacion.fecha_cotizacion) }}</p>
              </div>
              <div>
                <p style="color: var(--color-text-light); font-size: 0.875rem; margin-bottom: 0.25rem;">Total</p>
                <p style="font-weight: 700; color: var(--color-primary); font-size: 1.5rem;">Bs. {{ parseFloat(cotizacion.total).toFixed(2) }}</p>
              </div>
            </div>
          </div>

          <!-- Detalles de productos -->
          <div style="background: var(--color-bg); border-radius: 16px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="font-size: 1.5rem; color: var(--color-primary); margin-bottom: 1.5rem; font-weight: 600; display: flex; align-items: center;">
              <Package :size="24" style="margin-right: 0.75rem;" />
              Productos Cotizados
            </h3>
            
            <div class="table-responsive">
              <table style="width: 100%; border-collapse: collapse;">
                <thead>
                  <tr style="background: var(--color-bg-alt);">
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Producto</th>
                    <th style="padding: 1rem; text-align: center; font-weight: 600; color: var(--color-text);">Cantidad</th>
                    <th style="padding: 1rem; text-align: right; font-weight: 600; color: var(--color-text);">Precio Unit.</th>
                    <th style="padding: 1rem; text-align: right; font-weight: 600; color: var(--color-text);">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="detalle in cotizacion.detalles" :key="detalle.id" style="border-bottom: 1px solid var(--color-border);">
                    <td style="padding: 1rem;">
                      <div style="display: flex; align-items: center; gap: 1rem;">
                        <img 
                          :src="detalle.imagen_url || '/default-product.jpg'" 
                          :alt="detalle.nombre"
                          style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid var(--color-border);"
                        >
                        <div>
                          <h4 style="font-weight: 600; color: var(--color-text); margin-bottom: 0.25rem;">{{ detalle.nombre }}</h4>
                          <p style="color: var(--color-text-light); font-size: 0.875rem;">{{ detalle.descripcion }}</p>
                        </div>
                      </div>
                    </td>
                    <td style="padding: 1rem; text-align: center; font-weight: 600; color: var(--color-text);">
                      {{ detalle.cantidad }}
                    </td>
                    <td style="padding: 1rem; text-align: right; font-weight: 600; color: var(--color-text);">
                      Bs. {{ parseFloat(detalle.costo_unitario).toFixed(2) }}
                    </td>
                    <td style="padding: 1rem; text-align: right; font-weight: 700; color: var(--color-primary);">
                      Bs. {{ parseFloat(detalle.subtotal).toFixed(2) }}
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr style="background: var(--color-bg-alt); font-weight: 700;">
                    <td colspan="3" style="padding: 1rem; text-align: right; color: var(--color-text);">
                      Total:
                    </td>
                    <td style="padding: 1rem; text-align: right; color: var(--color-primary); font-size: 1.25rem;">
                      Bs. {{ parseFloat(cotizacion.total).toFixed(2) }}
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- Acciones -->
          <div style="background: var(--color-bg); border-radius: 16px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div style="display: flex; gap: 1rem; justify-content: space-between; align-items: center; flex-wrap: wrap;">
              <h3 style="font-size: 1.25rem; color: var(--color-text); font-weight: 600; margin: 0;">Acciones</h3>
              <div style="display: flex; gap: 1rem;">
                <button @click="descargarPDF" class="btn btn-secondary">
                  <Download :size="20" style="margin-right: 0.5rem;" />
                  Descargar PDF
                </button>
                <button @click="duplicarCotizacion" class="btn btn-secondary">
                  <Copy :size="20" style="margin-right: 0.5rem;" />
                  Duplicar al Carrito
                </button>
                <button @click="crearNuevaCotizacion" class="btn btn-primary">
                  <Plus :size="20" style="margin-right: 0.5rem;" />
                  Nueva Cotización
                </button>
              </div>
            </div>
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
import { ref, onMounted, computed, watch } from 'vue';
import { ShoppingCart, User, FileText, Package, ArrowLeft, Download, Copy, Plus } from 'lucide-vue-next';
import { useApi } from '../composables/useApi';

const { apiFetch, getAppUrl } = useApi();

// Props
const props = defineProps({
  cotizacionId: String
});

// Estados
const cotizacion = ref({});
const cargando = ref(true);
const error = ref('');
const usuario = ref(null);
const userMenuOpen = ref(false);
const carrito = ref([]);
const contadorVisitas = ref(0);

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

const carritoCount = computed(() => {
  return carrito.value.reduce((total, item) => total + item.cantidad, 0);
});

const cerrarSesion = () => {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  usuario.value = null;
  window.location.reload();
};

const toggleUserMenu = () => {
  userMenuOpen.value = !userMenuOpen.value;
};

const formatearFecha = (fecha) => {
  return new Date(fecha).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const cargarDetalleCotizacion = async () => {
  try {
    const token = localStorage.getItem('token');
    if (!token) {
      window.location.href = getAppUrl('/login');
      return;
    }

    const response = await apiFetch(`/api/cotizaciones/${props.cotizacionId}`);

    const data = await response.json();
    if (response.ok) {
      cotizacion.value = data;
    } else {
      error.value = data.error || 'Error al cargar la cotización';
    }
  } catch (err) {
    console.error('Error al cargar cotización:', err);
    error.value = 'Error de conexión al cargar la cotización';
  } finally {
    cargando.value = false;
  }
};

const descargarPDF = async () => {
  try {
    const token = localStorage.getItem('token');
    if (!token) {
      alert('Debe iniciar sesión para descargar el PDF');
      return;
    }

    const response = await apiFetch(`/api/cotizaciones/${props.cotizacionId}/pdf`, {
      method: 'GET'
    });

    if (response.ok) {
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `cotizacion_${props.cotizacionId}_${new Date().toISOString().split('T')[0]}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } else {
      const error = await response.json();
      alert('Error al descargar PDF: ' + (error.error || 'Error desconocido'));
    }
  } catch (error) {
    console.error('Error al descargar PDF:', error);
    alert('Error al descargar PDF');
  }
};

const duplicarCotizacion = () => {
  if (confirm('¿Duplicar esta cotización al carrito?')) {
    try {
      // Agregar productos de la cotización al carrito
      const nuevosItems = cotizacion.value.detalles.map(detalle => ({
        producto_id: detalle.producto_id,
        nombre: detalle.nombre,
        costo_unitario: detalle.costo_unitario,
        cantidad: detalle.cantidad,
        stock_maximo: 100, // Valor por defecto
        imagen_url: detalle.imagen_url
      }));
      
      carrito.value = [...carrito.value, ...nuevosItems];
      localStorage.setItem('carrito', JSON.stringify(carrito.value));
      
      // Disparar evento para sincronizar con otras pestañas
      window.dispatchEvent(new StorageEvent('storage', {
        key: 'carrito',
        newValue: JSON.stringify(carrito.value)
      }));
      
      alert('Productos agregados al carrito exitosamente');
    } catch (error) {
      console.error('Error al duplicar cotización:', error);
      alert('Error al duplicar cotización');
    }
  }
};

const crearNuevaCotizacion = () => {
  window.location.href = getAppUrl('/catalogo');
};

// Registrar visita
const registrarVisita = async () => {
  try {
    const response = await apiFetch('/api/visitas/detalle-cotizacion', {
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
  
  // Cargar detalle de cotización
  await cargarDetalleCotizacion();

  // Escuchar cambios en el carrito desde otras pestañas
  window.addEventListener('storage', (e) => {
    if (e.key === 'carrito' && e.newValue) {
      carrito.value = JSON.parse(e.newValue);
    }
  });
});
</script>