import { createRouter, createWebHistory } from 'vue-router';
import { currentUser } from '../utils/session';

// Route configuration
const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/LoginView.vue'),
    meta: { guestOnly: true }
  },
  {
    path: '/signup',
    name: 'Signup',
    component: () => import('../views/SignupView.vue'),
    meta: { guestOnly: true }
  },
  // Customer Views
  {
    path: '/customer',
    redirect: '/customer/cars',
    meta: { requiresAuth: true, role: 'customer' },
    children: [
      {
        path: 'cars',
        name: 'BrowseCars',
        component: () => import('../views/customer/BrowseCarsView.vue')
      },
      {
        path: 'cars/:id',
        name: 'CarDetail',
        component: () => import('../views/customer/CarDetailView.vue')
      },
      {
        path: 'payments',
        name: 'MyPayments',
        component: () => import('../views/customer/PaymentsView.vue')
      },
      {
        path: 'feedback',
        name: 'Feedback',
        component: () => import('../views/customer/FeedbackView.vue')
      },
      {
        path: 'bookings',
        name: 'BookingManagement',
        component: () => import('../views/customer/BookingManagementView.vue')
      }
    ]
  },
  // Admin Views
  {
    path: '/admin',
    redirect: '/admin/dashboard',
    meta: { requiresAuth: true, role: 'admin' },
    children: [
      {
        path: 'dashboard',
        name: 'AdminDashboard',
        component: () => import('../views/admin/AdminDashboardView.vue')
      },
      {
        path: 'cars',
        name: 'CarManagement',
        component: () => import('../views/admin/CarManagementView.vue')
      },
      {
        path: 'clearance',
        name: 'PaymentClearanceLog',
        component: () => import('../views/admin/PaymentClearanceView.vue')
      },
      {
        path: 'analytics',
        name: 'FeedbackAnalytics',
        component: () => import('../views/admin/FeedbackAnalyticsView.vue')
      },
      {
        path: 'rentals',
        name: 'RentalProcessing',
        component: () => import('../views/admin/RentalProcessingView.vue')
      }
    ]
  },
  // Catch All Route
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login'
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 };
  }
});

// Navigation Guards for Role-Based Access Control
router.beforeEach((to, from, next) => {
  const user = currentUser.value;

  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!user) {
      // User is not logged in, redirect to login
      next({ name: 'Login' });
    } else {
      // User is logged in, check role permissions
      const requiredRole = to.matched.find(record => record.meta.role)?.meta.role;
      if (requiredRole && user.role !== requiredRole) {
        // Role mismatch, redirect to appropriate default route
        if (user.role === 'admin') {
          next({ name: 'AdminDashboard' });
        } else {
          next({ name: 'BrowseCars' });
        }
      } else {
        next();
      }
    }
  } else if (to.matched.some(record => record.meta.guestOnly)) {
    if (user) {
      // User is already logged in, redirect away from guest-only pages
      if (user.role === 'admin') {
        next({ name: 'AdminDashboard' });
      } else {
        next({ name: 'BrowseCars' });
      }
    } else {
      next();
    }
  } else {
    next();
  }
});

export default router;
