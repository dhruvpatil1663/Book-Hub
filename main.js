document.addEventListener('DOMContentLoaded', function () {
    var forms = document.querySelectorAll('[data-confirm-delete]');
    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm('Delete this listing permanently?')) {
                event.preventDefault();
            }
        });
    });

    var bookForms = document.querySelectorAll('[data-validate-book]');
    bookForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var title = form.querySelector('#title');
            var isbn = form.querySelector('#isbn');
            var price = form.querySelector('#price');
            var stock = form.querySelector('#stock');

            if (!title || !isbn || !price || !stock) {
                return;
            }

            var errors = [];
            if (title.value.trim().length < 2) {
                errors.push('Title must be at least 2 characters.');
            }
            if (!/^[0-9Xx\-]{10,20}$/.test(isbn.value.trim())) {
                errors.push('ISBN format is invalid.');
            }
            if (Number(price.value) < 0) {
                errors.push('Price cannot be negative.');
            }
            if (!Number.isInteger(Number(stock.value)) || Number(stock.value) < 0) {
                errors.push('Stock must be a non-negative integer.');
            }

            if (errors.length > 0) {
                event.preventDefault();
                window.alert(errors.join('\n'));
            }
        });
    });
});
