<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class BookingController extends Controller
{
    public function index(Request $request )
    {
        $bookings = DB::table('products')
            ->select('name', 'description', 'price')
            ->join('bookings', 'product_id', '=', 'products.id')
            ->orderBy('user_id')->where('user_id', '=', $request->user()->id)
            ->get();

        return view('dashboard', compact([
            'bookings'
        ]));
    }
}
