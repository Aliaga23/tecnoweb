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
        <a href="/" class="navbar-logo">MOTO<span>PARTS</span></a>
        
        <ul class="navbar-menu">
          <li><a href="/" class="navbar-link">Inicio</a></li>
          <li><a href="/catalogo" class="navbar-link">Productos</a></li>
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
                <a href="/perfil" class="user-menu-item">Mi perfil</a>
                <a href="/mis-cotizaciones" class="user-menu-item">Mis cotizaciones</a>
                <button @click="cerrarSesion" class="user-menu-item">Cerrar sesión</button>
              </div>
            </div>
          </template>
          <template v-else>
            <a href="/register" class="btn btn-secondary">Registrarse</a>
            <a href="/login" class="btn btn-primary">Ingresar</a>
          </template>
          <a href="/cart" class="navbar-icon" title="Carrito" style="position: relative;">
            <ShoppingCart :size="24" />
            <span v-if="carritoCount > 0" style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; display: flex; align-items: center; justify-content: center; font-weight: bold;">{{ carritoCount }}</span>
          </a>
        </div>
      </div>
    </nav>

    <!-- Catálogo de Productos -->
    <section class="section" style="background-color: var(--color-bg-alt); min-height: 80vh; padding: 3rem 0;">
      <div class="container">
        <h2 class="section-title">Catálogo de <span class="highlight">Productos</span></h2>
        <p class="section-subtitle">Explora nuestros productos y agrega al carrito para cotizar</p>
        
        <!-- Filtros -->
        <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
          <input 
            type="text" 
            v-model="busqueda" 
            placeholder="Buscar productos..." 
            class="form-input"
            style="flex: 1;"
          >
          <select v-model="categoriaFiltro" class="form-input" style="width: auto; min-width: 200px;">
            <option value="">Todas las categorías</option>
            <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
              {{ cat.nombre }}
            </option>
          </select>
        </div>

        <!-- Grid de Productos -->
        <div v-if="cargando" class="loading-container">
          <div class="loading"></div>
        </div>

        <div v-else-if="productosFiltrados.length > 0" class="grid grid-cols-4">
          <div v-for="producto in productosFiltrados" :key="producto.id" class="card">
            <div class="card-image">
              <img v-if="producto.imagen_url" :src="producto.imagen_url" :alt="producto.nombre">
              <span v-else style="font-size: 24px; color: #999;">Sin imagen</span>
            </div>
            <h3 class="card-title">{{ producto.nombre }}</h3>
            <p class="card-description">{{ producto.descripcion }}</p>
            <p style="font-size: 0.875rem; color: var(--color-text-light); margin: 0.5rem 0;">
              Disponible: {{ producto.stock_actual }} unidades
            </p>
            <div class="card-footer">
              <span class="card-price">Bs. {{ parseFloat(producto.precio_unitario).toFixed(2) }}</span>
            </div>
            <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
              <a :href="`/catalogo/${producto.id}`" class="btn btn-primary" style="flex: 1; text-align: center; text-decoration: none;">Ver más</a>
              <button 
                @click="agregarAlCarrito(producto)" 
                class="btn btn-primary" 
                style="flex: 1;"
                :disabled="producto.stock_actual <= 0"
              >
                {{ producto.stock_actual > 0 ? 'Agregar' : 'Sin stock' }}
              </button>
            </div>
          </div>
        </div>

        <div v-else style="text-align: center; padding: 3rem;">
          <p style="font-size: 1.2rem; color: var(--color-text);">No se encontraron productos</p>
        </div>
      </div>
    </section>

    <!-- Modal Carrito -->
    <div v-if="showCarrito" @click.self="toggleCarrito" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem;">
      <div style="background: var(--color-bg); border-radius: 12px; max-width: 600px; width: 100%; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 8px 24px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-bottom: 2px solid var(--color-border);">
          <h3 style="font-size: 1.5rem; color: var(--color-primary); margin: 0;">Carrito de Cotización</h3>
          <button @click="toggleCarrito" style="background: none; border: none; font-size: 2rem; cursor: pointer; color: var(--color-text); line-height: 1; padding: 0; width: 32px; height: 32px;">&times;</button>
        </div>
        <div style="flex: 1; overflow-y: auto; padding: 1.5rem;">
          <div v-if="carrito.length === 0" style="text-align: center; padding: 3rem; color: var(--color-text);">
            <p>El carrito está vacío</p>
          </div>
          <div v-else>
            <div v-for="item in carrito" :key="item.producto_id" style="display: flex; gap: 1rem; align-items: center; padding: 1rem; border: 2px solid var(--color-border); border-radius: 8px; margin-bottom: 1rem;">
              <div style="flex: 1;">
                <h4 style="margin: 0 0 0.5rem 0; color: var(--color-text);">{{ item.nombre }}</h4>
                <p style="margin: 0; color: var(--color-primary); font-weight: 600;">Bs. {{ parseFloat(item.costo_unitario).toFixed(2) }}</p>
              </div>
              <div style="display: flex; align-items: center; gap: 0.5rem;">
                <button @click="decrementarCantidad(item)" style="background: var(--color-primary); color: white; border: none; width: 32px; height: 32px; border-radius: 4px; cursor: pointer; font-size: 1.25rem; line-height: 1;">-</button>
                <input 
                  type="number" 
                  v-model.number="item.cantidad" 
                  min="1" 
                  :max="item.stock_maximo"
                  @change="actualizarCantidad(item)"
                  style="width: 60px; text-align: center; padding: 0.4rem; border: 2px solid var(--color-border); border-radius: 4px; background: var(--color-bg); color: var(--color-text);"
                >
                <button @click="incrementarCantidad(item)" style="background: var(--color-primary); color: white; border: none; width: 32px; height: 32px; border-radius: 4px; cursor: pointer; font-size: 1.25rem; line-height: 1;">+</button>
              </div>
              <div style="text-align: right; min-width: 100px;">
                <p style="margin: 0 0 0.5rem 0; font-weight: 600; color: var(--color-text);">Bs. {{ (item.cantidad * item.costo_unitario).toFixed(2) }}</p>
                <button @click="eliminarDelCarrito(item.producto_id)" style="color: #dc2626; background: none; border: none; cursor: pointer; font-size: 0.85rem; padding: 0;">Eliminar</button>
              </div>
            </div>
            <div style="border-top: 2px solid var(--color-border); margin-top: 1rem; padding-top: 1rem; text-align: right;">
              <h3 style="color: var(--color-primary); font-size: 1.5rem; margin: 0;">Total: Bs. {{ totalCarrito.toFixed(2) }}</h3>
            </div>
          </div>
        </div>
        <div style="display: flex; gap: 1rem; padding: 1.5rem; border-top: 2px solid var(--color-border);">
          <button @click="vaciarCarrito" class="btn btn-secondary" style="flex: 1;" :disabled="carrito.length === 0">Vaciar carrito</button>
          <button @click="generarCotizacion" class="btn btn-primary" style="flex: 1;" :disabled="carrito.length === 0">Generar Cotización</button>
        </div>
      </div>
    </div>

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
import { ShoppingCart, User } from 'lucide-vue-next';

// Estados
const productos = ref([]);
const categorias = ref([]);
const cargando = ref(true);
const usuario = ref(null);
const userMenuOpen = ref(false);
const busqueda = ref('');
const categoriaFiltro = ref('');
const contadorVisitas = ref(0);

// Carrito
const carrito = ref([]);
const showCarrito = ref(false);

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

// Accesibilidad
const accessibilityPanelOpen = ref(false);
const currentTheme = ref(localStorage.getItem('theme') || 'theme-adults');
const fontSize = ref(parseInt(localStorage.getItem('fontSize')) || 16);
const highContrast = ref(localStorage.getItem('highContrast') === 'true');
const manualNightMode = ref(localStorage.getItem('manualNightMode') === 'true');
const autoNightMode = ref(localStorage.getItem('autoNightMode') !== 'false'); // Por defecto activado

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

// Detectar modo noche por horario
const isNightMode = computed(() => {
  if (!autoNightMode.value) {
    return manualNightMode.value;
  }
  const hora = new Date().getHours();
  return hora >= 19 || hora < 7; // 7pm a 7am
});

const toggleNightMode = () => {
  if (autoNightMode.value) {
    // Si está en auto, cambiar a manual
    autoNightMode.value = false;
    manualNightMode.value = !isNightMode.value;
  } else {
    // Si está en manual, alternar
    manualNightMode.value = !manualNightMode.value;
  }
};

// Guardar preferencias
watch(currentTheme, (val) => localStorage.setItem('theme', val));
watch(fontSize, (val) => localStorage.setItem('fontSize', val.toString()));
watch(highContrast, (val) => localStorage.setItem('highContrast', val));
watch(manualNightMode, (val) => localStorage.setItem('manualNightMode', val));
watch(autoNightMode, (val) => localStorage.setItem('autoNightMode', val));

const toggleAccessibilityPanel = () => {
  accessibilityPanelOpen.value = !accessibilityPanelOpen.value;
};

const toggleCarrito = () => {
  showCarrito.value = !showCarrito.value;
};

// Computed
const productosFiltrados = computed(() => {
  let resultado = productos.value;

  if (busqueda.value) {
    resultado = resultado.filter(p => 
      p.nombre.toLowerCase().includes(busqueda.value.toLowerCase()) ||
      p.descripcion.toLowerCase().includes(busqueda.value.toLowerCase())
    );
  }

  if (categoriaFiltro.value) {
    resultado = resultado.filter(p => p.categoria_id == categoriaFiltro.value);
  }

  return resultado;
});

const carritoCount = computed(() => {
  return carrito.value.reduce((sum, item) => sum + item.cantidad, 0);
});

const totalCarrito = computed(() => {
  return carrito.value.reduce((sum, item) => sum + (item.cantidad * item.costo_unitario), 0);
});

// Funciones carrito
const agregarAlCarrito = (producto) => {
  const existe = carrito.value.find(item => item.producto_id === producto.id);
  
  if (existe) {
    if (existe.cantidad < producto.stock_actual) {
      existe.cantidad++;
    } else {
      alert('No hay más stock disponible');
    }
  } else {
    carrito.value.push({
      producto_id: producto.id,
      nombre: producto.nombre,
      costo_unitario: parseFloat(producto.precio_unitario),
      cantidad: 1,
      stock_maximo: producto.stock_actual,
      imagen_url: producto.imagen_url
    });
  }
  
  localStorage.setItem('carrito', JSON.stringify(carrito.value));
};

const incrementarCantidad = (item) => {
  if (item.cantidad < item.stock_maximo) {
    item.cantidad++;
    localStorage.setItem('carrito', JSON.stringify(carrito.value));
  } else {
    alert('No hay más stock disponible');
  }
};

const decrementarCantidad = (item) => {
  if (item.cantidad > 1) {
    item.cantidad--;
    localStorage.setItem('carrito', JSON.stringify(carrito.value));
  }
};

const actualizarCantidad = (item) => {
  if (item.cantidad < 1) {
    item.cantidad = 1;
  } else if (item.cantidad > item.stock_maximo) {
    item.cantidad = item.stock_maximo;
    alert('No hay más stock disponible');
  }
  localStorage.setItem('carrito', JSON.stringify(carrito.value));
};

const eliminarDelCarrito = (productoId) => {
  carrito.value = carrito.value.filter(item => item.producto_id !== productoId);
  localStorage.setItem('carrito', JSON.stringify(carrito.value));
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

    const response = await fetch('/api/cotizaciones', {
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
      showCarrito.value = false;
      window.location.href = '/mis-cotizaciones';
    } else {
      alert('Error al generar cotización: ' + (data.error || 'Error desconocido'));
    }
    
  } catch (error) {
    console.error('Error al generar cotización:', error);
    alert('Error al generar cotización');
  }
};

// Registrar visita
const registrarVisita = async () => {
  try {
    const response = await fetch('/api/visitas/catalogo', {
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

onMounted(async () => {
  // Registrar visita
  await registrarVisita();
  
  const carritoGuardado = localStorage.getItem('carrito');
  if (carritoGuardado) {
    carrito.value = JSON.parse(carritoGuardado);
  }
  
  try {
    const responseProductos = await fetch('/api/catalogo');
    const dataProductos = await responseProductos.json();
    if (dataProductos.success) {
      productos.value = dataProductos.data;
    }

    const responseCategorias = await fetch('/api/catalogo-categorias');
    const dataCategorias = await responseCategorias.json();
    if (dataCategorias.success) {
      categorias.value = dataCategorias.data;
    }
  } catch (error) {
    console.error('Error al cargar datos:', error);
  } finally {
    cargando.value = false;
  }

  // Escuchar cambios en el localStorage desde otras pestañas
  window.addEventListener('storage', (e) => {
    if (e.key === 'carrito' && e.newValue) {
      carrito.value = JSON.parse(e.newValue);
    }
  });
});
</script>
