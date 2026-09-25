const focusedDays = document.querySelectorAll<HTMLElement>('[data-calendar-focus="true"]');
const visibleFocusedDay = Array.from(focusedDays).find((day) => day.getClientRects().length > 0);

visibleFocusedDay?.scrollIntoView({ behavior: 'smooth', block: 'center' });
