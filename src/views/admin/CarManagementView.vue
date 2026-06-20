<template>
  <div>
    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Car Inventory</h1>
        <p class="page-subtitle">Manage the BlueDrive fleet — add, edit, and track all vehicles</p>
      </div>
      <button class="btn btn-primary" @click="openAddModal" id="add-car-btn">
        + Add New Vehicle
      </button>
    </div>

    <!-- Metric Cards -->
    <div class="metrics-grid">
      <div class="metric-card">
        <div class="metric-icon-box" style="background: #eff6ff; color: #2563eb;">🚗</div>
        <div class="metric-info">
          <span class="metric-label">Total Fleet</span>
          <span class="metric-value">{{ totalCarsCount }}</span>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon-box" style="background: #d1fae5; color: #059669;">✅</div>
        <div class="metric-info">
          <span class="metric-label">Available</span>
          <span class="metric-value">{{ availableCarsCount }}</span>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon-box" style="background: #fef3c7; color: #d97706;">📋</div>
        <div class="metric-info">
          <span class="metric-label">Total Bookings</span>
          <span class="metric-value">{{ totalBookingsCount }}</span>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon-box" style="background: #ede9fe; color: #7c3aed;">💲</div>
        <div class="metric-info">
          <span class="metric-label">Avg. Price/Day</span>
          <span class="metric-value">RM{{ averageCarPrice.toFixed(0) }}</span>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-row">
      <div class="search-bar-container">
        <span class="search-bar-icon">🔍</span>
        <input
          id="admin-car-search"
          type="text"
          class="search-bar"
          placeholder="Search inventory..."
          v-model="searchQuery"
        />
      </div>
      <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <select id="admin-filter-category" v-model="filterCategory" class="filter-select">
          <option value="all">All Categories</option>
          <option value="Sedan">Sedan</option>
          <option value="SUV">SUV</option>
          <option value="Sports">Sports</option>
          <option value="Electric">Electric</option>
          <option value="Luxury">Luxury</option>
        </select>
        <select id="admin-filter-status" v-model="filterStatus" class="filter-select">
          <option value="all">All Status</option>
          <option value="available">Available</option>
          <option value="unavailable">Unavailable</option>
        </select>
      </div>
    </div>

    <!-- Data Table -->
    <div class="table-container">
      <table class="data-table" id="car-inventory-table">
        <thead>
          <tr>
            <th>Vehicle</th>
            <th>Category</th>
            <th>Year</th>
            <th>Price/Day</th>
            <th>Seats</th>
            <th>Status</th>
            <th style="text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="car in filteredCars" :key="car.id" :id="`admin-car-${car.id}`">
            <td>
              <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 42px; height: 42px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; overflow: hidden; background: #f1f5f9;">
                  <img v-if="car.imageUrl" :src="car.imageUrl" :alt="car.name" style="width: 100%; height: 100%; object-fit: cover;" />
                  <span v-else>🚗</span>
                </div>
                <div>
                  <div style="font-weight: 600; font-size: 0.9rem;">{{ car.brand }}</div>
                  <div style="font-size: 0.75rem; color: var(--text-muted);">{{ car.name }} · {{ car.transmission }}</div>
                </div>
              </div>
            </td>
            <td>
              <span class="badge" :style="getCategoryStyle(car.category)">{{ car.category }}</span>
            </td>
            <td>{{ car.year }}</td>
            <td style="font-weight: 700;">RM{{ car.pricePerDay.toFixed(2) }}</td>
            <td>{{ car.seats }}</td>
            <td>
              <span class="badge" :class="car.available ? 'badge-success' : 'badge-danger'">
                {{ car.available ? 'Available' : 'Unavailable' }}
              </span>
            </td>
            <td style="text-align: right;">
              <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button class="btn btn-outline btn-sm" @click="toggleAvailability(car)" :id="`toggle-${car.id}`">
                  {{ car.available ? '🔒' : '🔓' }}
                </button>
                <button class="btn btn-outline btn-sm" @click="openEditModal(car)" :id="`edit-${car.id}`">
                  ✏️ Edit
                </button>
                <button class="btn btn-danger btn-sm" @click="confirmDelete(car)" :id="`delete-${car.id}`">
                  🗑️
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="filteredCars.length === 0">
            <td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-muted);">
              No vehicles match your search criteria.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Add/Edit Car Modal -->
    <div v-if="showFormModal" class="modal-overlay" @click.self="closeFormModal">
      <div class="modal-content" style="max-width: 600px;">
        <button class="modal-close" @click="closeFormModal">✕</button>
        <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem;">
          {{ isEditing ? '✏️ Edit Vehicle' : '+ Add New Vehicle' }}
        </h3>

        <form @submit.prevent="handleSubmitCar" :class="{ 'shake': formHasError }" id="car-form">
          <!-- Row: Name + Brand -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
              <label class="form-label" for="car-name">Car Name *</label>
              <input
                type="text"
                id="car-name"
                v-model="carForm.name"
                class="form-control"
                :class="{ 'is-valid': carForm.name && carForm.name.trim().length >= 3, 'is-invalid': carTouched.name && carForm.name.trim().length < 3 }"
                placeholder="e.g. Corolla Cross"
                @blur="carTouched.name = true"
              />
              <span v-if="carTouched.name && carForm.name.trim().length < 3" class="invalid-feedback">
                At least 3 characters required.
              </span>
            </div>
            <div class="form-group">
              <label class="form-label" for="car-brand">Brand *</label>
              <input
                type="text"
                id="car-brand"
                v-model="carForm.brand"
                class="form-control"
                :class="{ 'is-valid': carForm.brand && carForm.brand.trim().length >= 2, 'is-invalid': carTouched.brand && carForm.brand.trim().length < 2 }"
                placeholder="e.g. Toyota"
                @blur="carTouched.brand = true"
              />
              <span v-if="carTouched.brand && carForm.brand.trim().length < 2" class="invalid-feedback">
                Brand is required.
              </span>
            </div>
          </div>

          <!-- Row: Category + Year -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
              <label class="form-label" for="car-category">Category *</label>
              <select id="car-category" v-model="carForm.category" class="form-control" @blur="carTouched.category = true">
                <option value="">Select category</option>
                <option value="Sedan">Sedan</option>
                <option value="SUV">SUV</option>
                <option value="Sports">Sports</option>
                <option value="Electric">Electric</option>
                <option value="Luxury">Luxury</option>
              </select>
              <span v-if="carTouched.category && !carForm.category" class="invalid-feedback">
                Please select a category.
              </span>
            </div>
            <div class="form-group">
              <label class="form-label" for="car-year">Year *</label>
              <input
                type="number"
                id="car-year"
                v-model.number="carForm.year"
                class="form-control"
                :class="{ 'is-valid': carForm.year >= 2000 && carForm.year <= 2027, 'is-invalid': carTouched.year && (carForm.year < 2000 || carForm.year > 2027) }"
                placeholder="2025"
                min="2000"
                max="2027"
                @blur="carTouched.year = true"
              />
              <span v-if="carTouched.year && (carForm.year < 2000 || carForm.year > 2027)" class="invalid-feedback">
                Year must be between 2000 and 2027.
              </span>
            </div>
          </div>

          <!-- Row: Price + Seats -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
              <label class="form-label" for="car-price">Price Per Day (RM) *</label>
              <input
                type="number"
                id="car-price"
                v-model.number="carForm.pricePerDay"
                class="form-control"
                :class="{ 'is-valid': carForm.pricePerDay > 0, 'is-invalid': carTouched.pricePerDay && carForm.pricePerDay <= 0 }"
                placeholder="75.00"
                step="0.01"
                min="1"
                @blur="carTouched.pricePerDay = true"
              />
              <span v-if="carTouched.pricePerDay && carForm.pricePerDay <= 0" class="invalid-feedback">
                Price must be a positive number.
              </span>
            </div>
            <div class="form-group">
              <label class="form-label" for="car-seats">Seats *</label>
              <input
                type="number"
                id="car-seats"
                v-model.number="carForm.seats"
                class="form-control"
                :class="{ 'is-valid': carForm.seats >= 2 && carForm.seats <= 9, 'is-invalid': carTouched.seats && (carForm.seats < 2 || carForm.seats > 9) }"
                placeholder="5"
                min="2"
                max="9"
                @blur="carTouched.seats = true"
              />
              <span v-if="carTouched.seats && (carForm.seats < 2 || carForm.seats > 9)" class="invalid-feedback">
                Seats must be between 2 and 9.
              </span>
            </div>
          </div>

          <!-- Row: Transmission + Fuel -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
              <label class="form-label" for="car-transmission">Transmission *</label>
              <select id="car-transmission" v-model="carForm.transmission" class="form-control" @blur="carTouched.transmission = true">
                <option value="">Select</option>
                <option value="Automatic">Automatic</option>
                <option value="Manual">Manual</option>
              </select>
              <span v-if="carTouched.transmission && !carForm.transmission" class="invalid-feedback">
                Please select a transmission type.
              </span>
            </div>
            <div class="form-group">
              <label class="form-label" for="car-fuel">Fuel Type *</label>
              <select id="car-fuel" v-model="carForm.fuelType" class="form-control" @blur="carTouched.fuelType = true">
                <option value="">Select</option>
                <option value="Petrol">Petrol</option>
                <option value="Diesel">Diesel</option>
                <option value="Electric">Electric</option>
                <option value="Hybrid">Hybrid</option>
              </select>
              <span v-if="carTouched.fuelType && !carForm.fuelType" class="invalid-feedback">
                Please select a fuel type.
              </span>
            </div>
          </div>

          <!-- Description -->
          <div class="form-group">
            <label class="form-label" for="car-description">Description</label>
            <textarea
              id="car-description"
              v-model="carForm.description"
              class="form-control"
              rows="3"
              placeholder="Brief vehicle description..."
              maxlength="500"
            ></textarea>
            <div class="char-counter" :class="{ 'warn': carForm.description.length > 400, 'danger': carForm.description.length > 475 }">
              {{ carForm.description.length }} / 500
            </div>
          </div>

          <!-- Features -->
          <div class="form-group">
            <label class="form-label" for="car-features">Features (comma-separated)</label>
            <input
              type="text"
              id="car-features"
              v-model="carForm.featuresStr"
              class="form-control"
              placeholder="e.g. Autopilot, Heated Seats, Navigation"
            />
            <div v-if="parsedFeatures.length > 0" class="features-container" style="margin-top: 0.5rem;">
              <span v-for="f in parsedFeatures" :key="f" class="feature-tag">{{ f }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem;">
            <button type="button" class="btn btn-outline" @click="closeFormModal">Cancel</button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="!isCarFormValid || isFormSubmitting"
              id="submit-car-btn"
            >
              <span v-if="isFormSubmitting">{{ isEditing ? 'Updating...' : 'Adding...' }}</span>
              <span v-else>{{ isEditing ? 'Update Vehicle' : 'Add Vehicle' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
      <div class="modal-content" style="max-width: 420px;">
        <div class="confirm-modal-body">
          <div class="confirm-modal-icon">🗑️</div>
          <h3 class="confirm-modal-title">Remove Vehicle?</h3>
          <p class="confirm-modal-text">
            Are you sure you want to delete <strong>{{ deleteTarget?.brand }} {{ deleteTarget?.name }}</strong>?
            This action cannot be undone.
          </p>
          <div class="confirm-modal-actions">
            <button class="btn btn-outline" @click="showDeleteModal = false" :disabled="isDeleting">Cancel</button>
            <button class="btn btn-danger" @click="handleDelete" :disabled="isDeleting" id="confirm-delete-btn">
              {{ isDeleting ? 'Deleting...' : 'Delete Vehicle' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast Notifications -->
    <div class="toast-container">
      <div v-for="toast in toasts" :key="toast.id" class="toast" :class="'toast-' + toast.type">
        {{ toast.message }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import {
  cars,
  totalCarsCount,
  availableCarsCount,
  totalBookingsCount,
  averageCarPrice,
  addCar,
  updateCar,
  deleteCar,
  toggleCarAvailability
} from '../../utils/mockData';

// Search & Filters
const searchQuery = ref('');
const filterCategory = ref('all');
const filterStatus = ref('all');

const filteredCars = computed(() => {
  let result = [...cars.value];

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(c =>
      c.name.toLowerCase().includes(q) ||
      c.brand.toLowerCase().includes(q) ||
      c.id.toLowerCase().includes(q)
    );
  }
  if (filterCategory.value !== 'all') {
    result = result.filter(c => c.category === filterCategory.value);
  }
  if (filterStatus.value === 'available') {
    result = result.filter(c => c.available);
  } else if (filterStatus.value === 'unavailable') {
    result = result.filter(c => !c.available);
  }

  return result;
});

// Category badge styling
const getCategoryStyle = (category) => {
  const styles = {
    Sedan: { background: '#eff6ff', color: '#2563eb' },
    SUV: { background: '#fef3c7', color: '#d97706' },
    Sports: { background: '#fee2e2', color: '#dc2626' },
    Electric: { background: '#d1fae5', color: '#059669' },
    Luxury: { background: '#ede9fe', color: '#7c3aed' }
  };
  return styles[category] || { background: '#f1f5f9', color: '#64748b' };
};

// ─── Form Modal ───
const showFormModal = ref(false);
const isEditing = ref(false);
const editingCarId = ref(null);
const isFormSubmitting = ref(false);
const formHasError = ref(false);

const getDefaultForm = () => ({
  name: '',
  brand: '',
  category: '',
  year: 2025,
  pricePerDay: 0,
  seats: 5,
  transmission: '',
  fuelType: '',
  description: '',
  featuresStr: '',
  imageUrl: 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=800&auto=format&fit=crop'
});

const carForm = reactive(getDefaultForm());
const carTouched = reactive({
  name: false,
  brand: false,
  category: false,
  year: false,
  pricePerDay: false,
  seats: false,
  transmission: false,
  fuelType: false
});

const parsedFeatures = computed(() => {
  if (!carForm.featuresStr) return [];
  return carForm.featuresStr.split(',').map(f => f.trim()).filter(f => f.length > 0);
});

const isCarFormValid = computed(() => {
  return (
    carForm.name.trim().length >= 3 &&
    carForm.brand.trim().length >= 2 &&
    carForm.category !== '' &&
    carForm.year >= 2000 && carForm.year <= 2027 &&
    carForm.pricePerDay > 0 &&
    carForm.seats >= 2 && carForm.seats <= 9 &&
    carForm.transmission !== '' &&
    carForm.fuelType !== ''
  );
});

const openAddModal = () => {
  isEditing.value = false;
  editingCarId.value = null;
  Object.assign(carForm, getDefaultForm());
  resetCarTouched();
  showFormModal.value = true;
};

const openEditModal = (car) => {
  isEditing.value = true;
  editingCarId.value = car.id;
  Object.assign(carForm, {
    name: car.name,
    brand: car.brand,
    category: car.category,
    year: car.year,
    pricePerDay: car.pricePerDay,
    seats: car.seats,
    transmission: car.transmission,
    fuelType: car.fuelType,
    description: car.description || '',
    featuresStr: (car.features || []).join(', '),
    imageUrl: car.imageUrl || 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=800&auto=format&fit=crop'
  });
  resetCarTouched();
  showFormModal.value = true;
};

const closeFormModal = () => {
  showFormModal.value = false;
};

const resetCarTouched = () => {
  Object.keys(carTouched).forEach(k => carTouched[k] = false);
};

const handleSubmitCar = async () => {
  // Touch all fields
  Object.keys(carTouched).forEach(k => carTouched[k] = true);

  if (!isCarFormValid.value) {
    formHasError.value = true;
    setTimeout(() => { formHasError.value = false; }, 400);
    return;
  }

  isFormSubmitting.value = true;

  const payload = {
    name: carForm.name.trim(),
    brand: carForm.brand.trim(),
    category: carForm.category,
    year: carForm.year,
    pricePerDay: parseFloat(carForm.pricePerDay),
    seats: parseInt(carForm.seats),
    transmission: carForm.transmission,
    fuelType: carForm.fuelType,
    description: carForm.description.trim(),
    features: parsedFeatures.value,
    imageUrl: carForm.imageUrl
  };

  try {
    if (isEditing.value) {
      await updateCar(editingCarId.value, payload);
      showToast('Vehicle updated successfully!', 'success');
    } else {
      await addCar(payload);
      showToast('New vehicle added to fleet!', 'success');
    }
    closeFormModal();
  } catch (err) {
    console.error('Car form submit error:', err);
    showToast('Operation failed. Please try again.', 'danger');
  } finally {
    isFormSubmitting.value = false;
  }
};

// ─── Delete ───
const showDeleteModal = ref(false);
const deleteTarget = ref(null);
const isDeleting = ref(false);

const confirmDelete = (car) => {
  deleteTarget.value = car;
  showDeleteModal.value = true;
};

const handleDelete = async () => {
  if (!deleteTarget.value) return;
  isDeleting.value = true;
  try {
    await deleteCar(deleteTarget.value.id);
    showToast(`${deleteTarget.value.brand} ${deleteTarget.value.name} removed from fleet.`, 'success');
    showDeleteModal.value = false;
    deleteTarget.value = null;
  } catch (err) {
    showToast('Delete failed. Please try again.', 'danger');
  } finally {
    isDeleting.value = false;
  }
};

// ─── Toggle Availability ───
const toggleAvailability = async (car) => {
  try {
    await toggleCarAvailability(car.id);
    const newStatus = car.available ? 'available' : 'unavailable';
    showToast(`${car.brand} ${car.name} is now ${newStatus}.`, 'success');
  } catch (err) {
    showToast('Failed to toggle availability.', 'danger');
  }
};

// ─── Toast Notifications ───
const toasts = ref([]);
let toastCounter = 0;

const showToast = (message, type = 'success') => {
  const id = ++toastCounter;
  toasts.value.push({ id, message, type });
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id);
  }, 3500);
};
</script>
