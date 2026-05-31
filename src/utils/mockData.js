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

export const updateFeedback = async (feedbackId, updatedPayload) => {
  await new Promise(resolve => setTimeout(resolve, 800)); // Mimic API delay
  const idx = feedbacks.value.findIndex(f => f.id === feedbackId);
  if (idx !== -1) {
    feedbacks.value[idx] = {
      ...feedbacks.value[idx],
      ...updatedPayload,
      date: new Date().toISOString().split('T')[0]
    };
    feedbacks.value = [...feedbacks.value];
    return feedbacks.value[idx];
  }
  throw new Error('Feedback not found');
};

export const deleteFeedback = async (feedbackId) => {
  await new Promise(resolve => setTimeout(resolve, 500)); // Mimic API delay
  const idx = feedbacks.value.findIndex(f => f.id === feedbackId);
  if (idx !== -1) {
    feedbacks.value = feedbacks.value.filter(f => f.id !== feedbackId);
    return true;
  }
  throw new Error('Feedback not found');
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

// Mock data for Booking Management and Rental Processing
const customersData = [
  { id: 1, name: 'John Doe', email: 'john@example.com', phone: '555-0101' },
  { id: 2, name: 'Jane Smith', email: 'jane@example.com', phone: '555-0102' },
  { id: 3, name: 'Michael Johnson', email: 'michael@example.com', phone: '555-0103' },
  { id: 4, name: 'Emily Davis', email: 'emily@example.com', phone: '555-0104' },
  { id: 5, name: 'Robert Wilson', email: 'robert@example.com', phone: '555-0105' }
];

const carsData = [
  { id: 1, brand: 'Toyota', model: 'Corolla', pricePerDay: 50, status: 'available' },
  { id: 2, brand: 'Honda', model: 'Civic', pricePerDay: 55, status: 'available' },
  { id: 3, brand: 'BMW', model: '3 Series', pricePerDay: 100, status: 'available' },
  { id: 4, brand: 'Tesla', model: 'Model 3', pricePerDay: 150, status: 'available' },
  { id: 5, brand: 'Ford', model: 'Explorer', pricePerDay: 80, status: 'unavailable' },
  { id: 6, brand: 'Audi', model: 'A4', pricePerDay: 95, status: 'available' },
  { id: 7, brand: 'Mercedes', model: 'C-Class', pricePerDay: 120, status: 'available' }
];

const bookingsData = [
  {
    id: 1,
    customerId: 1,
    customerName: 'John Doe',
    carId: 1,
    carInfo: 'Toyota Corolla',
    startDate: '2026-06-01',
    endDate: '2026-06-03',
    totalPrice: 150,
    status: 'pending'
  },
  {
    id: 2,
    customerId: 2,
    customerName: 'Jane Smith',
    carId: 3,
    carInfo: 'BMW 3 Series',
    startDate: '2026-05-31',
    endDate: '2026-06-05',
    totalPrice: 600,
    status: 'confirmed'
  },
  {
    id: 3,
    customerId: 3,
    customerName: 'Michael Johnson',
    carId: 4,
    carInfo: 'Tesla Model 3',
    startDate: '2026-06-02',
    endDate: '2026-06-04',
    totalPrice: 450,
    status: 'confirmed'
  },
  {
    id: 4,
    customerId: 4,
    customerName: 'Emily Davis',
    carId: 2,
    carInfo: 'Honda Civic',
    startDate: '2026-06-05',
    endDate: '2026-06-08',
    totalPrice: 220,
    status: 'cancelled'
  }
];

const rentalsData = [
  {
    id: 1,
    bookingId: 1,
    customerName: 'John Doe',
    carInfo: 'Toyota Corolla',
    startDate: '2026-06-01',
    endDate: '2026-06-03',
    status: 'booked'
  },
  {
    id: 2,
    bookingId: 2,
    customerName: 'Jane Smith',
    carInfo: 'BMW 3 Series',
    startDate: '2026-05-31',
    endDate: '2026-06-05',
    status: 'ongoing'
  },
  {
    id: 3,
    bookingId: 3,
    customerName: 'Michael Johnson',
    carInfo: 'Tesla Model 3',
    startDate: '2026-06-02',
    endDate: '2026-06-04',
    status: 'completed'
  },
  {
    id: 4,
    bookingId: 4,
    customerName: 'Robert Wilson',
    carInfo: 'Audi A4',
    startDate: '2026-05-25',
    endDate: '2026-05-28',
    status: 'completed'
  }
];

export const mockData = {
  customers: customersData,
  cars: carsData,
  bookings: bookingsData,
  rentals: rentalsData
};
