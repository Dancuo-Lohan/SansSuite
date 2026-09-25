const statusSelect = document.querySelector<HTMLSelectElement>('#status');
const rejectionFields = document.querySelector<HTMLElement>('[data-rejection-fields]');
const rejectionDate = document.querySelector<HTMLInputElement>('#rejection_date');
const rejectionNote = document.querySelector<HTMLTextAreaElement>('#rejection_note');
const statusForm = document.querySelector<HTMLFormElement>('[data-status-form]');
const statusSubmit = document.querySelector<HTMLButtonElement>('[data-status-submit]');

const hasStatusChanged = (): boolean => {
  if (!statusForm || !statusSelect) {
    return true;
  }

  const currentStatus = statusForm.dataset.currentStatus ?? '';
  if (statusSelect.value !== currentStatus) {
    return true;
  }
  if (currentStatus !== 'rejected') {
    return false;
  }

  return rejectionDate?.value !== (statusForm.dataset.initialRejectionDate ?? '')
    || rejectionNote?.value.trim() !== (statusForm.dataset.initialRejectionNote ?? '');
};

const updateStatusSubmit = (): void => {
  if (!statusSubmit) {
    return;
  }
  const changed = hasStatusChanged();
  statusSubmit.disabled = !changed;
  statusSubmit.textContent = changed ? 'Mettre à jour' : 'Aucun changement';
};

const updateRejectionFields = (): void => {
  const isRejected = statusSelect?.value === 'rejected';
  rejectionFields?.classList.toggle('hidden', !isRejected);
  if (rejectionDate) {
    rejectionDate.required = isRejected;
  }
  updateStatusSubmit();
};

statusSelect?.addEventListener('change', updateRejectionFields);
rejectionDate?.addEventListener('input', updateStatusSubmit);
rejectionNote?.addEventListener('input', updateStatusSubmit);
updateRejectionFields();

document.querySelectorAll<HTMLButtonElement>('[data-activity-editor-toggle]').forEach((toggle) => {
  toggle.addEventListener('click', () => {
    const editorId = toggle.getAttribute('aria-controls');
    const editor = editorId ? document.getElementById(editorId) : null;
    if (!editor) {
      return;
    }

    const isOpening = editor.hidden;
    editor.hidden = !isOpening;
    toggle.setAttribute('aria-expanded', String(isOpening));
    if (isOpening) {
      editor.querySelector<HTMLElement>('select, input, textarea')?.focus();
    }
  });
});

document.querySelectorAll<HTMLButtonElement>('[data-activity-editor-close]').forEach((closeButton) => {
  closeButton.addEventListener('click', () => {
    const editorId = closeButton.dataset.activityEditorClose;
    const editor = editorId ? document.getElementById(editorId) : null;
    const toggle = editorId
      ? document.querySelector<HTMLButtonElement>(`[data-activity-editor-toggle][aria-controls="${editorId}"]`)
      : null;
    if (!editor) {
      return;
    }

    editor.hidden = true;
    toggle?.setAttribute('aria-expanded', 'false');
    if (toggle) {
      toggle.focus();
    }
  });
});
