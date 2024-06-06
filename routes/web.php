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

Route::get('/', function () {

    //return App\Models\User::all();
    return view('welcome');
});
Route::post('load-suggest', 'App\Http\Controllers\AjaxController@loadInfoSuggest');
Route::post('user/subscribe', 'App\Http\Controllers\AjaxController@subscribe');
Route::get('get-feather-icons', 'App\Http\Controllers\HomeController@get_feather_icons');
Route::get('pub/dorm-type-get', 'App\Http\Controllers\AjaxController@dorm_type_get');
Route::get('pub/muni-get', 'App\Http\Controllers\AjaxController@get_muni_in');
Route::get('pub/dorm-featured', 'App\Http\Controllers\AjaxController@get_featured');

//Route::get('/sendhtmlemail', 'App\Http\Controllers\MailController@html_email');
Route::post('reservation/initial', 'App\Http\Controllers\MailController@ini_reserve');
Route::post('reservation/checkemail', 'App\Http\Controllers\MailController@check_email');
Route::post('reservation/verify', 'App\Http\Controllers\MailController@verify_reserve');
//Route::get('/sendhtmlemail', 'App\Http\Controllers\MailController@testvue_email');

Route::post('accommodationSearchQuery', 'App\Http\Controllers\SubController@getSearchItem');
//administrator
Route::get('get-user-type', 'App\Http\Controllers\HomeController@user_type_index');
Route::get('admin/user-table', 'App\Http\Controllers\Administrator\UserController@user_table');
Route::resource('admin/user', 'App\Http\Controllers\Administrator\UserController');
Route::get('admin/dorm-branches', 'App\Http\Controllers\Administrator\DormBranchController@dorm_branch_tbl');
Route::post('admin/form/dorm-data-get', 'App\Http\Controllers\Administrator\DormBranchController@edit');

Route::post('admin/form/dorm-for-approval', 'App\Http\Controllers\Administrator\DormBranchController@forapprove_dorm_branch');
Route::post('admin/photo/set-primary', 'App\Http\Controllers\Administrator\DormBranchController@photo_set_primary');
Route::post('admin/featured/set-featured', 'App\Http\Controllers\Administrator\DormBranchController@set_featured');

Route::get('admin/tmpuser-table', 'App\Http\Controllers\Administrator\SubsController@tmpuser_table');
Route::post('admin/approve-subscription', 'App\Http\Controllers\Administrator\SubsController@approve_subscription');
//administrator

//dorm user
Route::get('form/common-refregion', 'App\Http\Controllers\Dormitories\DormBranchController@get_common_refregion');
Route::get('form/common-refprovince', 'App\Http\Controllers\Dormitories\DormBranchController@get_common_refprovince');
Route::get('form/common-refcitymun', 'App\Http\Controllers\Dormitories\DormBranchController@get_common_refcitymun');
Route::get('form/common-refbrgy', 'App\Http\Controllers\Dormitories\DormBranchController@get_common_refbrgy');
Route::get('form/dorm-type-get', 'App\Http\Controllers\Dormitories\DormBranchController@dorm_type_get');
Route::post('form/dorm-data-create', 'App\Http\Controllers\Dormitories\DormBranchController@store_dorm_branch');
Route::post('form/dorm-data-update', 'App\Http\Controllers\Dormitories\DormBranchController@update_dorm_branch');
Route::post('form/dorm-data-get', 'App\Http\Controllers\Dormitories\DormBranchController@edit');
Route::post('form/upload/support-doc', 'App\Http\Controllers\Dormitories\DormBranchController@upload_supporting_doc');
Route::post('form/unlink/support-doc', 'App\Http\Controllers\Dormitories\DormBranchController@unlink_doc');
Route::post('form/upload/dorm-img', 'App\Http\Controllers\Dormitories\DormBranchController@upload_dorm_img');
Route::post('form/unlink/dorm-img', 'App\Http\Controllers\Dormitories\DormBranchController@unlink_dorm_img');
Route::get('dorm/branches/user-table', 'App\Http\Controllers\Dormitories\DormBranchController@dorm_branch_tbl');
Route::post('form/dorm-amenities-create', 'App\Http\Controllers\Dormitories\DormBranchController@add_amenities');
Route::get('form/dorm-amenities-get/{id}', 'App\Http\Controllers\Dormitories\DormBranchController@get_amenities');
Route::post('form/dorm-amenities-remove', 'App\Http\Controllers\Dormitories\DormBranchController@remove_amenities');

Route::post('form/dorm-roomrate-create', 'App\Http\Controllers\Dormitories\RoomRateController@add_room_rate');
Route::get('form/dorm-roomrate-get/{id}', 'App\Http\Controllers\Dormitories\RoomRateController@get_room_rates');
Route::post('form/dorm-roomrate-remove', 'App\Http\Controllers\Dormitories\RoomRateController@soft_remove_rr');

Route::post('/form/dorm-for-approval', 'App\Http\Controllers\Dormitories\DormBranchController@forapprove_dorm_branch');
Route::post('dorm/photo/set-primary', 'App\Http\Controllers\Dormitories\DormBranchController@photo_set_primary');
Route::post('dorm/update-available', 'App\Http\Controllers\Dormitories\DormBranchController@update_available');

Route::get('dorm/reservations/data-table', 'App\Http\Controllers\Dormitories\ReservationController@dorm_reser_tbl');
Route::post('dorm/reservations/confirm', 'App\Http\Controllers\Dormitories\ReservationController@confirm_dorm_reservation');
//dorm user

//Route::post('payment/enrollment/charge/online', 'App\Http\Controllers\Enrollment\PaymentController@onlinePaymentCharge');



Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

require __DIR__ . '/auth.php';

Route::get('/{any}', 'App\Http\Controllers\HomeController@index')
    ->middleware('auth')
    ->where('any', '.*');

// Route::view('/{any}', 'dashboard')
// ->middleware('auth')
// ->where('any', '.*');

//Route::get('{vue_route?}', 'HomeController@index')->where('vue_route', '[\/\w\.-]*');