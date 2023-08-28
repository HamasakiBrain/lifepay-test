<?php

use App\Utils\Acquirer;
use Illuminate\Http\Request;
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

Route::get('/', function (Request $request) {
//    return ['Laravel' => app()->version()];
    $customAcquirer = Acquirer::instance('life-pay');
    $paymentLink = $customAcquirer->cancelPayment(1);
});

require __DIR__.'/auth.php';


Route::get('/payment/status/update', fn (Request $request) => $request->number)->name('payment.status.update');
