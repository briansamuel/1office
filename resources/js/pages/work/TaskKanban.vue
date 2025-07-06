<template>
  <div class="px-4 py-6 sm:px-0">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Task Kanban Board</h1>
      <p class="mt-1 text-sm text-gray-600">
        Drag and drop tasks between columns to update their status
      </p>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white p-4 rounded-lg shadow">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Assigned To</label>
          <select v-model="filters.assigned_to" @change="loadKanbanData" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Users</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Project</label>
          <select v-model="filters.project_id" @change="loadKanbanData" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Projects</option>
            <option v-for="project in projects" :key="project.id" :value="project.id">
              {{ project.name }}
            </option>
          </select>
        </div>
        <div class="flex items-end">
          <button @click="resetFilters" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
            Reset Filters
          </button>
        </div>
      </div>
    </div>

    <!-- Kanban Board -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6" v-if="!loading">
      <!-- To Do Column -->
      <div class="bg-gray-50 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-medium text-gray-900">To Do</h3>
          <span class="bg-gray-200 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
            {{ kanbanData.todo?.length || 0 }}
          </span>
        </div>
        <div class="space-y-3">
          <TaskCard
            v-for="task in kanbanData.todo"
            :key="task.id"
            :task="task"
            @update-status="updateTaskStatus"
          />
        </div>
      </div>

      <!-- In Progress Column -->
      <div class="bg-blue-50 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-medium text-gray-900">In Progress</h3>
          <span class="bg-blue-200 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
            {{ kanbanData.in_progress?.length || 0 }}
          </span>
        </div>
        <div class="space-y-3">
          <TaskCard
            v-for="task in kanbanData.in_progress"
            :key="task.id"
            :task="task"
            @update-status="updateTaskStatus"
          />
        </div>
      </div>

      <!-- In Review Column -->
      <div class="bg-yellow-50 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-medium text-gray-900">In Review</h3>
          <span class="bg-yellow-200 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
            {{ kanbanData.in_review?.length || 0 }}
          </span>
        </div>
        <div class="space-y-3">
          <TaskCard
            v-for="task in kanbanData.in_review"
            :key="task.id"
            :task="task"
            @update-status="updateTaskStatus"
          />
        </div>
      </div>

      <!-- Completed Column -->
      <div class="bg-green-50 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-medium text-gray-900">Completed</h3>
          <span class="bg-green-200 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
            {{ kanbanData.completed?.length || 0 }}
          </span>
        </div>
        <div class="space-y-3">
          <TaskCard
            v-for="task in kanbanData.completed"
            :key="task.id"
            :task="task"
            @update-status="updateTaskStatus"
          />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import TaskCard from '../../components/work/TaskCard.vue'

export default {
  name: 'TaskKanban',
  components: {
    TaskCard
  },
  setup() {
    const loading = ref(false)
    const kanbanData = ref({
      todo: [],
      in_progress: [],
      in_review: [],
      completed: []
    })
    const filters = ref({
      assigned_to: '',
      project_id: ''
    })
    const users = ref([])
    const projects = ref([])

    const loadKanbanData = async () => {
      loading.value = true
      try {
        const response = await axios.get('/work/tasks/kanban', {
          params: filters.value
        })
        kanbanData.value = response.data.data
      } catch (error) {
        console.error('Error loading kanban data:', error)
      } finally {
        loading.value = false
      }
    }

    const updateTaskStatus = async (taskId, newStatus) => {
      try {
        await axios.patch(`/work/tasks/${taskId}/status`, {
          status: newStatus
        })
        // Reload kanban data to reflect changes
        await loadKanbanData()
      } catch (error) {
        console.error('Error updating task status:', error)
      }
    }

    const resetFilters = () => {
      filters.value = {
        assigned_to: '',
        project_id: ''
      }
      loadKanbanData()
    }

    onMounted(() => {
      loadKanbanData()
      // TODO: Load users and projects for filters
    })

    return {
      loading,
      kanbanData,
      filters,
      users,
      projects,
      loadKanbanData,
      updateTaskStatus,
      resetFilters
    }
  }
}
</script>
