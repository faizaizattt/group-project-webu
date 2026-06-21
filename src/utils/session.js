import { ref } from 'vue';
import api from './axios.js';

// Load initial user state from localStorage if it exists
const storedUser = localStorage.getItem('user_session');
const initialUser = storedUser ? JSON.parse(storedUser) : null;

export const currentUser = ref(initialUser);

/**
 * Login - POST /api/login
 * Sends credentials to the PHP Slim backend and stores JWT token
 */
export const loginSim = async (email, password, role) => {
  try {
    const res = await api.post('/login', { email, password, role });
    const data = res.data;

    const user = {
      email: data.email,
      name: data.name,
      role: data.role,
      token: data.token
    };

    currentUser.value = user;
    localStorage.setItem('user_session', JSON.stringify(user));
    localStorage.setItem('auth_token', user.token);
    return user;
  } catch (err) {
    // If backend is unreachable, fall back to mock login for development
    if (!err.response) {
      console.warn('Backend unreachable — using mock login');
      return mockLogin(email, password, role);
    }
    const message = err.response?.data?.message || 'Login failed';
    throw new Error(message);
  }
};

/**
 * Signup - POST /api/register
 */
export const signupSim = async (name, email, password, role) => {
  try {
    const res = await api.post('/register', { name, email, password, phone: '', role });
    const data = res.data;

    const user = {
      email: data.email,
      name: data.name,
      role: data.role,
      token: data.token
    };

    currentUser.value = user;
    localStorage.setItem('user_session', JSON.stringify(user));
    localStorage.setItem('auth_token', user.token);
    return user;
  } catch (err) {
    if (!err.response) {
      console.warn('Backend unreachable — using mock signup');
      return mockLogin(email, password, role);
    }
    const message = err.response?.data?.message || 'Registration failed';
    throw new Error(message);
  }
};

/**
 * Logout - Clears local session and JWT token
 */
export const logoutSim = () => {
  currentUser.value = null;
  localStorage.removeItem('user_session');
  localStorage.removeItem('auth_token');
};

/**
 * Fallback mock login when the PHP backend is not running.
 * Allows the Vue frontend to function independently during development.
 */
const mockLogin = async (email, password, role) => {
  await new Promise(resolve => setTimeout(resolve, 800));

  const user = {
    email: email,
    name: role === 'admin' ? 'Administrator Account' : 'Customer Account',
    role: role,
    token: 'mock_jwt_token_xyz_123'
  };

  currentUser.value = user;
  localStorage.setItem('user_session', JSON.stringify(user));
  localStorage.setItem('auth_token', user.token);
  return user;
};
