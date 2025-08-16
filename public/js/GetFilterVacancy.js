document.getElementById('apply-filters').addEventListener('click', function () {
    const category = document.getElementById('filter-category').value.toLowerCase();
    const plateSearch = document.getElementById('search-plate').value.toLowerCase();
    const vacancies = document.querySelectorAll('#vacancy-list > div');

    vacancies.forEach(vacancy => {
        const vacancyCategory = vacancy.dataset.category.toLowerCase();
        const vacancyPlate = vacancy.dataset.plate.toLowerCase();

        const matchesCategory = (category === 'all' || vacancyCategory === category);
        const matchesPlate = (plateSearch === '' || vacancyPlate.includes(plateSearch));

        if (matchesCategory && matchesPlate) {
            vacancy.style.display = '';
        } else {
            vacancy.style.display = 'none';
        }
    });
});