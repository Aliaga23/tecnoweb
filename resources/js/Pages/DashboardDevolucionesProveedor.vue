<template>
  <div :class="['app-wrapper', currentTheme, fontSizeClass, { 'high-contrast': highContrast, 'theme-night': isNightMode }]">
    <button class="accessibility-toggle" @click="toggleAccessibilityPanel">
      Accesibilidad
    </button>

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
              <a :href="getAppUrl('/dashboard/ventas-contado')" class="dropdown-item">Ventas al Contado</a>
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

    <section class="section" style="background-color: var(--color-bg);">
      <div style="padding: 0 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
          <h1 class="section-title" style="margin-bottom: 0;">Gestión de Devoluciones a Proveedores</h1>
          <button @click="abrirModalCrear" class="btn btn-primary">Nueva Devolución Proveedor</button>
        </div>

        <div class="card" style="padding: 0; overflow: hidden;">
          <table style="width: 100%; border-collapse: collapse;">
            <thead style="background-color: var(--color-bg-alt);">
              <tr>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">ID</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Proveedor</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Usuario</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Productos</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Fecha</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="devolucion in devoluciones" :key="devolucion.id" style="border-top: 1px solid var(--color-border);">
                <td style="padding: 1rem; color: var(--color-text);">#{{ devolucion.id }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ devolucion.proveedor_nombre }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ devolucion.usuario_nombre }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ devolucion.total_productos || 0 }} producto(s)</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ formatDate(devolucion.fecha_devolucion) }}</td>
                <td style="padding: 1rem;">
                  <button @click="verDetalle(devolucion)" style="color: var(--color-primary); background: none; border: none; cursor: pointer;">Ver detalle</button>
                </td>
              </tr>
              <tr v-if="devoluciones.length === 0">
                <td colspan="6" style="padding: 2rem; text-align: center; color: var(--color-text-light);">
                  No hay devoluciones a proveedores registradas
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- Modal Detalle -->
    <div v-if="modalDetalleAbierto" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem;">
      <div class="card" style="width: 100%; max-width: 600px; max-height: fit-content; overflow: visible;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.75rem;">
          <h2 style="font-size: 1.25rem; font-weight: bold; color: var(--color-text); margin: 0;">
            Devolución #{{ devolucionSeleccionada?.id }}
          </h2>
          <button @click="cerrarModal" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--color-text);">&times;</button>
        </div>

        <div v-if="devolucionSeleccionada">
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; margin-bottom: 1rem; padding: 0.75rem; background-color: var(--color-bg-alt); border-radius: 0.5rem;">
            <div>
              <strong style="color: var(--color-text);">Proveedor:</strong>
              <span style="color: var(--color-text-light); margin-left: 0.5rem;">{{ devolucionSeleccionada.proveedor_nombre }}</span>
            </div>
            <div>
              <strong style="color: var(--color-text);">Fecha:</strong>
              <span style="color: var(--color-text-light); margin-left: 0.5rem;">{{ formatDate(devolucionSeleccionada.fecha_devolucion) }}</span>
            </div>
            <div>
              <strong style="color: var(--color-text);">Usuario responsable:</strong>
              <span style="color: var(--color-text-light); margin-left: 0.5rem;">{{ devolucionSeleccionada.usuario_nombre }}</span>
            </div>
            <div>
              <strong style="color: var(--color-text);">Total productos:</strong>
              <span style="color: var(--color-text-light); margin-left: 0.5rem;">{{ devolucionSeleccionada.detalles?.length || 0 }}</span>
            </div>
          </div>

          <div style="margin-bottom: 1rem;">
            <strong style="color: var(--color-text); display: block; margin-bottom: 0.5rem;">Observación:</strong>
            <div style="padding: 0.75rem; background-color: var(--color-bg-alt); border-radius: 0.5rem; color: var(--color-text-light);">
              {{ devolucionSeleccionada.observacion }}
            </div>
          </div>

          <div v-if="devolucionSeleccionada.detalles && devolucionSeleccionada.detalles.length > 0" style="margin-bottom: 1rem;">
            <strong style="color: var(--color-text); display: block; margin-bottom: 0.5rem;">Productos devueltos:</strong>
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
                  <tr v-for="detalle in devolucionSeleccionada.detalles" :key="detalle.producto_id">
                    <td style="padding: 0.5rem; color: var(--color-text-light); font-size: 0.875rem;">{{ detalle.producto_nombre }}</td>
                    <td style="padding: 0.5rem; text-align: center; color: var(--color-text-light); font-size: 0.875rem;">{{ detalle.cantidad }}</td>
                    <td style="padding: 0.5rem; text-align: right; color: var(--color-text-light); font-size: 0.875rem;">
                      Bs. {{ detalle.precio_unitario || '0.00' }}
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

    <!-- Modal Crear Devolución -->
    <div v-if="modalCrearAbierto" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem;">
      <div class="card" style="width: 100%; max-width: 800px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.75rem;">
          <h2 style="font-size: 1.25rem; font-weight: bold; color: var(--color-text); margin: 0;">
            Nueva Devolución a Proveedor
          </h2>
          <button @click="cerrarModalCrear" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--color-text);">&times;</button>
        </div>

        <!-- Paso 1: Seleccionar proveedor -->
        <div v-if="pasoActual === 1" style="margin-bottom: 1rem;">
          <h3 style="color: var(--color-text); margin-bottom: 1rem;">Seleccionar Proveedor</h3>
          <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: var(--color-text); font-weight: 600;">Proveedor:</label>
            <select v-model="proveedorSeleccionado" style="width: 100%; padding: 0.5rem; border: 1px solid var(--color-border); border-radius: 0.25rem;">
              <option value="">Seleccione un proveedor</option>
              <option v-for="proveedor in proveedores" :key="proveedor.id" :value="proveedor">{{ proveedor.nombre }}</option>
            </select>
          </div>

          <div v-if="proveedorSeleccionado" style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: var(--color-text); font-weight: 600;">Observación:</label>
            <textarea 
              v-model="observacion"
              placeholder="Describa el motivo de la devolución al proveedor"
              style="width: 100%; padding: 0.5rem; border: 1px solid var(--color-border); border-radius: 0.25rem; min-height: 80px; resize: vertical;"
              required
            ></textarea>
          </div>

          <div v-if="proveedorSeleccionado && observacion" style="display: flex; justify-content: flex-end; margin-top: 1rem;">
            <button @click="pasoActual = 2" class="btn btn-primary">Continuar</button>
          </div>
        </div>

        <!-- Paso 2: Seleccionar productos -->
        <div v-if="pasoActual === 2" style="margin-bottom: 1rem;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="color: var(--color-text); margin: 0;">Productos Disponibles para Devolver</h3>
            <button @click="pasoActual = 1" class="btn btn-secondary" style="padding: 0.25rem 0.5rem;">Volver</button>
          </div>

          <div style="background-color: var(--color-bg-alt); padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem;">
            <p style="margin: 0; color: var(--color-text);"><strong>Proveedor:</strong> {{ proveedorSeleccionado?.nombre }}</p>
            <p style="margin: 0; color: var(--color-text);"><strong>Observación:</strong> {{ observacion }}</p>
          </div>

          <div v-if="productosDisponibles.length > 0">
            <h4 style="color: var(--color-text); margin-bottom: 0.5rem;">Seleccionar productos a devolver</h4>
            <div style="background-color: var(--color-bg-alt); border-radius: 0.5rem; overflow: hidden;">
              <table style="width: 100%; border-collapse: collapse;">
                <thead style="background-color: var(--color-border);">
                  <tr>
                    <th style="padding: 0.75rem; text-align: left; font-size: 0.875rem; color: var(--color-text);">Producto</th>
                    <th style="padding: 0.75rem; text-align: center; font-size: 0.875rem; color: var(--color-text);">Stock Actual</th>
                    <th style="padding: 0.75rem; text-align: center; font-size: 0.875rem; color: var(--color-text);">A devolver</th>
                    <th style="padding: 0.75rem; text-align: right; font-size: 0.875rem; color: var(--color-text);">Precio</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="producto in productosDisponibles" :key="producto.id">
                    <td style="padding: 0.75rem; color: var(--color-text-light); font-size: 0.875rem;">{{ producto.nombre }}</td>
                    <td style="padding: 0.75rem; text-align: center; color: var(--color-text-light); font-size: 0.875rem;">{{ producto.stock_actual }}</td>
                    <td style="padding: 0.75rem; text-align: center;">
                      <input 
                        v-model.number="producto.cantidad_devolver"
                        type="number" 
                        :min="0" 
                        :max="producto.stock_actual"
                        style="width: 80px; padding: 0.25rem; border: 1px solid var(--color-border); border-radius: 0.25rem; text-align: center;"
                      >
                    </td>
                    <td style="padding: 0.75rem; text-align: right; color: var(--color-text-light); font-size: 0.875rem;">Bs. {{ producto.precio }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div style="display: flex; justify-content: space-between; margin-top: 1rem;">
            <button @click="cerrarModalCrear" class="btn btn-secondary">Cancelar</button>
            <button @click="crearDevolucion" class="btn btn-primary" :disabled="procesandoDevolucion">
              {{ procesandoDevolucion ? 'Creando...' : 'Crear Devolución' }}
            </button>
          </div>
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

const { apiFetch, getAppUrl } = useApi();

// Verificar rol del usuario INMEDIATAMENTE
const userDataCheck = localStorage.getItem('user');
if (userDataCheck) {
  try {
    const user = JSON.parse(userDataCheck);
    if (user.rol !== 'Propietario' && user.rol !== 'Vendedor') {
      window.location.href = getAppUrl('/');
    }
  } catch (error) {
    console.error('Error al verificar usuario:', error);
    window.location.href = getAppUrl('/');
  }
} else {
  window.location.href = getAppUrl('/login');
}

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

const dropdownOpen = ref(null);

const toggleDropdown = (menu) => {
  dropdownOpen.value = dropdownOpen.value === menu ? null : menu;
};

const devoluciones = ref([]);
const modalDetalleAbierto = ref(false);
const devolucionSeleccionada = ref(null);
const modalCrearAbierto = ref(false);
const pasoActual = ref(1);
const proveedores = ref([]);
const proveedorSeleccionado = ref(null);
const observacion = ref('');
const productosDisponibles = ref([]);
const procesandoDevolucion = ref(false);

const cargarDevoluciones = async () => {
  try {
    const response = await apiFetch('/api/devoluciones-proveedor');
    const data = await response.json();
    if (data.success) {
      devoluciones.value = data.data || [];
    } else {
      console.error('Error al cargar devoluciones:', data.message);
      devoluciones.value = [];
    }
  } catch (error) {
    console.error('Error al cargar devoluciones:', error);
    devoluciones.value = [];
  }
};

const verDetalle = async (devolucion) => {
  try {
    const response = await apiFetch(`/api/devoluciones-proveedor/${devolucion.id}/detalle`);
    const data = await response.json();
    if (data.success) {
      devolucionSeleccionada.value = data.data;
      modalDetalleAbierto.value = true;
    }
  } catch (error) {
    console.error('Error al cargar detalle:', error);
  }
};

const cerrarModal = () => {
  modalDetalleAbierto.value = false;
  devolucionSeleccionada.value = null;
};

const abrirModalCrear = async () => {
  try {
    // Cargar proveedores y productos en paralelo
    const [proveedoresResponse, productosResponse] = await Promise.all([
      apiFetch('/api/proveedores'),
      apiFetch('/api/productos-disponibles')
    ]);
    
    const proveedoresData = await proveedoresResponse.json();
    const productosData = await productosResponse.json();
    
    if (proveedoresData.success && productosData.success) {
      proveedores.value = proveedoresData.data || [];
      productosDisponibles.value = productosData.data.map(producto => ({
        ...producto,
        cantidad_devolver: 0
      })) || [];
      modalCrearAbierto.value = true;
    }
  } catch (error) {
    console.error('Error al cargar datos:', error);
  }
};

const cerrarModalCrear = () => {
  modalCrearAbierto.value = false;
  pasoActual.value = 1;
  proveedorSeleccionado.value = null;
  observacion.value = '';
  productosDisponibles.value = [];
};



const crearDevolucion = async () => {
  if (!observacion.value.trim()) return;
  
  const productosADevolver = productosDisponibles.value.filter(p => p.cantidad_devolver > 0);
  if (productosADevolver.length === 0) {
    alert('Debe seleccionar al menos un producto para devolver');
    return;
  }
  
  procesandoDevolucion.value = true;
  
  try {
    const response = await apiFetch('/api/devoluciones-proveedor', {
      method: 'POST',
      body: JSON.stringify({
        proveedor_id: proveedorSeleccionado.value.id,
        observacion: observacion.value,
        productos: productosADevolver.map(p => ({
          producto_id: p.id,
          cantidad: p.cantidad_devolver
        }))
      })
    });
    
    const data = await response.json();
    if (data.success) {
      alert('Devolución a proveedor creada exitosamente');
      cerrarModalCrear();
      await cargarDevoluciones();
    } else {
      alert('Error al crear la devolución: ' + data.message);
    }
  } catch (error) {
    console.error('Error al crear devolución:', error);
    alert('Error al crear la devolución');
  } finally {
    procesandoDevolucion.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  });
};

const registrarVisita = async () => {
  try {
    const response = await apiFetch('/api/visitas/dashboard-devoluciones-proveedor', {
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
  await registrarVisita();
  cargarDevoluciones();
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