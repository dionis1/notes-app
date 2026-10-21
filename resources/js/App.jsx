import { createContext, useContext, useEffect, useState } from 'react';
import { Link, Navigate, Route, Routes, useNavigate } from 'react-router-dom';
import { api } from './api';
import AuthPage from './pages/AuthPage';
import NotesPage from './pages/NotesPage';

export const AuthContext = createContext(null);
export const useAuth = () => useContext(AuthContext);

function Protected({ children }) {
  const { user, ready } = useAuth();
  if (!ready) return <p role="status" className="mx-auto max-w-xl p-12 text-slate-600">Loading your workspace…</p>;
  return user ? children : <Navigate to="/login" replace />;
}

function Header() {
  const { user, setUser } = useAuth();
  const navigate = useNavigate();
  const [error, setError] = useState('');
  async function signOut() {
    setError('');
    try {
      await api.post('/logout');
      setUser(null);
      navigate('/login', { replace: true });
    } catch {
      setError('Could not sign out. Please try again.');
    }
  }
  return <header className="border-b border-slate-200 bg-white">
    <div className="mx-auto flex max-w-6xl flex-wrap items-center gap-4 px-5 py-4 sm:px-8">
      <Link to="/notes" className="flex items-center gap-3 text-xl font-extrabold tracking-tight text-emerald-900">
        <span className="grid size-10 place-items-center rounded-xl bg-emerald-900 font-bold text-white">N</span>
        Notes <span className="hidden border-l border-slate-200 pl-3 text-xs font-medium uppercase tracking-widest text-slate-500 sm:block">Personal workspace</span>
      </Link>
      <div className="ml-auto flex items-center gap-4">
        {user && <span className="text-sm text-slate-600">{user.name}</span>}
        {user && <button type="button" onClick={signOut} className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-50">Sign out</button>}
      </div>
      {error && <p role="alert" className="w-full text-sm text-rose-700">{error}</p>}
    </div>
  </header>;
}

export default function App() {
  const [user, setUser] = useState(null);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    let active = true;
    api.get('/api/v1/me').then(({ data }) => { if (active) setUser(data.data); })
      .catch(error => { if (active && error.response?.status !== 401) console.error('Session check failed.'); })
      .finally(() => { if (active) setReady(true); });
    return () => { active = false; };
  }, []);

  return <AuthContext.Provider value={{ user, setUser, ready }}>
    <div className="min-h-screen bg-slate-50 text-slate-900">
      <a href="#main" className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-3 focus:z-50 focus:rounded focus:bg-white focus:p-3">Skip to content</a>
      <Header />
      <Routes>
        <Route path="/" element={<Navigate to="/notes" replace />} />
        <Route path="/login" element={<AuthPage mode="login" />} />
        <Route path="/register" element={<AuthPage mode="register" />} />
        <Route path="/forgot-password" element={<AuthPage mode="forgot" />} />
        <Route path="/reset-password/:token" element={<AuthPage mode="reset" />} />
        <Route path="/notes" element={<Protected><NotesPage /></Protected>} />
        <Route path="*" element={<Navigate to="/notes" replace />} />
      </Routes>
    </div>
  </AuthContext.Provider>;
}
