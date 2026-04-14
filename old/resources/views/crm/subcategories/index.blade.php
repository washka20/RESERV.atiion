<x-crm-layout>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                @if (session('status'))
                    <div class="alert alert-success mt-3">
                        {{ session('status') }}
                    </div>
                @endif
                <a href="{{ route('subcategories.create') }}" class="btn btn-success my-2">Добавить новую подкатегорию</a>
                @foreach($subcategories as $subcategory)
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row g-0">
                                <div class="col-sm-12 col-md-12 col-lg-4 my-2 d-flex ">
                                    <div class="ms-3">Подкатегория: {{ $subcategory->subcategoryName }}</div>
                                </div>
                                <div class="col-sm-12 col-md-12 col-lg-4 my-2 d-flex ">
                                    <div class="ms-3">Категория: {{ $subcategory->categoryName }}</div>
                                </div>
                                <div class="col-sm-12 col-md-12 col-lg-3 ms-2 my-2 d-flex ">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('subcategories.edit', $subcategory->subcategoryId) }}" class="btn btn-warning">Редактировать</a>
                                        <button type="button" class="btn btn-danger delete-subcategory" data-id="{{ $subcategory->subcategoryId }}">Удалить</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-crm-layout>
