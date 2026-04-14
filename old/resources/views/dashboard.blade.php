<x-app-layout>
    <div class="container ">
        <div class="row align-items">
            @foreach($bookings as $booking)
                <div class="card col-4 mx-3 my-3" style="width: 18rem;">
                    {{--                    <img src="..." class="card-img-top" alt="...">--}}
                    <svg class="bd-placeholder-img card-img-top" width="100%" height="180" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Image cap" preserveAspectRatio="xMidYMid slice" focusable="false"><title>Placeholder</title><rect width="100%" height="100%" fill="#868e96"></rect></svg>
                    <div class="card-body">
                        <h5 class="card-title">{{$booking->name}}</h5>
                        <p class="card-text">{{$booking->description}}</p>
                        <p class="card-text">{{$booking->price}} руб.</p>
                        <a href="#" class="btn btn-primary">Подробнее</a>
                    </div>
                </div>
                @endforeach
        </div>
    </div>
</x-app-layout>
