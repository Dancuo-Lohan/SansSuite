document.querySelectorAll<HTMLFormElement>('form[data-confirm]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    const message = form.dataset.confirm ?? 'Confirmer cette action ?';
    if (!window.confirm(message)) {
      event.preventDefault();
    }
  });
});

document.querySelectorAll<HTMLButtonElement>('[data-open-details]').forEach((button) => {
  button.addEventListener('click', () => {
    const detailsId = button.dataset.openDetails;
    if (!detailsId) {
      return;
    }

    const details = document.getElementById(detailsId);
    if (!(details instanceof HTMLDetailsElement)) {
      return;
    }

    details.open = true;
    details.scrollIntoView({ behavior: 'smooth', block: 'center' });
    details.querySelector<HTMLElement>('select, input, textarea, button')?.focus({ preventScroll: true });
  });
});

document.querySelectorAll<HTMLElement>('[data-flash]').forEach((flash) => {
  window.setTimeout(() => {
    flash.classList.add('is-dismissing');
    window.setTimeout(() => flash.remove(), 180);
  }, 5000);
});
