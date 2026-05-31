import { ref, computed } from 'vue';

// ──────────────────────────────────────────────
//  PAYMENTS DATA (existing)
// ──────────────────────────────────────────────
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

// ──────────────────────────────────────────────
//  FEEDBACK DATA (existing)
// ──────────────────────────────────────────────
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

// ──────────────────────────────────────────────
//  CAR FLEET DATA (new)
// ──────────────────────────────────────────────
const carsData = [
  {
    id: 'CAR-001',
    name: 'Model 3 Long Range',
    brand: 'Tesla',
    category: 'Electric',
    year: 2025,
    pricePerDay: 89.00,
    seats: 5,
    transmission: 'Automatic',
    fuelType: 'Electric',
    available: true,
    imageUrl: 'https://images.unsplash.com/photo-1560958089-b8a1929cea89?q=80&w=800&auto=format&fit=crop',
    features: ['Autopilot', 'Premium Audio', 'Heated Seats', 'Glass Roof', 'Supercharger Access'],
    description: 'Experience the future of driving with this all-electric sedan. The Model 3 Long Range offers an impressive 358-mile range, lightning-fast acceleration, and the most advanced autopilot features available.'
  },
  {
    id: 'CAR-002',
    name: '3 Series 330i',
    brand: 'BMW',
    category: 'Sedan',
    year: 2025,
    pricePerDay: 75.00,
    seats: 5,
    transmission: 'Automatic',
    fuelType: 'Petrol',
    available: true,
    imageUrl: 'https://images.unsplash.com/photo-1555215695-3004980ad54e?q=80&w=800&auto=format&fit=crop',
    features: ['Sport Package', 'Leather Interior', 'Navigation', 'Parking Sensors', 'Apple CarPlay'],
    description: 'The ultimate driving machine. This BMW 3 Series combines athletic agility with refined luxury, featuring a turbocharged engine that delivers exhilarating performance on every drive.'
  },
  {
    id: 'CAR-003',
    name: 'Camry XSE',
    brand: 'Toyota',
    category: 'Sedan',
    year: 2026,
    pricePerDay: 52.00,
    seats: 5,
    transmission: 'Automatic',
    fuelType: 'Hybrid',
    available: true,
    imageUrl: 'https://platform.cstatic-images.com/in/v2/stock_photos/d2d23f58-b525-4eb3-826c-eab83b8b616f/c763b801-5d75-492d-87c6-9cf50427ecd3.png',
    features: ['Hybrid Engine', 'Toyota Safety Sense', 'Wireless Charging', 'JBL Audio', 'Panoramic Roof'],
    description: 'The most popular sedan just got better. The Camry XSE Hybrid delivers exceptional fuel economy without sacrificing driving excitement, wrapped in a stunning sport-tuned design.'
  },
  {
    id: 'CAR-004',
    name: 'Explorer ST',
    brand: 'Ford',
    category: 'SUV',
    year: 2025,
    pricePerDay: 95.00,
    seats: 7,
    transmission: 'Automatic',
    fuelType: 'Petrol',
    available: false,
    imageUrl: 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?q=80&w=800&auto=format&fit=crop',
    features: ['3rd Row Seating', 'B&O Sound', '360° Camera', 'Adaptive Cruise', 'Tow Package'],
    description: 'Command the road in this powerful 7-seat SUV. The Explorer ST features a twin-turbo V6 engine, sport-tuned suspension, and enough space for the whole family and all their gear.'
  },
  {
    id: 'CAR-005',
    name: 'A4 Premium Plus',
    brand: 'Audi',
    category: 'Luxury',
    year: 2025,
    pricePerDay: 110.00,
    seats: 5,
    transmission: 'Automatic',
    fuelType: 'Petrol',
    available: true,
    imageUrl: 'https://images.unsplash.com/photo-1606152421802-db97b9c7a11b?q=80&w=800&auto=format&fit=crop',
    features: ['Quattro AWD', 'Virtual Cockpit', 'Bang & Olufsen Audio', 'Matrix LED', 'Sport Seats'],
    description: 'Sophistication meets performance. The Audi A4 Premium Plus features Quattro all-wheel drive, a stunning virtual cockpit display, and the finest interior craftsmanship in its class.'
  },
  {
    id: 'CAR-006',
    name: 'Grand Cherokee L',
    brand: 'Jeep',
    category: 'SUV',
    year: 2026,
    pricePerDay: 105.00,
    seats: 7,
    transmission: 'Automatic',
    fuelType: 'Diesel',
    available: true,
    imageUrl: 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?q=80&w=800&auto=format&fit=crop',
    features: ['4x4 System', 'Air Suspension', 'McIntosh Audio', 'Night Vision', 'Head-Up Display'],
    description: 'Legendary capability meets modern luxury. The Grand Cherokee L offers unmatched off-road prowess with three rows of premium comfort and the most advanced technology in its segment.'
  },
  {
    id: 'CAR-008',
    name: 'Mustang GT',
    brand: 'Ford',
    category: 'Sports',
    year: 2025,
    pricePerDay: 120.00,
    seats: 4,
    transmission: 'Manual',
    fuelType: 'Petrol',
    available: false,
    imageUrl: 'https://images.unsplash.com/photo-1584345604476-8ec5e12e42dd?q=80&w=800&auto=format&fit=crop',
    features: ['5.0L V8', 'Performance Pack', 'MagneRide', 'Launch Control', 'Recaro Seats'],
    description: 'An American icon reborn. The Mustang GT delivers pure muscle car thrills with its naturally aspirated 5.0L V8, producing 480 horsepower of unbridled excitement.'
  }
];

// ──────────────────────────────────────────────
//  BOOKINGS DATA (new)
// ──────────────────────────────────────────────
const bookingsData = [
  {
    id: 'BKG-9281',
    carId: 'CAR-001',
    carName: 'Tesla Model 3 Long Range',
    customerName: 'Sarah Jenkins',
    phone: '+63 917 123 4567',
    pickupDate: '2026-05-25',
    returnDate: '2026-05-28',
    totalPrice: 267.00,
    status: 'completed',
    specialRequests: 'Need child seat installed'
  },
  {
    id: 'BKG-7492',
    carId: 'CAR-005',
    carName: 'Audi A4 Premium Plus',
    customerName: 'Emily Chen',
    phone: '+63 918 555 0199',
    pickupDate: '2026-05-30',
    returnDate: '2026-06-02',
    totalPrice: 330.00,
    status: 'active',
    specialRequests: ''
  },
  {
    id: 'BKG-6610',
    carId: 'CAR-002',
    carName: 'BMW 3 Series 330i',
    customerName: 'David Vance',
    phone: '+63 919 321 8888',
    pickupDate: '2026-05-22',
    returnDate: '2026-05-26',
    totalPrice: 300.00,
    status: 'completed',
    specialRequests: 'Airport pickup required'
  },
  {
    id: 'BKG-8831',
    carId: 'CAR-004',
    carName: 'Ford Explorer ST',
    customerName: 'Michael Brody',
    phone: '+63 920 112 3344',
    pickupDate: '2026-05-28',
    returnDate: '2026-05-30',
    totalPrice: 190.00,
    status: 'completed',
    specialRequests: 'GPS navigation required'
  }
];

// ──────────────────────────────────────────────
//  REACTIVE STATES
// ──────────────────────────────────────────────
export const payments = ref(paymentsData);
export const feedbacks = ref(feedbackData);
export const cars = ref(carsData);
export const bookings = ref(bookingsData);

// Active booking that is pending review for the Customer's Submit Feedback page
export const activeBookingPendingFeedback = ref({
  bookingId: 'BKG-7492',
  car: 'Audi A4 Premium',
  date: '2026-05-30',
  totalAmount: 150.50
});

// ──────────────────────────────────────────────
//  PAYMENT METHODS (existing)
// ──────────────────────────────────────────────
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

// ──────────────────────────────────────────────
//  CAR METHODS (new)
// ──────────────────────────────────────────────
export const fetchCars = async (filters = {}) => {
  await new Promise(resolve => setTimeout(resolve, 600));
  let result = [...cars.value];

  if (filters.search) {
    const q = filters.search.toLowerCase();
    result = result.filter(c =>
      c.name.toLowerCase().includes(q) ||
      c.brand.toLowerCase().includes(q) ||
      c.category.toLowerCase().includes(q)
    );
  }
  if (filters.category && filters.category !== 'all') {
    result = result.filter(c => c.category === filters.category);
  }
  if (filters.transmission && filters.transmission !== 'all') {
    result = result.filter(c => c.transmission === filters.transmission);
  }
  if (filters.fuelType && filters.fuelType !== 'all') {
    result = result.filter(c => c.fuelType === filters.fuelType);
  }
  if (filters.availableOnly) {
    result = result.filter(c => c.available);
  }
  if (filters.sort === 'price-asc') {
    result.sort((a, b) => a.pricePerDay - b.pricePerDay);
  } else if (filters.sort === 'price-desc') {
    result.sort((a, b) => b.pricePerDay - a.pricePerDay);
  } else if (filters.sort === 'name') {
    result.sort((a, b) => a.name.localeCompare(b.name));
  } else if (filters.sort === 'year') {
    result.sort((a, b) => b.year - a.year);
  }

  return result;
};

export const fetchCarById = async (id) => {
  await new Promise(resolve => setTimeout(resolve, 400));
  return cars.value.find(c => c.id === id) || null;
};

export const addCar = async (carPayload) => {
  await new Promise(resolve => setTimeout(resolve, 800));
  const newCar = {
    id: `CAR-${String(cars.value.length + 1).padStart(3, '0')}`,
    available: true,
    ...carPayload
  };
  cars.value = [newCar, ...cars.value];
  return newCar;
};

export const updateCar = async (carId, updates) => {
  await new Promise(resolve => setTimeout(resolve, 800));
  const idx = cars.value.findIndex(c => c.id === carId);
  if (idx !== -1) {
    cars.value[idx] = { ...cars.value[idx], ...updates };
    // Trigger reactivity
    cars.value = [...cars.value];
    return cars.value[idx];
  }
  return null;
};

export const deleteCar = async (carId) => {
  await new Promise(resolve => setTimeout(resolve, 600));
  cars.value = cars.value.filter(c => c.id !== carId);
  return true;
};

export const toggleCarAvailability = async (carId) => {
  await new Promise(resolve => setTimeout(resolve, 400));
  const car = cars.value.find(c => c.id === carId);
  if (car) {
    car.available = !car.available;
    cars.value = [...cars.value];
    return car;
  }
  return null;
};

// ──────────────────────────────────────────────
//  BOOKING METHODS (new)
// ──────────────────────────────────────────────
export const createBooking = async (bookingPayload) => {
  await new Promise(resolve => setTimeout(resolve, 1000));
  const newBooking = {
    id: `BKG-${Math.floor(1000 + Math.random() * 9000)}`,
    status: 'active',
    ...bookingPayload
  };
  bookings.value = [newBooking, ...bookings.value];
  return newBooking;
};

// ──────────────────────────────────────────────
//  COMPUTED ANALYTICS (existing + new)
// ──────────────────────────────────────────────
export const totalRevenue = computed(() => {
  return payments.value
    .filter(p => p.status === 'paid')
    .reduce((sum, p) => sum + parseFloat(p.amount), 0);
});

export const pendingClearancesCount = computed(() => {
  return payments.value.filter(p => p.status === 'pending').length;
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

// Car analytics (new)
export const totalCarsCount = computed(() => cars.value.length);
export const availableCarsCount = computed(() => cars.value.filter(c => c.available).length);
export const totalBookingsCount = computed(() => bookings.value.length);
export const averageCarPrice = computed(() => {
  if (cars.value.length === 0) return 0;
  const sum = cars.value.reduce((total, c) => total + c.pricePerDay, 0);
  return parseFloat((sum / cars.value.length).toFixed(2));
});
export const categoryBreakdown = computed(() => {
  const breakdown = {};
  cars.value.forEach(c => {
    breakdown[c.category] = (breakdown[c.category] || 0) + 1;
  });
  return breakdown;
});
