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

    <!-- Mis Cotizaciones -->
    <section class="section" style="background-color: var(--color-bg-alt); min-height: 80vh; padding: 4rem 0;">
      <div class="container">
        <div style="margin-bottom: 3rem;">
          <h2 class="section-title" style="margin-bottom: 0.5rem;">Mis <span class="highlight">Cotizaciones</span></h2>
          <p class="section-subtitle" style="margin: 0;">Historial de todas tus cotizaciones</p>
        </div>

        <div v-if="cargando" class="loading-container">
          <div class="loading"></div>
        </div>

        <div v-else-if="cotizaciones.length === 0" style="text-align: center; padding: 6rem 2rem; background: var(--color-bg); border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
          <div style="background: var(--color-bg-alt); width: 120px; height: 120px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem;">
            <FileText :size="64" style="color: var(--color-text-light);" />
          </div>
          <h3 style="font-size: 1.75rem; color: var(--color-text); margin-bottom: 1rem; font-weight: 600;">No tienes cotizaciones</h3>
          <p style="color: var(--color-text-light); margin-bottom: 2.5rem; font-size: 1.125rem;">Crea tu primera cotización desde el catálogo</p>
          <a :href="getAppUrl('/catalogo')" class="btn btn-primary" style="padding: 1rem 3rem; font-size: 1.125rem;">Explorar catálogo</a>
        </div>

        <div v-else class="grid grid-cols-1" style="gap: 1.5rem;">
          <div v-for="cotizacion in cotizaciones" :key="cotizacion.id" style="background: var(--color-bg); border-radius: 16px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: transform 0.2s, box-shadow 0.2s;" @mouseenter="$event.currentTarget.style.transform = 'translateY(-2px)'; $event.currentTarget.style.boxShadow = '0 4px 12px rgba(0,0,0,0.12)'" @mouseleave="$event.currentTarget.style.transform = 'translateY(0)'; $event.currentTarget.style.boxShadow = '0 2px 8px rgba(0,0,0,0.08)'">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1.5rem;">
              <div>
                <h3 style="font-size: 1.5rem; color: var(--color-primary); margin-bottom: 0.5rem; font-weight: 600;">
                  Cotización #{{ cotizacion.id }}
                </h3>
                <p style="color: var(--color-text-light); font-size: 1rem;">
                  {{ formatearFecha(cotizacion.fecha_cotizacion) }}
                </p>
              </div>
              <div style="text-align: right;">
                <p style="font-size: 2rem; font-weight: 700; color: var(--color-primary);">
                  Bs. {{ parseFloat(cotizacion.total).toFixed(2) }}
                </p>
              </div>
            </div>

            <div style="display: flex; gap: 1rem; align-items: center; justify-content: space-between;">
              <button @click="verDetalles(cotizacion.id)" class="btn btn-primary" style="flex: 1;">
                Ver detalles
              </button>
              <button @click="descargarPDF(cotizacion.id)" class="btn btn-secondary" style="flex: 1;">
                Descargar PDF
              </button>
              <button @click="comprarCotizacion(cotizacion)" class="btn btn-primary" style="padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 0.5rem; background: #16a34a;" title="Comprar cotización">
                <ShoppingCart :size="20" />
                Comprar
              </button>
            </div>
          </div>
        </div>

        <!-- Paginación (si es necesaria después) -->
        <div v-if="cotizaciones.length > 0" style="text-align: center; margin-top: 3rem;">
          <a :href="getAppUrl('/catalogo')" class="btn btn-secondary">Crear nueva cotización</a>
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
import { ShoppingCart, User, FileText, Copy } from 'lucide-vue-next';
import { useApi } from '../composables/useApi';
const { apiFetch, getAppUrl } = useApi();

// Estados
const cotizaciones = ref([]);
const cargando = ref(true);
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

const cargarCotizaciones = async () => {
  try {
    const token = localStorage.getItem('token');
    if (!token) {
      window.location.href = getAppUrl('/login');
      return;
    }

    const response = await apiFetch(`/api/cotizaciones/usuario/${usuario.value.id}`);

    const data = await response.json();
    if (response.ok) {
      cotizaciones.value = data;
    } else {
      console.error('Error al cargar cotizaciones:', data.error);
    }
  } catch (error) {
    console.error('Error al cargar cotizaciones:', error);
  } finally {
    cargando.value = false;
  }
};

const verDetalles = async (cotizacionId) => {
  window.location.href = getAppUrl(`/cotizacion/${cotizacionId}`);
};

const descargarPDF = async (cotizacionId) => {
  try {
    const token = localStorage.getItem('token');
    if (!token) {
      alert('Debe iniciar sesión para descargar el PDF');
      return;
    }

    const response = await apiFetch(`/api/cotizaciones/${cotizacionId}/pdf`, {
      method: 'GET'
    });

    if (response.ok) {
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `cotizacion_${cotizacionId}_${new Date().toISOString().split('T')[0]}.pdf`;
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

const duplicarCotizacion = async (cotizacion) => {
  if (confirm('¿Duplicar esta cotización al carrito?')) {
    try {
      // Aquí necesitaríamos cargar los detalles de la cotización y agregarlos al carrito
      alert('Funcionalidad de duplicar próximamente disponible');
    } catch (error) {
      console.error('Error al duplicar cotización:', error);
      alert('Error al duplicar cotización');
    }
  }
};

const comprarCotizacion = async (cotizacion) => {
  try {
    const token = localStorage.getItem('token');
    if (!token) {
      alert('Debe iniciar sesión para realizar la compra');
      window.location.href = getAppUrl('/login');
      return;
    }

    // Obtener los detalles de la cotización
    const detailsResponse = await apiFetch(`/api/cotizaciones/${cotizacion.id}`);
    if (!detailsResponse.ok) {
      throw new Error('Error al obtener detalles de la cotización');
    }
    
    const cotizacionDetalle = await detailsResponse.json();
    
    // Obtener el stock actual de cada producto
    const carritoItems = await Promise.all(
      cotizacionDetalle.detalles.map(async (detalle) => {
        try {
          const productoResponse = await apiFetch(`/api/productos/${detalle.producto_id}`);
          const productoData = await productoResponse.json();
          
          return {
            producto_id: detalle.producto_id,
            nombre: detalle.producto || 'Producto',
            costo_unitario: parseFloat(detalle.costo_unitario),
            cantidad: detalle.cantidad,
            stock_maximo: productoData.data?.stock_actual || detalle.cantidad,
            imagen_url: productoData.data?.imagen_url || null
          };
        } catch (error) {
          console.error('Error al obtener producto:', error);
          return {
            producto_id: detalle.producto_id,
            nombre: detalle.producto || 'Producto',
            costo_unitario: parseFloat(detalle.costo_unitario),
            cantidad: detalle.cantidad,
            stock_maximo: detalle.cantidad,
            imagen_url: null
          };
        }
      })
    );

    // Guardar en localStorage
    localStorage.setItem('carrito', JSON.stringify(carritoItems));
    localStorage.setItem('cotizacion_id', cotizacion.id);

    // Redirigir a la página de pago
    window.location.href = getAppUrl('/pago-qr');
    
  } catch (error) {
    console.error('Error al comprar cotización:', error);
    alert('Error al procesar la compra: ' + error.message);
  }
};

// Registrar visita
const registrarVisita = async () => {
  try {
    const response = await apiFetch('/api/visitas/mis-cotizaciones', {
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
  
  // Cargar cotizaciones
  await cargarCotizaciones();

  // Escuchar cambios en el carrito desde otras pestañas
  window.addEventListener('storage', (e) => {
    if (e.key === 'carrito' && e.newValue) {
      carrito.value = JSON.parse(e.newValue);
    }
  });
});
</script>