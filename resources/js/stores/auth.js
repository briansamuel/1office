import { defineStore } from 'pinia'
import axios from 'axios'
import router from '../router'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('auth_token'),
    loading: false,
    error: null
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    isAdmin: (state) => state.user?.role === 'admin',
    isManager: (state) => state.user?.role === 'manager',
    isUser: (state) => state.user?.role === 'user',
    userRole: (state) => state.user?.role
  },

  actions: {
    async login(credentials) {
      this.loading = true
      this.error = null

      try {
        // Get CSRF cookie first
        await axios.get('/sanctum/csrf-cookie')
        
        // Login request
        const response = await axios.post('/login', credentials)
        
        if (response.data.success) {
          this.token = response.data.token
          this.user = response.data.user
          
          // Store token in localStorage
          localStorage.setItem('auth_token', this.token)
          
          // Set default authorization header
          axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
          
          // Redirect to dashboard
          router.push('/')
          
          return { success: true }
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Login failed'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    async logout() {
      this.loading = true

      try {
        await axios.post('/logout')
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        // Clear state regardless of API call success
        this.user = null
        this.token = null
        this.error = null
        
        // Remove token from localStorage
        localStorage.removeItem('auth_token')
        
        // Remove authorization header
        delete axios.defaults.headers.common['Authorization']
        
        // Redirect to login
        router.push('/login')
        
        this.loading = false
      }
    },

    async fetchUser() {
      if (!this.token) return

      this.loading = true

      try {
        const response = await axios.get('/user')
        this.user = response.data
      } catch (error) {
        console.error('Fetch user error:', error)
        // If token is invalid, logout
        if (error.response?.status === 401) {
          this.logout()
        }
      } finally {
        this.loading = false
      }
    },

    async register(userData) {
      this.loading = true
      this.error = null

      try {
        // Get CSRF cookie first
        await axios.get('/sanctum/csrf-cookie')
        
        const response = await axios.post('/register', userData)
        
        if (response.data.success) {
          this.token = response.data.token
          this.user = response.data.user
          
          // Store token in localStorage
          localStorage.setItem('auth_token', this.token)
          
          // Set default authorization header
          axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
          
          // Redirect to dashboard
          router.push('/')
          
          return { success: true }
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Registration failed'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    clearError() {
      this.error = null
    },

    // Initialize auth state from localStorage
    initializeAuth() {
      const token = localStorage.getItem('auth_token')
      if (token) {
        this.token = token
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
        this.fetchUser()
      }
    }
  }
})
