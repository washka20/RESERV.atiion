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
                <form method="post" action="{{ route('categories.store') }}">
                    @csrf
                    <div class="form-group my-2">
                        <label for="categoryName">Название</label>
                        <input type="text" name="name" class="form-control" id="categoryName">
                    </div>
                    <button type="submit" class="btn btn-outline-primary mt-2">Submit</button>
                </form>
            </div>
        </div>
    </div>
</x-crm-layout>
