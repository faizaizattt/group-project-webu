import { ref, reactive } from 'vue';

// Load initial user state from localStorage if it exists
const storedUser = localStorage.getItem('user_session');
const initialUser = storedUser ? JSON.parse(storedUser) : null;

export const currentUser = ref(initialUser);

export const loginSim = async (email, password, role) => {
  // Simulate API delay
  await new Promise(resolve => setTimeout(resolve, 800));
  
  // Set mock authenticated user
  const user = {
    email: email,
    name: role === 'admin' ? 'Administrator Account' : 'Customer Account',
    role: role, // 'customer' or 'admin'
    token: 'mock_jwt_token_xyz_123'
  };
  
  currentUser.value = user;
  localStorage.setItem('user_session', JSON.stringify(user));
  localStorage.setItem('auth_token', user.token);
  return user;
};

export const signupSim = async (name, email, password, role) => {
  // Simulate API delay
  await new Promise(resolve => setTimeout(resolve, 800));
  
  const user = {
    email: email,
    name: name,
    role: role,
    token: 'mock_jwt_token_xyz_123'
  };
  
  currentUser.value = user;
  localStorage.setItem('user_session', JSON.stringify(user));
  localStorage.setItem('auth_token', user.token);
  return user;
};

export const logoutSim = () => {
  currentUser.value = null;
  localStorage.removeItem('user_session');
  localStorage.removeItem('auth_token');
};
