<template>
  <div class="app-container">
    <!-- View Case A: User Not Logged In (Show pure Login/Signup Pages) -->
    <div v-if="!user" class="auth-only-container">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </div>

    <!-- View Case B: Logged In as Customer (Sticky Top Navbar Layout) -->
    <div v-else-if="user.role === 'customer'">
      <!-- Customer Sticky Navbar -->
      <nav class="customer-navbar">
        <router-link to="/customer/cars" class="logo-container">
          <div class="logo-icon">🚗</div>
          <span>BlueDrive</span>
        </router-link>

        <div class="customer-nav-links">
          <router-link to="/customer/cars" class="nav-link">
            🚘 Browse Cars
          </router-link>
          <router-link to="/customer/payments" class="nav-link">
            💵 My Payments
          </router-link>
          <router-link to="/customer/bookings" class="nav-link">
            📅 Manage Bookings
          </router-link>
          <router-link to="/customer/feedback" class="nav-link">
            💬 Feedback
          </router-link>
        </div>

        <div class="user-profile-menu">
          <div style="text-align: right; display: flex; flex-direction: column; justify-content: center;">
            <span style="font-size: 0.85rem; font-weight: 700; color: var(--text-main);">{{ user.name }}</span>
            <span style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Customer Account</span>
          </div>
          <div class="user-avatar">
            C
          </div>
          <router-link :to="{ name: 'Profile' }" class="nav-link" style="padding: 0.4rem 0.8rem; margin-right: 0.5rem;">
            👤 My Profile
          </router-link>
          <button @click="handleLogout" class="btn-logout">
            Logout
          </button>
        </div>
      </nav>

      <!-- Customer Viewport Main Content Wrapper -->
      <main style="padding: 2rem; max-width: var(--max-width); margin: 0 auto; width: 100%;">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>

    <!-- View Case C: Logged In as Admin (Sleek Sidebar & Main Dashboard Area) -->
    <div v-else-if="user.role === 'admin'" class="admin-layout">
      <!-- Admin Left Sidebar -->
      <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
          <div class="logo-container" style="font-size: 1.35rem;">
            <div class="logo-icon" style="width: 2rem; height: 2rem; font-size: 0.95rem;">🚗</div>
            <span>BlueDrive</span>
          </div>
          <span class="admin-badge">System Admin</span>
        </div>

        <nav class="admin-nav-links">
          <router-link to="/admin/dashboard" class="admin-nav-link">
            📊 Overview Dashboard
          </router-link>
          <router-link to="/admin/cars" class="admin-nav-link">
            🚘 Car Inventory
          </router-link>
          <router-link to="/admin/clearance" class="admin-nav-link">
            ⚙ Payment Clearance Log
          </router-link>
          <router-link to="/admin/rentals" class="admin-nav-link">
            🚗 Rental Processing
          </router-link>
          <router-link :to="{ name: 'CustomerManagement' }" class="admin-nav-link">
            👥 Customers
          </router-link>
          <router-link to="/admin/analytics" class="admin-nav-link">
            📈 Feedback Analytics
          </router-link>
        </nav>

        <div class="admin-sidebar-footer">
          <div class="admin-user-info">
            <div class="user-avatar" style="background: var(--primary); color: var(--white);">
              A
            </div>
            <div class="admin-user-details">
              <span class="admin-user-name">Staff Admin</span>
              <span class="admin-user-role">admin@bluedrive.com</span>
            </div>
          </div>
          <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 0.5rem 0;" />
          <router-link :to="{ name: 'Profile' }" class="admin-nav-link" style="margin-bottom: 0.5rem; justify-content: center;">
            👤 My Profile
          </router-link>
          <button @click="handleLogout" class="btn btn-outline btn-sm" style="width: 100%; text-align: center; display: block; font-weight: 500;">
            🚪 Exit Admin Panel
          </button>
        </div>
      </aside>

      <!-- Admin Main Content Area -->
      <main class="admin-main-content">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { currentUser, logoutSim } from './utils/session';

const router = useRouter();
const user = computed(() => currentUser.value);

const handleLogout = () => {
  logoutSim();
  router.push('/login');
};
</script>

<style>
/* Core view transition styling */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
