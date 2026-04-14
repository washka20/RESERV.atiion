<x-crm-layout>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                @if (session('status'))
                    <div class="alert alert-success mt-3">
                        {{ session('status') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger mt-4">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="post" action="{{ route('products.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group my-2">
                        <label for="name">Название</label>
                        <input type="text" name="name" class="form-control" id="name">
                    </div>
                    <div class="form-group my-2">
                        <label for="description">Описание</label>
                        <input type="text" name="description" class="form-control" id="description">
                    </div>
                    <div class="form-group my-2">
                    </div>
                    <div class="form-group my-2">
                        <label for="price">Цена</label>
                        <input type="text" name="price" class="form-control" id="price" placeholder="Пример: 00000000.00">
                        <small class="form-text text-muted">Введите цену в формате XXXXXXXX.XX (до 99999999.99).</small>
                    </div>
                    <div class="form-floating my-2">
                        <select name="category_id" class="form-select" id="floatingSelect" aria-label="Floating label select example" required>
                            <option value="">Выберите категорию</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <label for="floatingSelect">Категории</label>
                    </div>
                    <div class="form-floating my-2">
                        <select name="subcategory_id" class="form-select" id="subcategorySelect" aria-label="Floating label select example">
                            <!-- Подкатегории будут добавлены сюда динамически -->
                        </select>
                        <label for="floatingSelect">Подкатегории</label>
                    </div>
                    <div class="form-group my-2">
                        <label for="image" class="form-label">Выберите картинку</label>
                        <input class="form-control" type="file" id="image" name="image">
                    </div>

                    <button type="submit" class="btn btn-outline-primary mt-2">Submit</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('floatingSelect').addEventListener('change', function() {
            let categoryId = this.value;
            fetch(`/subcategories/${categoryId}`)
                .then(response => response.json())
                .then(subcategories => {
                    let subcategorySelect = document.getElementById('subcategorySelect');
                    subcategorySelect.innerHTML = '';

                    if (subcategories.length > 0) {
                        subcategories.forEach(subcategory => {
                            let option = new Option(subcategory.name, subcategory.id);
                            subcategorySelect.add(option);
                        });
                    } else {
                        let option = new Option('Подкатегорий нет', '');
                        subcategorySelect.add(option);
                    }
                });
        });
    </script>
</x-crm-layout>
