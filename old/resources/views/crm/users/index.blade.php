<x-crm-layout>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                @if (session('status'))
                    <div class="alert alert-success mt-3">
                        {{ session('status') }}
                    </div>
                @endif
                @foreach($users as $user)
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row g-0">
                                <div class="col-sm-12 col-md-12 col-lg-4 my-2 d-flex ">
                                    <div class="ms-3">{{ $user->last_name . ' ' . $user->first_name . ' ' . $user->middle_name }}</div>
                                </div>
                                <div class="col-sm-12 col-md-12 col-lg-4 my-2 d-flex ">
                                    <div class="ms-3">
                                        Роль:
                                        @foreach($user->roles as $role)
                                            {{ $role['name'] }}
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-12 col-lg-3 ms-2 my-2 d-flex ">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning">Редактировать</a>
                                        <button type="button" class="btn btn-danger delete-user" data-id="{{ $user->id }}">Удалить</button>
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
