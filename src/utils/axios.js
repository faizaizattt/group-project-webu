import axios from 'axios';

// Create a clean axios instance targeting a dummy backend base URL
const api = axios.create({
  baseURL: 'http://localhost:8000/api', // Target URL for future PHP Slim REST Backend
  timeout: 5000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Add a request interceptor to attach bearer tokens if they exist
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

export default api;
