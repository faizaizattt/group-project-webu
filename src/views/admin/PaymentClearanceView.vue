<template>
  <div>
    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Payment Clearance Log</h1>
        <p class="page-subtitle">Monitor, inspect, and authorize rental transaction payments to approve rentals.</p>
      </div>
      <div style="display: flex; gap: 0.5rem;">
        <div style="background: var(--white); border: 1px solid var(--border-color); padding: 0.5rem 1rem; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
          <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--status-pending);"></span>
          Pending: {{ pendingCount }}
        </div>
        <div style="background: var(--white); border: 1px solid var(--border-color); padding: 0.5rem 1rem; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
          <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--status-success);"></span>
          Cleared: {{ clearedCount }}
        </div>
      </div>
    </div>

    <!-- Filters and Search Bar -->
    <div class="filters-row">
      <div>
        <input 
          type="text" 
          v-model="searchQuery" 
          class="form-control" 
          placeholder="🔍 Search Booking ID or Ref..." 
          style="min-width: 250px; padding: 0.5rem 1rem;"
        />
      </div>
      <div style="display: flex; gap: 0.5rem;">
        <select v-model="statusFilter" class="filter-select">
          <option value="">All Statuses</option>
          <option value="pending">Pending Only</option>
          <option value="paid">Cleared Only</option>
          <option value="flagged">Flagged Only</option>
        </select>
      </div>
    </div>

    <!-- Main Clearance Grid Table -->
    <div class="card" style="padding: 0; overflow: hidden; border-radius: var(--radius-md);">
      <div class="table-container" style="margin: 0; border: none; box-shadow: none;">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Booking ID</th>
              <th>Date Filed</th>
              <th>Amount ($)</th>
              <th>Method</th>
              <th>Verification / Reference Memo</th>
              <th>Status</th>
              <th style="text-align: center;">Clearance Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredPayments.length === 0">
              <td colspan="8" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                <span style="font-size: 2.5rem; display: block; margin-bottom: 0.5rem;">📝</span>
                No transaction records match the current filters.
              </td>
            </tr>
            <tr v-else v-for="pay in filteredPayments" :key="pay.id">
              <td style="font-weight: 800; font-family: monospace;">{{ pay.id }}</td>
              <td style="font-weight: 700; color: var(--primary-dark);">{{ pay.bookingId }}</td>
              <td>{{ pay.date }}</td>
              <td style="font-weight: 700;">${{ pay.amount.toFixed(2) }}</td>
              <td>{{ pay.method }}</td>
              <td style="font-size: 0.85rem; color: var(--text-muted); font-style: italic;">
                {{ pay.details }}
              </td>
              <td>
                <span class="badge" :class="`badge-${pay.status}`">
                  {{ pay.status === 'paid' ? 'Cleared' : pay.status }}
                </span>
              </td>
              <td style="text-align: center;">
                <div v-if="pay.status === 'pending'" style="display: inline-flex; gap: 0.5rem; justify-content: center; align-items: center;">
                  <button 
                    @click="handleApprove(pay.id)" 
                    class="btn btn-sm btn-success"
                    :disabled="processingId === pay.id"
                  >
                    {{ processingId === pay.id ? '...' : 'Approve' }}
                  </button>
                  <button 
                    @click="handleFlag(pay.id)" 
                    class="btn btn-sm btn-danger"
                    :disabled="processingId === pay.id"
                  >
                    {{ processingId === pay.id ? '...' : 'Flag' }}
                  </button>
                </div>
                <div v-else style="display: inline-flex; gap: 0.5rem; justify-content: center; align-items: center;">
                  <button 
                    v-if="pay.status === 'flagged'" 
                    @click="handleApprove(pay.id)" 
                    class="btn btn-sm btn-success" 
                    style="opacity: 0.8;"
                  >
                    Clear Flag
                  </button>
                  <span v-else style="font-size: 0.75rem; color: var(--status-success); font-weight: 700;">
                    ✓ Authorized Verified
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
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
import { ref, computed } from 'vue';
import { payments, clearPayment, flagPayment } from '../../utils/mockData';

const searchQuery = ref('');
const statusFilter = ref('');
const processingId = ref(null);
const toasts = ref([]);

// Totals computations
const pendingCount = computed(() => payments.value.filter(p => p.status === 'pending').length);
const clearedCount = computed(() => payments.value.filter(p => p.status === 'paid').length);

const filteredPayments = computed(() => {
  return payments.value.filter(pay => {
    // Filter by Query
    const query = searchQuery.value.toLowerCase().trim();
    const matchesQuery = !query || 
                         pay.bookingId.toLowerCase().includes(query) || 
                         pay.id.toLowerCase().includes(query) ||
                         pay.details.toLowerCase().includes(query);
    
    // Filter by status
    const matchesStatus = !statusFilter.value || pay.status === statusFilter.value;
    
    return matchesQuery && matchesStatus;
  });
});

// Async Action: Approve Clearance
const handleApprove = async (id) => {
  processingId.value = id;
  try {
    await clearPayment(id);
    addToast(`Transaction ${id} successfully cleared and approved!`, 'success');
  } catch (err) {
    addToast('Failed to clear transaction.', 'danger');
  } finally {
    processingId.value = null;
  }
};

// Async Action: Flag Transaction
const handleFlag = async (id) => {
  processingId.value = id;
  try {
    await flagPayment(id);
    addToast(`Transaction ${id} flagged as disputed. Payment locked.`, 'danger');
  } catch (err) {
    addToast('Failed to flag transaction.', 'danger');
  } finally {
    processingId.value = null;
  }
};

const addToast = (text, type = 'success') => {
  const id = Date.now();
  toasts.value.push({ id, text, type });
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id);
  }, 4000);
};
</script>
