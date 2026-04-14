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
                <form method="post" action="{{ route('categories.update', $category->id) }}">
                    @csrf
                    @method('put')
                    <div class="form-group my-3">
                        <label for="categoryName">Название</label>
                        <input type="text" name="name" value="{{ $category->name }}" class="form-control" id="categoryName">
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Отправить</button>
                </form>
            </div>
        </div>
    </div>
</x-crm-layout>
