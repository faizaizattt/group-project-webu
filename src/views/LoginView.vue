<template>
  <div class="auth-page">
    <div class="auth-card">
      <div class="auth-header">
        <div class="auth-logo">
          <div class="logo-icon">🚗</div>
          <span>BlueDrive</span>
        </div>
        <h2 class="auth-title">Welcome Back</h2>
        <p class="auth-subtitle">Sign in to manage your car rentals, payments, and feedback</p>
      </div>

      <!-- Quick Credentials Helper -->
      <div style="background: rgba(37, 99, 235, 0.05); border: 1px solid rgba(37, 99, 235, 0.15); border-radius: var(--radius-sm); padding: 1rem; margin-bottom: 1.5rem; font-size: 0.8rem;">
        <span style="font-weight: 700; color: var(--primary-dark); display: block; margin-bottom: 0.5rem;">🔑 Quick Select Demo Account:</span>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
          <button @click="fillCredentials('customer')" class="btn btn-sm btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
            Customer Demo
          </button>
          <button @click="fillCredentials('admin')" class="btn btn-sm btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
            Admin Demo
          </button>
        </div>
      </div>

      <form @submit.prevent="handleLogin" :class="{ 'shake': hasError }">
        <!-- Role Dropdown -->
        <div class="form-group">
          <label class="form-label" for="login-role">Account Role</label>
          <select 
            id="login-role" 
            v-model="form.role" 
            class="form-control"
            required
          >
            <option value="customer">Customer View</option>
            <option value="admin">Administrator Dashboard</option>
          </select>
        </div>

        <!-- Email Field -->
        <div class="form-group">
          <label class="form-label" for="login-email">Email Address</label>
          <input 
            type="email" 
            id="login-email" 
            v-model="form.email" 
            class="form-control" 
            :class="{ 'is-valid': form.email && isEmailValid, 'is-invalid': form.email && !isEmailValid }"
            placeholder="name@example.com" 
            required
          />
          <span v-if="form.email && !isEmailValid" class="invalid-feedback">
            Please enter a valid email address structure.
          </span>
        </div>

        <!-- Password Field -->
        <div class="form-group">
          <label class="form-label" for="login-password">Password</label>
          <input 
            type="password" 
            id="login-password" 
            v-model="form.password" 
            class="form-control" 
            :class="{ 'is-valid': form.password.length >= 6, 'is-invalid': form.password && form.password.length < 6 }"
            placeholder="••••••••" 
            required
          />
          <span v-if="form.password && form.password.length < 6" class="invalid-feedback">
            Password must be at least 6 characters long.
          </span>
        </div>

        <!-- Submit Button -->
        <button 
          type="submit" 
          class="btn btn-primary" 
          style="width: 100%; margin-top: 1rem;"
          :disabled="isLoading || !isFormValid"
        >
          <span v-if="isLoading">Authenticating...</span>
          <span v-else>Sign In</span>
        </button>
      </form>

      <div class="auth-footer">
        <p>Don't have an account? <router-link to="/signup" class="auth-link">Sign Up Here</router-link></p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { loginSim } from '../utils/session';

const router = useRouter();
const isLoading = ref(false);
const hasError = ref(false);

const form = reactive({
  email: '',
  password: '',
  role: 'customer'
});

// Real-time validations
const isEmailValid = computed(() => {
  const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  return re.test(form.email);
});

const isFormValid = computed(() => {
  return isEmailValid.value && form.password.length >= 6;
});

// Demo Helper
const fillCredentials = (role) => {
  form.role = role;
  if (role === 'admin') {
    form.email = 'admin@bluedrive.com';
    form.password = 'admin123';
  } else {
    form.email = 'customer@bluedrive.com';
    form.password = 'customer123';
  }
};

// Async Login trigger
const handleLogin = async () => {
  if (!isFormValid.value) {
    triggerErrorEffect();
    return;
  }

  isLoading.value = true;
  hasError.value = false;

  try {
    const user = await loginSim(form.email, form.password, form.role);
    
    // Redirect based on Authenticated Role
    if (user.role === 'admin') {
      router.push({ name: 'PaymentClearanceLog' });
    } else {
      router.push({ name: 'MyPayments' });
    }
  } catch (err) {
    triggerErrorEffect();
  } finally {
    isLoading.value = false;
  }
};

const triggerErrorEffect = () => {
  hasError.value = true;
  setTimeout(() => {
    hasError.value = false;
  }, 400);
};
</script>
