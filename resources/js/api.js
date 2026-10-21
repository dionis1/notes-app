import axios from 'axios';

export const api = axios.create({
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
});

export const csrf = () => api.get('/sanctum/csrf-cookie');

export function errorMessage(error) {
  const errors = error.response?.data?.errors;
  if (errors) return Object.values(errors).flat().join(' ');
  if (error.response?.status === 419) return 'Your session has expired. Refresh the page and try again.';
  return error.response?.data?.message || 'The request failed. Your draft has been kept.';
}
