<template>
  <div>
    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Feedback Analytics Dashboard</h1>
        <p class="page-subtitle">Track customer satisfaction, analyze review metrics, and inspect user commentary feed.</p>
      </div>
    </div>

    <!-- Metrics Tracking Grid (KPI Cards) -->
    <div class="metrics-grid">
      <!-- Metric 1: Total Revenue -->
      <div class="metric-card">
        <div class="metric-icon-box" style="background: var(--primary-light); color: var(--primary);">
          💵
        </div>
        <div class="metric-info">
          <span class="metric-label">Total Cleared Revenue</span>
          <span class="metric-value">RM{{ totalRevenue.toFixed(2) }}</span>
        </div>
      </div>

      <!-- Metric 2: Average Rating -->
      <div class="metric-card">
        <div class="metric-icon-box" style="background: #fef3c7; color: #d97706;">
          ★
        </div>
        <div class="metric-info">
          <span class="metric-label">Average Satisfaction</span>
          <span class="metric-value" style="display: flex; align-items: center; gap: 0.5rem;">
            {{ averageRating }} 
            <span style="font-size: 1rem; color: #eab308;">★★★★★</span>
          </span>
        </div>
      </div>

      <!-- Metric 3: Total Reviews -->
      <div class="metric-card">
        <div class="metric-icon-box" style="background: var(--status-success-bg); color: var(--status-success);">
          ✍
        </div>
        <div class="metric-info">
          <span class="metric-label">Total Customer Reviews</span>
          <span class="metric-value">{{ feedbacksCount }}</span>
        </div>
      </div>
    </div>

    <!-- Analytics & Star Breakdown Section -->
    <div class="view-grid-two-cols" style="margin-bottom: 2rem;">
      
      <!-- Left Column: Review Distribution Breakdown -->
      <div class="card">
        <h3 class="card-title">Satisfaction Distribution</h3>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">
          Detailed ratio analysis illustrating the proportion of 1-to-5 star ratings generated from rentals.
        </p>

        <div class="distribution-container">
          <div 
            v-for="stars in [5, 4, 3, 2, 1]" 
            :key="stars" 
            class="distribution-row"
          >
            <div class="distribution-stars">
              <span>{{ stars }}</span>
              <span style="color: #eab308;">★</span>
            </div>
            
            <div class="distribution-bar-bg">
              <div 
                class="distribution-bar-fill" 
                :style="{ width: `${getPercentage(stars)}%` }"
              ></div>
            </div>

            <div class="distribution-count">
              {{ getCount(stars) }}
            </div>
            <div style="min-width: 45px; text-align: right; color: var(--text-light); font-size: 0.8rem;">
              {{ getPercentage(stars).toFixed(0) }}%
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Card Comments Feed with Reactive Dropdown Filters -->
      <div class="card" style="display: flex; flex-direction: column; max-height: 520px; overflow: hidden;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1rem;">
          <h3 class="card-title" style="margin-bottom: 0;">User Review Feed</h3>
          
          <!-- Dropdown Filter for Star rating -->
          <select v-model="selectedRatingFilter" class="filter-select" style="min-width: 140px; padding: 0.35rem 1.5rem 0.35rem 0.75rem; font-size: 0.8rem;">
            <option value="">All Star Ratings</option>
            <option value="5">5 Stars only</option>
            <option value="4">4 Stars only</option>
            <option value="3">3 Stars only</option>
            <option value="2">2 Stars only</option>
            <option value="1">1 Star only</option>
          </select>
        </div>

        <!-- Scrolling card feed -->
        <div style="overflow-y: auto; flex-grow: 1; padding-right: 0.5rem;" class="reviews-feed">
          <div v-if="filteredReviews.length === 0" style="text-align: center; padding: 3rem; color: var(--text-muted);">
            <span style="font-size: 2.5rem; display: block; margin-bottom: 0.5rem;">📭</span>
            No review comments match this rating filter.
          </div>

          <div 
            v-else 
            v-for="rev in filteredReviews" 
            :key="rev.id" 
            class="review-card"
            style="border-left: 4px solid var(--primary);"
          >
            <div class="review-card-header">
              <div>
                <span class="review-author">{{ rev.author }}</span>
                <span style="font-size: 0.8rem; color: var(--text-light); margin-left: 0.5rem;">verified driver</span>
                <div class="review-stars">
                  <span v-for="s in rev.stars" :key="s">★</span>
                  <span v-for="s in (5 - rev.stars)" :key="'e'+s" style="color: #e2e8f0;">★</span>
                </div>
              </div>
              <div style="text-align: right;">
                <span class="review-booking-ref">{{ rev.bookingId }}</span>
                <div class="review-date" style="margin-top: 0.25rem;">{{ rev.date }}</div>
              </div>
            </div>
            
            <p class="review-text" style="font-size: 0.85rem; font-style: italic; background: #f8fafc; padding: 0.75rem; border-radius: var(--radius-sm);">
              "{{ rev.comment }}"
            </p>
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--primary-dark);">
              Vehicle Driven: {{ rev.car || 'Standard Class Utility' }}
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { feedbacks, totalRevenue, averageRating, ratingBreakdown } from '../../utils/mockData';

const selectedRatingFilter = ref('');

const feedbacksCount = computed(() => feedbacks.value.length);

// Breakdown stats helpers
const getCount = (stars) => {
  return ratingBreakdown.value[stars] || 0;
};

const getPercentage = (stars) => {
  if (feedbacks.value.length === 0) return 0;
  return (getCount(stars) / feedbacks.value.length) * 100;
};

// Filtered reviews computing
const filteredReviews = computed(() => {
  if (!selectedRatingFilter.value) return feedbacks.value;
  const targetStars = parseInt(selectedRatingFilter.value);
  return feedbacks.value.filter(f => f.stars === targetStars);
});
</script>
