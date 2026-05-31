<template>
  <div>
    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">My Profile</h1>
        <p class="page-subtitle">
          {{ userRole === 'admin' ? 'Manage your account details' : 'Manage your personal information' }}
        </p>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; align-items: start;">
      
      <!-- Left Column: Profile Card & Account Info -->
      <div style="display: flex; flex-direction: column; gap: 2rem;">
        
        <!-- Profile Card -->
        <div class="card" style="text-align: center; display: flex; flex-direction: column; align-items: center;">
          <div 
            style="width: 80px; height: 80px; border-radius: 50%; background-color: var(--primary); color: var(--white); display: flex; justify-content: center; align-items: center; font-size: 2rem; font-weight: 700; margin-bottom: 1rem;"
          >
            {{ initials }}
          </div>
          <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.25rem;">{{ user.name }}</h2>
          <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">{{ user.email }}</p>
          <span class="badge" :class="userRole === 'admin' ? 'badge-success' : 'badge-pending'">
            {{ userRole }}
          </span>
        </div>

        <!-- Account Info -->
        <div class="card">
          <h3 class="card-title">Account Info</h3>
          <div style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
              <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600;">Account ID</span>
              <span style="font-weight: 500; font-family: monospace;">{{ user.id || 'N/A' }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
              <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600;">Role</span>
              <span style="font-weight: 500; text-transform: capitalize;">{{ userRole }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem;">
              <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600;">Member Since</span>
              <span style="font-weight: 500;">{{ user.joinDate || '2026-05-31' }}</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Right Column: Edit Forms -->
      <div style="display: flex; flex-direction: column; gap: 2rem;">
        
        <!-- Edit Profile Form -->
        <div class="card">
          <h3 class="card-title">Edit Profile</h3>
          <form @submit.prevent="handleProfileSubmit">
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <input type="text" v-model="profileForm.name" class="form-control" required />
            </div>
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <input type="email" v-model="profileForm.email" class="form-control" required />
            </div>
            <div class="form-group">
              <label class="form-label">Phone Number</label>
              <input type="text" v-model="profileForm.phone" class="form-control" placeholder="Optional" />
            </div>
            <div v-if="userRole === 'customer'" class="form-group">
              <label class="form-label">Total Bookings</label>
              <input type="text" :value="user.totalBookings || 0" class="form-control" readonly disabled style="background: #f8fafc; color: var(--text-muted);" />
            </div>
            <button type="submit" class="btn btn-primary" :disabled="isSavingProfile">
              {{ isSavingProfile ? 'Saving...' : 'Save Changes' }}
            </button>
          </form>
        </div>

        <!-- Change Password Section -->
        <div class="card">
          <h3 class="card-title">Change Password</h3>
          <form @submit.prevent="handlePasswordSubmit">
            <div class="form-group">
              <label class="form-label">Current Password</label>
              <input type="password" v-model="passwordForm.current" class="form-control" required />
            </div>
            <div class="form-group">
              <label class="form-label">New Password</label>
              <input type="password" v-model="passwordForm.new" class="form-control" required minlength="6" />
            </div>
            <div class="form-group">
              <label class="form-label">Confirm New Password</label>
              <input 
                type="password" 
                v-model="passwordForm.confirm" 
                class="form-control" 
                :class="{ 'is-invalid': passwordForm.new && passwordForm.confirm && passwordForm.new !== passwordForm.confirm }" 
                required 
              />
              <span v-if="passwordForm.new && passwordForm.confirm && passwordForm.new !== passwordForm.confirm" class="invalid-feedback">
                Passwords do not match.
              </span>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="isSavingPassword || (passwordForm.new !== passwordForm.confirm)">
              {{ isSavingPassword ? 'Updating...' : 'Update Password' }}
            </button>
          </form>
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
import { ref, computed, reactive, onMounted } from 'vue';
import { currentUser } from '@/utils/session.js';
import { updateCurrentUserProfile } from '@/utils/mockData.js';

const toasts = ref([]);
const isSavingProfile = ref(false);
const isSavingPassword = ref(false);

const user = computed(() => currentUser.value || {});
const userRole = computed(() => user.value.role || 'customer');

const initials = computed(() => {
  const name = user.value.name || 'User';
  const parts = name.split(' ');
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
});

const profileForm = reactive({
  name: '',
  email: '',
  phone: ''
});

const passwordForm = reactive({
  current: '',
  new: '',
  confirm: ''
});

onMounted(() => {
  if (user.value) {
    profileForm.name = user.value.name || '';
    profileForm.email = user.value.email || '';
    profileForm.phone = user.value.phone || '';
  }
});

const addToast = (text, type = 'success') => {
  const id = Date.now();
  toasts.value.push({ id, text, type });
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id);
  }, 4000);
};

const handleProfileSubmit = async () => {
  isSavingProfile.value = true;
  try {
    await updateCurrentUserProfile({
      name: profileForm.name,
      email: profileForm.email,
      phone: profileForm.phone
    });
    addToast('Profile updated successfully', 'success');
  } catch (err) {
    addToast('Failed to update profile', 'danger');
  } finally {
    isSavingProfile.value = false;
  }
};

const handlePasswordSubmit = async () => {
  if (passwordForm.new !== passwordForm.confirm) {
    addToast('New passwords do not match', 'danger');
    return;
  }
  isSavingPassword.value = true;
  try {
    // Simulate API delay
    await new Promise(resolve => setTimeout(resolve, 1000));
    addToast('Password updated successfully', 'success');
    passwordForm.current = '';
    passwordForm.new = '';
    passwordForm.confirm = '';
  } catch (err) {
    addToast('Failed to update password', 'danger');
  } finally {
    isSavingPassword.value = false;
  }
};
</script>
