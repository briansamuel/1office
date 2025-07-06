import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

// Import components
import Home from '../pages/Home.vue'
import Login from '../pages/auth/Login.vue'

// Work module components
import WorkDashboard from '../pages/work/Dashboard.vue'
import TaskList from '../pages/work/TaskList.vue'
import TaskKanban from '../pages/work/TaskKanban.vue'
import TaskForm from '../pages/work/TaskForm.vue'

// Placeholder components for other modules
import HRMDashboard from '../pages/hrm/Dashboard.vue'
import CRMDashboard from '../pages/crm/Dashboard.vue'
import WarehouseDashboard from '../pages/warehouse/Dashboard.vue'

const routes = [
  {
    path: '/',
    name: 'home',
    component: Home
  },
  {
    path: '/login',
    name: 'login',
    component: Login,
    meta: { guest: true }
  },
  
  // Work Module Routes
  {
    path: '/work',
    name: 'work',
    component: WorkDashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/work/tasks',
    name: 'work.tasks',
    component: TaskList,
    meta: { requiresAuth: true }
  },
  {
    path: '/work/tasks/kanban',
    name: 'work.tasks.kanban',
    component: TaskKanban,
    meta: { requiresAuth: true }
  },
  {
    path: '/work/tasks/create',
    name: 'work.tasks.create',
    component: TaskForm,
    meta: { requiresAuth: true }
  },
  {
    path: '/work/tasks/:id/edit',
    name: 'work.tasks.edit',
    component: TaskForm,
    props: true,
    meta: { requiresAuth: true }
  },
  
  // HRM Module Routes
  {
    path: '/hrm',
    name: 'hrm',
    component: HRMDashboard,
    meta: { requiresAuth: true }
  },
  
  // CRM Module Routes
  {
    path: '/crm',
    name: 'crm',
    component: CRMDashboard,
    meta: { requiresAuth: true }
  },
  
  // Warehouse Module Routes
  {
    path: '/warehouse',
    name: 'warehouse',
    component: WarehouseDashboard,
    meta: { requiresAuth: true }
  },
  
  // Catch all route - 404
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('../pages/NotFound.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
  } else if (to.meta.guest && authStore.isAuthenticated) {
    next('/')
  } else {
    next()
  }
})

export default router
