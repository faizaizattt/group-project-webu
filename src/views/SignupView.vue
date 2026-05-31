<template>
  <div class="auth-page">
    <div class="auth-card">
      <div class="auth-header">
        <div class="auth-logo">
          <div class="logo-icon">🚗</div>
          <span>BlueDrive</span>
        </div>
        <h2 class="auth-title">Create Account</h2>
        <p class="auth-subtitle">Join us to secure modern premium vehicle rentals today</p>
      </div>

      <form @submit.prevent="handleSignup" :class="{ 'shake': hasError }">
        <!-- Full Name -->
        <div class="form-group">
          <label class="form-label" for="signup-name">Full Name</label>
          <input 
            type="text" 
            id="signup-name" 
            v-model="form.name" 
            class="form-control" 
            :class="{ 'is-valid': form.name.length >= 3, 'is-invalid': form.name && form.name.length < 3 }"
            placeholder="John Doe" 
            required
          />
          <span v-if="form.name && form.name.length < 3" class="invalid-feedback">
            Name must be at least 3 characters long.
          </span>
        </div>

        <!-- Account Role -->
        <div class="form-group">
          <label class="form-label" for="signup-role">Registration Role</label>
          <select 
            id="signup-role" 
            v-model="form.role" 
            class="form-control"
            required
          >
            <option value="customer">Customer Account</option>
            <option value="admin">Platform Administrator</option>
          </select>
        </div>

        <!-- Email Address -->
        <div class="form-group">
          <label class="form-label" for="signup-email">Email Address</label>
          <input 
            type="email" 
            id="signup-email" 
            v-model="form.email" 
            class="form-control" 
            :class="{ 'is-valid': form.email && isEmailValid, 'is-invalid': form.email && !isEmailValid }"
            placeholder="john@example.com" 
            required
          />
          <span v-if="form.email && !isEmailValid" class="invalid-feedback">
            Please enter a valid email structure.
          </span>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label class="form-label" for="signup-password">Password</label>
          <input 
            type="password" 
            id="signup-password" 
            v-model="form.password" 
            class="form-control" 
            :class="{ 'is-valid': form.password.length >= 6, 'is-invalid': form.password && form.password.length < 6 }"
            placeholder="••••••••" 
            required
          />
          <span v-if="form.password && form.password.length < 6" class="invalid-feedback">
            Password must be at least 6 characters.
          </span>
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
          <label class="form-label" for="signup-confirm-password">Confirm Password</label>
          <input 
            type="password" 
            id="signup-confirm-password" 
            v-model="form.confirmPassword" 
            class="form-control" 
            :class="{ 'is-valid': isPasswordMatching && form.confirmPassword, 'is-invalid': form.confirmPassword && !isPasswordMatching }"
            placeholder="••••••••" 
            required
          />
          <span v-if="form.confirmPassword && !isPasswordMatching" class="invalid-feedback">
            Passwords do not match.
          </span>
        </div>

        <!-- Submit -->
        <button 
          type="submit" 
          class="btn btn-primary" 
          style="width: 100%; margin-top: 1rem;"
          :disabled="isLoading || !isFormValid"
        >
          <span v-if="isLoading">Creating Account...</span>
          <span v-else>Register & Get Started</span>
        </button>
      </form>

      <div class="auth-footer">
        <p>Already have an account? <router-link to="/login" class="auth-link">Sign In Here</router-link></p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { signupSim } from '../utils/session';

const router = useRouter();
const isLoading = ref(false);
const hasError = ref(false);

const form = reactive({
  name: '',
  email: '',
  role: 'customer',
  password: '',
  confirmPassword: ''
});

// Real-time validations
const isEmailValid = computed(() => {
  const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  return re.test(form.email);
});

const isPasswordMatching = computed(() => {
  return form.password === form.confirmPassword;
});

const isFormValid = computed(() => {
  return form.name.length >= 3 &&
         isEmailValid.value &&
         form.password.length >= 6 &&
         isPasswordMatching.value;
});

// Async Register trigger
const handleSignup = async () => {
  if (!isFormValid.value) {
    triggerErrorEffect();
    return;
  }

  isLoading.value = true;
  hasError.value = false;

  try {
    const user = await signupSim(form.name, form.email, form.password, form.role);
    
    // Redirect based on role
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
