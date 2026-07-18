function openConfirmationModal() {
    const form = document.querySelector('form[action="/vacancy/apply"]');
    const modal = document.getElementById('confirmModal');
    const plateInput = form?.elements.namedItem('plate');
    const timeInput = form?.elements.namedItem('entry_time');
    const plate = String(plateInput?.value || '').trim();
    const time = String(timeInput?.value || '').trim();

    if (!form || !modal || !plate || !time || !form.reportValidity()) {
        return;
    }

    document.getElementById('reviewPlate').innerText = plate;
    document.getElementById('reviewTime').innerText = time;
    modal.classList.remove('hidden');
}

function closeConfirmationModal() {
    document.getElementById('confirmModal').classList.add('hidden');
}

function submitRealForm() {
    const form = document.querySelector('form[action="/vacancy/apply"]');
    if (form?.reportValidity()) {
        form.requestSubmit();
    }
}
