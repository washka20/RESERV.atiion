<x-crm-layout>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                @if (session('status'))
                    <div class="alert alert-success">
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
                <form method="post" action="{{ route('users.update', $user->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="form-group my-2">
                        <label for="last_name">Фамилия</label>
                        <input type="text" name="last_name" value="{{ $user->last_name }}" class="form-control" id="last_name">
                    </div>
                    <div class="form-group my-2">
                        <label for="first_name">Имя</label>
                        <input type="text" name="first_name" value="{{ $user->first_name }}" class="form-control" id="first_name">
                    </div>
                    <div class="form-group my-2">
                        <label for="middle_name">Отчество</label>
                        <input type="text" name="middle_name" value="{{ $user->middle_name }}" class="form-control" id="middle_name">
                    </div>
                    <div class="form-floating">
                        <select name="role_id" class="form-select" id="floatingSelect" aria-label="Floating label select example">
                            @foreach($roles as $role)
                                <option value="{{ $role['id'] }}" @if($user->hasRole($role['name'])) selected @endif>{{ $role['name'] }}</option>
                            @endforeach
                        </select>
                        <label for="floatingSelect">Roles with selects</label>
                    </div>
                    <button type="submit" class="btn btn-outline-primary my-2">Отправить</button>
                </form>
            </div>
        </div>
    </div>
</x-crm-layout>
