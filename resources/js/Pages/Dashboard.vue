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
          <li><a href="/dashboard" class="navbar-link">Dashboard</a></li>
          <li><a href="/dashboard/productos" class="navbar-link">Productos</a></li>
          <li><a href="/dashboard/categorias" class="navbar-link">Categorías</a></li>
          <li><a href="/dashboard/usuarios" class="navbar-link">Usuarios</a></li>
          <li><a href="/dashboard/ventas" class="navbar-link">Ventas</a></li>
        </ul>

        <div class="navbar-controls">
          <span style="color: var(--color-text); margin-right: 1rem;">{{ usuario?.nombre }}</span>
          <div class="user-menu-container">
            <button @click="toggleUserMenu" class="navbar-icon" title="Mi cuenta">
              <User :size="24" />
            </button>
            <div v-if="userMenuOpen" class="user-menu">
              <a href="/perfil" class="user-menu-item">Mi perfil</a>
              <button @click="cerrarSesion" class="user-menu-item">Cerrar sesión</button>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Dashboard Content -->
    <section class="section" style="background-color: var(--color-bg);">
      <div style="padding: 0 2rem;">
        <h1 class="section-title">Panel de Administración</h1>

        <!-- Estadísticas -->
        <div class="grid grid-cols-4" style="margin-bottom: 3rem;">
          <div class="card" style="text-align: center; padding: 2rem;">
            <h3 style="font-size: 2.5rem; color: var(--color-primary); margin-bottom: 0.5rem;">{{ stats.productos }}</h3>
            <p style="color: var(--color-text-light); font-weight: 600;">Productos</p>
          </div>
          <div class="card" style="text-align: center; padding: 2rem;">
            <h3 style="font-size: 2.5rem; color: var(--color-primary); margin-bottom: 0.5rem;">{{ stats.categorias }}</h3>
            <p style="color: var(--color-text-light); font-weight: 600;">Categorías</p>
          </div>
          <div class="card" style="text-align: center; padding: 2rem;">
            <h3 style="font-size: 2.5rem; color: var(--color-primary); margin-bottom: 0.5rem;">{{ stats.usuarios }}</h3>
            <p style="color: var(--color-text-light); font-weight: 600;">Usuarios</p>
          </div>
          <div class="card" style="text-align: center; padding: 2rem;">
            <h3 style="font-size: 2.5rem; color: var(--color-primary); margin-bottom: 0.5rem;">{{ stats.ventas }}</h3>
            <p style="color: var(--color-text-light); font-weight: 600;">Ventas</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <div class="container">
        <p>&copy; 2025 MotoParts. Todos los derechos reservados.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { User } from 'lucide-vue-next';

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

// Usuario
const usuario = ref(null);
const userMenuOpen = ref(false);

const usuarioData = localStorage.getItem('user');
if (usuarioData) {
  try {
    usuario.value = JSON.parse(usuarioData);
  } catch (error) {
    console.error('Error al parsear usuario:', error);
    window.location.href = '/login';
  }
} else {
  window.location.href = '/login';
}

const toggleUserMenu = () => {
  userMenuOpen.value = !userMenuOpen.value;
};

const cerrarSesion = () => {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  window.location.href = '/';
};

// Estadísticas
const stats = ref({
  productos: 0,
  categorias: 0,
  usuarios: 0,
  ventas: 0
});

const cargarEstadisticas = async () => {
  const token = localStorage.getItem('token');
  
  try {
    // Cargar productos
    const productosRes = await fetch('/api/productos', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const productosData = await productosRes.json();
    stats.value.productos = productosData.data?.length || 0;

    // Cargar categorías
    const categoriasRes = await fetch('/api/categorias', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const categoriasData = await categoriasRes.json();
    stats.value.categorias = categoriasData.data?.length || 0;

    // Cargar usuarios
    const usuariosRes = await fetch('/api/usuarios', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const usuariosData = await usuariosRes.json();
    stats.value.usuarios = usuariosData.data?.length || 0;

    stats.value.ventas = 0; // Placeholder hasta implementar ventas
  } catch (error) {
    console.error('Error al cargar estadísticas:', error);
  }
};

onMounted(() => {
  cargarEstadisticas();
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
</style>
