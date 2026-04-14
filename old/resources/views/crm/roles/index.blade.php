<x-crm-layout>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif
                <a href="{{ route('roles.create') }}" class="btn btn-success my-2">Добавить новую роль</a>
                @foreach($roles as $role)
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row g-0">
                                    <div class="col-sm-12 col-md-12 col-lg-4 my-2 d-flex ">
                                        <div class="ms-3">{{ $role->name }}</div>
                                    </div>
                                    <div class="col-sm-12 col-md-12 col-lg-4 my-2 d-flex ">
                                        <div class="ms-3">
                                            ID: {{ $role->id }} | Создание: {{ $role->created_at }}
                                        </div>
                                    </div>
                                    <div class="col-sm-12 col-md-12 col-lg-3 ms-2 my-2 d-flex ">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-warning">Редактировать</a>
                                            <button type="button" class="btn btn-danger delete-user" data-id="{{ $role->id }}">Удалить</button>
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
