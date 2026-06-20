<template>
  <div>
    <!-- Back Link -->
    <router-link to="/customer/cars" class="back-link">← Back to Fleet</router-link>

    <!-- Loading State -->
    <div v-if="isLoading" style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
      <div class="skeleton" style="height: 400px; border-radius: var(--radius-md);"></div>
    </div>

    <!-- Car Not Found -->
    <div v-else-if="!car" class="empty-state">
      <div class="empty-state-icon">🚫</div>
      <h3 class="empty-state-title">Car Not Found</h3>
      <p class="empty-state-text">The vehicle you're looking for doesn't exist or has been removed.</p>
      <router-link to="/customer/cars" class="btn btn-primary" style="margin-top: 1rem;">
        Browse Available Cars
      </router-link>
    </div>

    <!-- Car Detail Content -->
    <div v-else class="car-detail-layout">
      <!-- Left Column: Car Details -->
      <div class="car-detail-hero">
        <div class="car-detail-image">
          <img v-if="car.imageUrl" :src="car.imageUrl" :alt="car.name" style="width: 100%; height: 100%; object-fit: cover;" />
          <span v-else style="font-size: 7rem;">🚗</span>
        </div>
        <div class="car-detail-info">
          <div class="car-detail-brand">{{ car.brand }}</div>
          <h1 class="car-detail-name">{{ car.name }}</h1>
          <div style="margin-bottom: 1rem;">
            <span
              class="availability-dot"
              :class="car.available ? 'available' : 'unavailable'"
              style="font-size: 0.85rem;"
            >
              {{ car.available ? 'Available for Booking' : 'Currently Unavailable' }}
            </span>
          </div>
          <p class="car-detail-description">{{ car.description }}</p>

          <!-- Specs Grid -->
          <div class="car-detail-specs-grid">
            <div class="detail-spec-item">
              <span class="detail-spec-icon">👤</span>
              <div class="detail-spec-text">
                <span class="detail-spec-label">Seats</span>
                <span class="detail-spec-value">{{ car.seats }} Passengers</span>
              </div>
            </div>
            <div class="detail-spec-item">
              <span class="detail-spec-icon">⚙️</span>
              <div class="detail-spec-text">
                <span class="detail-spec-label">Transmission</span>
                <span class="detail-spec-value">{{ car.transmission }}</span>
              </div>
            </div>
            <div class="detail-spec-item">
              <span class="detail-spec-icon">⛽</span>
              <div class="detail-spec-text">
                <span class="detail-spec-label">Fuel Type</span>
                <span class="detail-spec-value">{{ car.fuelType }}</span>
              </div>
            </div>
            <div class="detail-spec-item">
              <span class="detail-spec-icon">📅</span>
              <div class="detail-spec-text">
                <span class="detail-spec-label">Year</span>
                <span class="detail-spec-value">{{ car.year }}</span>
              </div>
            </div>
            <div class="detail-spec-item">
              <span class="detail-spec-icon">🏷️</span>
              <div class="detail-spec-text">
                <span class="detail-spec-label">Category</span>
                <span class="detail-spec-value">{{ car.category }}</span>
              </div>
            </div>
            <div class="detail-spec-item">
              <span class="detail-spec-icon">💰</span>
              <div class="detail-spec-text">
                <span class="detail-spec-label">Daily Rate</span>
                <span class="detail-spec-value">RM{{ car.pricePerDay.toFixed(2) }}</span>
              </div>
            </div>
          </div>

          <!-- Features -->
          <div>
            <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">Features</span>
            <div class="features-container">
              <span v-for="feature in car.features" :key="feature" class="feature-tag">
                ✓ {{ feature }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Booking Form -->
      <div>
        <div class="booking-card">
          <!-- Success State -->
          <div v-if="bookingSuccess">
            <div class="success-animation">
              <div class="success-icon">✓</div>
              <h3 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 0.5rem;">Booking Confirmed!</h3>
              <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;">
                Your reservation for <strong>{{ car.brand }} {{ car.name }}</strong> has been placed.
              </p>
              <p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 1.5rem;">
                Booking ID: <strong>{{ confirmedBookingId }}</strong>
              </p>
              <div style="display: flex; gap: 0.75rem; justify-content: center;">
                <router-link to="/customer/cars" class="btn btn-outline">Browse More Cars</router-link>
                <button @click="resetForm" class="btn btn-primary">Book Again</button>
              </div>
            </div>
          </div>

          <!-- Booking Form -->
          <div v-else>
            <div class="booking-card-title">📋 Reserve This Vehicle</div>

            <form @submit.prevent="handleBooking" :class="{ 'shake': hasError }" id="booking-form">
              <!-- Full Name -->
              <div class="form-group">
                <label class="form-label" for="booking-name">Full Name *</label>
                <input
                  type="text"
                  id="booking-name"
                  v-model="form.customerName"
                  class="form-control"
                  :class="{
                    'is-valid': form.customerName && isNameValid,
                    'is-invalid': form.customerName && !isNameValid
                  }"
                  placeholder="Enter your full name"
                  @blur="touched.customerName = true"
                />
                <span v-if="touched.customerName && !isNameValid" class="invalid-feedback">
                  Name must be at least 3 characters long.
                </span>
              </div>

              <!-- Phone Number -->
              <div class="form-group">
                <label class="form-label" for="booking-phone">Phone Number *</label>
                <input
                  type="tel"
                  id="booking-phone"
                  v-model="form.phone"
                  class="form-control"
                  :class="{
                    'is-valid': form.phone && isPhoneValid,
                    'is-invalid': form.phone && !isPhoneValid
                  }"
                  placeholder="+63 9XX XXX XXXX"
                  @blur="touched.phone = true"
                />
                <span v-if="touched.phone && !isPhoneValid" class="invalid-feedback">
                  Please enter a valid phone number (at least 10 digits).
                </span>
              </div>

              <!-- Pickup Date -->
              <div class="form-group">
                <label class="form-label" for="booking-pickup">Pickup Date *</label>
                <input
                  type="date"
                  id="booking-pickup"
                  v-model="form.pickupDate"
                  class="form-control"
                  :class="{
                    'is-valid': form.pickupDate && isPickupValid,
                    'is-invalid': form.pickupDate && !isPickupValid
                  }"
                  :min="todayStr"
                  @blur="touched.pickupDate = true"
                />
                <span v-if="touched.pickupDate && !isPickupValid" class="invalid-feedback">
                  Pickup date must be today or a future date.
                </span>
              </div>

              <!-- Return Date -->
              <div class="form-group">
                <label class="form-label" for="booking-return">Return Date *</label>
                <input
                  type="date"
                  id="booking-return"
                  v-model="form.returnDate"
                  class="form-control"
                  :class="{
                    'is-valid': form.returnDate && isReturnValid,
                    'is-invalid': form.returnDate && !isReturnValid
                  }"
                  :min="form.pickupDate || todayStr"
                  @blur="touched.returnDate = true"
                />
                <span v-if="touched.returnDate && !isReturnValid" class="invalid-feedback">
                  Return date must be after the pickup date.
                </span>
              </div>

              <!-- Special Requests -->
              <div class="form-group">
                <label class="form-label" for="booking-requests">Special Requests (Optional)</label>
                <textarea
                  id="booking-requests"
                  v-model="form.specialRequests"
                  class="form-control"
                  rows="3"
                  placeholder="Child seat, GPS, airport pickup, etc."
                  maxlength="300"
                ></textarea>
                <div class="char-counter" :class="{ 'warn': form.specialRequests.length > 250, 'danger': form.specialRequests.length > 280 }">
                  {{ form.specialRequests.length }} / 300
                </div>
              </div>

              <!-- Price Calculation Summary -->
              <div v-if="rentalDays > 0" class="booking-summary">
                <div class="booking-summary-row">
                  <span>Daily Rate</span>
                  <span>RM{{ car.pricePerDay.toFixed(2) }}</span>
                </div>
                <div class="booking-summary-row">
                  <span>Duration</span>
                  <span>{{ rentalDays }} day{{ rentalDays !== 1 ? 's' : '' }}</span>
                </div>
                <div class="booking-summary-total">
                  <span>Total</span>
                  <span>RM{{ totalPrice.toFixed(2) }}</span>
                </div>
              </div>

              <!-- Submit -->
              <button
                type="submit"
                class="btn btn-primary"
                style="width: 100%; margin-top: 1.5rem;"
                :disabled="!isFormValid || isSubmitting || !car.available"
                id="submit-booking-btn"
              >
                <span v-if="isSubmitting">Processing Booking...</span>
                <span v-else-if="!car.available">Vehicle Unavailable</span>
                <span v-else>Confirm Booking — RM{{ totalPrice.toFixed(2) }}</span>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { fetchCarById, createBooking } from '../../utils/mockData';

const route = useRoute();
const car = ref(null);
const isLoading = ref(true);
const isSubmitting = ref(false);
const hasError = ref(false);
const bookingSuccess = ref(false);
const confirmedBookingId = ref('');

const todayStr = new Date().toISOString().split('T')[0];

const form = reactive({
  customerName: '',
  phone: '',
  pickupDate: '',
  returnDate: '',
  specialRequests: ''
});

const touched = reactive({
  customerName: false,
  phone: false,
  pickupDate: false,
  returnDate: false
});

// Validation rules
const isNameValid = computed(() => form.customerName.trim().length >= 3);
const isPhoneValid = computed(() => {
  const digits = form.phone.replace(/\D/g, '');
  return digits.length >= 10;
});
const isPickupValid = computed(() => {
  if (!form.pickupDate) return false;
  return form.pickupDate >= todayStr;
});
const isReturnValid = computed(() => {
  if (!form.returnDate || !form.pickupDate) return false;
  return form.returnDate > form.pickupDate;
});

const rentalDays = computed(() => {
  if (!form.pickupDate || !form.returnDate) return 0;
  const pickup = new Date(form.pickupDate);
  const ret = new Date(form.returnDate);
  const diff = Math.ceil((ret - pickup) / (1000 * 60 * 60 * 24));
  return diff > 0 ? diff : 0;
});

const totalPrice = computed(() => {
  if (!car.value) return 0;
  return rentalDays.value * car.value.pricePerDay;
});

const isFormValid = computed(() => {
  return isNameValid.value && isPhoneValid.value && isPickupValid.value && isReturnValid.value;
});

const handleBooking = async () => {
  // Mark all fields as touched
  touched.customerName = true;
  touched.phone = true;
  touched.pickupDate = true;
  touched.returnDate = true;

  if (!isFormValid.value) {
    hasError.value = true;
    setTimeout(() => { hasError.value = false; }, 400);
    return;
  }

  isSubmitting.value = true;
  try {
    const booking = await createBooking({
      carId: car.value.id,
      carName: `${car.value.brand} ${car.value.name}`,
      customerName: form.customerName.trim(),
      phone: form.phone.trim(),
      pickupDate: form.pickupDate,
      returnDate: form.returnDate,
      totalPrice: totalPrice.value,
      specialRequests: form.specialRequests.trim()
    });
    confirmedBookingId.value = booking.id;
    bookingSuccess.value = true;
  } catch (err) {
    console.error('Booking failed:', err);
    hasError.value = true;
    setTimeout(() => { hasError.value = false; }, 400);
  } finally {
    isSubmitting.value = false;
  }
};

const resetForm = () => {
  form.customerName = '';
  form.phone = '';
  form.pickupDate = '';
  form.returnDate = '';
  form.specialRequests = '';
  touched.customerName = false;
  touched.phone = false;
  touched.pickupDate = false;
  touched.returnDate = false;
  bookingSuccess.value = false;
  confirmedBookingId.value = '';
};

onMounted(async () => {
  isLoading.value = true;
  try {
    const carId = route.params.id;
    car.value = await fetchCarById(carId);
  } catch (err) {
    console.error('Failed to load car:', err);
  } finally {
    isLoading.value = false;
  }
});
</script>
