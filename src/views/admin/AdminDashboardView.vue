<template>
  <div class="admin-dashboard">
    <div class="dashboard-header">
      <h2>Admin Dashboard Overview</h2>
      <p class="subtitle">Quick overview of system performance and current metrics.</p>
    </div>

    <div class="metrics-grid">
      <!-- Total Revenue -->
      <div class="metric-card primary-card">
        <div class="metric-icon">
          <i class="fas fa-wallet"></i>
        </div>
        <div class="metric-info">
          <span class="metric-title">Total Revenue</span>
          <span class="metric-value">RM{{ totalRevenue.toFixed(2) }}</span>
        </div>
      </div>
      
      <!-- Pending Clearances -->
      <div class="metric-card warning-card">
        <div class="metric-icon">
          <i class="fas fa-clock"></i>
        </div>
        <div class="metric-info">
          <span class="metric-title">Pending Clearances</span>
          <span class="metric-value">{{ pendingClearancesCount }}</span>
        </div>
      </div>

      <!-- Active Bookings -->
      <div class="metric-card success-card">
        <div class="metric-icon">
          <i class="fas fa-calendar-check"></i>
        </div>
        <div class="metric-info">
          <span class="metric-title">Total Bookings</span>
          <span class="metric-value">{{ totalBookingsCount }}</span>
        </div>
      </div>

      <!-- Cars Available -->
      <div class="metric-card info-card">
        <div class="metric-icon">
          <i class="fas fa-car"></i>
        </div>
        <div class="metric-info">
          <span class="metric-title">Cars Available</span>
          <span class="metric-value">{{ availableCarsCount }} / {{ totalCarsCount }}</span>
        </div>
      </div>

      <!-- Avg Rating -->
      <div class="metric-card highlight-card">
        <div class="metric-icon">
          <i class="fas fa-star"></i>
        </div>
        <div class="metric-info">
          <span class="metric-title">Avg Feedback Rating</span>
          <span class="metric-value">{{ averageRating }} / 5</span>
        </div>
      </div>
    </div>

    <div class="quick-links">
      <h3>Quick Actions</h3>
      <div class="actions-grid">
        <router-link to="/admin/cars" class="action-btn">
          <i class="fas fa-car-side"></i> Manage Cars
        </router-link>
        <router-link to="/admin/payments" class="action-btn">
          <i class="fas fa-money-check-alt"></i> Clear Payments
        </router-link>
        <router-link to="/admin/feedback" class="action-btn">
          <i class="fas fa-comments"></i> View Feedback
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  totalRevenue,
  pendingClearancesCount,
  totalBookingsCount,
  totalCarsCount,
  availableCarsCount,
  averageRating
} from '../../utils/mockData'
</script>

<style scoped>
.admin-dashboard {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.dashboard-header {
  margin-bottom: 2rem;
}

.dashboard-header h2 {
  font-size: 2rem;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.subtitle {
  color: #7f8c8d;
  font-size: 1.1rem;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}

.metric-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  box-shadow: 0 4px 6px rgba(0,0,0,0.05);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  border-left: 5px solid transparent;
}

.metric-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 15px rgba(0,0,0,0.1);
}

.primary-card { border-left-color: #3498db; }
.warning-card { border-left-color: #f39c12; }
.success-card { border-left-color: #2ecc71; }
.info-card { border-left-color: #9b59b6; }
.highlight-card { border-left-color: #f1c40f; }

.metric-icon {
  font-size: 2.5rem;
  margin-right: 1.5rem;
  color: #bdc3c7;
}

.primary-card .metric-icon { color: #3498db; }
.warning-card .metric-icon { color: #f39c12; }
.success-card .metric-icon { color: #2ecc71; }
.info-card .metric-icon { color: #9b59b6; }
.highlight-card .metric-icon { color: #f1c40f; }

.metric-info {
  display: flex;
  flex-direction: column;
}

.metric-title {
  font-size: 0.9rem;
  color: #7f8c8d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.5rem;
}

.metric-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: #2c3e50;
}

.quick-links {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.quick-links h3 {
  margin-bottom: 1.5rem;
  color: #2c3e50;
}

.actions-grid {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.8rem;
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  color: #2c3e50;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.2s ease;
  border: 1px solid #e9ecef;
}

.action-btn i {
  font-size: 1.2rem;
  color: #3498db;
}

.action-btn:hover {
  background: #3498db;
  color: white;
  border-color: #3498db;
  transform: translateY(-2px);
}

.action-btn:hover i {
  color: white;
}

@media (max-width: 768px) {
  .actions-grid {
    flex-direction: column;
  }
}
</style>
