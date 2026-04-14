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
                <form method="post" action="{{ route('subcategories.store') }}">
                    @csrf
                    <div class="form-group my-2">
                        <label for="categoryName">Название</label>
                        <input type="text" name="name" class="form-control" id="categoryName">
                    </div>
                    <div class="form-floating my-2">
                        <select name="category_id" class="form-select" id="floatingSelect" aria-label="Floating label select example">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <label for="floatingSelect">Подкатегории с выбранными параметрами</label>
                    </div>
                    <button type="submit" class="btn btn-outline-primary mt-2">Submit</button>
                </form>
            </div>
        </div>
    </div>
</x-crm-layout>
