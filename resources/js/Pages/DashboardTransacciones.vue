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
          <h1 class="section-title" style="margin-bottom: 0;">Gestión de Transacciones</h1>
          <div style="display: flex; gap: 1rem;">
            <button @click="seccionActiva = 'ventas'" :class="['btn', seccionActiva === 'ventas' ? 'btn-primary' : 'btn-secondary']">
              Ventas
            </button>
            <button @click="seccionActiva = 'pagos'" :class="['btn', seccionActiva === 'pagos' ? 'btn-primary' : 'btn-secondary']">
              Pagos
            </button>
          </div>
        </div>

        <!-- Sección Ventas -->
        <div v-if="seccionActiva === 'ventas'" class="card" style="padding: 0; overflow: hidden;">
          <table style="width: 100%; border-collapse: collapse;">
            <thead style="background-color: var(--color-bg-alt);">
              <tr>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">ID</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Cliente</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Productos</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Total</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Estado</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Fecha</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="venta in ventas" :key="venta.id" style="border-top: 1px solid var(--color-border);">
                <td style="padding: 1rem; color: var(--color-text);">#{{ venta.id }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ venta.cliente_nombre }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ venta.cantidad_productos }} producto(s)</td>
                <td style="padding: 1rem; color: var(--color-text-light);">Bs. {{ venta.total }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ venta.estado }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ formatDate(venta.fecha_venta) }}</td>
                <td style="padding: 1rem;">
                  <button @click="verDetalleVenta(venta)" style="color: var(--color-primary); background: none; border: none; cursor: pointer;">Ver detalle</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Sección Pagos -->
        <div v-if="seccionActiva === 'pagos'" class="card" style="padding: 0; overflow: hidden;">
          <table style="width: 100%; border-collapse: collapse;">
            <thead style="background-color: var(--color-bg-alt);">
              <tr>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">ID</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Cliente</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Monto</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Método</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Fecha</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Venta</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="pago in pagos" :key="pago.id" style="border-top: 1px solid var(--color-border);">
                <td style="padding: 1rem; color: var(--color-text);">#{{ pago.id }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ pago.cliente_nombre }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">Bs. {{ pago.monto }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ pago.metodo_pago }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ formatDate(pago.fecha_pago) }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">#{{ pago.venta_id || 'N/A' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- Modal Detalle Venta -->
    <div v-if="modalDetalleAbierto" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem;">
      <div class="card" style="width: 100%; max-width: 600px; max-height: fit-content; overflow: visible;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.75rem;">
          <h2 style="font-size: 1.25rem; font-weight: bold; color: var(--color-text); margin: 0;">
            Venta #{{ ventaSeleccionada?.id }}
          </h2>
          <button @click="cerrarModal" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--color-text);">&times;</button>
        </div>

        <div v-if="ventaSeleccionada">
          <!-- Información básica -->
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; margin-bottom: 1rem; padding: 0.75rem; background-color: var(--color-bg-alt); border-radius: 0.5rem;">
            <div>
              <strong style="color: var(--color-text);">Cliente:</strong>
              <span style="color: var(--color-text-light); margin-left: 0.5rem;">{{ ventaSeleccionada.cliente_nombre }}</span>
            </div>
            <div>
              <strong style="color: var(--color-text);">Fecha:</strong>
              <span style="color: var(--color-text-light); margin-left: 0.5rem;">{{ formatDate(ventaSeleccionada.fecha_venta) }}</span>
            </div>
            <div>
              <strong style="color: var(--color-text);">Estado:</strong>
              <span :style="{
                padding: '0.125rem 0.5rem',
                borderRadius: '0.25rem',
                marginLeft: '0.5rem',
                backgroundColor: ventaSeleccionada.estado === 'completada' ? 'var(--color-text)' : 
                               ventaSeleccionada.estado === 'pendiente' ? 'var(--color-text-light)' : 'var(--color-error)',
                color: 'white'
              }">
                {{ ventaSeleccionada.estado }}
              </span>
            </div>
            <div>
              <strong style="color: var(--color-text);">Total:</strong>
              <span style="color: var(--color-text); margin-left: 0.5rem; font-weight: bold;">
                Bs. {{ ventaSeleccionada.total }}
              </span>
            </div>
          </div>

          <!-- Productos -->
          <div>
            <div style="font-weight: 600; color: var(--color-text); margin-bottom: 0.5rem;">
              Productos
            </div>
            
            <div v-if="ventaSeleccionada.items && ventaSeleccionada.items.length > 0" class="card" style="padding: 0; overflow: hidden;">
              <table style="width: 100%; border-collapse: collapse;">
                <thead style="background-color: var(--color-bg-alt);">
                  <tr>
                    <th style="padding: 0.5rem; text-align: left; font-weight: 600; color: var(--color-text); border-bottom: 1px solid var(--color-border);">Producto</th>
                    <th style="padding: 0.5rem; text-align: center; font-weight: 600; color: var(--color-text); border-bottom: 1px solid var(--color-border);">Cant.</th>
                    <th style="padding: 0.5rem; text-align: right; font-weight: 600; color: var(--color-text); border-bottom: 1px solid var(--color-border);">P. Unit.</th>
                    <th style="padding: 0.5rem; text-align: right; font-weight: 600; color: var(--color-text); border-bottom: 1px solid var(--color-border);">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in ventaSeleccionada.items" :key="item.id" style="border-bottom: 1px solid var(--color-border);">
                    <td style="padding: 0.5rem; font-weight: 500; color: var(--color-text);">{{ item.producto_nombre }}</td>
                    <td style="padding: 0.5rem; text-align: center; color: var(--color-text);">{{ item.cantidad }}</td>
                    <td style="padding: 0.5rem; text-align: right; color: var(--color-text-light);">Bs. {{ Number(item.precio_unitario).toFixed(2) }}</td>
                    <td style="padding: 0.5rem; text-align: right; font-weight: 600; color: var(--color-text);">Bs. {{ Number(item.subtotal).toFixed(2) }}</td>
                  </tr>
                </tbody>
                <tfoot style="background-color: var(--color-bg-alt);">
                  <tr>
                    <td colspan="3" style="padding: 0.5rem; text-align: right; font-weight: 600; color: var(--color-text); border-top: 1px solid var(--color-border);">
                      Total:
                    </td>
                    <td style="padding: 0.5rem; text-align: right; font-weight: bold; color: var(--color-text); border-top: 1px solid var(--color-border);">
                      Bs. {{ Number(ventaSeleccionada.total).toFixed(2) }}
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
            
            <div v-else class="card" style="text-align: center; padding: 1rem; color: var(--color-text-light);">
              Sin productos
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
import { User, Sun, Moon } from 'lucide-vue-next';
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

// Datos de transacciones
const ventas = ref([]);
const pagos = ref([]);
const seccionActiva = ref('ventas');
const modalDetalleAbierto = ref(false);
const ventaSeleccionada = ref(null);

// Cargar transacciones usando la API unificada
const cargarTransacciones = async () => {
  try {
    const token = localStorage.getItem('token');
    const response = await apiFetch('/api/transacciones');

    const result = await response.json();
    
    if (result.success) {
      ventas.value = result.data.ventas;
      pagos.value = result.data.pagos;
    } else {
      console.error('Error en la respuesta:', result.message);
    }
  } catch (error) {
    console.error('Error al cargar transacciones:', error);
  }
};

// Ver detalle de venta
const verDetalleVenta = async (venta) => {
  try {
    const token = localStorage.getItem('token');
    const response = await apiFetch(`/api/transacciones/venta/${venta.id}`);

    const result = await response.json();
    
    if (result.success) {
      ventaSeleccionada.value = result.data;
      modalDetalleAbierto.value = true;
    } else {
      console.error('Error en la respuesta:', result.message);
    }
  } catch (error) {
    console.error('Error al cargar detalle de venta:', error);
  }
};

const cerrarModal = () => {
  modalDetalleAbierto.value = false;
  ventaSeleccionada.value = null;
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  try {
    return new Date(dateString).toLocaleDateString('es-ES');
  } catch {
    return dateString;
  }
};

const registrarVisita = async () => {
  try {
    const response = await apiFetch('/api/visitas/dashboard-transacciones', {
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
  await cargarTransacciones();
  
  // Cerrar menús al hacer clic fuera
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.navbar-dropdown') && !e.target.closest('.user-menu-container')) {
      dropdownOpen.value = null;
      userMenuOpen.value = false;
    }
  });
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