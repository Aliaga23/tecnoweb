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
          <li><a href="#categorias" class="navbar-link">Categorías</a></li>
          <li><a href="#contacto" class="navbar-link">Contacto</a></li>
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

    <!-- Hero Section -->
    <section id="inicio" class="hero">
      <div class="container">
        <h1>Repuestos de <span class="highlight">Calidad</span></h1>
        <p>
          Encuentra todos los repuestos que necesitas para tu moto. 
          Calidad garantizada y los mejores precios del mercado.
        </p>
        <div class="hero-buttons">
          <a href="#productos" class="btn btn-primary">Ver Productos</a>
          <a href="#contacto" class="btn btn-secondary">Contáctanos</a>
        </div>
      </div>
    </section>

    <!-- Características -->
    <section class="section" style="background-color: var(--color-bg);">
      <div class="container">
        <div class="grid grid-cols-3">
          <div class="feature-card">
            <div class="feature-icon">✓</div>
            <h3 class="card-title">Calidad Garantizada</h3>
            <p class="card-description">Todos nuestros repuestos son originales y de alta calidad</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">Bs.</div>
            <h3 class="card-title">Mejores Precios</h3>
            <p class="card-description">Precios competitivos y ofertas especiales</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">→</div>
            <h3 class="card-title">Entrega Rápida</h3>
            <p class="card-description">Envío rápido y seguro a todo el país</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Productos Destacados -->
    <section id="productos" class="section" style="background-color: var(--color-bg-alt);">
      <div class="container">
        <h2 class="section-title">Productos <span class="highlight">Destacados</span></h2>
        <p class="section-subtitle">Los repuestos más vendidos y mejor valorados</p>
        
        <div v-if="cargando" class="loading-container">
          <div class="loading"></div>
        </div>
        
        <div v-else class="grid grid-cols-4">
          <div v-for="producto in productos" :key="producto.id" class="card">
            <div class="card-image">
              <img v-if="producto.imagen_url" :src="producto.imagen_url" :alt="producto.nombre">
              <span v-else style="font-size: 24px; color: #999;">Sin imagen</span>
            </div>
            <h3 class="card-title">{{ producto.nombre }}</h3>
            <p class="card-description">{{ producto.descripcion }}</p>
            <div class="card-footer">
              <span class="card-price">Bs. {{ producto.precio_unitario }}</span>
              <a :href="getAppUrl(`/catalogo/${producto.id}`)" class="btn btn-primary">Ver más</a>
            </div>
          </div>
        </div>

        <div class="text-center mt-xl">
          <a :href="getAppUrl('/catalogo')" class="btn btn-primary">Ver Todos los Productos</a>
        </div>
      </div>
    </section>

    <!-- Categorías -->
    <section id="categorias" class="section" style="background-color: var(--color-bg);">
      <div class="container">
        <h2 class="section-title">Nuestras <span class="highlight">Categorías</span></h2>
        <p class="section-subtitle">Encuentra lo que necesitas por categoría</p>
        
        <div class="grid grid-cols-4">
          <div v-for="categoria in categorias" :key="categoria.id" class="card" style="text-align: center;">
            <h3 class="card-title">{{ categoria.nombre }}</h3>
            <p class="card-description">{{ categoria.total_productos }} productos</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Contacto -->
    <section id="contacto" class="section contact-section">
      <div class="container">
        <h2 class="section-title contact-title">
          ¿Necesitas <span class="highlight">Ayuda?</span>
        </h2>
        <p class="section-subtitle contact-subtitle">Estamos aquí para ayudarte</p>
        
        <div class="grid grid-cols-3">
          <div class="feature-card contact-card">
            <div class="feature-icon">
              <Phone :size="32" />
            </div>
            <h3 class="card-title contact-card-title">Teléfono</h3>
            <p class="contact-card-text">+591 7777-7777</p>
          </div>
          <div class="feature-card contact-card">
            <div class="feature-icon">
              <Mail :size="32" />
            </div>
            <h3 class="card-title contact-card-title">Email</h3>
            <p class="contact-card-text">contacto@ELYTA.com</p>
          </div>
          <div class="feature-card contact-card">
            <div class="feature-icon">
              <MapPin :size="32" />
            </div>
            <h3 class="card-title contact-card-title">Dirección</h3>
            <p class="contact-card-text">La Paz, Bolivia</p>
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
import { ShoppingCart, Phone, Mail, MapPin, User } from 'lucide-vue-next';
import { useApi } from '../composables/useApi';

const { apiFetch, getAppUrl } = useApi();

// Estados
const menuAbierto = ref(false);
const productos = ref([]);
const categorias = ref([]);
const cargando = ref(true);
const contadorVisitas = ref(0);
const usuario = ref(null);
const userMenuOpen = ref(false);
const carrito = ref([]);

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

// Registrar visita
const registrarVisita = async () => {
  try {
    const response = await apiFetch('/api/visitas/landing', {
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
  // Registrar visita
  await registrarVisita();
  
  try {
    // Cargar productos
    const responseProductos = await apiFetch('/api/catalogo');
    const dataProductos = await responseProductos.json();
    if (dataProductos.success) {
      productos.value = dataProductos.data.slice(0, 8);
    }

    // Cargar categorías
    const responseCategorias = await apiFetch('/api/catalogo-categorias');
    const dataCategorias = await responseCategorias.json();
    if (dataCategorias.success) {
      categorias.value = dataCategorias.data;
    }
  } catch (error) {
    console.error('Error al cargar datos:', error);
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
