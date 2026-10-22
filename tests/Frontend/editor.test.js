import { describe, expect, it } from 'vitest';
import { blankNote, isDirty } from '../../resources/js/lib/editor';

describe('private editor draft comparisons', () => {
  it('detects title, content, and importance changes against the saved version', () => {
    const note = { id: 1, title: 'Original', content: 'Body', is_important: false, version: 2 };
    expect(isDirty({ title: note.title, content: note.content, is_important: false }, note)).toBe(false);
    expect(isDirty({ ...note, content: 'Unsaved body' }, note)).toBe(true);
    expect(isDirty({ ...note, is_important: true }, note)).toBe(true);
    expect(isDirty({ ...note, title: '' }, note)).toBe(true);
  });
  it('treats an untouched new note as clean and creates independent form objects', () => {
    expect(isDirty(blankNote(), null)).toBe(false);
    expect(isDirty({ ...blankNote(), content: 'An idea' }, null)).toBe(true);
    const changed = blankNote(); changed.title = 'Changed';
    expect(blankNote().title).toBe('');
  });
});
