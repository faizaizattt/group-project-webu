<template>
  <div class="dashboard">

    <!-- ── Header ────────────────────────────────────────────── -->
    <div class="dashboard-header">
      <div class="header-left">
        <p class="greeting">Good day, Admin 👋</p>
        <h1 class="dashboard-title">Overview Dashboard</h1>
      </div>
      <router-link to="/admin/rentals" class="btn btn-primary header-cta">
        🚗 Process Rentals
      </router-link>
    </div>

    <!-- ── KPI Cards ─────────────────────────────────────────── -->
    <div class="kpi-grid">
      <div class="kpi-card kpi-revenue">
        <div class="kpi-icon">💰</div>
        <div class="kpi-body">
          <span class="kpi-label">Total Revenue</span>
          <span class="kpi-value">RM {{ totalRevenue.toFixed(2) }}</span>
          <span class="kpi-sub">From cleared payments</span>
        </div>
      </div>

      <div class="kpi-card kpi-bookings">
        <div class="kpi-icon">📋</div>
        <div class="kpi-body">
          <span class="kpi-label">Total Bookings</span>
          <span class="kpi-value">{{ totalBookingsCount }}</span>
          <span class="kpi-sub">All-time rentals</span>
        </div>
      </div>

      <div class="kpi-card kpi-pending">
        <div class="kpi-icon">⏳</div>
        <div class="kpi-body">
          <span class="kpi-label">Pending Clearances</span>
          <span class="kpi-value">{{ pendingClearancesCount }}</span>
          <span class="kpi-sub">Awaiting your action</span>
        </div>
      </div>

      <div class="kpi-card kpi-fleet">
        <div class="kpi-icon">🚘</div>
        <div class="kpi-body">
          <span class="kpi-label">Fleet Available</span>
          <span class="kpi-value">{{ availableCarsCount }} <em>/ {{ totalCarsCount }}</em></span>
          <span class="kpi-sub">Cars ready to rent</span>
        </div>
      </div>

      <div class="kpi-card kpi-rating">
        <div class="kpi-icon">⭐</div>
        <div class="kpi-body">
          <span class="kpi-label">Customer Rating</span>
          <span class="kpi-value">{{ averageRating }} <em>/ 5.0</em></span>
          <span class="kpi-sub">Average satisfaction</span>
        </div>
      </div>
    </div>

    <!-- ── Main Content ──────────────────────────────────────── -->
    <div class="main-grid">

      <!-- Recent Bookings -->
      <section class="panel bookings-panel">
        <div class="panel-header">
          <div class="panel-header-left">
            <span class="panel-icon">📅</span>
            <h2>Recent Bookings</h2>
          </div>
          <router-link to="/admin/rentals" class="panel-link">View all →</router-link>
        </div>
        <div class="panel-body">
          <div v-if="recentBookings.length === 0" class="empty-state">
            <span class="empty-icon">📭</span>
            <p>No bookings yet.</p>
          </div>
          <table v-else class="bookings-table">
            <thead>
              <tr>
                <th>Customer</th>
                <th>Vehicle</th>
                <th>Dates</th>
                <th>Total</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="booking in recentBookings" :key="booking.id" class="table-row">
                <td>
                  <div class="customer-cell">
                    <div class="customer-avatar">{{ booking.customerName.charAt(0) }}</div>
                    <span class="customer-name">{{ booking.customerName }}</span>
                  </div>
                </td>
                <td class="vehicle-cell">{{ booking.carInfo }}</td>
                <td class="dates-cell">{{ booking.startDate }} → {{ booking.endDate }}</td>
                <td class="amount-cell">RM {{ booking.totalPrice?.toFixed(2) ?? '—' }}</td>
                <td>
                  <span :class="['status-pill', `pill-${booking.status}`]">
                    {{ booking.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Right Sidebar -->
      <aside class="sidebar">

        <!-- Pending Payments -->
        <section class="panel">
          <div class="panel-header">
            <div class="panel-header-left">
              <span class="panel-icon">💳</span>
              <h2>Pending Payments</h2>
            </div>
            <router-link to="/admin/clearance" class="panel-link">Manage →</router-link>
          </div>
          <div class="panel-body no-pad">
            <div v-if="pendingPayments.length === 0" class="empty-state small-empty">
              <span>✅ All payments cleared</span>
            </div>
            <div v-else class="payment-list">
              <div
                v-for="payment in pendingPayments"
                :key="payment.id"
                class="payment-row"
              >
                <div class="payment-info">
                  <span class="payment-id">{{ payment.bookingId }}</span>
                  <span class="payment-method">{{ payment.method }}</span>
                </div>
                <div class="payment-amount">
                  <span class="amount">RM {{ payment.amount.toFixed(2) }}</span>
                  <span class="pill-pending pill-badge">Pending</span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Fleet Status -->
        <section class="panel fleet-panel">
          <div class="panel-header">
            <div class="panel-header-left">
              <span class="panel-icon">🏎️</span>
              <h2>Fleet Status</h2>
            </div>
          </div>
          <div class="panel-body">
            <div class="fleet-stats">
              <div class="fleet-stat-item">
                <div class="fleet-stat-header">
                  <span>Available</span>
                  <strong>{{ availableCarsCount }} / {{ totalCarsCount }}</strong>
                </div>
                <div class="track">
                  <div class="fill fill-blue" :style="{ width: availabilityPct + '%' }"></div>
                </div>
                <span class="fleet-pct">{{ availabilityPct.toFixed(0) }}% of fleet</span>
              </div>

              <div class="fleet-stat-item">
                <div class="fleet-stat-header">
                  <span>Active Rentals</span>
                  <strong>{{ ongoingRentalsCount }}</strong>
                </div>
                <div class="track">
                  <div class="fill fill-orange" :style="{ width: ongoingPct + '%' }"></div>
                </div>
                <span class="fleet-pct">{{ ongoingPct.toFixed(0) }}% of fleet</span>
              </div>
            </div>

            <!-- Category Breakdown -->
            <div class="category-grid">
              <div
                v-for="(count, cat) in categoryBreakdown"
                :key="cat"
                class="category-chip"
              >
                <span class="cat-name">{{ cat }}</span>
                <span class="cat-count">{{ count }}</span>
              </div>
            </div>
          </div>
        </section>

        <!-- Quick Actions -->
        <section class="panel quick-actions-panel">
          <div class="panel-header">
            <div class="panel-header-left">
              <span class="panel-icon">⚡</span>
              <h2>Quick Actions</h2>
            </div>
          </div>
          <div class="panel-body actions-body">
            <router-link to="/admin/cars" class="action-card">
              <span class="action-icon">🚗</span>
              <span class="action-label">Manage Cars</span>
            </router-link>
            <router-link to="/admin/clearance" class="action-card">
              <span class="action-icon">💳</span>
              <span class="action-label">Payments</span>
            </router-link>
            <router-link to="/admin/analytics" class="action-card">
              <span class="action-icon">📊</span>
              <span class="action-label">Analytics</span>
            </router-link>
            <router-link to="/admin/rentals" class="action-card">
              <span class="action-icon">🔑</span>
              <span class="action-label">Rentals</span>
            </router-link>
          </div>
        </section>

      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  totalRevenue,
  pendingClearancesCount,
  totalBookingsCount,
  totalCarsCount,
  availableCarsCount,
  averageRating,
  payments,
  categoryBreakdown,
  mockData
} from '../../utils/mockData';

const recentBookings = computed(() =>
  [...mockData.bookings].reverse().slice(0, 5)
);

const pendingPayments = computed(() =>
  payments.value.filter(p => p.status === 'pending').slice(0, 4)
);

const ongoingRentalsCount = computed(() =>
  mockData.rentals.filter(r => r.status === 'ongoing' || r.status === 'booked').length
);

const availabilityPct = computed(() =>
  totalCarsCount.value === 0 ? 0 : (availableCarsCount.value / totalCarsCount.value) * 100
);

const ongoingPct = computed(() =>
  totalCarsCount.value === 0 ? 0 : (ongoingRentalsCount.value / totalCarsCount.value) * 100
);
</script>

<style scoped>
/* ── Layout ─────────────────────────────────────────────────── */
.dashboard {
  padding: 2rem 2.5rem 3rem;
  max-width: 1400px;
  margin: 0 auto;
}

/* ── Header ─────────────────────────────────────────────────── */
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 2.5rem;
}

.greeting {
  font-size: 0.95rem;
  color: var(--text-muted);
  margin-bottom: 0.35rem;
  font-weight: 500;
}

.dashboard-title {
  font-size: 2rem;
  font-weight: 800;
  color: var(--text-main);
  letter-spacing: -0.5px;
}

.header-cta {
  white-space: nowrap;
}

/* ── KPI Grid ───────────────────────────────────────────────── */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 1.25rem;
  margin-bottom: 2.5rem;
}

@media (max-width: 1200px) {
  .kpi-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
  .kpi-grid { grid-template-columns: repeat(2, 1fr); }
  .dashboard { padding: 1.5rem 1.25rem; }
}

.kpi-card {
  border-radius: 18px;
  padding: 1.5rem;
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  transition: transform 0.25s var(--transition-normal), box-shadow 0.25s ease;
  cursor: default;
  position: relative;
  overflow: hidden;
}

.kpi-card::after {
  content: '';
  position: absolute;
  bottom: -20px;
  right: -20px;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: rgba(255,255,255,0.12);
}

.kpi-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}

/* Card color themes */
.kpi-revenue  { background: linear-gradient(135deg, #1e40af, #3b82f6); color: #fff; }
.kpi-bookings { background: linear-gradient(135deg, #065f46, #10b981); color: #fff; }
.kpi-pending  { background: linear-gradient(135deg, #92400e, #f59e0b); color: #fff; }
.kpi-fleet    { background: linear-gradient(135deg, #4c1d95, #8b5cf6); color: #fff; }
.kpi-rating   { background: linear-gradient(135deg, #9f1239, #f43f5e); color: #fff; }

.kpi-icon {
  font-size: 2rem;
  line-height: 1;
  flex-shrink: 0;
  margin-top: 2px;
  filter: drop-shadow(0 2px 6px rgba(0,0,0,0.2));
}

.kpi-body {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  min-width: 0;
}

.kpi-label {
  font-size: 0.78rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  opacity: 0.85;
}

.kpi-value {
  font-size: 1.65rem;
  font-weight: 800;
  line-height: 1.15;
  letter-spacing: -0.5px;
}

.kpi-value em {
  font-style: normal;
  font-size: 1rem;
  font-weight: 600;
  opacity: 0.7;
}

.kpi-sub {
  font-size: 0.75rem;
  opacity: 0.7;
  font-weight: 500;
}

/* ── Main 2-col Grid ────────────────────────────────────────── */
.main-grid {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 1.75rem;
  align-items: start;
}

@media (max-width: 1100px) {
  .main-grid { grid-template-columns: 1fr; }
}

/* ── Panel Base ─────────────────────────────────────────────── */
.panel {
  background: var(--white);
  border-radius: 18px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--border-color);
  background: #fafbfc;
}

.panel-header-left {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.panel-icon {
  font-size: 1.1rem;
}

.panel-header h2 {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--text-main);
  margin: 0;
}

.panel-link {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--primary);
  text-decoration: none;
  transition: opacity 0.15s;
}

.panel-link:hover { opacity: 0.7; }

.panel-body {
  padding: 1.25rem 1.5rem;
}

.no-pad { padding: 0; }

/* ── Bookings Table ─────────────────────────────────────────── */
.bookings-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.bookings-table thead th {
  padding: 0.6rem 0.75rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-muted);
  border-bottom: 2px solid var(--border-color);
}

.table-row {
  transition: background 0.15s ease;
}

.table-row:hover { background: #f8faff; }

.bookings-table tbody td {
  padding: 0.9rem 0.75rem;
  border-bottom: 1px solid var(--border-color);
  vertical-align: middle;
}

.table-row:last-child td {
  border-bottom: none;
}

.customer-cell {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.customer-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: var(--primary-light);
  color: var(--primary);
  font-weight: 700;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.customer-name {
  font-weight: 600;
  color: var(--text-main);
}

.vehicle-cell {
  color: var(--text-muted);
  font-weight: 500;
}

.dates-cell {
  color: var(--text-muted);
  font-size: 0.83rem;
  white-space: nowrap;
}

.amount-cell {
  font-weight: 700;
  color: var(--text-main);
  white-space: nowrap;
}

/* ── Status Pills ───────────────────────────────────────────── */
.status-pill, .pill-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.pill-pending   { background: var(--status-pending-bg);  color: var(--status-pending); }
.pill-confirmed { background: var(--status-info-bg);     color: var(--status-info); }
.pill-cancelled { background: var(--status-danger-bg);   color: var(--status-danger); }
.pill-completed { background: var(--status-success-bg);  color: var(--status-success); }
.pill-active    { background: var(--status-info-bg);     color: var(--status-info); }

/* ── Sidebar ─────────────────────────────────────────────────  */
.sidebar {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* ── Payment List ───────────────────────────────────────────── */
.payment-list {
  display: flex;
  flex-direction: column;
}

.payment-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--border-color);
  transition: background 0.15s;
}

.payment-row:last-child { border-bottom: none; }
.payment-row:hover { background: #fafbff; }

.payment-info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.payment-id {
  font-weight: 700;
  font-size: 0.9rem;
  color: var(--text-main);
}

.payment-method {
  font-size: 0.78rem;
  color: var(--text-muted);
}

.payment-amount {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.3rem;
}

.amount {
  font-weight: 800;
  font-size: 0.95rem;
  color: var(--text-main);
}

/* ── Fleet Status ───────────────────────────────────────────── */
.fleet-stats {
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
  margin-bottom: 1.25rem;
}

.fleet-stat-item {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.fleet-stat-header {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
  color: var(--text-main);
}

.fleet-stat-header strong { font-weight: 700; }

.track {
  height: 8px;
  background: #e9ecef;
  border-radius: 99px;
  overflow: hidden;
}

.fill {
  height: 100%;
  border-radius: 99px;
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.fill-blue   { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
.fill-orange { background: linear-gradient(90deg, #f59e0b, #fbbf24); }

.fleet-pct {
  font-size: 0.75rem;
  color: var(--text-muted);
  font-weight: 500;
}

.category-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.6rem;
  border-top: 1px solid var(--border-color);
  padding-top: 1rem;
}

.category-chip {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: var(--primary-light);
  border-radius: 10px;
  padding: 0.5rem 0.4rem;
  gap: 0.15rem;
}

.cat-name {
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--primary);
  text-align: center;
}

.cat-count {
  font-size: 1.1rem;
  font-weight: 800;
  color: var(--primary-dark);
}

/* ── Quick Actions ──────────────────────────────────────────── */
.actions-body {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.action-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
  padding: 1rem 0.5rem;
  background: #f8faff;
  border: 1px solid var(--border-color);
  border-radius: 14px;
  text-decoration: none;
  transition: all 0.2s ease;
}

.action-card:hover {
  background: var(--primary);
  border-color: var(--primary);
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(37, 99, 235, 0.25);
}

.action-card:hover .action-label { color: #fff; }

.action-icon {
  font-size: 1.5rem;
}

.action-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-main);
  transition: color 0.2s;
}

/* ── Shared utilities ───────────────────────────────────────── */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 2rem;
  color: var(--text-muted);
  font-size: 0.9rem;
}

.empty-icon { font-size: 2rem; }

.small-empty {
  padding: 1.25rem 1.5rem;
  flex-direction: row;
  justify-content: center;
  font-weight: 500;
  color: var(--status-success);
  background: var(--status-success-bg);
}
</style>
