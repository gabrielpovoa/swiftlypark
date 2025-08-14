document.addEventListener('DOMContentLoaded', function () {
    // Seleciona todos os elementos com a classe 'js-print-logs'
    const btnPrintElements = document.querySelectorAll('.js-print-logs');

    if (btnPrintElements.length === 0) return;

    // Função que contém a lógica de impressão
    const handlePrintClick = async (event) => {
        // Previne o comportamento padrão do link, que é navegar para '#'
        event.preventDefault();

        try {
            const response = await fetch('/logs/options');
            const filters = await response.json();

            const inputOptions = { '': 'Todos' };
            filters.forEach(item => {
                inputOptions[item.value] = item.label;
            });

            const { value: selectedDate } = await Swal.fire({
                title: 'Selecione a data para imprimir',
                input: 'select',
                inputOptions: inputOptions,
                inputPlaceholder: 'Selecione uma data ou Todos',
                showCancelButton: true,
                confirmButtonText: 'Imprimir',
                cancelButtonText: 'Cancelar',
            });

            if (selectedDate !== undefined) {
                const query = selectedDate ? `?filter=${selectedDate}` : '';
                window.location.href = `/logs/print${query}`;
            }

        } catch (error) {
            console.error('Erro ao carregar filtros:', error);
            Swal.fire('Erro', 'Não foi possível carregar as datas para filtro.', 'error');
        }
    };

    // Itera sobre a lista de elementos e adiciona o 'event listener' a cada um
    btnPrintElements.forEach(element => {
        element.addEventListener('click', handlePrintClick);
    });
});