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
          <a :href="getAppUrl('/carrito')" class="navbar-icon" title="Carrito">
            <ShoppingCart :size="24" />
          </a>
        </div>
      </div>
    </nav>

    <!-- Formulario de Registro -->
    <section style="background-color: var(--color-bg); padding: 3rem 0;">
      <div class="container">
        <div class="auth-container">
          <div class="card" style="max-width: 600px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 2rem;">
              <h1 class="section-title">Crear Cuenta</h1>
              <p class="section-subtitle">Completa tus datos para registrarte</p>
            </div>

            <form @submit.prevent="handleRegister">
              <div class="grid grid-cols-2" style="gap: 1.5rem;">
                <div class="form-group">
                  <label for="nombre">Nombre</label>
                  <input 
                    type="text" 
                    id="nombre" 
                    v-model="form.nombre" 
                    required
                    class="form-input"
                    placeholder="Tu nombre"
                  >
                  <span v-if="errors.nombre" class="error-message">{{ errors.nombre }}</span>
                </div>

                <div class="form-group">
                  <label for="apellido">Apellido</label>
                  <input 
                    type="text" 
                    id="apellido" 
                    v-model="form.apellido" 
                    required
                    class="form-input"
                    placeholder="Tu apellido"
                  >
                  <span v-if="errors.apellido" class="error-message">{{ errors.apellido }}</span>
                </div>

                <div class="form-group">
                  <label for="ci">Carnet de Identidad</label>
                  <input 
                    type="text" 
                    id="ci" 
                    v-model="form.ci" 
                    required
                    class="form-input"
                    placeholder="Número de CI"
                  >
                  <span v-if="errors.ci" class="error-message">{{ errors.ci }}</span>
                </div>

                <div class="form-group">
                  <label for="telefono">Teléfono</label>
                  <input 
                    type="tel" 
                    id="telefono" 
                    v-model="form.telefono"
                    required
                    class="form-input"
                    placeholder="Número de teléfono"
                  >
                  <span v-if="errors.telefono" class="error-message">{{ errors.telefono }}</span>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
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

                <div class="form-group">
                  <label for="password">Contraseña</label>
                  <input 
                    type="password" 
                    id="password" 
                    v-model="form.password" 
                    required
                    class="form-input"
                    placeholder="Mínimo 6 caracteres"
                  >
                  <span v-if="errors.password" class="error-message">{{ errors.password }}</span>
                </div>

                <div class="form-group">
                  <label for="password_confirmation">Confirmar Contraseña</label>
                  <input 
                    type="password" 
                    id="password_confirmation" 
                    v-model="form.password_confirmation" 
                    required
                    class="form-input"
                    placeholder="Repite tu contraseña"
                  >
                  <span v-if="errors.password_confirmation" class="error-message">{{ errors.password_confirmation }}</span>
                </div>
              </div>

              <div v-if="errorMessage" class="alert alert-error" style="margin-top: 1.5rem;">
                {{ errorMessage }}
              </div>

              <div v-if="successMessage" class="alert alert-success" style="margin-top: 1.5rem;">
                {{ successMessage }}
              </div>

              <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 1rem;" :disabled="loading">
                {{ loading ? 'Registrando...' : 'Registrarse' }}
              </button>

              <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid var(--color-border);">
                <p style="color: var(--color-text); margin-bottom: 0.5rem;">
                  ¿Ya tienes cuenta? <a :href="getAppUrl('/login')" style="color: var(--color-primary); text-decoration: none; font-weight: 600;">Inicia sesión</a>
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
  nombre: '',
  apellido: '',
  ci: '',
  telefono: '',
  correo: '',
  password: '',
  password_confirmation: ''
});

const errors = ref({});
const errorMessage = ref('');
const successMessage = ref('');
const loading = ref(false);
const contadorVisitas = ref(0);
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
    const response = await apiFetch('/api/visitas/register', {
      method: 'POST'
    });
    const data = await response.json();
    contadorVisitas.value = data.visitas || 0;
  } catch (error) {
    console.error('Error al registrar visita:', error);
  }
};

registrarVisita();

const handleRegister = async () => {
  loading.value = true;
  errors.value = {};
  errorMessage.value = '';
  successMessage.value = '';

  if (form.value.password !== form.value.password_confirmation) {
    errors.value.password_confirmation = 'Las contraseñas no coinciden';
    loading.value = false;
    return;
  }

  if (form.value.password.length < 6) {
    errors.value.password = 'La contraseña debe tener al menos 6 caracteres';
    loading.value = false;
    return;
  }

  try {
    const response = await apiFetch('/api/register', {
      method: 'POST',
      headers: {
        'Accept': 'application/json'
      },
      body: JSON.stringify(form.value)
    });

    const data = await response.json();

    if (response.ok && data.success) {
      successMessage.value = 'Registro exitoso. Redirigiendo...';
      setTimeout(() => {
        window.location.href = getAppUrl('/login');
      }, 2000);
    } else {
      errorMessage.value = data.message || 'Error al registrar. Por favor intenta nuevamente.';
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

.form-group {
  margin-bottom: 0;
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
