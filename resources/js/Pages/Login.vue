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
          <li><a href="/#productos" class="navbar-link">Productos</a></li>
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
                <button @click="cerrarSesion" class="user-menu-item">Cerrar sesión</button>
              </div>
            </div>
          </template>
          <template v-else>
            <a :href="getAppUrl('/register')" class="btn btn-secondary">Registrarse</a>
            <a :href="getAppUrl('/login')" class="btn btn-primary">Ingresar</a>
          </template>
          <a :href="getAppUrl('/carrito')" class="navbar-icon" title="Carrito">
            <ShoppingCart :size="24" />
          </a>
        </div>
      </div>
    </nav>

    <!-- Formulario de Login -->
    <section style="background-color: var(--color-bg); padding: 3rem 0;">
      <div class="container">
        <div class="auth-container">
          <div class="card" style="max-width: 500px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 2rem;">
              <h1 class="section-title">Iniciar Sesión</h1>
              <p class="section-subtitle">Ingresa tus credenciales para continuar</p>
            </div>

            <form @submit.prevent="handleLogin">
              <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="correo">Correo Electrónico</label>
                <input 
                  type="email" 
                  id="correo" 
                  v-model="form.correo" 
                  required
                  class="form-input"
                  placeholder="tu@email.com"
                >
                <span v-if="errors.correo" class="error-message">{{ errors.correo }}</span>
              </div>

              <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="password">Contraseña</label>
                <input 
                  type="password" 
                  id="password" 
                  v-model="form.password" 
                  required
                  class="form-input"
                  placeholder="Tu contraseña"
                >
                <span v-if="errors.password" class="error-message">{{ errors.password }}</span>
              </div>

              <div v-if="errorMessage" class="alert alert-error" style="margin-bottom: 1.5rem;">
                {{ errorMessage }}
              </div>

              <div v-if="successMessage" class="alert alert-success" style="margin-bottom: 1.5rem;">
                {{ successMessage }}
              </div>

              <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;" :disabled="loading">
                {{ loading ? 'Ingresando...' : 'Ingresar' }}
              </button>

              <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid var(--color-border);">
                <p style="color: var(--color-text); margin-bottom: 0.5rem;">
                  ¿No tienes cuenta? <a :href="getAppUrl('/register')" style="color: var(--color-primary); text-decoration: none; font-weight: 600;">Regístrate</a>
                </p>
                <a :href="getAppUrl('/')" style="color: var(--color-primary); text-decoration: none; font-weight: 600;">Volver al inicio</a>
              </div>
            </form>
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
import { ref, computed, watch } from 'vue';
import { ShoppingCart, User } from 'lucide-vue-next';
import { useApi } from '../composables/useApi';
const { apiFetch, getAppUrl } = useApi();

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

// Formulario
const form = ref({
  correo: '',
  password: ''
});

const errors = ref({});
const contadorVisitas = ref(0);
const errorMessage = ref('');
const successMessage = ref('');
const loading = ref(false);
const usuario = ref(null);
const userMenuOpen = ref(false);

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

const registrarVisita = async () => {
  try {
    const response = await apiFetch('/api/visitas/login', {
      method: 'POST'
    });
    const data = await response.json();
    contadorVisitas.value = data.visitas || 0;
  } catch (error) {
    console.error('Error al registrar visita:', error);
  }
};

registrarVisita();

const handleLogin = async () => {
  loading.value = true;
  errors.value = {};
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const response = await apiFetch('/api/login', {
      method: 'POST',
      headers: {
        'Accept': 'application/json'
      },
      body: JSON.stringify(form.value)
    });

    const data = await response.json();

    if (response.ok && data.success) {
      // Guardar token
      localStorage.setItem('token', data.data.token);
      localStorage.setItem('user', JSON.stringify(data.data.usuario));
      
      successMessage.value = 'Inicio de sesión exitoso. Redirigiendo...';
      
      // Redirigir según rol
      setTimeout(() => {
        const rolNombre = data.data.usuario.rol?.nombre || data.data.usuario.rol;
        if (rolNombre === 'Propietario' || rolNombre === 'Vendedor') {
          window.location.href = getAppUrl('/dashboard');
        } else {
          window.location.href = getAppUrl('/');
        }
      }, 1500);
    } else {
      errorMessage.value = data.message || 'Credenciales incorrectas. Por favor intenta nuevamente.';
      if (data.errors) {
        errors.value = data.errors;
      }
    }
  } catch (error) {
    errorMessage.value = 'Error de conexión. Por favor intenta nuevamente.';
  } finally {
    loading.value = false;
  }
};
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

.auth-container {
  padding: 2rem 0;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: var(--color-text);
}

.form-input {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid var(--color-border);
  border-radius: 0.5rem;
  font-size: 1rem;
  color: var(--color-text);
  background-color: var(--color-bg);
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: var(--color-primary);
}

.error-message {
  display: block;
  color: var(--color-primary);
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.alert {
  padding: 1rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
}

.alert-error {
  background-color: #fee2e2;
  color: #991b1b;
  border: 2px solid #fecaca;
}

.alert-success {
  background-color: #d1fae5;
  color: #065f46;
  border: 2px solid #a7f3d0;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
