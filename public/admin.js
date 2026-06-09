const GLOBAL_CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

var menuToggle = document.getElementById('menuToggle');
var headerNav  = document.getElementById('headerNav');
if (menuToggle && headerNav) {
    menuToggle.addEventListener('click', () => {
        menuToggle.classList.toggle('open');
        headerNav.classList.toggle('open');
    });
    headerNav.addEventListener('click', (e) => {
        if (e.target.tagName === 'A') {
            menuToggle.classList.remove('open');
            headerNav.classList.remove('open');
        }
    });
}

const addModal        = document.getElementById('addModal');
const descriptionModal= document.getElementById('descriptionModal');
const addModalTitle   = document.getElementById('addModalTitle');
const addForms        = Array.from(document.querySelectorAll('[data-entity-form]'));
const addMessage      = document.querySelector('[data-modal-message]');
const descriptionTitle= document.getElementById('descriptionModalTitle');
const descriptionBody = document.getElementById('descriptionModalBody');
const activeTab       = document.querySelector('meta[name="active-tab"]')?.content ?? 'events';
const addButton       = document.querySelector('[data-open-add-modal]');

function openModal(modal) {
    if (!modal) return;
    modal.hidden = false;
    document.body.classList.add('modal-open');
}

function closeModal(modal) {
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('modal-open');
}

function showModalMessage(text, type) {
    if (!addMessage) return;
    addMessage.hidden    = false;
    addMessage.textContent = text;
    addMessage.className = 'modal-message ' + (type === 'success' ? 'success' : 'error');
}

function resetModalMessage() {
    if (!addMessage) return;
    addMessage.hidden    = true;
    addMessage.textContent = '';
    addMessage.className = 'modal-message';
}

function openAddModal() {
    addForms.forEach(form => {
        form.classList.toggle('active', form.dataset.entityForm === activeTab);
    });
    addModalTitle.textContent = 'Add ' + (activeTab === 'events' ? 'Disaster Event' : (activeTab === 'shelters' ? 'Shelter' : 'Evacuation Route'));
    resetModalMessage();
    openModal(addModal);
}

const importModal  = document.getElementById('importModal');
const importButton = document.querySelector('[data-open-import-modal]');
const importMessage= document.querySelector('[data-import-message]');
const importForm   = document.getElementById('importForm');

function openImportModal() {
    resetImportMessage();
    openModal(importModal);
}

if (importButton) {
    importButton.addEventListener('click', openImportModal);
}

function resetImportMessage() {
    if (importMessage) {
        importMessage.hidden    = true;
        importMessage.textContent = '';
        importMessage.className = 'modal-message';
    }
}

function showImportMessage(text, type) {
    if (!importMessage) return;
    importMessage.hidden    = false;
    importMessage.textContent = text;
    importMessage.className = 'modal-message ' + (type === 'success' ? 'success' : 'error');
}

if (addButton) {
    addButton.addEventListener('click', openAddModal);
}

function closeDescriptionModal() {
    if (descriptionTitle) descriptionTitle.textContent = 'Description';
    if (descriptionBody)  descriptionBody.textContent  = '';
    closeModal(descriptionModal);
}

document.querySelectorAll('[data-close-modal]').forEach(btn => btn.addEventListener('click', () => closeModal(addModal)));
document.querySelectorAll('[data-close-description]').forEach(btn => btn.addEventListener('click', closeDescriptionModal));
document.querySelectorAll('[data-close-import]').forEach(btn => btn.addEventListener('click', () => closeModal(importModal)));

[addModal, descriptionModal, importModal].forEach(modal => {
    if (!modal) return;
    modal.addEventListener('click', (event) => {
        if (event.target !== modal) return;
        if (modal === addModal)        { closeModal(addModal);      return; }
        if (modal === importModal)     { closeModal(importModal);   return; }
        closeDescriptionModal();
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    if (addModal        && !addModal.hidden)        closeModal(addModal);
    if (importModal     && !importModal.hidden)     closeModal(importModal);
    if (descriptionModal && !descriptionModal.hidden) closeDescriptionModal();
});

if (importForm) {
    importForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const submitButton  = importForm.querySelector('.modal-submit');
        const previousText  = submitButton ? submitButton.textContent : '';

        if (submitButton) {
            submitButton.disabled    = true;
            submitButton.textContent = 'Importing...';
        }
        resetImportMessage();

        try {
            const response = await fetch(importForm.action, {
                method: 'POST',
                body:   new FormData(importForm),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': GLOBAL_CSRF_TOKEN
                }
            });

            const data = await response.json().catch(() => null);
            if (!response.ok || !data || data.success !== true) {
                showImportMessage(data?.error ?? 'The server rejected the import request.', 'error');
                return;
            }

            let msg = `Successfully imported ${data.imported} records.`;
            if (data.skipped > 0) msg += ` Skipped ${data.skipped} records.`;
            showImportMessage(msg, 'success');
            window.setTimeout(() => window.location.reload(), 1500);
        } catch {
            showImportMessage('Network error while performing import.', 'error');
        } finally {
            if (submitButton) {
                submitButton.disabled    = false;
                submitButton.textContent = previousText;
            }
        }
    });
}

document.addEventListener('click', (event) => {
    const descButton = event.target.closest('[data-open-description]');
    if (!descButton) return;
    descriptionTitle.textContent = descButton.dataset.title || 'Description';
    descriptionBody.textContent  = descButton.dataset.description || '';
    openModal(descriptionModal);
});

function toggleEditRow(button) {
    const tr = button.closest('tr');
    if (!tr) return;
    const views  = tr.querySelectorAll('.mode-view');
    const edits  = tr.querySelectorAll('.mode-edit');
    const goEdit = edits[0].style.display === 'none';
    views.forEach(el => el.style.display = goEdit ? 'none' : '');
    edits.forEach(el => el.style.display = goEdit ? ''     : 'none');
}

function parseResponseData(response) {
    return response.json().catch(() => null);
}

async function submitAddForm(form) {
    const endpoint     = form.action;
    const submitButton = form.querySelector('.modal-submit');
    const previousText = submitButton ? submitButton.textContent : '';
    const controller   = new AbortController();
    const timer        = setTimeout(() => controller.abort(), 12000);

    if (submitButton) {
        submitButton.disabled    = true;
        submitButton.textContent = 'Saving...';
    }
    resetModalMessage();

    try {
        const response = await fetch(endpoint, {
            method:  'POST',
            body:    new FormData(form),
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal:  controller.signal
        });

        const data = await parseResponseData(response);
        if (!response.ok || !data || data.success !== true) {
            showModalMessage(data?.error ?? 'The server rejected the request.', 'error');
            return;
        }

        showModalMessage('Saved successfully. Refreshing data...', 'success');
        window.setTimeout(() => window.location.reload(), 350);
    } catch (error) {
        showModalMessage(error?.name === 'AbortError' ? 'The server did not respond in time.' : 'Network error while saving the record.', 'error');
    } finally {
        clearTimeout(timer);
        if (submitButton) {
            submitButton.disabled    = false;
            submitButton.textContent = previousText;
        }
    }
}

addForms.forEach(form => {
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        submitAddForm(form);
    });
});

const entityTargets = {
    event:   '/admin/events',
    shelter: '/admin/shelters',
    route:   '/admin/routes'
};

function dispatchDelete(entityType, recordId) {
    if (!confirm(`Are you absolutely sure you want to delete this ${entityType}?`)) return;

    fetch(`${entityTargets[entityType]}/${recordId}`, {
        method:  'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': GLOBAL_CSRF_TOKEN }
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) window.location.reload();
            else alert('Operation failed: ' + (data.error || 'Unknown Error'));
        })
        .catch(() => alert('Network processing failure.'));
}

function dispatchUpdate(entityType, recordId, button) {
    const tr      = button.closest('tr');
    const payload = {};

    tr.querySelectorAll('input, select, textarea').forEach(input => {
        const match = input.className.match(/field-(\w+)/);
        if (match && match[1]) payload[match[1]] = input.value;
    });

    fetch(`${entityTargets[entityType]}/${recordId}`, {
        method:  'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': GLOBAL_CSRF_TOKEN },
        body:    JSON.stringify(payload)
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) window.location.reload();
            else alert('Update aborted: ' + (data.error || 'Validation error'));
        })
        .catch(() => alert('Network update error.'));
}

const actionsMenuToggle  = document.getElementById('actionsMenuToggle');
const actionsMenuContent = document.getElementById('actionsMenuContent');
if (actionsMenuToggle && actionsMenuContent) {
    actionsMenuToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        actionsMenuContent.classList.toggle('show');
    });
    document.addEventListener('click', (e) => {
        if (!actionsMenuToggle.contains(e.target)) {
            actionsMenuContent.classList.remove('show');
        }
    });
}
