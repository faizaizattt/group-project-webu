<template>
  <div>
    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Feedback History</h1>
        <p class="page-subtitle">View and filter all reviews and ratings you have shared about our vehicles.</p>
      </div>
      <div>
        <router-link to="/customer/feedback" class="btn btn-primary">
          Write a New Review
        </router-link>
      </div>
    </div>

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
        style="border-left: 4px solid var(--primary-light);"
      >
        <div class="review-card-header">
          <div>
            <h4 class="review-author">{{ fdb.car }}</h4>
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
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { feedbacks } from '../../utils/mockData';

const starFilter = ref('');

const filteredFeedbacks = computed(() => {
  if (!starFilter.value) return feedbacks.value;
  const ratingVal = parseInt(starFilter.value);
  return feedbacks.value.filter(f => f.stars === ratingVal);
});
</script>
