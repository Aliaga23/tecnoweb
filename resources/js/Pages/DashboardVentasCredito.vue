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
          <h1 class="section-title" style="margin-bottom: 0;">Gestión de Ventas al Crédito</h1>
          <button @click="abrirModalCrearVenta" class="btn btn-primary">Nueva Venta al Crédito</button>
        </div>

        <!-- Sección Ventas al Crédito -->
        <div class="card" style="padding: 0; overflow: hidden;">
          <table style="width: 100%; border-collapse: collapse;">
            <thead style="background-color: var(--color-bg-alt);">
              <tr>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">ID</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Cliente</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Fecha</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Total</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Pagado</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Saldo</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Estado</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--color-text);">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="venta in ventas" :key="venta.id" style="border-top: 1px solid var(--color-border);">
                <td style="padding: 1rem; color: var(--color-text);">#{{ venta.id }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ venta.cliente_nombre }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">{{ formatDate(venta.fecha_venta) }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">Bs. {{ venta.total }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">Bs. {{ venta.monto_pagado }}</td>
                <td style="padding: 1rem; color: var(--color-text-light);">Bs. {{ venta.saldo_pendiente }}</td>
                <td style="padding: 1rem;">
                  <span :style="{ 
                    padding: '0.25rem 0.75rem', 
                    borderRadius: '9999px', 
                    fontSize: '0.75rem', 
                    fontWeight: '600',
                    color: 'var(--color-text-light)'
                  }">
                    {{ venta.estado === 'pagada' ? 'Pagada' : 'Pendiente' }}
                  </span>
                </td>
                <td style="padding: 1rem;">
                  <button @click="verDetalleVenta(venta)" style="color: #dc2626; background: none; border: none; cursor: pointer; margin-right: 0.75rem;">Ver detalle</button>
                  <button 
                    v-if="venta.saldo_pendiente > 0"
                    @click="abrirModalRegistrarPagoDirecto(venta)" 
                    style="color: #dc2626; background: none; border: none; cursor: pointer;"
                  >
                    Registrar Pago
                  </button>
                </td>
              </tr>
              <tr v-if="ventas.length === 0">
                <td colspan="8" style="padding: 2rem; text-align: center; color: var(--color-text-light);">
                  No hay ventas al crédito registradas
                </td>
              </tr>
            </tbody>
            </table>
        </div>
      </div>
    </section>

    <!-- Modal Detalle Venta -->
    <div v-if="modalDetalleAbierto" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem;">
      <div class="card" style="width: 100%; max-width: 600px; max-height: fit-content; overflow: visible;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.75rem;">
          <h2 style="font-size: 1.25rem; font-weight: bold; color: var(--color-text); margin: 0;">
            Venta al Crédito #{{ ventaSeleccionada?.id }}
          </h2>
          <button @click="cerrarModal" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--color-text);">&times;</button>
        </div>

        <div v-if="ventaSeleccionada">
          <!-- Información básica -->
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; margin-bottom: 1rem; padding: 0.75rem; background-color: var(--color-bg-alt); border-radius: 0.5rem;">
            <div>
              <strong style="color: var(--color-text);">Cliente:</strong>
              <span style="color: var(--color-text-light); margin-left: 0.5rem;">{{ ventaSeleccionada.cliente_nombre }}</span>
            </div>
            <div>
              <strong style="color: var(--color-text);">Fecha:</strong>
              <span style="color: var(--color-text-light); margin-left: 0.5rem;">{{ formatDate(ventaSeleccionada.fecha_venta) }}</span>
            </div>
            <div>
              <strong style="color: var(--color-text);">Total:</strong>
              <span style="color: var(--color-text-light); margin-left: 0.5rem;">Bs. {{ ventaSeleccionada.total }}</span>
            </div>
            <div>
              <strong style="color: var(--color-text);">Estado:</strong>
              <span :style="{ 
                marginLeft: '0.5rem',
                padding: '0.25rem 0.75rem', 
                borderRadius: '9999px', 
                fontSize: '0.75rem', 
                fontWeight: '600',
                color: 'var(--color-text-light)'
              }">
                {{ ventaSeleccionada.estado === 'pagada' ? 'Pagada' : 'Pendiente' }}
              </span>
            </div>
            <div>
              <strong style="color: var(--color-text);">Monto Pagado:</strong>
              <span style="color: var(--color-text-light); margin-left: 0.5rem; font-weight: 600;">Bs. {{ ventaSeleccionada.monto_pagado }}</span>
            </div>
            <div>
              <strong style="color: var(--color-text);">Saldo Pendiente:</strong>
              <span style="color: var(--color-text-light); margin-left: 0.5rem; font-weight: 600;">Bs. {{ ventaSeleccionada.saldo_pendiente }}</span>
            </div>
          </div>

          <!-- Historial de pagos -->
          <div v-if="ventaSeleccionada.pagos && ventaSeleccionada.pagos.length > 0" style="margin-bottom: 1rem;">
            <strong style="color: var(--color-text); display: block; margin-bottom: 0.5rem;">Historial de Pagos:</strong>
            <div style="background-color: var(--color-bg-alt); border-radius: 0.5rem; overflow: hidden;">
              <table style="width: 100%; border-collapse: collapse;">
                <thead style="background-color: var(--color-border);">
                  <tr>
                    <th style="padding: 0.5rem; text-align: left; font-size: 0.875rem; color: var(--color-text);">Fecha</th>
                    <th style="padding: 0.5rem; text-align: center; font-size: 0.875rem; color: var(--color-text);">Monto</th>
                    <th style="padding: 0.5rem; text-align: center; font-size: 0.875rem; color: var(--color-text);">Método</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="pago in ventaSeleccionada.pagos" :key="pago.id">
                    <td style="padding: 0.5rem; color: var(--color-text); font-size: 0.875rem;">{{ formatDate(pago.fecha_pago) }}</td>
                    <td style="padding: 0.5rem; text-align: center; color: var(--color-text); font-size: 0.875rem; font-weight: 600;">Bs. {{ pago.monto }}</td>
                    <td style="padding: 0.5rem; text-align: center; color: var(--color-text); font-size: 0.875rem; text-transform: capitalize;">{{ pago.metodo }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div style="display: flex; justify-content: flex-end; border-top: 1px solid var(--color-border); padding-top: 0.75rem; margin-top: 1rem;">
          <button @click="cerrarModal" class="btn btn-secondary">Cerrar</button>
        </div>
      </div>
    </div>

 <!-- Modal Registrar Pago -->
<div v-if="modalPagoAbierto" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000; padding: 1rem;">
  <div class="card" style="width: 100%; max-width: 400px; max-height: fit-content; overflow: visible; padding: 1.25rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.75rem;">
      <h2 style="font-size: 1.125rem; font-weight: bold; color: var(--color-text); margin: 0;">
        Registrar Pago - Venta #{{ ventaSeleccionada?.id }}
      </h2>
      <button @click="cerrarModalPago" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-text); line-height: 1; padding: 0;">&times;</button>
    </div>

    <!-- Formulario de pago -->
    <div v-if="estadoPagoQR === 'formulario'">
      <div style="background-color: var(--color-bg-alt); padding: 0.75rem; border-radius: 0.375rem; margin-bottom: 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <span style="color: var(--color-text); font-size: 0.9375rem;">Saldo pendiente:</span>
          <span style="color: var(--color-text-light); font-weight: 700; font-size: 1.25rem;">Bs. {{ ventaSeleccionada?.saldo_pendiente }}</span>
        </div>
      </div>

      <div style="margin-bottom: 1rem;">
        <label style="display: block; margin-bottom: 0.5rem; color: var(--color-text); font-size: 0.9375rem; font-weight: 500;">Monto a pagar:</label>
        <input 
          v-model.number="montoPago" 
          type="number" 
          min="0.01" 
          :max="ventaSeleccionada?.saldo_pendiente"
          step="0.01"
          placeholder="0.00"
          style="width: 100%; padding: 0.625rem; border: 1px solid var(--color-border); border-radius: 0.375rem; background-color: var(--color-bg); color: var(--color-text); font-size: 1rem;"
        >
        <p v-if="montoPago > 0 && montoPago <= ventaSeleccionada?.saldo_pendiente" style="margin-top: 0.375rem; margin-bottom: 0; color: var(--color-text); font-size: 0.8125rem;">
          ✓ Saldo restante: Bs. {{ (ventaSeleccionada?.saldo_pendiente - montoPago).toFixed(2) }}
        </p>
        <p v-if="montoPago > ventaSeleccionada?.saldo_pendiente" style="margin-top: 0.375rem; margin-bottom: 0; color: #dc2626; font-size: 0.8125rem;">
          ✗ El monto excede el saldo
        </p>
      </div>

      <div style="margin-bottom: 1.25rem;">
        <label style="display: block; margin-bottom: 0.5rem; color: var(--color-text); font-size: 0.9375rem; font-weight: 500;">Método de pago:</label>
        <select v-model="metodoPago" style="width: 100%; padding: 0.625rem; border: 1px solid var(--color-border); border-radius: 0.375rem; background-color: var(--color-bg); color: var(--color-text); font-size: 1rem;">
          <option value="">Seleccione método</option>
          <option value="efectivo">Efectivo</option>
          <option value="qr">QR</option>
          <option value="tarjeta">Tarjeta</option>
        </select>
      </div>

      <div style="display: flex; gap: 0.75rem; justify-content: flex-end; border-top: 1px solid var(--color-border); padding-top: 1rem;">
        <button @click="cerrarModalPago" class="btn btn-secondary" style="padding: 0.625rem 1.25rem; font-size: 0.9375rem;">Cancelar</button>
        <button 
          @click="registrarPago" 
          class="btn btn-primary" 
          style="padding: 0.625rem 1.25rem; font-size: 0.9375rem;"
          :disabled="!montoPago || montoPago <= 0 || montoPago > ventaSeleccionada?.saldo_pendiente || !metodoPago || procesandoPago"
        >
          {{ procesandoPago ? 'Procesando...' : 'Registrar' }}
        </button>
      </div>
    </div>

    <!-- Vista QR generando -->
    <div v-else-if="estadoPagoQR === 'generando'" style="text-align: center; padding: 2rem;">
      <div style="margin-bottom: 1rem; color: var(--color-text); font-size: 1.125rem;">Generando código QR...</div>
    </div>

    <!-- Vista QR para escanear -->
    <div v-else-if="estadoPagoQR === 'escaneando'" style="text-align: center;">
      <div style="margin-bottom: 1rem;">
        <img :src="pagoQRInfo?.qr_image" alt="Código QR" style="max-width: 300px; width: 100%; height: auto; border-radius: 0.5rem;">
      </div>
      <div style="background-color: var(--color-bg-alt); padding: 1rem; border-radius: 0.375rem; margin-bottom: 1rem;">
        <p style="margin: 0 0 0.5rem 0; color: var(--color-text); font-weight: 600;">Monto a pagar:</p>
        <p style="margin: 0; color: var(--color-primary); font-size: 1.5rem; font-weight: bold;">Bs. {{ montoPago.toFixed(2) }}</p>
      </div>
      <p style="color: var(--color-text); margin-bottom: 0.5rem;">Escanea el código QR con tu app de pagos</p>
      <p style="color: var(--color-text-light); font-size: 0.875rem;">Esperando confirmación del pago...</p>
    </div>

    <!-- Vista pago completado -->
    <div v-else-if="estadoPagoQR === 'completado'" style="text-align: center; padding: 2rem;">
      <div style="color: #10b981; font-size: 3rem; margin-bottom: 1rem;">✓</div>
      <div style="color: var(--color-text); font-size: 1.25rem; font-weight: bold;">¡Pago completado!</div>
    </div>
  </div>
</div>

    <!-- Modal Nueva Venta al Crédito -->
    <div v-if="modalCrearAbierto" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem;">
      <div class="card" :style="pasoActual === 1 
          ? { width: '100%', maxWidth: '500px', maxHeight: 'fit-content', overflow: 'visible' }
          : { width: '100%', maxWidth: '600px', maxHeight: '80vh', overflowY: 'auto' }">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.75rem;">
          <h2 style="font-size: 1.25rem; font-weight: bold; color: var(--color-text); margin: 0;">
            Nueva Venta al Crédito
          </h2>
          <button @click="cerrarModalCrear" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--color-text);">&times;</button>
        </div>

        <!-- Paso 1: Buscar cliente -->
        <div v-if="pasoActual === 1" style="margin-bottom: 1rem;">
          <h3 style="color: var(--color-text); margin-bottom: 1rem;">Buscar Cliente</h3>
          <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
            <input 
              v-model="busquedaCI" 
              type="text" 
              placeholder="Ingrese CI del cliente"
              style="flex: 1; padding: 0.5rem; border: 1px solid var(--color-border); border-radius: 0.25rem;"
              @keyup.enter="buscarCliente"
            >
            <button @click="buscarCliente" class="btn btn-primary" :disabled="!busquedaCI || cargandoCliente">
              {{ cargandoCliente ? 'Buscando...' : 'Buscar' }}
            </button>
          </div>

          <div v-if="clienteEncontrado" style="background-color: var(--color-bg-alt); padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem;">
            <h4 style="color: var(--color-text); margin-bottom: 0.5rem; font-size: 0.95rem;">Cliente Encontrado:</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 0.75rem;">
              <p style="margin: 0; color: var(--color-text); font-size: 0.875rem;"><strong>Nombre:</strong> {{ clienteEncontrado.nombre }} {{ clienteEncontrado.apellido }}</p>
              <p style="margin: 0; color: var(--color-text); font-size: 0.875rem;"><strong>CI:</strong> {{ clienteEncontrado.ci }}</p>
              <p style="margin: 0; color: var(--color-text); font-size: 0.875rem;"><strong>Teléfono:</strong> {{ clienteEncontrado.telefono }}</p>
              <p style="margin: 0; color: var(--color-text); font-size: 0.875rem;"><strong>Correo:</strong> {{ clienteEncontrado.correo }}</p>
            </div>
            <button @click="pasoActual = 2" class="btn btn-primary" style="padding: 0.5rem 1rem; width: 100%;">Continuar con la venta</button>
          </div>

          <div v-else-if="busquedaRealizada && !cargandoCliente" style="background-color: var(--color-text-light-bg); padding: 0.75rem; border-radius: 0.5rem; text-align: center; color: var(--color-text-light);">
            No se encontró un cliente con ese CI
          </div>
        </div>

        <!-- Paso 2: Seleccionar productos -->
        <div v-if="pasoActual === 2" style="margin-bottom: 1rem;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="color: var(--color-text); margin: 0;">Agregar Productos</h3>
            <button @click="pasoActual = 1" class="btn btn-secondary" style="padding: 0.25rem 0.5rem;">Volver</button>
          </div>

          <!-- Selector de productos -->
          <div style="background-color: var(--color-bg-alt); padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem;">
            <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 0.75rem; align-items: end;">
              <div>
                <label style="display: block; margin-bottom: 0.25rem; color: var(--color-text); font-size: 0.875rem;">Producto:</label>
                <select v-model="productoSeleccionadoId" style="width: 100%; padding: 0.5rem; border: 1px solid var(--color-border); border-radius: 0.25rem; background-color: var(--color-bg); color: var(--color-text);">
                  <option value="">Seleccione un producto</option>
                  <option v-for="producto in productos" :key="producto.id" :value="producto.id">
                    {{ producto.nombre }} - Bs. {{ producto.precio }} (Stock: {{ producto.stock }})
                  </option>
                </select>
              </div>
              <div>
                <label style="display: block; margin-bottom: 0.25rem; color: var(--color-text); font-size: 0.875rem;">Cantidad:</label>
                <input 
                  v-model.number="cantidadProducto" 
                  type="number" 
                  min="1" 
                  placeholder="1"
                  style="width: 100%; padding: 0.5rem; border: 1px solid var(--color-border); border-radius: 0.25rem; background-color: var(--color-bg); color: var(--color-text);"
                >
              </div>
              <button @click="agregarProducto" class="btn btn-primary" style="padding: 0.5rem 1rem;" :disabled="!productoSeleccionadoId || !cantidadProducto">
                Agregar
              </button>
            </div>
          </div>

          <!-- Lista de productos -->
          <div v-if="productosVenta.length > 0">
            <h4 style="color: var(--color-text); margin-bottom: 0.5rem;">Productos a vender:</h4>
            <div style="background-color: var(--color-bg-alt); border-radius: 0.5rem; overflow: hidden;">
              <table style="width: 100%; border-collapse: collapse;">
                <thead style="background-color: var(--color-border);">
                  <tr>
                    <th style="padding: 0.75rem; text-align: left; font-size: 0.875rem; color: var(--color-text);">Producto</th>
                    <th style="padding: 0.75rem; text-align: center; font-size: 0.875rem; color: var(--color-text);">Cantidad</th>
                    <th style="padding: 0.75rem; text-align: right; font-size: 0.875rem; color: var(--color-text);">Precio Unit.</th>
                    <th style="padding: 0.75rem; text-align: right; font-size: 0.875rem; color: var(--color-text);">Subtotal</th>
                    <th style="padding: 0.75rem; text-align: center; font-size: 0.875rem; color: var(--color-text);">Acción</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in productosVenta" :key="index">
                    <td style="padding: 0.75rem; color: var(--color-text-light); font-size: 0.875rem;">{{ item.nombre }}</td>
                    <td style="padding: 0.75rem; text-align: center; color: var(--color-text-light); font-size: 0.875rem;">{{ item.cantidad }}</td>
                    <td style="padding: 0.75rem; text-align: right; color: var(--color-text-light); font-size: 0.875rem;">Bs. {{ item.precio_unitario }}</td>
                    <td style="padding: 0.75rem; text-align: right; color: var(--color-text-light); font-size: 0.875rem;">Bs. {{ (item.cantidad * item.precio_unitario).toFixed(2) }}</td>
                    <td style="padding: 0.75rem; text-align: center;">
                      <button @click="eliminarProducto(index)" style="color: var(--color-danger); background: none; border: none; cursor: pointer; font-size: 0.875rem;">
                        Eliminar
                      </button>
                    </td>
                  </tr>
                  <tr style="border-top: 1px solid var(--color-border); font-weight: 600;">
                    <td colspan="3" style="padding: 0.75rem; text-align: right; color: var(--color-text);">TOTAL:</td>
                    <td style="padding: 0.75rem; text-align: right; color: var(--color-text); font-size: 1rem;">Bs. {{ totalVenta.toFixed(2) }}</td>
                    <td></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Adelanto -->
          <div v-if="productosVenta.length > 0" style="background-color: var(--color-bg-alt); padding: 0.75rem; border-radius: 0.5rem; margin-top: 1rem;">
            <h4 style="color: var(--color-text); margin-bottom: 0.5rem; font-size: 0.95rem;">Adelanto (Opcional)</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
              <div>
                <label style="display: block; margin-bottom: 0.25rem; color: var(--color-text); font-size: 0.875rem;">Monto:</label>
                <input 
                  v-model.number="montoAdelanto" 
                  type="number" 
                  min="0" 
                  :max="totalVenta"
                  step="0.01"
                  placeholder="0.00"
                  style="width: 100%; padding: 0.5rem; border: 1px solid var(--color-border); border-radius: 0.25rem;"
                >
              </div>
              <div v-if="montoAdelanto > 0">
                <label style="display: block; margin-bottom: 0.25rem; color: var(--color-text); font-size: 0.875rem;">Método:</label>
                <select v-model="metodoAdelanto" style="width: 100%; padding: 0.5rem; border: 1px solid var(--color-border); border-radius: 0.25rem;">
                  <option value="">Seleccione método</option>
                  <option value="efectivo">Efectivo</option>
                  <option value="qr">QR</option>
                  <option value="tarjeta">Tarjeta</option>
                </select>
              </div>
            </div>
            <p v-if="montoAdelanto > 0" style="margin-top: 0.5rem; margin-bottom: 0; color: var(--color-text); font-size: 0.875rem; font-weight: 600;">
              Saldo pendiente: Bs. {{ (totalVenta - montoAdelanto).toFixed(2) }}
            </p>
          </div>

          <div style="display: flex; justify-content: space-between; margin-top: 1rem;">
            <button @click="pasoActual = 1" class="btn btn-secondary">Cancelar</button>
            <button @click="crearVenta" class="btn btn-primary" :disabled="productosVenta.length === 0 || procesandoVenta || (montoAdelanto > 0 && !metodoAdelanto)">
              {{ procesandoVenta ? 'Creando...' : 'Crear Venta' }}
            </button>
          </div>
        </div>
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
import { useApi } from '../composables/useApi.js';

// Configuración de accesibilidad
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

// Usuario y autenticación
const { apiFetch, getAppUrl } = useApi();
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

// Estado del dashboard - Ventas al Crédito
const ventas = ref([]);

// Modal Detalle
const modalDetalleAbierto = ref(false);
const ventaSeleccionada = ref(null);

// Modal Registrar Pago
const modalPagoAbierto = ref(false);
const montoPago = ref(0);
const metodoPago = ref('');
const procesandoPago = ref(false);
const pagoQRInfo = ref(null);
const estadoPagoQR = ref('formulario');
const intervalVerificacionQR = ref(null);

// Modal crear venta
const modalCrearAbierto = ref(false);
const pasoActual = ref(1);
const busquedaCI = ref('');
const busquedaRealizada = ref(false);
const cargandoCliente = ref(false);
const clienteEncontrado = ref(null);
const productos = ref([]);
const productoSeleccionadoId = ref('');
const cantidadProducto = ref(1);
const productosVenta = ref([]);
const montoAdelanto = ref(0);
const metodoAdelanto = ref('');
const procesandoVenta = ref(false);

const totalVenta = computed(() => {
  return productosVenta.value.reduce((sum, item) => {
    return sum + (item.cantidad * item.precio_unitario);
  }, 0);
});

const verDetalleVenta = async (venta) => {
  ventaSeleccionada.value = venta;
  modalDetalleAbierto.value = true;
};

const cerrarModal = () => {
  modalDetalleAbierto.value = false;
  ventaSeleccionada.value = null;
};

// Funciones para modal de pago
const abrirModalRegistrarPago = () => {
  montoPago.value = 0;
  metodoPago.value = '';
  modalPagoAbierto.value = true;
};

const abrirModalRegistrarPagoDirecto = (venta) => {
  ventaSeleccionada.value = venta;
  montoPago.value = 0;
  metodoPago.value = '';
  modalPagoAbierto.value = true;
};

const cerrarModalPago = () => {
  modalPagoAbierto.value = false;
  montoPago.value = 0;
  metodoPago.value = '';
  pagoQRInfo.value = null;
  estadoPagoQR.value = 'formulario';
  if (intervalVerificacionQR.value) {
    clearInterval(intervalVerificacionQR.value);
    intervalVerificacionQR.value = null;
  }
};

const registrarPago = async () => {
  if (!montoPago.value || montoPago.value <= 0 || !metodoPago.value) {
    alert('Complete todos los campos');
    return;
  }

  if (montoPago.value > ventaSeleccionada.value.saldo_pendiente) {
    alert('El monto no puede exceder el saldo pendiente');
    return;
  }

  // Si es pago con QR, generar código QR
  if (metodoPago.value === 'qr') {
    await generarQRPago();
    return;
  }

  // Para otros métodos, registrar directamente
  procesandoPago.value = true;

  try {
    const response = await apiFetch(`/api/ventas/${ventaSeleccionada.value.id}/pagos`, {
      method: 'POST',
      body: JSON.stringify({
        monto: montoPago.value,
        metodo: metodoPago.value
      })
    });

    const data = await response.json();
    if (data.success) {
      alert(data.message || 'Pago registrado exitosamente');
      cerrarModalPago();
      cerrarModal();
      await cargarVentas();
    } else {
      alert('Error al registrar pago: ' + data.message);
    }
  } catch (error) {
    console.error('Error al registrar pago:', error);
    alert('Error al registrar el pago');
  } finally {
    procesandoPago.value = false;
  }
};

const generarQRPago = async () => {
  procesandoPago.value = true;
  estadoPagoQR.value = 'generando';

  try {
    const user = usuario.value;
    const payload = {
      cliente_id: user.id,
      productos: [{
        nombre: `Pago Venta #${ventaSeleccionada.value.id}`,
        cantidad: 1,
        costo_unitario: montoPago.value
      }],
      total: montoPago.value,
      cliente_nombre: `${user.nombre} ${user.apellido}`,
      cliente_ci: user.ci,
      cliente_telefono: user.telefono || '00000000',
      cliente_email: user.correo
    };

    const response = await apiFetch('/api/generar-qr', {
      method: 'POST',
      body: JSON.stringify(payload)
    });

    const data = await response.json();

    if (data.success) {
      pagoQRInfo.value = data;
      estadoPagoQR.value = 'escaneando';
      
      // Iniciar verificación automática cada 5 segundos
      intervalVerificacionQR.value = setInterval(verificarEstadoQR, 5000);
    } else {
      alert('Error al generar QR: ' + (data.error || 'Error desconocido'));
      estadoPagoQR.value = 'formulario';
    }
  } catch (error) {
    console.error('Error al generar QR:', error);
    alert('Error de conexión al generar QR');
    estadoPagoQR.value = 'formulario';
  } finally {
    procesandoPago.value = false;
  }
};

const verificarEstadoQR = async () => {
  try {
    if (!pagoQRInfo.value.pago_id) return;

    const response = await apiFetch(`/api/pago-estado/${pagoQRInfo.value.pago_id}`);
    const data = await response.json();

    if (data.estado === 'pagada') {
      estadoPagoQR.value = 'completado';
      if (intervalVerificacionQR.value) {
        clearInterval(intervalVerificacionQR.value);
      }
      
      // Registrar el pago en la venta al crédito
      await registrarPagoQRConfirmado();
    } else if (data.estado === 'fallido') {
      alert('El pago QR falló. Intenta nuevamente.');
      estadoPagoQR.value = 'formulario';
      if (intervalVerificacionQR.value) {
        clearInterval(intervalVerificacionQR.value);
      }
    }
  } catch (error) {
    console.error('Error al verificar estado QR:', error);
  }
};

const registrarPagoQRConfirmado = async () => {
  try {
    const response = await apiFetch(`/api/ventas/${ventaSeleccionada.value.id}/pagos`, {
      method: 'POST',
      body: JSON.stringify({
        monto: montoPago.value,
        metodo: 'qr'
      })
    });

    const data = await response.json();
    if (data.success) {
      alert('Pago con QR completado exitosamente');
      cerrarModalPago();
      cerrarModal();
      await cargarVentas();
    }
  } catch (error) {
    console.error('Error al confirmar pago QR:', error);
  }
};

// Funciones para modal crear venta
const abrirModalCrearVenta = async () => {
  modalCrearAbierto.value = true;
  pasoActual.value = 1;
  busquedaCI.value = '';
  busquedaRealizada.value = false;
  clienteEncontrado.value = null;
  productoSeleccionadoId.value = '';
  cantidadProducto.value = 1;
  productosVenta.value = [];
  montoAdelanto.value = 0;
  metodoAdelanto.value = '';
  
  // Cargar productos disponibles
  await cargarProductos();
};

const cerrarModalCrear = () => {
  modalCrearAbierto.value = false;
  pasoActual.value = 1;
  busquedaCI.value = '';
  busquedaRealizada.value = false;
  clienteEncontrado.value = null;
  productoSeleccionadoId.value = '';
  cantidadProducto.value = 1;
  productosVenta.value = [];
  montoAdelanto.value = 0;
  metodoAdelanto.value = '';
};

const buscarCliente = async () => {
  if (!busquedaCI.value.trim()) return;
  
  cargandoCliente.value = true;
  busquedaRealizada.value = true;
  clienteEncontrado.value = null;
  
  try {
    const response = await apiFetch(`/api/clientes/buscar/${busquedaCI.value.trim()}`);
    
    if (response.ok) {
      const data = await response.json();
      if (data.success && data.data) {
        clienteEncontrado.value = data.data;
      } else {
        clienteEncontrado.value = null;
      }
    } else {
      clienteEncontrado.value = null;
    }
  } catch (error) {
    console.error('Error al buscar cliente:', error);
    clienteEncontrado.value = null;
  } finally {
    cargandoCliente.value = false;
  }
};

const cargarProductos = async () => {
  try {
    const response = await apiFetch('/api/productos');
    const data = await response.json();
    // Filtrar solo productos con stock disponible
    productos.value = (Array.isArray(data) ? data : data.data || []).filter(p => p.stock > 0);
  } catch (error) {
    console.error('Error al cargar productos:', error);
    productos.value = [];
  }
};

const agregarProducto = () => {
  if (!productoSeleccionadoId.value || !cantidadProducto.value) return;
  
  const producto = productos.value.find(p => p.id === parseInt(productoSeleccionadoId.value));
  if (!producto) return;
  
  if (cantidadProducto.value > producto.stock) {
    alert(`Stock insuficiente. Disponible: ${producto.stock}`);
    return;
  }
  
  // Verificar si ya existe en la lista
  const existe = productosVenta.value.find(p => p.producto_id === producto.id);
  if (existe) {
    alert('Este producto ya está en la lista');
    return;
  }
  
  productosVenta.value.push({
    producto_id: producto.id,
    nombre: producto.nombre,
    cantidad: cantidadProducto.value,
    precio_unitario: parseFloat(producto.precio)
  });
  
  productoSeleccionadoId.value = '';
  cantidadProducto.value = 1;
};

const eliminarProducto = (index) => {
  productosVenta.value.splice(index, 1);
};

const crearVenta = async () => {
  if (productosVenta.value.length === 0) {
    alert('Debe agregar al menos un producto');
    return;
  }
  
  if (montoAdelanto.value > totalVenta.value) {
    alert('El adelanto no puede ser mayor al total');
    return;
  }
  
  if (montoAdelanto.value > 0 && !metodoAdelanto.value) {
    alert('Debe seleccionar un método de pago para el adelanto');
    return;
  }
  
  procesandoVenta.value = true;
  
  try {
    const payload = {
      cliente_id: clienteEncontrado.value.id,
      productos: productosVenta.value.map(p => ({
        producto_id: p.producto_id,
        cantidad: p.cantidad,
        precio_unitario: p.precio_unitario
      }))
    };
    
    if (montoAdelanto.value > 0) {
      payload.monto_adelanto = montoAdelanto.value;
      payload.metodo_adelanto = metodoAdelanto.value;
    }
    
    const response = await apiFetch('/api/ventas/credito', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
    
    const data = await response.json();
    if (data.success) {
      alert('Venta al crédito creada exitosamente');
      cerrarModalCrear();
      await cargarVentas();
    } else {
      alert('Error al crear la venta: ' + data.message);
    }
  } catch (error) {
    console.error('Error al crear venta:', error);
    alert('Error al crear la venta');
  } finally {
    procesandoVenta.value = false;
  }
};

// Funciones utilitarias
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  });
};

// Cargar datos
const cargarVentas = async () => {
  try {
    const response = await apiFetch('/api/ventas/credito');
    const data = await response.json();
    
    // Manejar tanto Array directo como {success, data}
    if (Array.isArray(data)) {
      ventas.value = data;
    } else if (data.success && data.data) {
      ventas.value = data.data;
    } else {
      console.error('Error al cargar ventas:', data.message);
      ventas.value = [];
    }
  } catch (error) {
    console.error('Error al cargar ventas:', error);
    ventas.value = [];
  }
};

const registrarVisita = async () => {
  try {
    const response = await apiFetch('/api/visitas/dashboard-ventas-credito', {
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
  cargarVentas();
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