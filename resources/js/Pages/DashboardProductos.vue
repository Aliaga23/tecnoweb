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

    <!-- Content -->
    <section class="section" style="background-color: var(--color-bg);">
      <div style="padding: 0 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
          <h1 class="section-title" style="margin-bottom: 0;">Gestión de Productos</h1>
          <button @click="abrirModalCrear" class="btn btn-primary">Nuevo Producto</button>
        </div>

        <!-- Tabla -->
        <div class="card" style="padding: 0; overflow: hidden;">
          <table style="width: 100%; border-collapse: collapse;">
            <thead style="background-color: var(--color-bg-alt);">
              <tr>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Nombre</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Descripción</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Precio</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Stock</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Categoría</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="producto in productos" :key="producto.id" style="border-top: 1px solid var(--color-border);">
                <td style="padding: 1rem; color: var(--color-text);">{{ producto.nombre }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ producto.descripcion }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">Bs. {{ producto.precio }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ producto.stock }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ producto.categoria?.nombre || 'Sin categoría' }}</td>
                <td style="padding: 1rem;">
                  <button @click="abrirModalEditar(producto)" style="color: var(--color-primary); background: none; border: none; cursor: pointer; margin-right: 1rem;">Editar</button>
                  <button @click="eliminar(producto.id)" style="color: var(--color-primary); background: none; border: none; cursor: pointer;">Eliminar</button>
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
          {{ editando ? 'Editar Producto' : 'Nuevo Producto' }}
        </h2>

        <form @submit.prevent="guardar">
          <div class="form-group" style="margin-bottom: 1rem;">
            <label>Nombre</label>
            <input v-model="form.nombre" type="text" required class="form-input">
          </div>

          <div class="form-group" style="margin-bottom: 1rem;">
            <label>Descripción</label>
            <textarea v-model="form.descripcion" rows="3" class="form-input"></textarea>
          </div>

          <div class="grid grid-cols-2" style="gap: 1rem; margin-bottom: 1rem;">
            <div class="form-group">
              <label>Precio (Bs.)</label>
              <input v-model="form.precio" type="number" step="0.01" required class="form-input">
            </div>
            <div class="form-group">
              <label>Stock</label>
              <input v-model="form.stock" type="number" required class="form-input">
            </div>
          </div>

          <div class="form-group" style="margin-bottom: 1rem;">
            <label>Categoría</label>
            <select v-model="form.categoria_id" required class="form-input">
              <option value="">Seleccione una categoría</option>
              <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
            </select>
          </div>

          <div class="form-group" style="margin-bottom: 1.5rem;">
            <label>Imagen del Producto</label>
            <input @change="handleImageUpload" type="file" accept="image/*" class="form-input" style="padding: 0.5rem;">
            <p v-if="form.imagen" style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--color-text-light);">Imagen actual: {{ form.imagen }}</p>
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

// Usuario
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

// Dropdowns
const dropdownOpen = ref(null);

const toggleDropdown = (menu) => {
  dropdownOpen.value = dropdownOpen.value === menu ? null : menu;
};

// CRUD
const productos = ref([]);
const categorias = ref([]);
const modalAbierto = ref(false);
const editando = ref(false);
const form = ref({
  id: null,
  nombre: '',
  descripcion: '',
  precio: '',
  stock: '',
  categoria_id: '',
  imagen: ''
});

const imagenFile = ref(null);

const cargarProductos = async () => {
  const token = localStorage.getItem('token');
  try {
    const response = await apiFetch('/api/productos');
    const data = await response.json();
    productos.value = data.data || [];
  } catch (error) {
    console.error('Error al cargar productos:', error);
  }
};

const cargarCategorias = async () => {
  const token = localStorage.getItem('token');
  try {
    const response = await apiFetch('/api/categorias');
    const data = await response.json();
    categorias.value = data.data || [];
  } catch (error) {
    console.error('Error al cargar categorías:', error);
  }
};

const abrirModalCrear = () => {
  editando.value = false;
  form.value = {
    id: null,
    nombre: '',
    descripcion: '',
    precio: '',
    stock: '',
    categoria_id: '',
    imagen: ''
  };
  modalAbierto.value = true;
};

const abrirModalEditar = (producto) => {
  editando.value = true;
  form.value = {
    id: producto.id,
    nombre: producto.nombre,
    descripcion: producto.descripcion,
    precio: producto.precio,
    stock: producto.stock,
    categoria_id: producto.categoria_id?.toString() || '',
    imagen: producto.imagen || ''
  };
  modalAbierto.value = true;
};

const cerrarModal = () => {
  modalAbierto.value = false;
  imagenFile.value = null;
};

const handleImageUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    imagenFile.value = file;
  }
};

const guardar = async () => {
  const token = localStorage.getItem('token');
  const url = editando.value ? `/api/productos/${form.value.id}` : '/api/productos';
  const method = editando.value ? 'PUT' : 'POST';

  try {
    let imagenUrl = form.value.imagen;

    // Si hay una nueva imagen, subirla primero
    if (imagenFile.value) {
      const formData = new FormData();
      formData.append('imagen', imagenFile.value);

      const uploadResponse = await fetch(getAppUrl('/api/upload-imagen'), {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
        },
        body: formData
      });

      if (uploadResponse.ok) {
        const uploadData = await uploadResponse.json();
        imagenUrl = uploadData.url;
      } else {
        alert('Error al subir la imagen');
        return;
      }
    }

    // Mapear los campos del frontend a los nombres esperados por el backend
    const payload = {
      nombre: form.value.nombre,
      descripcion: form.value.descripcion,
      stock_actual: form.value.stock,
      precio_unitario: form.value.precio,
      imagen_url: imagenUrl,
      categoria_id: form.value.categoria_id
    };

    const response = await apiFetch(url, {
      method,
      body: JSON.stringify(payload)
    });

    if (response.ok) {
      alert(editando.value ? 'Producto actualizado' : 'Producto creado');
      cerrarModal();
      cargarProductos();
    } else {
      alert('Error al guardar producto');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error al guardar producto');
  }
};

const eliminar = async (id) => {
  if (!confirm('¿Estás seguro de eliminar este producto?')) return;

  const token = localStorage.getItem('token');
  try {
    const response = await apiFetch(`/api/productos/${id}`, {
      method: 'DELETE'
    });

    if (response.ok) {
      alert('Producto eliminado');
      cargarProductos();
    } else {
      alert('Error al eliminar producto');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error al eliminar producto');
  }
};

const registrarVisita = async () => {
  try {
    const response = await apiFetch('/api/visitas/dashboard-productos', {
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
  // Verificar rol del usuario
  const userData = localStorage.getItem('user');
  if (userData) {
    try {
      const user = JSON.parse(userData);
      if (user.rol !== 'Propietario' && user.rol !== 'Vendedor') {
        window.location.href = getAppUrl('/');
        return;
      }
    } catch (error) {
      console.error('Error al verificar usuario:', error);
      window.location.href = getAppUrl('/');
      return;
    }
  } else {
    window.location.href = getAppUrl('/login');
    return;
  }
  
  await registrarVisita();
  cargarProductos();
  cargarCategorias();
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
