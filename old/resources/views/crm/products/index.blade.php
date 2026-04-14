<x-crm-layout>
    <div class="container ">
        <a href="{{ route('products.create') }}" class="btn btn-success my-2">Добавить новую карточку</a>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
            @foreach($products as $product)
                <div class="col ">
                    <div class="card shadow-sm">
                        @if($product->productImage)
                            <img src="{{ asset('storage/' . $product->productImage) }}" class="card-img-top" width="100%" height="225"alt="Product Image">
                        @else
                            <svg class="bd-placeholder-img card-img-top" width="100%" height="225" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Thumbnail" preserveAspectRatio="xMidYMid slice" focusable="false">
                                <title>Placeholder</title>
                                <rect width="100%" height="100%" fill="#55595c"></rect>
                                <text x="50%" y="50%" fill="#eceeef" dy=".3em">{{$product->productName}}</text>
                            </svg>
                        @endif

                        <div class="card-body">
                            <h5 class="card-title">{{$product->productName}}</h5>
                            <p class="card-text">{{$product->productDescription}}</p>
                            <p class="card-text">{{$product->productPrice}}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="btn-group">
                                    <a href="#" class="btn btn-sm btn-outline-secondary">View</a>
                                    <a href="{{ route('products.edit', $product->productId) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <button type="button" class="btn btn-sm btn-outline-secondary delete-product" data-id="{{ $product->productId }}">Удалить</button>
                                </div>
                                <small class="text-body-secondary">{{ $product->productCreated_at }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="my-2"></div>
        </div>
    </div>
</x-crm-layout>
