<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrintTransactionController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/transactions/{transaction}/print', [PrintTransactionController::class, 'printStruk'])
    ->name('transactions.print');
