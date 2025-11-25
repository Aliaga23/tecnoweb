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
      <div class="container navbar-content">
        <a :href="getAppUrl('/')" class="navbar-logo">MOTO<span>PARTS</span></a>
        
        <ul class="navbar-menu">
          <li><a :href="getAppUrl('/dashboard')" class="navbar-link">Dashboard</a></li>
          <li><a :href="getAppUrl('/dashboard/productos')" class="navbar-link">Productos</a></li>
          <li><a :href="getAppUrl('/dashboard/categorias')" class="navbar-link">Categorías</a></li>
          <li><a :href="getAppUrl('/dashboard/usuarios')" class="navbar-link">Usuarios</a></li>
          <li><a :href="getAppUrl('/dashboard/ventas')" class="navbar-link">Ventas</a></li>
        </ul>

        <div class="navbar-controls">
          <span style="color: var(--color-text); margin-right: 1rem;">{{ usuario?.nombre }}</span>
          <div class="user-menu-container">
            <button @click="toggleUserMenu" class="navbar-icon" title="Mi cuenta">
              <User :size="24" />
            </button>
            <div v-if="userMenuOpen" class="user-menu">
              <a :href="getAppUrl('/perfil')" class="user-menu-item">Mi perfil</a>
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
          <h1 class="section-title" style="margin-bottom: 0;">Gestión de Usuarios</h1>
          <button @click="abrirModalCrear" class="btn btn-primary">Nuevo Usuario</button>
        </div>

        <!-- Tabla -->
        <div class="card" style="padding: 0; overflow: hidden;">
          <table style="width: 100%; border-collapse: collapse;">
            <thead style="background-color: var(--color-bg-alt);">
              <tr>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Nombre</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">CI</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Correo</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Teléfono</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Rol</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in usuarios" :key="user.id" style="border-top: 1px solid var(--color-border);">
                <td style="padding: 1rem; color: var(--color-text);">{{ user.nombre }} {{ user.apellido }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ user.ci }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ user.correo }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ user.telefono }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ user.rol }}</td>
                <td style="padding: 1rem;">
                  <button @click="abrirModalEditar(user)" style="color: var(--color-primary); background: none; border: none; cursor: pointer; margin-right: 1rem;">Editar</button>
                  <button @click="eliminar(user.id)" style="color: var(--color-primary); background: none; border: none; cursor: pointer;">Eliminar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- Modal Crear/Editar -->
    <div v-if="modalAbierto" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;">
      <div class="card" style="width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <h2 style="font-size: 1.5rem; font-weight: bold; color: var(--color-text); margin-bottom: 1.5rem;">
          {{ editando ? 'Editar Usuario' : 'Nuevo Usuario' }}
        </h2>

        <form @submit.prevent="guardar">
          <div class="grid grid-cols-2" style="gap: 1rem; margin-bottom: 1rem;">
            <div class="form-group">
              <label>Nombre</label>
              <input v-model="form.nombre" type="text" required class="form-input">
            </div>
            <div class="form-group">
              <label>Apellido</label>
              <input v-model="form.apellido" type="text" required class="form-input">
            </div>
          </div>

          <div class="grid grid-cols-2" style="gap: 1rem; margin-bottom: 1rem;">
            <div class="form-group">
              <label>CI</label>
              <input v-model="form.ci" type="text" required class="form-input">
            </div>
            <div class="form-group">
              <label>Teléfono</label>
              <input v-model="form.telefono" type="text" required class="form-input">
            </div>
          </div>

          <div class="form-group" style="margin-bottom: 1rem;">
            <label>Correo</label>
            <input v-model="form.correo" type="email" required class="form-input">
          </div>

          <div class="form-group" style="margin-bottom: 1rem;">
            <label>Rol</label>
            <select v-model="form.rol_id" required class="form-input">
              <option value="1">Propietario</option>
              <option value="2">Vendedor</option>
              <option value="3">Cliente</option>
            </select>
          </div>

          <div class="form-group" style="margin-bottom: 1.5rem;" v-if="!editando">
            <label>Contraseña</label>
            <input v-model="form.password" type="password" required class="form-input">
          </div>

          <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button type="button" @click="cerrarModal" class="btn btn-secondary">Cancelar</button>
            <button type="submit" class="btn btn-primary">{{ editando ? 'Actualizar' : 'Crear' }}</button>
          </div>
        </form>
      </div>
    </div>

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
import { useApi } from '../composables/useApi';
const { apiFetch, getAppUrl } = useApi();

// Accesibilidad (mismo código que Dashboard)
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

// Usuario
const usuario = ref(null);
const userMenuOpen = ref(false);

const usuarioData = localStorage.getItem('user');
if (usuarioData) {
  try {
    usuario.value = JSON.parse(usuarioData);
  } catch (error) {
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

// CRUD
const usuarios = ref([]);
const modalAbierto = ref(false);
const editando = ref(false);
const form = ref({
  id: null,
  nombre: '',
  apellido: '',
  ci: '',
  telefono: '',
  correo: '',
  password: '',
  rol_id: '3'
});

const cargarUsuarios = async () => {
  const token = localStorage.getItem('token');
  try {
    const response = await apiFetch('/api/usuarios', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const data = await response.json();
    usuarios.value = (data.data || []).map(u => ({
      ...u,
      rol: u.rol?.nombre || 'Sin rol'
    }));
  } catch (error) {
    console.error('Error al cargar usuarios:', error);
  }
};

const abrirModalCrear = () => {
  editando.value = false;
  form.value = {
    id: null,
    nombre: '',
    apellido: '',
    ci: '',
    telefono: '',
    correo: '',
    password: '',
    rol_id: '3'
  };
  modalAbierto.value = true;
};

const abrirModalEditar = (user) => {
  editando.value = true;
  form.value = {
    id: user.id,
    nombre: user.nombre,
    apellido: user.apellido,
    ci: user.ci,
    telefono: user.telefono,
    correo: user.correo,
    password: '',
    rol_id: user.rol_id?.toString() || '3'
  };
  modalAbierto.value = true;
};

const cerrarModal = () => {
  modalAbierto.value = false;
};

const guardar = async () => {
  const token = localStorage.getItem('token');
  const url = editando.value ? `/api/usuarios/${form.value.id}` : '/api/usuarios';
  const method = editando.value ? 'PUT' : 'POST';

  try {
    const response = await fetch(url, {
      method,
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(form.value)
    });

    if (response.ok) {
      alert(editando.value ? 'Usuario actualizado' : 'Usuario creado');
      cerrarModal();
      cargarUsuarios();
    } else {
      alert('Error al guardar usuario');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error al guardar usuario');
  }
};

const eliminar = async (id) => {
  if (!confirm('¿Estás seguro de eliminar este usuario?')) return;

  const token = localStorage.getItem('token');
  try {
    const response = await apiFetch(`/api/usuarios/${id}`, {
      method: 'DELETE',
      headers: { 'Authorization': `Bearer ${token}` }
    });

    if (response.ok) {
      alert('Usuario eliminado');
      cargarUsuarios();
    } else {
      alert('Error al eliminar usuario');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error al eliminar usuario');
  }
};

onMounted(() => {
  cargarUsuarios();
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
</style>
