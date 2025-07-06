<template>
  <div id="app" class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <!-- Logo -->
            <div class="flex-shrink-0 flex items-center">
              <router-link to="/" class="text-xl font-bold text-gray-900">
                1Office
              </router-link>
            </div>
            
            <!-- Navigation Links -->
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <router-link
                to="/work"
                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                active-class="border-indigo-500 text-gray-900"
              >
                Work
              </router-link>
              <router-link
                to="/hrm"
                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                active-class="border-indigo-500 text-gray-900"
              >
                HRM
              </router-link>
              <router-link
                to="/crm"
                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                active-class="border-indigo-500 text-gray-900"
              >
                CRM
              </router-link>
              <router-link
                to="/warehouse"
                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                active-class="border-indigo-500 text-gray-900"
              >
                Warehouse
              </router-link>
            </div>
          </div>
          
          <!-- User Menu -->
          <div class="flex items-center">
            <div class="ml-3 relative" v-if="user">
              <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-700">{{ user.name }}</span>
                <button
                  @click="logout"
                  class="bg-white text-gray-400 hover:text-gray-600 px-3 py-2 rounded-md text-sm font-medium"
                >
                  Logout
                </button>
              </div>
            </div>
            <div v-else>
              <router-link
                to="/login"
                class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium"
              >
                Login
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <router-view />
    </main>
  </div>
</template>

<script>
import { useAuthStore } from './stores/auth'
import { computed, onMounted } from 'vue'

export default {
  name: 'App',
  setup() {
    const authStore = useAuthStore()
    
    const user = computed(() => authStore.user)
    
    const logout = async () => {
      await authStore.logout()
    }
    
    onMounted(() => {
      // Check if user is already authenticated
      if (localStorage.getItem('auth_token')) {
        authStore.fetchUser()
      }
    })
    
    return {
      user,
      logout
    }
  }
}
</script>
