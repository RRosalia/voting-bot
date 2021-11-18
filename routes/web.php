<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/votes', function () {
    $paginator = \App\Domain\Models\Vote::query()->latest()->paginate(100);

    $items = collect($paginator->items())->map(function(\App\Domain\Models\Vote $vote){
        $vote->image = \Illuminate\Support\Facades\Storage::path($vote->image);
        return $vote;
    });

    return [
        'data' => $items,
        'total_votes_casted' => $paginator->total(),
    ];
});
