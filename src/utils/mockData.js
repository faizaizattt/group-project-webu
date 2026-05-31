import { ref, computed } from 'vue';

// Pre-populate with realistic, modern mock data
const paymentsData = [
  {
    id: 'PAY-1001',
    bookingId: 'BKG-9281',
    amount: 320.00,
    method: 'Credit Card',
    status: 'paid',
    date: '2026-05-28',
    details: 'Visa ending in 8821'
  },
  {
    id: 'PAY-1002',
    bookingId: 'BKG-7492',
    amount: 150.50,
    method: 'Bank Transfer',
    status: 'pending',
    date: '2026-05-30',
    details: 'Reference: BT-991203-CHK'
  },
  {
    id: 'PAY-1003',
    bookingId: 'BKG-6610',
    amount: 540.00,
    method: 'Mobile Wallet',
    status: 'paid',
    date: '2026-05-25',
    details: 'G-Cash: +63 917 123 4567'
  },
  {
    id: 'PAY-1004',
    bookingId: 'BKG-8831',
    amount: 180.00,
    method: 'Credit Card',
    status: 'flagged',
    date: '2026-05-29',
    details: 'Mastercard ending in 1054'
  }
];

const feedbackData = [
  {
    id: 'FDB-201',
    author: 'Sarah Jenkins',
    bookingId: 'BKG-9281',
    stars: 5,
    comment: 'The Tesla Model 3 was absolutely clean and fully charged! The pick-up and drop-off process was extremely smooth. Will definitely rent again!',
    date: '2026-05-28',
    car: 'Tesla Model 3'
  },
  {
    id: 'FDB-202',
    author: 'David Vance',
    bookingId: 'BKG-6610',
    stars: 4,
    comment: 'Great service. The vehicle (BMW 3 Series) performed flawlessly. Only issue was a small scratch on the door which was already documented. Clear invoicing!',
    date: '2026-05-26',
    car: 'BMW 3 Series'
  },
  {
    id: 'FDB-203',
    author: 'Michael Brody',
    bookingId: 'BKG-8831',
    stars: 2,
    comment: 'The car interior smelled of smoke when I got it, although they cleaned it up, it was annoying. Customer support did offer a discount, which was nice.',
    date: '2026-05-30',
    car: 'Ford Explorer'
  }
];

// Reactive states
export const payments = ref(paymentsData);
export const feedbacks = ref(feedbackData);

// Active booking that is pending review for the Customer's Submit Feedback page
export const activeBookingPendingFeedback = ref({
  bookingId: 'BKG-7492',
  car: 'Audi A4 Premium',
  date: '2026-05-30',
  totalAmount: 150.50
});

// Methods simulating API calls with async/await payload structures
export const addPayment = async (paymentPayload) => {
  await new Promise(resolve => setTimeout(resolve, 1000)); // Mimic API delay
  const newPay = {
    id: `PAY-${1000 + payments.value.length + 1}`,
    status: 'paid', // Automatically cleared upon successful submission
    date: new Date().toISOString().split('T')[0],
    ...paymentPayload
  };
  payments.value = [newPay, ...payments.value];
  return newPay;
};

export const addFeedback = async (feedbackPayload) => {
  await new Promise(resolve => setTimeout(resolve, 1000)); // Mimic API delay
  const newFdb = {
    id: `FDB-${200 + feedbacks.value.length + 1}`,
    date: new Date().toISOString().split('T')[0],
    ...feedbackPayload
  };
  feedbacks.value = [newFdb, ...feedbacks.value];
  // Clear the active booking pending feedback once submitted
  activeBookingPendingFeedback.value = null;
  return newFdb;
};

export const clearPayment = async (paymentId) => {
  await new Promise(resolve => setTimeout(resolve, 500));
  const pay = payments.value.find(p => p.id === paymentId);
  if (pay) {
    pay.status = 'paid';
  }
};

export const flagPayment = async (paymentId) => {
  await new Promise(resolve => setTimeout(resolve, 500));
  const pay = payments.value.find(p => p.id === paymentId);
  if (pay) {
    pay.status = 'flagged';
  }
};

// Computed Analytics for Dashboard
export const totalRevenue = computed(() => {
  return payments.value
    .filter(p => p.status === 'paid')
    .reduce((sum, p) => sum + parseFloat(p.amount), 0);
});

export const averageRating = computed(() => {
  if (feedbacks.value.length === 0) return 0;
  const sum = feedbacks.value.reduce((total, f) => total + f.stars, 0);
  return parseFloat((sum / feedbacks.value.length).toFixed(1));
});

export const ratingBreakdown = computed(() => {
  const breakdown = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
  feedbacks.value.forEach(f => {
    if (breakdown[f.stars] !== undefined) {
      breakdown[f.stars]++;
    }
  });
  return breakdown;
});
