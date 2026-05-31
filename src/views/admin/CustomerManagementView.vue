<template>
  <div>
    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Customer Management</h1>
        <p class="page-subtitle">Manage registered customers</p>
      </div>
      <div>
        <button @click="toggleAddForm" class="btn btn-primary">
          {{ showAddForm ? 'Cancel Add' : 'Add Customer' }}
        </button>
      </div>
    </div>

    <!-- Metrics Grid -->
    <div class="metrics-grid">
      <div class="metric-card">
        <div class="metric-icon-box" style="background: var(--primary-light); color: var(--primary);">
          👥
        </div>
        <div class="metric-info">
          <span class="metric-label">Total Customers</span>
          <span class="metric-value">{{ totalCustomers }}</span>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon-box" style="background: var(--status-success-bg); color: var(--status-success);">
          ✨
        </div>
        <div class="metric-info">
          <span class="metric-label">New This Month</span>
          <span class="metric-value">{{ newThisMonth }}</span>
        </div>
      </div>
    </div>

    <!-- Filters Row -->
    <div class="filters-row">
      <input 
        type="text" 
        v-model="searchQuery" 
        class="form-control" 
        placeholder="🔍 Search by name or email..." 
        style="max-width: 400px;"
      />
    </div>

    <!-- Add Form -->
    <div v-if="showAddForm" class="card" style="margin-bottom: 1.5rem;">
      <h3 class="card-title">Add New Customer</h3>
      <form @submit.prevent="handleAddSubmit">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" v-model="addForm.name" class="form-control" required />
        </div>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" v-model="addForm.email" class="form-control" required />
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="text" v-model="addForm.phone" class="form-control" />
        </div>
        <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
          {{ isSubmitting ? 'Saving...' : 'Save Customer' }}
        </button>
      </form>
    </div>

    <!-- Data Table -->
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Join Date</th>
            <th>Total Bookings</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filteredCustomers.length === 0">
            <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
              No customers match your search.
            </td>
          </tr>
          <template v-for="c in filteredCustomers" :key="c.id">
            <tr>
              <td style="font-weight: 600;">{{ c.name }}</td>
              <td>{{ c.email }}</td>
              <td>{{ c.phone || 'N/A' }}</td>
              <td>{{ c.joinDate }}</td>
              <td>
                <span class="badge badge-success">{{ c.totalBookings }}</span>
              </td>
              <td>
                <div style="display: flex; gap: 0.5rem;">
                  <button @click="startEdit(c)" class="btn btn-sm btn-outline">Edit</button>
                  <button @click="confirmDelete(c)" class="btn btn-sm btn-danger">Delete</button>
                </div>
              </td>
            </tr>
            <!-- Inline Edit Form -->
            <tr v-if="editingId === c.id">
              <td colspan="6" style="background: #f8fafc; padding: 1.5rem;">
                <h4 style="margin-bottom: 1rem; font-size: 1rem;">Edit Customer</h4>
                <form @submit.prevent="handleEditSubmit" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                  <div class="form-group" style="margin-bottom: 0; flex-grow: 1;">
                    <label class="form-label">Name</label>
                    <input type="text" v-model="editForm.name" class="form-control" required />
                  </div>
                  <div class="form-group" style="margin-bottom: 0; flex-grow: 1;">
                    <label class="form-label">Email</label>
                    <input type="email" v-model="editForm.email" class="form-control" required />
                  </div>
                  <div class="form-group" style="margin-bottom: 0; flex-grow: 1;">
                    <label class="form-label">Phone</label>
                    <input type="text" v-model="editForm.phone" class="form-control" />
                  </div>
                  <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary" :disabled="isSubmitting">Save</button>
                    <button type="button" @click="cancelEdit" class="btn btn-outline">Cancel</button>
                  </div>
                </form>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Confirm Delete Modal -->
    <div v-if="customerToDelete" class="modal-overlay" @click.self="customerToDelete = null">
      <div class="modal-content" style="max-width: 450px;">
        <h3 class="card-title" style="color: var(--status-danger);">Confirm Deletion</h3>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
          Are you sure you want to remove <strong>{{ customerToDelete.name }}</strong>? This action cannot be undone.
        </p>
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
          <button @click="customerToDelete = null" class="btn btn-outline" :disabled="isSubmitting">Cancel</button>
          <button @click="handleDelete" class="btn btn-danger" :disabled="isSubmitting">
            {{ isSubmitting ? 'Deleting...' : 'Delete Customer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-container">
      <div v-for="toast in toasts" :key="toast.id" class="toast" :class="`toast-${toast.type}`">
        <span>{{ toast.text }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { customers, addCustomer, updateCustomer, deleteCustomer } from '@/utils/mockData.js';
import { currentUser } from '@/utils/session.js';

const searchQuery = ref('');
const toasts = ref([]);
const isSubmitting = ref(false);

const showAddForm = ref(false);
const addForm = reactive({ name: '', email: '', phone: '' });

const editingId = ref(null);
const editForm = reactive({ name: '', email: '', phone: '' });

const customerToDelete = ref(null);

const totalCustomers = computed(() => customers.value.length);
const newThisMonth = computed(() => {
  const currentMonth = new Date().toISOString().slice(0, 7);
  return customers.value.filter(c => c.joinDate && c.joinDate.startsWith(currentMonth)).length;
});

const filteredCustomers = computed(() => {
  const query = searchQuery.value.toLowerCase().trim();
  if (!query) return customers.value;
  return customers.value.filter(c => 
    (c.name && c.name.toLowerCase().includes(query)) || 
    (c.email && c.email.toLowerCase().includes(query))
  );
});

const addToast = (text, type = 'success') => {
  const id = Date.now();
  toasts.value.push({ id, text, type });
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id);
  }, 4000);
};

const toggleAddForm = () => {
  showAddForm.value = !showAddForm.value;
  if (showAddForm.value) {
    addForm.name = '';
    addForm.email = '';
    addForm.phone = '';
  }
};

const handleAddSubmit = async () => {
  isSubmitting.value = true;
  try {
    await addCustomer({ ...addForm });
    addToast('Customer added', 'success');
    showAddForm.value = false;
  } catch (error) {
    addToast('Failed to add customer', 'danger');
  } finally {
    isSubmitting.value = false;
  }
};

const startEdit = (c) => {
  editingId.value = c.id;
  editForm.name = c.name;
  editForm.email = c.email;
  editForm.phone = c.phone;
};

const cancelEdit = () => {
  editingId.value = null;
};

const handleEditSubmit = async () => {
  isSubmitting.value = true;
  try {
    await updateCustomer(editingId.value, { ...editForm });
    addToast('Customer updated', 'success');
    editingId.value = null;
  } catch (error) {
    addToast('Failed to update customer', 'danger');
  } finally {
    isSubmitting.value = false;
  }
};

const confirmDelete = (c) => {
  customerToDelete.value = c;
};

const handleDelete = async () => {
  isSubmitting.value = true;
  try {
    await deleteCustomer(customerToDelete.value.id);
    addToast('Customer removed', 'danger');
    customerToDelete.value = null;
  } catch (error) {
    addToast('Failed to delete customer', 'danger');
  } finally {
    isSubmitting.value = false;
  }
};
</script>
