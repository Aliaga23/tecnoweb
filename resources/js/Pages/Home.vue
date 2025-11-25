<template>
  <AppLayout>
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
      <div class="p-6 text-gray-900">
        <h2 class="text-2xl font-bold mb-4">Bienvenido a Tecno P2</h2>
        <p class="mb-4">Sistema de gestión completo con Laravel + Vue + Inertia</p>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
          <div class="bg-blue-100 p-4 rounded-lg">
            <h3 class="font-bold text-lg">Productos</h3>
            <p class="text-gray-600">Gestiona tu inventario</p>
          </div>
          <div class="bg-green-100 p-4 rounded-lg">
            <h3 class="font-bold text-lg">Ventas</h3>
            <p class="text-gray-600">Registra tus ventas</p>
          </div>
          <div class="bg-purple-100 p-4 rounded-lg">
            <h3 class="font-bold text-lg">Reportes</h3>
            <p class="text-gray-600">Estadísticas del negocio</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>
