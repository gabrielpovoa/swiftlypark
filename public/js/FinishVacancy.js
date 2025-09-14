document.querySelectorAll('.finalizar').forEach(button => {
    button.addEventListener('click', function () {
        const idVaga = this.dataset.id;

        Swal.fire({
            title: 'Finalizar Vaga',
            text: 'Digite a hora de saída:',
            input: 'time',
            inputLabel: 'Hora de Saída',
            inputPlaceholder: 'HH:mm',
            showCancelButton: true,
            confirmButtonText: 'Finalizar',
            cancelButtonText: 'Cancelar',
            preConfirm: (horaSaida) => {
                if (!horaSaida) {
                    Swal.showValidationMessage('Você precisa informar a hora de saída!');
                }
                return horaSaida;
            }
        }).then(async (result) => {
            if (!result.isConfirmed) return;

            // Envio como FormData (PHP lê em $_POST)
            const formData = new FormData();
            formData.append('id_vaga', idVaga);
            formData.append('hora_saida', result.value); // "HH:mm"

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
                    await Swal.fire('Finalizado!', 'A vaga foi liberada com sucesso.', 'success');
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
