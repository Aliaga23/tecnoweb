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
                <a :href="getAppUrl('/perfil')" class="user-menu-item">Mi perfil</a>
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

    <!-- Detalle del Producto -->
    <section class="section" style="background-color: var(--color-bg-alt); min-height: 80vh; padding: 3rem 0;">
      <div class="container">
        <div v-if="cargando" class="loading-container">
          <div class="loading"></div>
        </div>

        <div v-else-if="producto" style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: start;">
          <!-- Imagen del producto -->
          <div style="background: var(--color-bg); border-radius: 12px; padding: 2rem; text-align: center;">
            <img v-if="producto.imagen_url" :src="producto.imagen_url" :alt="producto.nombre" style="max-width: 100%; height: auto; border-radius: 8px;">
            <div v-else style="padding: 4rem; font-size: 48px; color: #999;">Sin imagen</div>
          </div>

          <!-- Información del producto -->
          <div>
            <h1 style="font-size: 2.5rem; color: var(--color-primary); margin-bottom: 1rem;">{{ producto.nombre }}</h1>
            
            <div style="background: var(--color-bg); border-radius: 12px; padding: 2rem; margin-bottom: 2rem;">
              <p style="font-size: 1.125rem; color: var(--color-text); line-height: 1.6; margin-bottom: 1.5rem;">{{ producto.descripcion }}</p>
              
              <div style="display: grid; gap: 1rem; margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: var(--color-bg-alt); border-radius: 8px;">
                  <span style="font-weight: 600; color: var(--color-text);">Categoría:</span>
                  <span style="color: var(--color-text);">{{ producto.categoria?.nombre || 'Sin categoría' }}</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: var(--color-bg-alt); border-radius: 8px;">
                  <span style="font-weight: 600; color: var(--color-text);">Stock disponible:</span>
                  <span :style="{ color: producto.stock_actual > 0 ? '#16a34a' : '#dc2626', fontWeight: '600' }">
                    {{ producto.stock_actual }} unidades
                  </span>
                </div>
                
                <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: var(--color-bg-alt); border-radius: 8px;">
                  <span style="font-weight: 600; color: var(--color-text);">Precio:</span>
                  <span style="font-size: 2rem; color: var(--color-primary); font-weight: bold;">Bs. {{ parseFloat(producto.precio_unitario).toFixed(2) }}</span>
                </div>
              </div>

              <!-- Cantidad y agregar al carrito -->
              <div style="display: flex; gap: 1rem; align-items: center; margin-top: 2rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                  <button @click="decrementarCantidad" class="btn btn-secondary" style="padding: 0.5rem 1rem;" :disabled="cantidad <= 1">-</button>
                  <input type="number" v-model.number="cantidad" min="1" :max="producto.stock_actual" style="width: 80px; text-align: center; padding: 0.5rem; border: 2px solid var(--color-border); border-radius: 8px; font-size: 1.125rem;">
                  <button @click="incrementarCantidad" class="btn btn-secondary" style="padding: 0.5rem 1rem;" :disabled="cantidad >= producto.stock_actual">+</button>
                </div>
                
                <button 
                  @click="agregarAlCarrito" 
                  class="btn btn-primary" 
                  style="flex: 1; font-size: 1.125rem; padding: 0.75rem;"
                  :disabled="producto.stock_actual <= 0"
                >
                  {{ producto.stock_actual > 0 ? 'Agregar al carrito' : 'Sin stock' }}
                </button>
              </div>

              <a :href="getAppUrl('/catalogo')" class="btn btn-secondary" style="width: 100%; margin-top: 1rem; text-align: center; display: block; text-decoration: none;">
                Volver al catálogo
              </a>
            </div>
          </div>
        </div>

        <div v-else style="text-align: center; padding: 3rem;">
          <p style="font-size: 1.2rem; color: var(--color-text);">Producto no encontrado</p>
          <a :href="getAppUrl('/catalogo')" class="btn btn-primary" style="margin-top: 1rem;">Volver al catálogo</a>
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
import { ShoppingCart, User } from 'lucide-vue-next';
import { useApi } from '../composables/useApi';
const { apiFetch, getAppUrl } = useApi();

const props = defineProps({
  productoId: {
    type: [String, Number],
    required: true
  }
});

// Estados
const producto = ref(null);
const cargando = ref(true);
const cantidad = ref(1);
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

const incrementarCantidad = () => {
  if (cantidad.value < producto.value.stock_actual) {
    cantidad.value++;
  }
};

const decrementarCantidad = () => {
  if (cantidad.value > 1) {
    cantidad.value--;
  }
};

const agregarAlCarrito = () => {
  const itemExistente = carrito.value.find(item => item.producto_id === producto.value.id);
  
  if (itemExistente) {
    const nuevaCantidad = itemExistente.cantidad + cantidad.value;
    if (nuevaCantidad <= producto.value.stock_actual) {
      itemExistente.cantidad = nuevaCantidad;
    } else {
      alert(`Solo hay ${producto.value.stock_actual} unidades disponibles`);
      return;
    }
  } else {
    carrito.value.push({
      producto_id: producto.value.id,
      nombre: producto.value.nombre,
      costo_unitario: producto.value.precio_unitario,
      cantidad: cantidad.value,
      stock_maximo: producto.value.stock_actual,
      imagen_url: producto.value.imagen_url
    });
  }
  
  localStorage.setItem('carrito', JSON.stringify(carrito.value));
  alert('Producto agregado al carrito');
  window.location.href = getAppUrl('/catalogo');
};

// Registrar visita
const registrarVisita = async () => {
  try {
    const response = await apiFetch(`/api/visitas/producto-${props.productoId}`, {
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
  
  // Registrar visita
  await registrarVisita();
  
  try {
    const response = await apiFetch(`/api/catalogo/${props.productoId}`);
    const data = await response.json();
    if (data.success) {
      producto.value = data.data;
    }
  } catch (error) {
    console.error('Error al cargar producto:', error);
  } finally {
    cargando.value = false;
  }

  // Escuchar cambios en el carrito desde otras pestañas
  window.addEventListener('storage', (e) => {
    if (e.key === 'carrito' && e.newValue) {
      carrito.value = JSON.parse(e.newValue);
    }
  });
});
</script>
