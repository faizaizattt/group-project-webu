<template>
  <div>
    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Feedback Dashboard</h1>
        <p class="page-subtitle">Share your rental experience and view your past reviews here.</p>
      </div>
    </div>

    <!-- Segmented Tab Control -->
    <div class="tabs-container">
      <div class="feedback-tabs">
        <button 
          class="tab-btn" 
          :class="{ 'active': activeTab === 'history' }"
          @click="handleTabSwitch('history')"
        >
          📂 Review History
        </button>
        <button 
          class="tab-btn" 
          :class="{ 'active': activeTab === 'submit' }"
          @click="handleTabSwitch('submit')"
        >
          <span v-if="editingFeedbackId">✏️ Edit Review</span>
          <span v-else>✍ Write a Review</span>
          <span v-if="activeBooking && !editingFeedbackId" class="pending-badge-dot"></span>
        </button>
      </div>
    </div>

    <!-- Tab View Window with Transitions -->
    <router-view v-slot="{ Component }">
      <transition name="fade" mode="out-in">
        <!-- Feedback History Tab -->
        <div v-if="activeTab === 'history'" key="history">
          <!-- Filters Row -->
          <div class="filters-row">
            <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 500;">
              Showing {{ filteredFeedbacks.length }} review(s)
            </div>
            <div>
              <select v-model="starFilter" class="filter-select">
                <option value="">All Ratings</option>
                <option value="5">5 Stars Only</option>
                <option value="4">4 Stars Only</option>
                <option value="3">3 Stars Only</option>
                <option value="2">2 Stars Only</option>
                <option value="1">1 Star Only</option>
              </select>
            </div>
          </div>

          <!-- Reviews Grid Card Feed -->
          <div class="reviews-feed">
            <div v-if="filteredFeedbacks.length === 0" class="card" style="text-align: center; padding: 3rem;">
              <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">🔍</span>
              <h3 style="margin-bottom: 0.5rem;">No Reviews Found</h3>
              <p style="color: var(--text-muted);">Try clearing or changing your filter criteria to display other reviews.</p>
            </div>

            <div 
              v-else 
              v-for="fdb in filteredFeedbacks" 
              :key="fdb.id" 
              class="review-card"
              :style="{ borderLeft: isOwnFeedback(fdb) ? '4px solid var(--primary)' : '4px solid var(--text-light)' }"
            >
              <div class="review-card-header">
                <div>
                  <h4 class="review-author" style="display: flex; align-items: center; gap: 0.5rem;">
                    {{ fdb.car }}
                    <span v-if="isOwnFeedback(fdb)" class="badge badge-success" style="font-size: 0.65rem; padding: 0.1rem 0.4rem;">Your Review</span>
                    <span v-else class="badge badge-pending" style="font-size: 0.65rem; padding: 0.1rem 0.4rem; background: #e2e8f0; color: #475569;">{{ fdb.author }}</span>
                  </h4>
                  <div class="review-stars">
                    <span v-for="s in fdb.stars" :key="s">★</span>
                    <span v-for="s in (5 - fdb.stars)" :key="'u'+s" style="color: #cbd5e1;">★</span>
                  </div>
                </div>
                <div style="text-align: right;">
                  <span class="review-booking-ref" style="font-family: monospace; display: block; margin-bottom: 0.25rem;">
                    {{ fdb.bookingId }}
                  </span>
                  <span class="review-date">{{ fdb.date }}</span>
                </div>
              </div>
              
              <p class="review-text">"{{ fdb.comment }}"</p>

              <!-- Edit/Delete Action Row for Owner -->
              <div v-if="isOwnFeedback(fdb)" class="review-actions-row">
                <button @click="startEdit(fdb)" class="btn-action-edit">
                  ✏️ Edit Review
                </button>
                <button @click="handleDelete(fdb.id)" class="btn-action-delete">
                  🗑️ Delete
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit/Edit Feedback Tab -->
        <div v-else-if="activeTab === 'submit'" key="submit">
          <!-- Scenario 1: Active Booking or Editing Mode -->
          <div v-if="displayBooking" style="max-width: 800px; margin: 0 auto; width: 100%;">
            
            <!-- Booking Details Card -->
            <div class="card" style="margin-bottom: 2rem; border-left: 5px solid var(--primary); background: linear-gradient(to right, rgba(37, 99, 235, 0.02), rgba(255,255,255,0.9));">
              <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                <div>
                  <span v-if="editingFeedbackId" class="badge badge-pending" style="margin-bottom: 0.5rem; background: var(--status-info-bg); color: var(--status-info);">Currently Editing Review</span>
                  <span v-else class="badge badge-success" style="margin-bottom: 0.5rem;">Recently Completed</span>
                  
                  <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.25rem;">
                    {{ displayBooking.car }}
                  </h2>
                  <p style="color: var(--text-muted); font-size: 0.9rem;">
                    Booking Reference: <strong style="font-family: monospace; color: var(--primary-dark);">{{ displayBooking.bookingId }}</strong>
                  </p>
                </div>
                <div style="text-align: right;">
                  <div style="font-size: 0.8rem; color: var(--text-light); text-transform: uppercase; font-weight: 700;">Amount Paid</div>
                  <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary);">${{ displayBooking.totalAmount.toFixed(2) }}</div>
                  <div style="font-size: 0.8rem; color: var(--text-muted);">Returned on {{ displayBooking.date }}</div>
                </div>
              </div>
            </div>

            <!-- Feedback Submission/Edit Card -->
            <div class="card" :class="{ 'shake': formHasError }">
              <h3 class="card-title">
                <span v-if="editingFeedbackId">Edit Your Rating & Review</span>
                <span v-else>Leave Your Rating & Review</span>
              </h3>
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

                <!-- Action Triggers -->
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                  <button 
                    v-if="editingFeedbackId"
                    type="button"
                    class="btn btn-outline"
                    style="flex: 1;"
                    @click="cancelEdit"
                  >
                    Cancel Edit
                  </button>
                  
                  <button 
                    type="submit" 
                    class="btn btn-primary" 
                    style="flex: 2;"
                    :disabled="isSubmitting"
                  >
                    <span v-if="isSubmitting">
                      {{ editingFeedbackId ? 'Updating Review...' : 'Submitting Review Feedback...' }}
                    </span>
                    <span v-else>
                      {{ editingFeedbackId ? 'Update Review' : 'Submit Review' }}
                    </span>
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Scenario 2: No Pending Booking & Not Editing -->
          <div v-else style="max-width: 600px; margin: 3rem auto; text-align: center;">
            <div class="card" style="padding: 3rem 2rem;">
              <div style="font-size: 4rem; margin-bottom: 1rem;">🎉</div>
              <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem;">
                All Caught Up!
              </h2>
              <p style="color: var(--text-muted); margin-bottom: 2rem;">
                You have no recently completed car rentals awaiting review feedback at this moment.
              </p>
              <button @click="activeTab = 'history'" class="btn btn-primary">
                View My Review History
              </button>
            </div>
          </div>
        </div>
      </transition>
    </router-view>

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
import { activeBookingPendingFeedback, addFeedback, updateFeedback, deleteFeedback, feedbacks } from '../../utils/mockData';
import { currentUser } from '../../utils/session';

const activeBooking = activeBookingPendingFeedback;
const activeTab = ref(activeBooking.value ? 'submit' : 'history');
const editingFeedbackId = ref(null);

// Form data structure
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

// History filters
const starFilter = ref('');

// Computed filtered list of feedbacks
const filteredFeedbacks = computed(() => {
  if (!starFilter.value) return feedbacks.value;
  const ratingVal = parseInt(starFilter.value);
  return feedbacks.value.filter(f => f.stars === ratingVal);
});

// Check if feedback author belongs to the current user
const isOwnFeedback = (fdb) => {
  if (!currentUser.value) return false;
  return fdb.author === currentUser.value.name;
};

// Check if booking is in progress or edit mode is active
const displayBooking = computed(() => {
  if (editingFeedbackId.value) {
    const fdb = feedbacks.value.find(f => f.id === editingFeedbackId.value);
    if (fdb) {
      return {
        bookingId: fdb.bookingId,
        car: fdb.car,
        date: fdb.date,
        totalAmount: 150.50
      };
    }
  }
  return activeBooking.value;
});

// Star hover & labels
const hoverStars = ref(0);
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

// Star selection
const selectStars = (star) => {
  form.stars = star;
  dirty.stars = true;
  errors.stars = star === 0;
};

// Validation touches
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

// Toast triggers
const toasts = ref([]);
const addToast = (text, type = 'success') => {
  const id = Date.now();
  toasts.value.push({ id, text, type });
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id);
  }, 4000);
};

const formHasError = ref(false);
const triggerFormShake = () => {
  formHasError.value = true;
  setTimeout(() => {
    formHasError.value = false;
  }, 450);
};

// Handling tabs carefully, resetting edit state if switching away
const handleTabSwitch = (tab) => {
  if (tab === 'history' && editingFeedbackId.value) {
    cancelEdit();
  }
  activeTab.value = tab;
};

// Initiate Editing State
const startEdit = (fdb) => {
  editingFeedbackId.value = fdb.id;
  form.stars = fdb.stars;
  form.comment = fdb.comment;
  dirty.stars = true;
  dirty.comment = true;
  errors.stars = false;
  errors.comment = false;
  activeTab.value = 'submit';
};

// Terminate/Cancel Editing State
const cancelEdit = () => {
  editingFeedbackId.value = null;
  form.stars = 0;
  form.comment = '';
  dirty.stars = false;
  dirty.comment = false;
  activeTab.value = 'history';
};

// Initiate Deletion
const handleDelete = async (feedbackId) => {
  if (confirm('Are you sure you want to delete this feedback? This action cannot be undone.')) {
    try {
      await deleteFeedback(feedbackId);
      addToast('Feedback deleted successfully!', 'success');
    } catch (error) {
      addToast('Failed to delete feedback. Please try again.', 'danger');
    }
  }
};

const isSubmitting = ref(false);

// Submit form (handle Create & Update)
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
    if (editingFeedbackId.value) {
      const payload = {
        stars: form.stars,
        comment: form.comment.trim()
      };
      await updateFeedback(editingFeedbackId.value, payload);
      addToast('Your review has been updated successfully!', 'success');
      editingFeedbackId.value = null;
    } else {
      const payload = {
        author: currentUser.value ? currentUser.value.name : 'Customer Account',
        bookingId: displayBooking.value.bookingId,
        stars: form.stars,
        comment: form.comment.trim(),
        car: displayBooking.value.car
      };
      await addFeedback(payload);
      addToast('Thank you! Your feedback has been registered.', 'success');
    }
    
    // Reset Form fields
    form.stars = 0;
    form.comment = '';
    dirty.stars = false;
    dirty.comment = false;
    
    // Slide transition to history list
    setTimeout(() => {
      activeTab.value = 'history';
    }, 800);
  } catch (error) {
    triggerFormShake();
    addToast('API server timed out. Please try again.', 'danger');
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.tabs-container {
  display: flex;
  justify-content: center;
  margin-bottom: 2.5rem;
}

.feedback-tabs {
  display: flex;
  gap: 0.5rem;
  background: rgba(226, 232, 240, 0.6);
  padding: 0.4rem;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-color);
  max-width: 480px;
  width: 100%;
  box-shadow: inset 0 2px 4px rgba(15, 23, 42, 0.03);
}

.tab-btn {
  flex: 1;
  background: transparent;
  border: none;
  padding: 0.8rem 1rem;
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--text-muted);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all var(--transition-fast);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  position: relative;
}

.tab-btn:hover {
  color: var(--primary);
  background: rgba(255, 255, 255, 0.4);
}

.tab-btn.active {
  background: var(--white);
  color: var(--primary);
  box-shadow: var(--shadow-sm);
}

.pending-badge-dot {
  width: 8px;
  height: 8px;
  background-color: var(--status-danger);
  border-radius: 50%;
  display: inline-block;
  box-shadow: 0 0 0 2px var(--white), 0 0 8px var(--status-danger);
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7);
  }
  70% {
    transform: scale(1);
    box-shadow: 0 0 0 6px rgba(220, 38, 38, 0);
  }
  100% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(220, 38, 38, 0);
  }
}

/* Owner specific action buttons */
.review-actions-row {
  display: flex;
  gap: 0.75rem;
  margin-top: 1rem;
  border-top: 1px dashed var(--border-color);
  padding-top: 0.75rem;
}

.btn-action-edit {
  background: transparent;
  border: 1px solid var(--primary);
  color: var(--primary);
  padding: 0.35rem 0.75rem;
  font-size: 0.8rem;
  font-weight: 600;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: var(--transition-fast);
  display: flex;
  align-items: center;
  gap: 0.25rem;
  outline: none;
}

.btn-action-edit:hover {
  background: var(--primary-light);
  transform: translateY(-1px);
}

.btn-action-delete {
  background: transparent;
  border: 1px solid var(--status-danger);
  color: var(--status-danger);
  padding: 0.35rem 0.75rem;
  font-size: 0.8rem;
  font-weight: 600;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: var(--transition-fast);
  display: flex;
  align-items: center;
  gap: 0.25rem;
  outline: none;
}

.btn-action-delete:hover {
  background: var(--status-danger-bg);
  transform: translateY(-1px);
}

/* Validation shake */
.shake {
  animation: shake 0.4s ease-in-out;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-6px); }
  75% { transform: translateX(6px); }
}

/* Toast container positioning */
.toast-container {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.toast {
  padding: 1rem 1.5rem;
  border-radius: var(--radius-sm);
  color: var(--white);
  font-weight: 600;
  font-size: 0.9rem;
  box-shadow: var(--shadow-lg);
  animation: slideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  min-width: 300px;
}

.toast-success {
  background-color: var(--status-success);
}

.toast-danger {
  background-color: var(--status-danger);
}

@keyframes slideIn {
  from {
    transform: translateY(1rem);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}
</style>
