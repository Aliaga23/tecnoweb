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
        <input type="range" v-model.number="fontSize" min="12" max="24" step="2" style="width: 100%;">
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
      <div class="navbar-content" style="display: flex; justify-content: space-between; align-items: center; width: 100%; padding: 0 2rem;">
        <a :href="getAppUrl('/')" class="navbar-logo">ELYTA</a>
        
        <ul class="navbar-menu" style="flex: 1; display: flex; justify-content: center;">
          <li><a :href="getAppUrl('/dashboard')" class="navbar-link">Dashboard</a></li>
          
          <li class="navbar-dropdown">
            <button @click="toggleDropdown('usuarios')" class="navbar-link dropdown-toggle">Gestión Usuarios</button>
            <div v-if="dropdownOpen === 'usuarios'" class="dropdown-menu">
              <a :href="getAppUrl('/dashboard/usuarios')" class="dropdown-item">Usuarios</a>
              <a :href="getAppUrl('/dashboard/proveedores')" class="dropdown-item">Proveedores</a>
            </div>
          </li>
          
          <li class="navbar-dropdown">
            <button @click="toggleDropdown('ventas')" class="navbar-link dropdown-toggle">Gestión Ventas</button>
            <div v-if="dropdownOpen === 'ventas'" class="dropdown-menu">
              <a :href="getAppUrl('/dashboard/productos')" class="dropdown-item">Productos</a>
              <a :href="getAppUrl('/dashboard/categorias')" class="dropdown-item">Categorías</a>
              <a :href="getAppUrl('/dashboard/transacciones')" class="dropdown-item">Transacciones</a>
              <a :href="getAppUrl('/dashboard/ventas-credito')" class="dropdown-item">Ventas al Crédito</a>
            </div>
          </li>
          
          <li class="navbar-dropdown">
            <button @click="toggleDropdown('cotizaciones')" class="navbar-link dropdown-toggle">Gestión Cotizaciones</button>
            <div v-if="dropdownOpen === 'cotizaciones'" class="dropdown-menu">
              <a :href="getAppUrl('/dashboard/cotizaciones')" class="dropdown-item">Cotizaciones</a>
            </div>
          </li>
          
          <li class="navbar-dropdown">
            <button @click="toggleDropdown('devoluciones')" class="navbar-link dropdown-toggle">Gestión Devoluciones</button>
            <div v-if="dropdownOpen === 'devoluciones'" class="dropdown-menu">
              <a :href="getAppUrl('/dashboard/devoluciones')" class="dropdown-item">Devoluciones Clientes</a>
              <a :href="getAppUrl('/dashboard/devoluciones-proveedor')" class="dropdown-item">Devoluciones Proveedores</a>
            </div>
          </li>
        </ul>

        <div class="navbar-controls">
          <span style="color: var(--color-text); margin-right: 1rem;">{{ usuario?.nombre }}</span>
          <div class="user-menu-container">
            <button @click="toggleUserMenu" class="navbar-icon" title="Mi cuenta">
              <User :size="24" />
            </button>
            <div v-if="userMenuOpen" class="user-menu">
              <button @click="cerrarSesion" class="user-menu-item">Cerrar sesión</button>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Content -->
    <section class="section" style="background-color: var(--color-bg);">
      <div style="padding: 0 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
          <h1 class="section-title" style="margin-bottom: 0;">Gestión de Cotizaciones</h1>
        </div>

        <!-- Tabla de Cotizaciones -->
        <div class="card" style="padding: 0; overflow: hidden;">
          <table style="width: 100%; border-collapse: collapse;">
            <thead style="background-color: var(--color-bg-alt);">
              <tr>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">ID</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Cliente</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Vendedor</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Fecha</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Total</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Estado</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cotizacion in cotizaciones" :key="cotizacion.id" style="border-top: 1px solid var(--color-border);">
                <td style="padding: 1rem; color: var(--color-text);">#{{ cotizacion.id }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ cotizacion.cliente_nombre }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ cotizacion.vendedor_nombre || 'N/A' }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ formatDate(cotizacion.fecha_cotizacion) }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">Bs {{ cotizacion.total }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ cotizacion.estado }}</td>
                <td style="padding: 1rem;">
                  <button @click="verDetalleCotizacion(cotizacion)" style="color: var(--color-primary); background: none; border: none; cursor: pointer;">Ver detalle</button>
                </td>
              </tr>
              <tr v-if="cotizaciones.length === 0">
                <td colspan="7" style="padding: 2rem; text-align: center; color: var(--color-text-light);">
                  No hay cotizaciones registradas
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- Modal Detalle Cotización -->
    <div v-if="modalDetalleAbierto" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem;">
      <div class="card" style="width: 100%; max-width: 600px; max-height: fit-content; overflow: visible;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.75rem;">
          <h2 style="font-size: 1.25rem; font-weight: bold; color: var(--color-text); margin: 0;">
            Cotización #{{ cotizacionSeleccionada?.id }}
          </h2>
          <button @click="cerrarModal" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--color-text);">&times;</button>
        </div>

        <div v-if="cotizacionSeleccionada">
          <!-- Información básica -->
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; margin-bottom: 1rem; padding: 0.75rem; background-color: var(--color-bg-alt); border-radius: 0.5rem;">
            <div>
              <strong style="color: var(--color-text);">Cliente:</strong>
              <span style="color: var(--color-text-light); margin-left: 0.5rem;">{{ cotizacionSeleccionada.cliente_nombre }}</span>
            </div>
            <div>
              <strong style="color: var(--color-text);">Vendedor:</strong>
              <span style="color: var(--color-text-light); margin-left: 0.5rem;">{{ cotizacionSeleccionada.vendedor_nombre || 'N/A' }}</span>
            </div>
            <div>
              <strong style="color: var(--color-text);">Fecha:</strong>
              <span style="color: var(--color-text-light); margin-left: 0.5rem;">{{ formatDate(cotizacionSeleccionada.fecha_cotizacion) }}</span>
            </div>
            <div>
              <strong style="color: var(--color-text);">Total:</strong>
              <span style="color: var(--color-text-light); margin-left: 0.5rem;">Bs {{ cotizacionSeleccionada.total }}</span>
            </div>
            <div>
              <strong style="color: var(--color-text);">Estado:</strong>
              <span style="color: var(--color-text-light); margin-left: 0.5rem;">{{ cotizacionSeleccionada.estado }}</span>
            </div>
            <div>
              <strong style="color: var(--color-text);">Total productos:</strong>
              <span style="color: var(--color-text-light); margin-left: 0.5rem;">{{ cotizacionSeleccionada.productos?.length || 0 }}</span>
            </div>
          </div>

          <!-- Detalles de productos -->
          <div v-if="cotizacionSeleccionada.productos && cotizacionSeleccionada.productos.length > 0" style="margin-bottom: 1rem;">
            <strong style="color: var(--color-text); display: block; margin-bottom: 0.5rem;">Productos cotizados:</strong>
            <div style="background-color: var(--color-bg-alt); border-radius: 0.5rem; overflow: hidden;">
              <table style="width: 100%; border-collapse: collapse;">
                <thead style="background-color: var(--color-border);">
                  <tr>
                    <th style="padding: 0.5rem; text-align: left; font-size: 0.875rem; color: var(--color-text);">Producto</th>
                    <th style="padding: 0.5rem; text-align: center; font-size: 0.875rem; color: var(--color-text);">Cantidad</th>
                    <th style="padding: 0.5rem; text-align: right; font-size: 0.875rem; color: var(--color-text);">Precio</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="producto in cotizacionSeleccionada.productos" :key="producto.id">
                    <td style="padding: 0.5rem; color: var(--color-text-light); font-size: 0.875rem;">{{ producto.producto_nombre }}</td>
                    <td style="padding: 0.5rem; text-align: center; color: var(--color-text-light); font-size: 0.875rem;">{{ producto.cantidad }}</td>
                    <td style="padding: 0.5rem; text-align: right; color: var(--color-text-light); font-size: 0.875rem;">
                      Bs. {{ producto.costo_unitario }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div style="display: flex; justify-content: flex-end; border-top: 1px solid var(--color-border); padding-top: 0.75rem; margin-top: 1rem;">
          <button @click="cerrarModal" class="btn btn-secondary">Cerrar</button>
        </div>
      </div>
    </div>

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
import { ref, computed, watch, onMounted } from 'vue';
import { User } from 'lucide-vue-next';
import { useApi } from '../composables/useApi.js';

// Configuración de accesibilidad
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
  if (!autoNightMode.value) return manualNightMode.value;
  const hora = new Date().getHours();
  return hora >= 19 || hora < 7;
});

const toggleNightMode = () => {
  if (autoNightMode.value) {
    autoNightMode.value = false;
    manualNightMode.value = !isNightMode.value;
  } else {
    manualNightMode.value = !manualNightMode.value;
  }
};

const toggleAccessibilityPanel = () => {
  accessibilityPanelOpen.value = !accessibilityPanelOpen.value;
};

watch(currentTheme, (val) => localStorage.setItem('theme', val));
watch(fontSize, (val) => localStorage.setItem('fontSize', val.toString()));
watch(highContrast, (val) => localStorage.setItem('highContrast', val));
watch(manualNightMode, (val) => localStorage.setItem('manualNightMode', val));
watch(autoNightMode, (val) => localStorage.setItem('autoNightMode', val));

// Contador de visitas
const contadorVisitas = ref(0);

// Usuario y autenticación
const { apiFetch, getAppUrl } = useApi();
const usuario = ref(null);
const userMenuOpen = ref(false);

const usuarioData = localStorage.getItem('user');
if (usuarioData) {
  try {
    usuario.value = JSON.parse(usuarioData);
  } catch (error) {
    window.location.href = getAppUrl('/login');
  }
} else {
  window.location.href = getAppUrl('/login');
}

const toggleUserMenu = () => {
  userMenuOpen.value = !userMenuOpen.value;
};

const cerrarSesion = () => {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  window.location.href = getAppUrl('/');
};

// Dropdowns
const dropdownOpen = ref(null);

const toggleDropdown = (menu) => {
  dropdownOpen.value = dropdownOpen.value === menu ? null : menu;
};

// Estado del dashboard
const cotizaciones = ref([]);

// Modal
const modalDetalleAbierto = ref(false);
const cotizacionSeleccionada = ref(null);

const verDetalleCotizacion = async (cotizacion) => {
  try {
    // Cargar los detalles completos desde la API
    const response = await apiFetch(`/api/cotizaciones/${cotizacion.id}/detalle`);
    const data = await response.json();
    
    // El backend puede devolver {success, data} o directamente los datos
    if (data.success && data.data) {
      cotizacionSeleccionada.value = data.data;
    } else if (!data.error) {
      // Si no hay error y no tiene estructura success/data, usar los datos directamente
      cotizacionSeleccionada.value = data;
    } else {
      // Fallback con los datos básicos si falla la API
      cotizacionSeleccionada.value = { ...cotizacion, productos: [] };
    }
  } catch (error) {
    console.error('Error al cargar detalles:', error);
    // Fallback con los datos básicos en caso de error
    cotizacionSeleccionada.value = { ...cotizacion, productos: [] };
  }
  
  modalDetalleAbierto.value = true;
};

const cerrarModal = () => {
  modalDetalleAbierto.value = false;
  cotizacionSeleccionada.value = null;
};

// Funciones utilitarias
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  });
};

// Cargar datos
const cargarCotizaciones = async () => {
  try {
    const response = await apiFetch('/api/cotizaciones');
    const data = await response.json();
    // El backend devuelve directamente el array, no {success, data}
    if (Array.isArray(data)) {
      cotizaciones.value = data;
    } else {
      console.error('Error al cargar cotizaciones:', data.message || data.error);
      cotizaciones.value = [];
    }
  } catch (error) {
    console.error('Error al cargar cotizaciones:', error);
    cotizaciones.value = [];
  }
};

const registrarVisita = async () => {
  try {
    const response = await apiFetch('/api/visitas/dashboard-cotizaciones', {
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

onMounted(async () => {
  // Verificar rol del usuario
  const userData = localStorage.getItem('user');
  if (userData) {
    try {
      const user = JSON.parse(userData);
      if (user.rol !== 'Propietario' && user.rol !== 'Vendedor') {
        window.location.href = getAppUrl('/');
        return;
      }
    } catch (error) {
      console.error('Error al verificar usuario:', error);
      window.location.href = getAppUrl('/');
      return;
    }
  } else {
    window.location.href = getAppUrl('/login');
    return;
  }
  
  await registrarVisita();
  cargarCotizaciones();
});
</script>

<style scoped>
.app-wrapper {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.app-wrapper > section {
  flex: 1;
}

.card-hover:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

table th,
table td {
  border-bottom: 1px solid var(--color-border);
}

table tbody tr:last-child td {
  border-bottom: none;
}

.navbar-dropdown {
  position: relative;
}

.dropdown-toggle {
  color: #000000;
  text-decoration: none;
  font-weight: 500;
  transition: var(--transition);
  font-size: var(--font-size-base);
  background: none;
  border: none;
  cursor: pointer;
}

.theme-night .dropdown-toggle {
  color: var(--color-accent);
}

.dropdown-toggle:hover {
  color: var(--color-primary);
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  background: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-lg);
  min-width: 200px;
  z-index: 1000;
  margin-top: 0.5rem;
}

.dropdown-item {
  display: block;
  padding: 0.75rem 1rem;
  color: var(--color-text);
  text-decoration: none;
  transition: var(--transition);
  font-size: var(--font-size-base);
}

.dropdown-item:hover {
  background-color: var(--color-bg-alt);
  color: var(--color-primary);
}
</style>
