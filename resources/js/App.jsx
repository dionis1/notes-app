import { createContext, useContext, useEffect, useRef, useState } from 'react';
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
  const { user, setUser, draftCache } = useAuth();
  const navigate = useNavigate();
  const [error, setError] = useState('');
  async function signOut() {
    if (draftCache.current.get(user.id)?.dirty && !window.confirm('Discard your unsaved note and sign out?')) return;
    setError('');
    try {
      await api.post('/logout');
      draftCache.current.clear();
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
  const draftCache = useRef(new Map());
  const [sessionNotice, setSessionNotice] = useState('');
  const navigate = useNavigate();
  useEffect(() => {
    const interceptor = api.interceptors.response.use(response => response, error => {
      if (error.response?.status === 401 && user) {
        setUser(null); setSessionNotice('Your session expired. Sign in again; your unsaved draft is kept in this tab.');
        navigate('/login', { replace: true });
      }
      return Promise.reject(error);
    });
    return () => api.interceptors.response.eject(interceptor);
  }, [user, navigate]);

  useEffect(() => {
    let active = true;
    api.get('/api/v1/me').then(({ data }) => { if (active) setUser(data.data); })
      .catch(error => { if (active && error.response?.status !== 401) console.error('Session check failed.'); })
      .finally(() => { if (active) setReady(true); });
    return () => { active = false; };
  }, []);

  return <AuthContext.Provider value={{ user, setUser, ready, draftCache }}>
    <div className="min-h-screen bg-slate-50 text-slate-900">
      <a href="#main" className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-3 focus:z-50 focus:rounded focus:bg-white focus:p-3">Skip to content</a>
      <Header />
      {!user && sessionNotice && <p role="status" className="mx-auto mt-4 max-w-3xl rounded bg-amber-50 p-4 text-amber-900">{sessionNotice}</p>}
      <Routes>
        <Route path="/" element={<Navigate to="/notes" replace />} />
        <Route path="/login" element={<AuthPage key="login" mode="login" />} />
        <Route path="/register" element={<AuthPage key="register" mode="register" />} />
        <Route path="/forgot-password" element={<AuthPage key="forgot" mode="forgot" />} />
        <Route path="/reset-password/:token" element={<AuthPage key="reset" mode="reset" />} />
        <Route path="/notes" element={<Protected><NotesPage /></Protected>} />
        <Route path="*" element={<Navigate to="/notes" replace />} />
      </Routes>
    </div>
  </AuthContext.Provider>;
}
