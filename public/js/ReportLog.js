document.addEventListener('DOMContentLoaded', function () {
    const btnPrint = document.getElementById('btn-print-logs');

    if (!btnPrint) return;

    btnPrint.addEventListener('click', async () => {
        try {
            // Busca os filtros (datas) via fetch
            const response = await fetch('/logs/options');
            const filters = await response.json();

            // Adiciona opção "Todos"
            const inputOptions = { '': 'Todos' };
            filters.forEach(item => {
                inputOptions[item.value] = item.label;
            });

            // Mostra SweetAlert com select
            const { value: selectedDate } = await Swal.fire({
                title: 'Selecione a data para imprimir',
                input: 'select',
                inputOptions: inputOptions,
                inputPlaceholder: 'Selecione uma data ou Todos',
                showCancelButton: true,
                confirmButtonText: 'Imprimir',
                cancelButtonText: 'Cancelar',
            });

            if (selectedDate !== undefined) {  // permite '' (todos)
                // Redireciona para a página de logs com o filtro escolhido
                // Se for '', envia sem filtro
                const query = selectedDate ? `?filter=${selectedDate}` : '';
                window.location.href = `/logs/print${query}`;
            }

        } catch (error) {
            console.error('Erro ao carregar filtros:', error);
            Swal.fire('Erro', 'Não foi possível carregar as datas para filtro.', 'error');
        }
    });
});
