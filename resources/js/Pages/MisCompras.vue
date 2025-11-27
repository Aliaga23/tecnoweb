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

    <!-- Mis Compras -->
    <section class="section" style="background-color: var(--color-bg-alt); min-height: 80vh; padding: 4rem 0;">
      <div class="container" style="max-width: 1400px;">
        <div style="margin-bottom: 3rem;">
          <h2 class="section-title" style="margin-bottom: 0.5rem;">Mis <span class="highlight">Compras</span></h2>
          <p class="section-subtitle" style="margin: 0;">Historial de todas tus compras</p>
        </div>

        <!-- Filtros -->
        <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
          <select v-model="filtroTipo" class="form-input" style="width: auto; min-width: 200px;">
            <option value="">Todos los tipos</option>
            <option value="contado">Contado</option>
            <option value="credito">Crédito</option>
          </select>
          <select v-model="filtroEstado" class="form-input" style="width: auto; min-width: 200px;">
            <option value="">Todos los estados</option>
            <option value="pendiente">Pendiente</option>
            <option value="pagada">Pagada</option>
          </select>
        </div>

        <!-- Loading -->
        <div v-if="cargando" class="loading-container">
          <div class="loading"></div>
        </div>

        <!-- Lista de Compras -->
        <div v-else-if="comprasFiltradas.length > 0" style="display: flex; flex-direction: column; gap: 1.5rem;">
          <div v-for="compra in comprasFiltradas" :key="compra.id" class="card" style="padding: 1.5rem;">
            <!-- Encabezado de la compra -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 2px solid var(--color-border);">
              <div>
                <h3 style="font-size: 1.25rem; color: var(--color-primary); margin: 0 0 0.5rem 0;">
                  Compra #{{ compra.id }}
                </h3>
                <p style="margin: 0; color: var(--color-text); font-size: 0.9rem;">
                  <strong>Fecha:</strong> {{ formatearFecha(compra.fecha_venta) }}
                </p>
                <p style="margin: 0.25rem 0 0 0; color: var(--color-text); font-size: 0.9rem;">
                  <strong>Tipo:</strong> {{ compra.tipo === 'contado' ? 'Contado' : 'Crédito' }}
                </p>
              </div>
              <div style="text-align: right;">
                <span :class="['badge', `badge-${compra.estado}`]">
                  {{ compra.estado === 'pendiente' ? 'Pendiente' : 'Pagada' }}
                </span>
                <p style="font-size: 1.5rem; font-weight: bold; color: var(--color-primary); margin: 0.5rem 0 0 0;">
                  Bs. {{ parseFloat(compra.total).toFixed(2) }}
                </p>
              </div>
            </div>

            <!-- Detalles de productos -->
            <div style="margin-bottom: 1rem;">
              <h4 style="font-size: 1rem; color: var(--color-text); margin: 0 0 1rem 0; font-weight: 600;">
                Productos comprados:
              </h4>
              <div v-if="compra.detalles && compra.detalles.length > 0" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div v-for="detalle in compra.detalles" :key="detalle.id" 
                     style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: var(--color-bg); border-radius: 8px; border: 1px solid var(--color-border);">
                  <div style="flex: 1;">
                    <p style="margin: 0; font-weight: 600; color: var(--color-text);">
                      {{ detalle.producto ? detalle.producto.nombre : 'Producto eliminado' }}
                    </p>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem; color: var(--color-text-light);">
                      Cantidad: {{ detalle.cantidad }} x Bs. {{ parseFloat(detalle.precio_unitario).toFixed(2) }}
                    </p>
                  </div>
                  <div style="text-align: right;">
                    <p style="margin: 0; font-weight: 600; color: var(--color-primary); font-size: 1.1rem;">
                      Bs. {{ parseFloat(detalle.subtotal).toFixed(2) }}
                    </p>
                  </div>
                </div>
              </div>
              <div v-else style="padding: 1rem; text-align: center; color: var(--color-text-light); background: var(--color-bg); border-radius: 8px;">
                No hay detalles disponibles
              </div>
            </div>

            <!-- Información del pago -->
            <div v-if="compra.pago || (compra.pagos && compra.pagos.length > 0)" style="padding: 1rem; background: var(--color-bg); border-radius: 8px; border: 1px solid var(--color-border); margin-bottom: 1rem;">
              <h4 style="font-size: 1rem; color: var(--color-text); margin: 0 0 0.75rem 0; font-weight: 600;">
                {{ compra.tipo === 'credito' ? 'Historial de pagos:' : 'Información del pago:' }}
              </h4>
              
              <!-- Si es crédito, mostrar todos los pagos -->
              <div v-if="compra.tipo === 'credito' && compra.pagos && compra.pagos.length > 0">
                <div v-for="pago in compra.pagos" :key="pago.id" style="padding: 0.75rem; background: var(--color-bg-alt); border-radius: 6px; margin-bottom: 0.5rem; border: 1px solid var(--color-border);">
                  <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem;">
                    <p style="margin: 0; color: var(--color-text); font-size: 0.9rem;">
                      <strong>Monto:</strong> Bs. {{ parseFloat(pago.monto).toFixed(2) }}
                    </p>
                    <p style="margin: 0; color: var(--color-text); font-size: 0.9rem;">
                      <strong>Método:</strong> {{ pago.metodo.toUpperCase() }}
                    </p>
                    <p style="margin: 0; color: var(--color-text); font-size: 0.9rem;">
                      <strong>Fecha:</strong> {{ formatearFecha(pago.fecha_pago) }}
                    </p>
                  </div>
                </div>
                <div style="padding: 0.75rem; background: var(--color-primary); color: white; border-radius: 6px; margin-top: 0.75rem; text-align: center; font-weight: 600;">
                  Saldo pendiente: Bs. {{ calcularSaldoPendiente(compra) }}
                </div>
              </div>
              
              <!-- Si es contado, mostrar pago único -->
              <div v-else-if="compra.pago" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem;">
                <p style="margin: 0; color: var(--color-text); font-size: 0.9rem;">
                  <strong>Método:</strong> {{ compra.pago.metodo.toUpperCase() }}
                </p>
                <p style="margin: 0; color: var(--color-text); font-size: 0.9rem;">
                  <strong>Monto:</strong> Bs. {{ parseFloat(compra.pago.monto).toFixed(2) }}
                </p>
                <p style="margin: 0; color: var(--color-text); font-size: 0.9rem;">
                  <strong>Fecha:</strong> {{ formatearFecha(compra.pago.fecha_pago) }}
                </p>
                <p v-if="compra.pago.transaccion_id" style="margin: 0; color: var(--color-text); font-size: 0.9rem;">
                  <strong>Transacción:</strong> {{ compra.pago.transaccion_id }}
                </p>
              </div>
            </div>

            <!-- Botón para pagar si está pendiente -->
            <div v-if="compra.estado === 'pendiente'" style="display: flex; justify-content: flex-end; gap: 1rem;">
              <button v-if="compra.tipo === 'contado'" @click="pagarCompra(compra)" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                Pagar ahora
              </button>
              <button v-else @click="abrirModalPagoCredito(compra)" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                Registrar pago
              </button>
            </div>
          </div>
        </div>

        <!-- Sin compras -->
        <div v-else style="text-align: center; padding: 3rem;">
          <p style="font-size: 1.2rem; color: var(--color-text);">No tienes compras registradas</p>
          <a :href="getAppUrl('/catalogo')" class="btn btn-primary" style="margin-top: 1rem; display: inline-block; text-decoration: none;">
            Ir al catálogo
          </a>
        </div>
      </div>
    </section>

    <!-- Modal para registrar pago a crédito -->
    <div v-if="modalPagoCredito" class="modal-overlay" @click="cerrarModalPagoCredito">
      <div class="modal-content" @click.stop style="max-width: 500px;">
        <h3 style="margin: 0 0 1.5rem 0; color: var(--color-primary);">Registrar Pago a Crédito</h3>
        
        <div style="background: var(--color-bg); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
          <p style="margin: 0 0 0.5rem 0; color: var(--color-text);"><strong>Compra #{{ ventaSeleccionada?.id }}</strong></p>
          <p style="margin: 0 0 0.5rem 0; color: var(--color-text);">Total: <strong style="color: var(--color-primary);">Bs. {{ parseFloat(ventaSeleccionada?.total || 0).toFixed(2) }}</strong></p>
          <p style="margin: 0; color: var(--color-text);">Saldo pendiente: <strong style="color: #ef4444;">Bs. {{ calcularSaldoPendiente(ventaSeleccionada) }}</strong></p>
        </div>

        <form @submit.prevent="registrarPagoCredito">
          <div class="form-group" style="margin-bottom: 1.5rem;">
            <label class="form-label">Monto a pagar (Bs.)</label>
            <input 
              v-model.number="formPagoCredito.monto" 
              type="number" 
              step="0.01"
              min="0.01"
              :max="parseFloat(calcularSaldoPendiente(ventaSeleccionada))"
              class="form-input"
              required
            >
            <p style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--color-text-light);">
              Se generará un código QR con PagoFácil para este monto
            </p>
          </div>

          <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button type="button" @click="cerrarModalPagoCredito" class="btn btn-secondary">
              Cancelar
            </button>
            <button type="submit" class="btn btn-primary" :disabled="procesandoPago">
              {{ procesandoPago ? 'Generar QR' : 'Continuar con QR' }}
            </button>
          </div>
        </form>
      </div>
    </div>

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
            <li><a :href="getAppUrl('/mis-cotizaciones')">Cotizaciones</a></li>
            <li><a :href="getAppUrl('/mis-compras')">Compras</a></li>
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

// Estados de compras
const cargando = ref(true);
const filtroTipo = ref('');
const filtroEstado = ref('');
const compras = ref([]);

// Estados del modal de pago a crédito
const modalPagoCredito = ref(false);
const ventaSeleccionada = ref(null);
const procesandoPago = ref(false);
const formPagoCredito = ref({
  monto: 0
});

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

const comprasFiltradas = computed(() => {
  let resultado = compras.value;
  
  if (filtroTipo.value) {
    resultado = resultado.filter(c => c.tipo === filtroTipo.value);
  }
  
  if (filtroEstado.value) {
    resultado = resultado.filter(c => c.estado === filtroEstado.value);
  }
  
  return resultado;
});

const carritoCount = computed(() => {
  return carrito.value.reduce((total, item) => total + item.cantidad, 0);
});

// Cargar compras
const cargarCompras = async () => {
  try {
    const token = localStorage.getItem('token');
    if (!token) {
      window.location.href = getAppUrl('/login');
      return;
    }

    const response = await apiFetch(`/api/mis-compras`);

    const data = await response.json();
    if (response.ok) {
      compras.value = data;
    } else {
      console.error('Error al cargar compras:', data.error);
    }
  } catch (error) {
    console.error('Error al cargar compras:', error);
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

// Función para pagar compra de contado pendiente
const pagarCompra = async (compra) => {
  try {
    // Preparar productos para el carrito temporal
    const productosCarrito = compra.detalles.map(detalle => ({
      producto_id: detalle.producto_id,
      nombre: detalle.producto ? detalle.producto.nombre : 'Producto',
      cantidad: detalle.cantidad,
      costo_unitario: parseFloat(detalle.precio_unitario),
      stock_maximo: detalle.cantidad,
      imagen_url: detalle.producto?.imagen_url || ''
    }));

    // Guardar en localStorage
    localStorage.setItem('carrito', JSON.stringify(productosCarrito));
    
    // Guardar venta_id para actualizar en lugar de crear nueva
    localStorage.setItem('venta_pendiente_id', compra.id);
    
    // Redirigir a página de pago QR
    window.location.href = getAppUrl('/pago-qr');
  } catch (error) {
    console.error('Error al procesar pago:', error);
    alert('Error al procesar el pago');
  }
};

// Funciones para pago a crédito
const abrirModalPagoCredito = (compra) => {
  ventaSeleccionada.value = compra;
  const saldoPendiente = parseFloat(calcularSaldoPendiente(compra));
  formPagoCredito.value = {
    monto: saldoPendiente
  };
  modalPagoCredito.value = true;
};

const cerrarModalPagoCredito = () => {
  modalPagoCredito.value = false;
  ventaSeleccionada.value = null;
  formPagoCredito.value = {
    monto: 0
  };
};

const calcularSaldoPendiente = (compra) => {
  if (!compra) return '0.00';
  
  const total = parseFloat(compra.total);
  
  // Si tiene pagos, sumar todos los pagos realizados
  const pagosRealizados = compra.pagos ? 
    compra.pagos.reduce((sum, pago) => sum + parseFloat(pago.monto), 0) : 0;
  
  const saldo = total - pagosRealizados;
  return saldo.toFixed(2);
};

const registrarPagoCredito = async () => {
  if (!ventaSeleccionada.value) return;
  
  const monto = parseFloat(formPagoCredito.value.monto);
  const saldoPendiente = parseFloat(calcularSaldoPendiente(ventaSeleccionada.value));
  
  if (monto <= 0 || monto > saldoPendiente) {
    alert(`El monto debe ser mayor a 0 y no puede exceder el saldo pendiente (Bs. ${saldoPendiente})`);
    return;
  }
  
  procesandoPago.value = true;
  
  try {
    // Guardar datos temporalmente para generar QR
    localStorage.setItem('pago_credito_pendiente', JSON.stringify({
      venta_id: ventaSeleccionada.value.id,
      monto: monto,
      metodo: 'qr'
    }));
    
    // Redirigir a página de QR para pago a crédito
    window.location.href = getAppUrl('/pago-credito-qr');
  } catch (error) {
    console.error('Error al procesar pago:', error);
    alert('Error al procesar el pago');
    procesandoPago.value = false;
  }
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
    const response = await apiFetch('/api/visitas/mis-compras', {
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

  cargarCompras();

  // Verificar modo noche automático cada hora
  const nightModeInterval = setInterval(checkAutoNightMode, 3600000);
  
  onUnmounted(() => {
    clearInterval(nightModeInterval);
  });
});
</script>

<style scoped>
.badge {
  padding: 0.35rem 0.85rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  text-transform: uppercase;
  display: inline-block;
}

.badge-pendiente {
  background: #fef3c7;
  color: #92400e;
}

.badge-pagada {
  background: #d1fae5;
  color: #065f46;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
}

.modal-content {
  background: var(--color-bg-alt);
  border-radius: 12px;
  padding: 2rem;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}
</style>
