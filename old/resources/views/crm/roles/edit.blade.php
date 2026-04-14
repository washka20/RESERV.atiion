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
                <form method="post" action="{{ route('roles.update', $role->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="form-group my-3">
                        <label for="exampleInputEmail1">Название</label>
                        <input type="text" name="name" value="{{ $role->name }}" class="form-control" id="exampleInputEmail1">
                    </div>
                    @foreach($permissions as $permission)
                        <div class="form-group form-check my-2">
                            <input type="checkbox" value="{{ $permission->id }}" @if($role->hasPermissionTo($permission->name)) checked @endif name="permissions[]" class="form-check-input" id="exampleCheck{{ $permission->id }}">
                            <label class="form-check-label" for="exampleCheck{{ $permission->id }}">{{ $permission->name }}</label>
                        </div>
                    @endforeach
                    <button type="submit" class="btn btn-outline-primary">Отправить</button>
                </form>
            </div>
        </div>
    </div>
</x-crm-layout>
