import { useState } from 'react';
import { Link, Navigate, useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { api, csrf, errorMessage } from '../api';
import { useAuth } from '../App';

export default function AuthPage({ mode }) {
  const { user, ready, setUser } = useAuth();
  const navigate = useNavigate();
  const { token } = useParams();
  const [searchParams] = useSearchParams();
  const [values, setValues] = useState({ name: '', email: searchParams.get('email') || '', password: '', password_confirmation: '' });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const labels = { login: 'Welcome back', register: 'Create your account', forgot: 'Reset your password', reset: 'Choose a new password' };
  const change = event => setValues(current => ({ ...current, [event.target.name]: event.target.value }));

  if (ready && user && ['login', 'register'].includes(mode)) return <Navigate to="/notes" replace />;

  async function submit(event) {
    event.preventDefault();
    setBusy(true); setError(''); setNotice('');
    try {
      await csrf();
      if (mode === 'forgot') {
        const { data } = await api.post('/forgot-password', { email: values.email });
        setNotice(data.message);
      } else if (mode === 'reset') {
        const { data } = await api.post('/reset-password', { ...values, token });
        setNotice(data.message);
      } else {
        await api.post(mode === 'register' ? '/register' : '/login', values);
        const { data } = await api.get('/api/v1/me');
        setUser(data.data);
        navigate('/notes', { replace: true });
      }
    } catch (reason) { setError(errorMessage(reason)); }
    finally { setBusy(false); }
  }

  return <main id="main" className="mx-auto max-w-lg px-5 py-12 sm:py-20">
    <section className="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm sm:p-10">
      <p className="mb-2 text-xs font-bold uppercase tracking-widest text-emerald-700">A little space to think</p>
      <h1 className="text-3xl font-bold">{labels[mode]}</h1>
      <p className="mt-3 text-sm text-slate-600">Private notes, securely saved and always yours.</p>
      {error && <p role="alert" className="mt-5 rounded-lg bg-rose-50 p-3 text-sm text-rose-800">{error}</p>}
      {notice && <p role="status" className="mt-5 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{notice}</p>}
      <form onSubmit={submit} className="mt-7 grid gap-4">
        {mode === 'register' && <label className="grid gap-1.5 text-sm font-semibold">Name
          <input name="name" value={values.name} onChange={change} autoComplete="name" required maxLength={100} className="rounded-lg border border-slate-300 px-3 py-2.5" /></label>}
        <label className="grid gap-1.5 text-sm font-semibold">Email address
          <input name="email" type="email" value={values.email} onChange={change} autoComplete="email" required maxLength={255} className="rounded-lg border border-slate-300 px-3 py-2.5" /></label>
        {!['forgot'].includes(mode) && <label className="grid gap-1.5 text-sm font-semibold">{mode === 'login' ? 'Password' : 'Password (at least 12 characters)'}
          <input name="password" type="password" value={values.password} onChange={change} autoComplete={mode === 'login' ? 'current-password' : 'new-password'} required minLength={mode === 'login' ? undefined : 12} className="rounded-lg border border-slate-300 px-3 py-2.5" /></label>}
        {['register', 'reset'].includes(mode) && <label className="grid gap-1.5 text-sm font-semibold">Confirm password
          <input name="password_confirmation" type="password" value={values.password_confirmation} onChange={change} autoComplete="new-password" required minLength={12} className="rounded-lg border border-slate-300 px-3 py-2.5" /></label>}
        <button type="submit" disabled={busy} className="mt-2 rounded-lg bg-emerald-900 px-5 py-3 font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">
          {busy ? 'Please wait…' : ({ login: 'Sign in', register: 'Create account', forgot: 'Email reset link', reset: 'Reset password' })[mode]}
        </button>
      </form>
      <nav aria-label="Account options" className="mt-7 flex flex-wrap gap-x-6 gap-y-2 text-sm font-semibold text-emerald-800">
        {mode !== 'login' && <Link to="/login" className="hover:underline">Sign in</Link>}
        {mode !== 'register' && <Link to="/register" className="hover:underline">Create an account</Link>}
        {mode === 'login' && <Link to="/forgot-password" className="hover:underline">Forgot password?</Link>}
      </nav>
    </section>
  </main>;
}
