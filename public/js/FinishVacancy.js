document.querySelectorAll('.finalizar').forEach(button => {
    button.addEventListener('click', function () {
        const idVaga = this.dataset.id;
        const isMonthly = this.dataset.monthly === '1';
        const csrfToken = this.dataset.csrf || '';

        Swal.fire({
            title: 'Finalizar Vaga',
            html: `
                <label for="checkout-time" class="swal2-input-label">Hora de saída</label>
                <input id="checkout-time" class="swal2-input" type="time" required>
                ${isMonthly ? '' : `
                    <label for="checkout-payment" class="swal2-input-label">Forma de pagamento</label>
                    <select id="checkout-payment" class="swal2-select" required>
                        <option value="">Selecione</option>
                        <option value="PIX">Pix</option>
                        <option value="CARD">Cartão</option>
                        <option value="CASH">Dinheiro</option>
                    </select>
                `}
            `,
            showCancelButton: true,
            confirmButtonText: 'Finalizar',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const horaSaida = document.getElementById('checkout-time').value;
                const paymentMethod = isMonthly
                    ? null
                    : document.getElementById('checkout-payment').value;
                if (!horaSaida) {
                    Swal.showValidationMessage('Você precisa informar a hora de saída!');
                    return false;
                }
                if (!isMonthly && !paymentMethod) {
                    Swal.showValidationMessage('Selecione a forma de pagamento.');
                    return false;
                }

                return { horaSaida, paymentMethod };
            }
        }).then(async (result) => {
            if (!result.isConfirmed) return;

            // Envio como FormData (PHP lê em $_POST)
            const formData = new FormData();
            formData.append('id_vaga', idVaga);
            formData.append('hora_saida', result.value.horaSaida);
            formData.append('csrf_token', csrfToken);
            if (result.value.paymentMethod) {
                formData.append('payment_method', result.value.paymentMethod);
            }

            try {
                const res = await fetch('/vacancy/finish', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin' // garante envio do cookie da sessão
                });

                // Se a sessão expirou e o router redirecionou pro /login
                if (res.redirected || (res.url && res.url.includes('/login'))) {
                    await Swal.fire('Sessão expirada', 'Faça login novamente.', 'warning');
                    window.location.href = '/login';
                    return;
                }

                const ct = res.headers.get('content-type') || '';
                const raw = await res.text();
                let data = null;
                if (ct.includes('application/json')) {
                    try { data = JSON.parse(raw); } catch (_) {}
                }

                if (!res.ok) {
                    // HTTP 4xx/5xx
                    const msg = (data && data.message) ? data.message : (raw || 'Falha desconhecida.');
                    Swal.fire('Erro', msg.toString().slice(0, 300), 'error');
                    return;
                }

                if (!data) {
                    // Servidor respondeu HTML/Texto em vez de JSON
                    Swal.fire('Erro', 'Retorno não-JSON do servidor.', 'error');
                    return;
                }

                if (data.success) {
                    const amount = Number(data.amount || 0).toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                    });
                    const message = data.monthly
                        ? 'Estadia mensalista encerrada sem nova cobrança.'
                        : `Pagamento de ${amount} registrado e vaga liberada.`;
                    await Swal.fire('Finalizado!', message, 'success');
                    window.location.reload();
                } else {
                    Swal.fire('Erro', data.message || 'Falha ao finalizar.', 'error');
                }
            } catch (err) {
                Swal.fire('Erro', 'Ocorreu um erro no servidor.', 'error');
            }
        });
    });
});
