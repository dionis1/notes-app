import { useEffect, useState } from 'react';
import { api, errorMessage } from '../api';
import { useDialog } from '../hooks/useDialog';

export default function RevisionHistory({ note, dirty, onClose, onRestored }) {
  const [page, setPage] = useState(1);
  const [pages, setPages] = useState(1);
  const [items, setItems] = useState([]);
  const [version, setVersion] = useState(note.version);
  const [busy, setBusy] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [refresh, setRefresh] = useState(0);
  const ref = useDialog(() => { if (!busy) onClose(); });
  useEffect(() => {
    const abort = new AbortController();
    setLoading(true);
    Promise.all([
      api.get(`/api/v1/notes/${note.id}`, { signal: abort.signal }),
      api.get(`/api/v1/notes/${note.id}/revisions`, { params: { page }, signal: abort.signal }),
    ]).then(([current, revisions]) => {
      if (abort.signal.aborted) return;
      setVersion(current.data.data.version); setItems(revisions.data.data); setPages(revisions.data.meta.last_page); setError('');
    }).catch(reason => { if (!abort.signal.aborted) setError(errorMessage(reason)); })
      .finally(() => { if (!abort.signal.aborted) setLoading(false); });
    return () => abort.abort();
  }, [note.id, page, refresh]);
  async function restore(snapshot) {
    if (!window.confirm(`Restore version ${snapshot.version} as a new version?${dirty ? ' Your unsaved editor draft will be replaced.' : ''}`)) return;
    setBusy(true); setError('');
    try {
      const { data } = await api.post(`/api/v1/notes/${note.id}/revisions/${snapshot.id}/restore`, { version });
      onRestored(data.data);
    } catch (reason) { setError(errorMessage(reason)); }
    finally { setBusy(false); }
  }
  return <div className="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/60 p-4">
    <section ref={ref} role="dialog" aria-modal="true" aria-labelledby="history-title" className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
      <div className="flex items-start justify-between gap-3"><div><h2 id="history-title" className="text-xl font-bold">Revision history</h2><p className="mt-1 break-words text-sm text-slate-600">{note.title} · Current version {version}</p></div><button disabled={busy} onClick={onClose} aria-label="Close history" className="rounded border px-3 py-2">Close</button></div>
      <p className="my-4 text-sm text-slate-600">Restoring creates a new version. Earlier snapshots stay unchanged.</p>
      {error && <p role="alert" className="my-3 rounded bg-rose-50 p-3 text-rose-800">{error}</p>}
      <button disabled={busy || loading} onClick={() => setRefresh(n => n + 1)} className="mb-4 rounded border px-3 py-2 text-sm">Reload history</button>
      {loading ? <p role="status">Loading history…</p> : <ol className="grid gap-3">{items.map(item => <li key={item.id} className="rounded-xl border border-slate-200 p-4">
        <details><summary className="cursor-pointer break-words font-semibold">Version {item.version}: {item.title}</summary><p className="mt-3 max-h-60 overflow-auto whitespace-pre-wrap break-words text-sm text-slate-600">{item.content}</p><p className="mt-2 text-xs text-slate-500">{item.is_important ? 'Important' : 'Standard'}{item.restored_from_version && ` · Restored from version ${item.restored_from_version}`}</p></details>
        <button disabled={busy || item.version === version} onClick={() => restore(item)} className="mt-3 rounded-lg bg-emerald-900 px-3 py-2 text-sm font-semibold text-white disabled:opacity-40">Restore version {item.version}</button>
      </li>)}</ol>}
      {!loading && !items.length && <p>No snapshots yet. Existing notes gain history on their next edit.</p>}
      <div className="mt-5 flex items-center justify-between text-sm"><button disabled={busy || loading || page <= 1} onClick={() => setPage(n => n - 1)}>Previous revisions</button><span>Page {page} of {pages}</span><button disabled={busy || loading || page >= pages} onClick={() => setPage(n => n + 1)}>Next revisions</button></div>
    </section>
  </div>;
}
