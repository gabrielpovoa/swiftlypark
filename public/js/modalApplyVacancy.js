function openConfirmationModal() {
    // Pegar os valores dos inputs
    const plate = document.getElementsByName('plate')[0].value;
    const time = document.getElementsByName('entry_time')[0].value;
    const price = document.getElementsByName('paid_amount')[0].value;

    // Validar se não estão vazios antes de abrir
    if(!plate || !time || !price) {
        alert("Por favor, preencha todos os campos obrigatórios.");
        return;
    }

    // Injetar no Modal
    document.getElementById('reviewPlate').innerText = plate;
    document.getElementById('reviewTime').innerText = time;
    document.getElementById('reviewPrice').innerText = 'R$ ' + parseFloat(price).toLocaleString('pt-br', {minimumFractionDigits: 2});

    // Mostrar modal
    document.getElementById('confirmModal').classList.remove('hidden');
}

function closeConfirmationModal() {
    document.getElementById('confirmModal').classList.add('hidden');
}

function submitRealForm() {
    // Pegar o formulário real pelo ID ou Nome e enviar
    document.querySelector('form').submit();
}