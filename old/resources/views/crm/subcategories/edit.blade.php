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
                <form method="post" action="{{ route('subcategories.update', $subcategory->id) }}">
                    @csrf
                    @method('put')
                    <div class="form-group my-3">
                        <label for="subcategoryName">Название</label>
                        <input type="text" name="name" value="{{ $subcategory->name }}" class="form-control" id="subcategoryName">
                    </div>
                    <div class="form-floating my-3">
                        <select name="category_id" class="form-select" id="floatingSelect" aria-label="Floating label select example">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ $subcategory->category_id == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>

                        <label for="floatingSelect">Subcategories with selects</label>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Отправить</button>
                </form>
            </div>
        </div>
    </div>
</x-crm-layout>
