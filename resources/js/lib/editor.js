export const blankNote = () => ({ title: '', content: '', is_important: false });
export function isDirty(form, note) {
  const baseline = note || blankNote();
  return ['title', 'content', 'is_important'].some(key => form[key] !== baseline[key]);
}
