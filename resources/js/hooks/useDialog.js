import { useEffect, useRef } from 'react';

export function useDialog(onClose) {
  const ref = useRef(null);
  const close = useRef(onClose);
  close.current = onClose;
  useEffect(() => {
    const previous = document.activeElement;
    const selector = 'button:not([disabled]), a[href], input:not([disabled]), textarea:not([disabled]), select:not([disabled])';
    ref.current?.querySelector(selector)?.focus();
    function keys(event) {
      if (event.key === 'Escape') { event.preventDefault(); close.current(); }
      if (event.key !== 'Tab') return;
      const nodes = [...(ref.current?.querySelectorAll(selector) || [])].filter(el => el.getClientRects().length);
      if (!nodes.length) { event.preventDefault(); return; }
      if (event.shiftKey && document.activeElement === nodes[0]) { event.preventDefault(); nodes.at(-1).focus(); }
      else if (!event.shiftKey && document.activeElement === nodes.at(-1)) { event.preventDefault(); nodes[0].focus(); }
    }
    document.addEventListener('keydown', keys);
    return () => { document.removeEventListener('keydown', keys); previous?.focus?.(); };
  }, []);
  return ref;
}
