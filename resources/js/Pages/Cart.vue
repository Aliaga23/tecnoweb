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
        <a :href="getAppUrl('/')" class="navbar-logo">MOTO<span>PARTS</span></a>
        
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
                <a :href="getAppUrl('/perfil')" class="user-menu-item">Mi perfil</a>
                <a :href="getAppUrl('/mis-cotizaciones')" class="user-menu-item">Mis cotizaciones</a>
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

    <!-- Carrito -->
    <section class="section" style="background-color: var(--color-bg-alt); min-height: 80vh; padding: 3rem 0;">
      <div class="container">
        <h2 class="section-title">Carrito de <span class="highlight">Compras</span></h2>
        
        <div v-if="carrito.length === 0" style="text-align: center; padding: 4rem; background: var(--color-bg); border-radius: 12px; margin-top: 2rem;">
          <ShoppingCart :size="64" style="color: var(--color-text-light); margin: 0 auto 1rem;" />
          <h3 style="font-size: 1.5rem; color: var(--color-text); margin-bottom: 1rem;">Tu carrito está vacío</h3>
          <p style="color: var(--color-text-light); margin-bottom: 2rem;">Agrega productos desde el catálogo para comenzar</p>
          <a :href="getAppUrl('/catalogo')" class="btn btn-primary">Ir al catálogo</a>
        </div>

        <div v-else style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-top: 2rem;">
          <!-- Lista de productos -->
          <div>
            <div v-for="item in carrito" :key="item.producto_id" style="background: var(--color-bg); border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem; display: flex; gap: 1.5rem; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
              <!-- Imagen del producto -->
              <div style="width: 100px; height: 100px; flex-shrink: 0; background: var(--color-bg-alt); border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                <img v-if="item.imagen_url" :src="item.imagen_url" :alt="item.nombre" style="width: 100%; height: 100%; object-fit: cover;">
                <span v-else style="color: var(--color-text-light); font-size: 0.875rem;">Sin imagen</span>
              </div>

              <!-- Info del producto -->
              <div style="flex: 1;">
                <h3 style="font-size: 1.25rem; color: var(--color-primary); margin-bottom: 0.5rem; font-weight: 600;">{{ item.nombre }}</h3>
                <p style="color: var(--color-text-light); font-size: 1rem; margin-bottom: 0.25rem;">Precio: Bs. {{ parseFloat(item.costo_unitario).toFixed(2) }}</p>
                <p style="color: var(--color-text-light); font-size: 0.875rem;">Stock disponible: {{ item.stock_maximo }}</p>
              </div>
              
              <div style="display: flex; align-items: center; gap: 0.5rem;">
                <button @click="decrementarCantidad(item)" class="btn btn-secondary" style="padding: 0.5rem; display: flex; align-items: center; justify-content: center; min-width: 36px;">
                  <Minus :size="16" />
                </button>
                <input 
                  :value="item.cantidad" 
                  readonly
                  style="width: 70px; text-align: center; padding: 0.5rem; border: 2px solid var(--color-border); border-radius: 8px; font-weight: 600;"
                >
                <button @click="incrementarCantidad(item)" class="btn btn-secondary" style="padding: 0.5rem; display: flex; align-items: center; justify-content: center; min-width: 36px;">
                  <Plus :size="16" />
                </button>
              </div>

              <div style="text-align: right; min-width: 120px;">
                <p style="font-size: 1.25rem; font-weight: 600; color: var(--color-primary);">
                  Bs. {{ (item.cantidad * parseFloat(item.costo_unitario)).toFixed(2) }}
                </p>
              </div>

              <button @click="eliminarDelCarrito(item.producto_id)" class="btn btn-secondary" style="padding: 0.5rem 0.75rem; background: #ef4444; border-color: #ef4444; display: flex; align-items: center; gap: 0.25rem;" title="Eliminar producto">
                <Trash2 :size="18" />
              </button>
            </div>
          </div>

          <!-- Resumen -->
          <div>
            <div style="background: var(--color-bg); border-radius: 12px; padding: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 2px solid var(--color-border); padding-bottom: 1rem;">
                <h3 style="font-size: 1.5rem; color: var(--color-primary); margin: 0;">
                  Resumen
                </h3>
                <button @click="generarCotizacion" class="btn btn-secondary" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                  Cotizar
                </button>
              </div>
              
              <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; color: var(--color-text);">
                <span>Productos ({{ carritoCount }})</span>
                <span>Bs. {{ totalCarrito.toFixed(2) }}</span>
              </div>
              
              <div style="border-top: 2px solid var(--color-border); padding-top: 1rem; margin-top: 1rem;">
                <div style="display: flex; justify-content: space-between; font-size: 1.5rem; font-weight: 700; color: var(--color-primary); margin-bottom: 2rem;">
                  <span>Total</span>
                  <span>Bs. {{ totalCarrito.toFixed(2) }}</span>
                </div>

                <button @click="generarVenta" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem; font-size: 1.125rem;">
                  Comprar
                </button>

                <button @click="vaciarCarrito" class="btn btn-secondary" style="width: 100%; background: #6b7280; border-color: #6b7280;">
                  Vaciar carrito
                </button>
              </div>

              <a :href="getAppUrl('/catalogo')" style="display: block; text-align: center; margin-top: 1.5rem; color: var(--color-primary); text-decoration: none;">
                ← Seguir comprando
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <div class="container">
        <h3 class="footer-title">MOTO<span class="highlight">PARTS</span></h3>
        <p class="footer-text">Tu mejor opción en repuestos para motos</p>
        <div class="footer-social">
          <a href="#">Facebook</a>
          <a href="#">Instagram</a>
          <a href="#">WhatsApp</a>
        </div>
        <p style="color: #6b7280; font-size: 14px;">&copy; 2025 MotoParts. Todos los derechos reservados.</p>
        
        <div class="footer-counter">
          Visitas en esta página: <strong>{{ contadorVisitas }}</strong>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { ShoppingCart, User, Trash2, Plus, Minus } from 'lucide-vue-next';
import { useApi } from '../composables/useApi';
const { apiFetch, getAppUrl } = useApi();

// Estados
const carrito = ref([]);
const usuario = ref(null);
const userMenuOpen = ref(false);
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

const totalCarrito = computed(() => {
  return carrito.value.reduce((total, item) => total + (item.cantidad * parseFloat(item.costo_unitario)), 0);
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

const incrementarCantidad = (item) => {
  if (item.cantidad < item.stock_maximo) {
    item.cantidad++;
    localStorage.setItem('carrito', JSON.stringify(carrito.value));
  } else {
    alert(`Stock máximo disponible: ${item.stock_maximo}`);
  }
};

const decrementarCantidad = (item) => {
  if (item.cantidad > 1) {
    item.cantidad--;
    localStorage.setItem('carrito', JSON.stringify(carrito.value));
  }
};

const actualizarCantidad = (item, nuevaCantidad) => {
  const cantidad = parseInt(nuevaCantidad);
  if (cantidad > 0 && cantidad <= item.stock_maximo) {
    item.cantidad = cantidad;
    localStorage.setItem('carrito', JSON.stringify(carrito.value));
  } else if (cantidad > item.stock_maximo) {
    alert(`Stock máximo disponible: ${item.stock_maximo}`);
    item.cantidad = item.stock_maximo;
    localStorage.setItem('carrito', JSON.stringify(carrito.value));
  }
};

const eliminarDelCarrito = (productoId) => {
  if (confirm('¿Eliminar este producto del carrito?')) {
    carrito.value = carrito.value.filter(item => item.producto_id !== productoId);
    localStorage.setItem('carrito', JSON.stringify(carrito.value));
  }
};

const vaciarCarrito = () => {
  if (confirm('¿Estás seguro de vaciar el carrito?')) {
    carrito.value = [];
    localStorage.setItem('carrito', JSON.stringify(carrito.value));
  }
};

const generarCotizacion = async () => {
  try {
    const token = localStorage.getItem('token');
    if (!token) {
      alert('Debes iniciar sesión para generar una cotización');
      window.location.href = '/login';
      return;
    }

    const detalles = carrito.value.map(item => ({
      producto_id: item.producto_id,
      cantidad: item.cantidad,
      costo_unitario: item.costo_unitario
    }));

    const response = await apiFetch('/api/cotizaciones', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        cliente_id: usuario.value.id,
        detalles: detalles
      })
    });

    const data = await response.json();

    if (response.ok) {
      alert('Cotización generada exitosamente');
      carrito.value = [];
      localStorage.setItem('carrito', JSON.stringify(carrito.value));
      window.location.href = '/mis-cotizaciones';
    } else {
      alert('Error al generar cotización: ' + (data.error || 'Error desconocido'));
    }
    
  } catch (error) {
    console.error('Error al generar cotización:', error);
    alert('Error al generar cotización');
  }
};

const generarVenta = () => {
  // Verificar autenticación
  if (!usuario.value) {
    alert('Debes iniciar sesión para comprar');
    window.location.href = '/login';
    return;
  }

  // Verificar que hay productos en el carrito
  if (carrito.value.length === 0) {
    alert('El carrito está vacío');
    return;
  }

  // Redirigir a la página de pago QR
  window.location.href = '/pago-qr';
};

// Registrar visita
const registrarVisita = async () => {
  try {
    const response = await apiFetch('/api/visitas/cart', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' }
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

onMounted(() => {
  document.documentElement.style.setProperty('--font-size-base', `${fontSize.value}px`);
  
  // Registrar visita
  registrarVisita();

  // Escuchar cambios en el carrito desde otras pestañas
  window.addEventListener('storage', (e) => {
    if (e.key === 'carrito' && e.newValue) {
      carrito.value = JSON.parse(e.newValue);
    }
  });
});
</script>
