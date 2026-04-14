class SearchFilter {
    constructor(listSelector, inputSelector, filterFields) {
        this.list = document.querySelectorAll(listSelector);
        this.input = document.querySelector(inputSelector);
        this.filterFields = filterFields;
        this.initEvents();
    }

    // Метод для инициализации событий
    initEvents() {
        this.input.addEventListener('input', () => {
            const query = this.input.value.toLowerCase();
            this.filterRecords(query);
        });
    }

    // Метод для фильтрации записей
    filterRecords(query) {
        this.list.forEach(card => {
            let isVisible = this.filterFields.some(field => {
                const elements = card.querySelectorAll(field);
                return Array.from(elements).some(element => element.textContent.toLowerCase().includes(query));
            });

            // Управляем видимостью элемента
            card.style.display = isVisible ? '' : 'none';
        });
    }
}

// Инициализация поиска после загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    new SearchFilter(
        '.card', // Селектор списка, по которому идет поиск
        'input[type="search"]', // Селектор поля ввода поиска
        ['.ms-3'] // Попробуем оставить только '.ms-3', если все нужные данные обёрнуты в этот класс
    );
});
