import { useEffect, useRef, useState } from 'react';
import { api, errorMessage } from '../api';
import { useAuth } from '../App';
import RevisionHistory from '../components/RevisionHistory';
import { blankNote, isDirty } from '../lib/editor';

const blank = blankNote();
const date = value => value ? new Date(value).toLocaleDateString() : '';
export default function NotesPage() {
  const { user, draftCache } = useAuth();
  const cached = draftCache.current.get(user.id);
  const mutation = useRef(false);
  const [historyNote, setHistoryNote] = useState(null);
  const [listError, setListError] = useState('');
  const [items, setItems] = useState([]);
  const [search, setSearch] = useState('');
  const [important, setImportant] = useState(false);
  const [page, setPage] = useState(1);
  const [pages, setPages] = useState(1);
  const [total, setTotal] = useState(0);
  const [revision, setRevision] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const [editing, setEditing] = useState(cached?.editing || null);
  const [form, setForm] = useState(cached?.form || blank);
  const [saving, setSaving] = useState(false);
  const dirty = isDirty(form, editing);
  useEffect(() => { draftCache.current.set(user.id, { editing, form, dirty }); }, [editing, form, dirty, user.id, draftCache]);
  useEffect(() => {
    if (!dirty) return;
    const warn = event => { event.preventDefault(); event.returnValue = ''; };
    window.addEventListener('beforeunload', warn);
    return () => window.removeEventListener('beforeunload', warn);
  }, [dirty]);
  const canDiscard = () => !dirty || window.confirm('Discard your unsaved note changes?');

  useEffect(() => {
    const abort = new AbortController();
    const timer = setTimeout(async () => {
      setLoading(true);
      try {
        const { data } = await api.get('/api/v1/notes', {
          params: { q: search || undefined, important: important ? 1 : undefined, page },
          signal: abort.signal,
        });
        if (abort.signal.aborted) return;
        if (page > data.meta.last_page) { setPage(data.meta.last_page); return; }
        setItems(data.data);
        setPages(data.meta?.last_page || 1);
        setTotal(data.meta?.total || 0);
        setListError('');
      } catch (reason) {
        if (!abort.signal.aborted) setListError(errorMessage(reason));
      } finally { if (!abort.signal.aborted) setLoading(false); }
    }, search ? 250 : 0);
    return () => { clearTimeout(timer); abort.abort(); };
  }, [search, important, page, revision]);

  function startNew() { if (mutation.current || !canDiscard()) return; setEditing(null); setForm({ ...blank }); setError(''); document.getElementById('title')?.focus(); }
  function edit(note) {
    if (mutation.current || !canDiscard()) return;
    setEditing(note);
    setForm({ title: note.title, content: note.content, is_important: note.is_important });
    setError('');
    document.getElementById('title')?.focus();
  }
  async function save(event) {
    event.preventDefault(); if (mutation.current) return; mutation.current = true; setSaving(true); setError(''); setNotice('');
    try {
      const payload = { ...form, title: form.title.trim(), content: form.content.trim() };
      if (editing) await api.patch('/api/v1/notes/' + editing.id, { ...payload, version: editing.version });
      else await api.post('/api/v1/notes', payload);
      setNotice(editing ? 'Note updated.' : 'Note created.');
      setEditing(null); setForm({ ...blank }); setPage(1); setRevision(n => n + 1);
    } catch (reason) {
      setError(reason.response?.status === 409 ? 'This note changed elsewhere. Your draft is still here. Reload before saving again.' : errorMessage(reason));
    } finally { mutation.current = false; setSaving(false); }
  }
  async function remove(note) {
    if (mutation.current) return;
    if (!window.confirm('Permanently delete "' + note.title + '"?')) return;
    mutation.current = true; setSaving(true);
    setError(''); setNotice('');
    try {
      await api.delete('/api/v1/notes/' + note.id, { data: { version: note.version } });
      if (editing?.id === note.id) { setEditing(null); setForm({ ...blank }); }
      setNotice('Note deleted.');
      setRevision(n => n + 1);
    } catch (reason) { setError(errorMessage(reason)); }
    finally { mutation.current = false; setSaving(false); }
  }

  async function reloadLatest() {
    if (!editing || mutation.current || !canDiscard()) return;
    mutation.current = true; setSaving(true);
    try {
      const { data } = await api.get('/api/v1/notes/' + editing.id);
      setEditing(data.data); setForm({ title: data.data.title, content: data.data.content, is_important: data.data.is_important }); setError('');
    } catch (reason) { setError(errorMessage(reason)); }
    finally { mutation.current = false; setSaving(false); }
  }
  function restored(note) {
    setEditing(note); setForm({ title: note.title, content: note.content, is_important: note.is_important });
    setHistoryNote(null); setError(''); setNotice('Earlier content restored as a new version.'); setRevision(n => n + 1);
  }
  return <main id="main" className="mx-auto max-w-6xl px-5 py-10 sm:px-8 sm:py-14">
    <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
      <div><p className="mb-2 text-xs font-bold uppercase tracking-widest text-emerald-700">Your personal notebook</p>
        <h1 className="text-4xl font-extrabold tracking-tight text-slate-900">A home for your thoughts.</h1>
        <p className="mt-2 text-slate-600">Capture small ideas and find them when you need them.</p></div>
      <span className="rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-900">{total} {total === 1 ? 'note' : 'notes'}</span>
    </div>
    {listError && <p role="alert" className="mb-5 rounded bg-rose-50 p-3 text-rose-800">{listError}</p>}
    {notice && <p role="status" className="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-emerald-900">{notice}</p>}
    {error && <p role="alert" className="mb-5 rounded-lg border border-rose-200 bg-rose-50 p-3 text-rose-800">{error}</p>}
    <div className="grid items-start gap-7 lg:grid-cols-[minmax(280px,380px)_minmax(0,1fr)]">
      <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:sticky lg:top-6">
        <p className="text-xs font-bold uppercase tracking-widest text-emerald-700">Notes editor</p>
        <h2 className="mt-2 text-xl font-bold">{editing ? 'Edit note' : 'A new idea'}</h2>
        {dirty && <p className="mt-2 text-xs text-amber-800">Unsaved changes</p>}
        <form onSubmit={save} className="mt-6 grid gap-4">
          <fieldset disabled={saving} className="contents">
          <label className="grid gap-2 text-sm font-semibold" htmlFor="title">Title
            <input id="title" value={form.title} onChange={e => setForm(f => ({ ...f, title: e.target.value }))} required maxLength={255} placeholder="Give your idea a name" className="rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 font-normal" />
          </label>
          <label className="grid gap-2 text-sm font-semibold" htmlFor="content">Your note
            <textarea id="content" value={form.content} onChange={e => setForm(f => ({ ...f, content: e.target.value }))} required maxLength={10000} rows={8} placeholder="Start writing…" className="resize-y rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 font-normal" />
          </label>
          <label className="flex items-center gap-3 text-sm font-medium">
            <input type="checkbox" checked={form.is_important} onChange={e => setForm(f => ({ ...f, is_important: e.target.checked }))} className="size-4 accent-emerald-800" />
            Mark as important
          </label>
          <div className="flex flex-wrap items-center gap-2">
            <button disabled={saving} type="submit" className="rounded-lg bg-emerald-900 px-5 py-2.5 font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">{saving ? 'Saving…' : editing ? 'Save changes' : 'Save note'}</button>
            {editing && <button type="button" onClick={startNew} className="rounded-lg px-4 py-2.5 font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>}
          </div>
          </fieldset>
        </form>
        {editing && error && <div className="mt-4 flex flex-wrap gap-2 text-sm">
          <button disabled={saving} onClick={reloadLatest} className="rounded border px-3 py-2">Reload latest</button>
          <button disabled={saving} onClick={() => { setEditing(null); setError(''); }} className="rounded border px-3 py-2">Keep draft as new note</button>
        </div>}
      </section>
      <section aria-label="Saved notes" className="min-w-0">
        <div className="mb-5 flex flex-wrap gap-3">
          <label className="min-w-48 flex-1" htmlFor="search-notes">
            <span className="sr-only">Search notes</span>
            <input id="search-notes" type="search" value={search} onChange={e => { setSearch(e.target.value); setPage(1); }} placeholder="Search notes…" className="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5" />
          </label>
          <button type="button" aria-pressed={important} onClick={() => { setImportant(v => !v); setPage(1); }} className={'rounded-lg px-4 py-2.5 text-sm font-semibold ' + (important ? 'bg-emerald-900 text-white' : 'border border-slate-300 bg-white text-emerald-900')}>{important ? 'Show all notes' : 'Important only'}</button>
        </div>
        {loading && <p role="status" className="p-5 text-slate-500">Loading notes…</p>}
        {!loading && !items.length && <div className="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
          <span aria-hidden="true" className="text-4xl">✎</span>
          <h2 className="mt-4 text-xl font-bold">Room for your next idea</h2>
          <p className="mt-2 text-sm text-slate-600">{important || search ? 'Try adjusting your filters.' : 'Write your first note to get started.'}</p>
        </div>}
        {!loading && <ol className="grid gap-4">
          {items.map(note => <li key={note.id} className={'min-w-0 rounded-2xl border bg-white p-5 shadow-sm ' + (note.is_important ? 'border-amber-300' : 'border-slate-200')}>
            <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
              <span className="text-xs font-medium text-slate-500">{date(note.updated_at)}</span>
              {note.is_important && <span className="rounded bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-800">Important</span>}
            </div>
            <h3 className="break-words text-lg font-bold">{note.title}</h3>
            <p className="mt-2 max-h-52 overflow-auto whitespace-pre-wrap break-words text-sm leading-relaxed text-slate-600">{note.content}</p>
            <div className="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-3">
              <button type="button" disabled={saving} onClick={() => edit(note)} aria-label={'Edit ' + note.title} className="rounded-lg px-3 py-1.5 text-sm font-semibold text-emerald-800 hover:bg-emerald-50">Edit</button>
              <button type="button" disabled={saving} onClick={() => setHistoryNote(note)} aria-label={'History of ' + note.title} className="rounded-lg px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">History</button>
              <button type="button" disabled={saving} onClick={() => remove(note)} aria-label={'Delete ' + note.title} className="rounded-lg px-3 py-1.5 text-sm font-semibold text-rose-700 hover:bg-rose-50">Delete</button>
            </div>
          </li>)}
        </ol>}
        <div className="mt-6 flex items-center justify-between gap-4 text-sm text-slate-600">
          <button disabled={loading || page <= 1} onClick={() => setPage(p => p - 1)} className="rounded-lg border border-slate-300 px-4 py-2 disabled:opacity-40">Previous</button>
          <span>Page {page} of {pages}</span>
          <button disabled={loading || page >= pages} onClick={() => setPage(p => p + 1)} className="rounded-lg border border-slate-300 px-4 py-2 disabled:opacity-40">Next</button>
        </div>
      </section>
    </div>
    {historyNote && <RevisionHistory note={historyNote} dirty={dirty} onClose={() => setHistoryNote(null)} onRestored={restored} />}
  </main>;
}
