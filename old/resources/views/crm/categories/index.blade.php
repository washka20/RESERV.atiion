<x-crm-layout>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <!-- Блок уведомления об успехе операции -->
                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- Кнопка добавления категории и форма поиска -->
                <div class="d-flex justify-content-between mb-3">
                    <a href="{{ route('categories.create') }}" class="btn btn-success">Добавить новую категорию</a>
                </div>

                <!-- Список категорий -->
                @foreach($categories as $category)
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row g-0">
                                <div class="col-sm-12 col-md-12 col-lg-4 my-2 d-flex ">
                                    <div class="ms-3">{{ $category->name }}</div>
                                </div>
                                <div class="col-sm-12 col-md-12 col-lg-4 my-2 d-flex ">
                                    <div class="ms-3">ID: {{ $category->id }} | Создание: {{ $category->created_at->format('Y-m-d') }}</div>
                                </div>
                                <div class="col-sm-12 col-md-12 col-lg-3 ms-2 my-2 d-flex ">
                                    <div class="btn-group" role="group">
                                            <a href="{{ route('categories.edit', $category->id) }}" class="btn btn-warning">Редактировать</a>
                                            <button type="button" class="btn btn-danger delete-category" data-id="{{ $category->id }}">Удалить</button>
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
