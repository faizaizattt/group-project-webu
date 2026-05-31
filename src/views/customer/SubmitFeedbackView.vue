<template>
  <div>
    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Submit Feedback</h1>
        <p class="page-subtitle">We value your opinion! Share your rental experience to help us improve.</p>
      </div>
    </div>

    <!-- Active Grid -->
    <div class="view-grid-two-cols" style="grid-template-columns: 1fr;">
      
      <!-- Scenario 1: Active Booking Awaiting Review -->
      <div v-if="activeBooking" style="max-width: 800px; margin: 0 auto; width: 100%;">
        
        <!-- Booking Details Card -->
        <div class="card" style="margin-bottom: 2rem; border-left: 5px solid var(--primary); background: linear-gradient(to right, rgba(37, 99, 235, 0.02), rgba(255,255,255,0.9));">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
            <div>
              <span class="badge badge-success" style="margin-bottom: 0.5rem;">Recently Completed</span>
              <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.25rem;">
                {{ activeBooking.car }}
              </h2>
              <p style="color: var(--text-muted); font-size: 0.9rem;">
                Booking Reference: <strong style="font-family: monospace; color: var(--primary-dark);">{{ activeBooking.bookingId }}</strong>
              </p>
            </div>
            <div style="text-align: right;">
              <div style="font-size: 0.8rem; color: var(--text-light); text-transform: uppercase; font-weight: 700;">Amount Paid</div>
              <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary);">${{ activeBooking.totalAmount.toFixed(2) }}</div>
              <div style="font-size: 0.8rem; color: var(--text-muted);">Returned on {{ activeBooking.date }}</div>
            </div>
          </div>
        </div>

        <!-- Feedback Submission Card -->
        <div class="card" :class="{ 'shake': formHasError }">
          <h3 class="card-title">Leave Your Rating & Review</h3>
          <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">
            Please select a star rating and describe your experience with the vehicle's cleanliness, performance, and our staff support.
          </p>

          <form @submit.prevent="submitFeedbackForm">
            
            <!-- Interactive Star Picker -->
            <div class="form-group">
              <label class="form-label">Overall Star Rating</label>
              <div class="star-rating-container">
                <button 
                  v-for="star in 5" 
                  :key="star"
                  type="button" 
                  class="star-btn"
                  :class="{ 
                    'active': star <= form.stars, 
                    'hovered': star <= hoverStars 
                  }"
                  @mouseover="hoverStars = star"
                  @mouseleave="hoverStars = 0"
                  @click="selectStars(star)"
                >
                  ★
                </button>
                <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-muted); margin-left: 0.5rem;">
                  {{ ratingLabel }}
                </span>
              </div>
              <span v-if="dirty.stars && errors.stars" class="invalid-feedback" style="margin-top: 0;">
                Please select a rating of at least 1 star.
              </span>
            </div>

            <!-- Review Comments Text Area -->
            <div class="form-group">
              <div style="display: flex; justify-content: space-between; align-items: center;">
                <label class="form-label" for="comments">Review Comments</label>
                <span style="font-size: 0.75rem; color: var(--text-light);">
                  {{ form.comment.length }} / 500 characters
                </span>
              </div>
              <textarea 
                id="comments" 
                v-model="form.comment" 
                @blur="touchField('comment')"
                @input="checkCommentLength"
                rows="5"
                class="form-control" 
                :class="{ 
                  'is-valid': dirty.comment && !errors.comment, 
                  'is-invalid': dirty.comment && errors.comment 
                }"
                placeholder="Write your honest review comments here... (Minimum 10 characters required)"
              ></textarea>
              <span v-if="dirty.comment && errors.comment" class="invalid-feedback">
                Your review comments must be at least 10 characters long.
              </span>
            </div>

            <!-- Submission Trigger -->
            <button 
              type="submit" 
              class="btn btn-primary" 
              style="width: 100%; margin-top: 1rem;"
              :disabled="isSubmitting"
            >
              <span v-if="isSubmitting">Submitting Review Feedback...</span>
              <span v-else>Submit Review</span>
            </button>
          </form>
        </div>
      </div>

      <!-- Scenario 2: No Pending Booking for Feedback -->
      <div v-else style="max-width: 600px; margin: 3rem auto; text-align: center;">
        <div class="card" style="padding: 3rem 2rem;">
          <div style="font-size: 4rem; margin-bottom: 1rem;">🎉</div>
          <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem;">
            All Caught Up!
          </h2>
          <p style="color: var(--text-muted); margin-bottom: 2rem;">
            You have no recently completed car rentals awaiting review feedback at this moment.
          </p>
          <router-link to="/customer/feedback-history" class="btn btn-primary">
            View My Review History
          </router-link>
        </div>
      </div>

    </div>

    <!-- Reactive Toast Notification -->
    <div class="toast-container">
      <div v-for="toast in toasts" :key="toast.id" class="toast" :class="`toast-${toast.type}`">
        <span>{{ toast.text }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { activeBookingPendingFeedback, addFeedback } from '../../utils/mockData';
import api from '../../utils/axios';

const activeBooking = activeBookingPendingFeedback;
const isSubmitting = ref(false);
const formHasError = ref(false);
const hoverStars = ref(0);
const toasts = ref([]);

const form = reactive({
  stars: 0,
  comment: ''
});

const dirty = reactive({
  stars: false,
  comment: false
});

const errors = reactive({
  stars: false,
  comment: false
});

// Dynamic Star rating description labels
const ratingLabel = computed(() => {
  const current = hoverStars.value || form.stars;
  switch (current) {
    case 1: return 'Terrible 😞';
    case 2: return 'Poor 😕';
    case 3: return 'Average 😐';
    case 4: return 'Good 🙂';
    case 5: return 'Excellent! 😍';
    default: return 'Rate your ride';
  }
});

// Select Stars
const selectStars = (star) => {
  form.stars = star;
  dirty.stars = true;
  errors.stars = star === 0;
};

// Check comments
const touchField = (field) => {
  dirty[field] = true;
  if (field === 'comment') {
    errors.comment = !form.comment || form.comment.trim().length < 10;
  }
};

const checkCommentLength = () => {
  if (dirty.comment) {
    errors.comment = form.comment.trim().length < 10;
  }
};

// Submit Feedback
const submitFeedbackForm = async () => {
  dirty.stars = true;
  dirty.comment = true;
  
  errors.stars = form.stars === 0;
  errors.comment = !form.comment || form.comment.trim().length < 10;

  if (errors.stars || errors.comment) {
    triggerFormShake();
    addToast('Please complete the feedback validation rules.', 'danger');
    return;
  }

  isSubmitting.value = true;
  
  try {
    const payload = {
      author: 'Customer Account', // Pre-auth username
      bookingId: activeBooking.value.bookingId,
      stars: form.stars,
      comment: form.comment.trim(),
      car: activeBooking.value.car
    };

    console.log('[Axios Dummy Hook] Posting review feedback payload to baseURL:', api.defaults.baseURL);
    console.log('[Axios Payload]', payload);

    // Call async database submit
    await addFeedback(payload);
    
    addToast('Thank you! Your feedback has been registered.', 'success');
    
    // Clear inputs
    form.stars = 0;
    form.comment = '';
    dirty.stars = false;
    dirty.comment = false;
  } catch (error) {
    triggerFormShake();
    addToast('API server timed out. Please try again.', 'danger');
  } finally {
    isSubmitting.value = false;
  }
};

const triggerFormShake = () => {
  formHasError.value = true;
  setTimeout(() => {
    formHasError.value = false;
  }, 450);
};

const addToast = (text, type = 'success') => {
  const id = Date.now();
  toasts.value.push({ id, text, type });
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id);
  }, 4000);
};
</script>
