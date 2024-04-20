<?php

namespace App\Http\Controllers\OtherPayments;

use App\OtherPaymentCart;
use App\NonEnrolmentFees;
use App\PaymentNonEnroll;
use App\Payment;
use App\OrLog;
use App\Fund;
use App\Fee;
use App\OrRecord;
use App\Collection;
use Illuminate\Support\Facades\Auth;
use App\Repositories\NumberConverter;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OtherFundPaymentController extends Controller
{
    public function addToCart(Request $request, NumberConverter $numberConverter)
    {
        $this->validate(
            $request,
            [
                'student_name' => 'required|max:255',
                'collection_id' => 'required',

            ],
            [
                'collection_id.required' => 'Nature of collection is required!',
            ]
        );

        $currentStudent_id = $request->student_name;
        $collection_id = $request->collection_id;
        $numberofitems =  $request->num_of_items;
        $amount = $request->amount;

        //$getfundamount = Collection::where('id', $requestedfund_id)->first();

        $totalamount = $amount;

        $myORLog = OrLog::where('user', Auth::user()->id)->first();

        $duplicate = otherPaymentCart::where('student_id', $currentStudent_id)->where('collection_id', $collection_id)->first();

        DB::beginTransaction();
        try {

            if (is_null($duplicate)) {
                $new =  new OtherPaymentCart;
                $new->student_id  = $currentStudent_id;

                //  $new->payor_name  = ($currentStudent_id == 0 ? $request->payor_name : null); 

                $new->collection_id = $collection_id;
                $new->user_id = Auth::user()->id;
                $new->amount = $totalamount;

                $new->save();

                $getItemToCart = otherPaymentCart::where('student_id', $currentStudent_id)->where('user_id', Auth::user()->id)->join('otherpayment.collections', 'collections.id', '=', 'cart.collection_id')
                    ->select([
                        'cart.*',
                        'collections.c_code',
                        'collections.description as desc'
                    ])->get();

                //$getItemToCart = otherPaymentCart::where('student_id', 22586)->with('fund', 'fees')->get();

                $amount = [];

                foreach ($getItemToCart as $x) {

                    $sumAmount = $x->amount;
                    array_push($amount, $sumAmount);
                    //return $sumAmount; 
                }

                $currency = 'Pesos';
                $sumtotal =  array_sum($amount);
                //$converted = $numberConverter->toText(6066.04);
                $converted = $numberConverter->toText($sumtotal, $currency);

                return response()->json([
                    'cart'      => $getItemToCart,
                    'sumamount' => $sumtotal,
                    'converted' => $converted,
                    'currentStudent_id' => $currentStudent_id,
                    'orNumber' => $myORLog->last_or
                ], 200);
            } else {
                return response()->json([
                    'isdup'      => 1
                ], 200);
            }


            DB::commit();
        } //try
        catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }


        //return $new;
    }

    public function checkOutItems(Request $request, NumberConverter $numberConverter)
    {
        $currentStudent_id = $request->stud_id;
        $payorname = $request->payor_name;
        $or = $request->or_num;

        // $notdeleted = [];
        $amount = [];

        $getItemToCart = otherPaymentCart::where('student_id', $currentStudent_id)->where('user_id', Auth::user()->id)->get();

        DB::beginTransaction();
        try {

            foreach ($getItemToCart as $x) {

                $sumAmount = $x->amount;
                array_push($amount, $sumAmount);
                //return $sumAmount; 
            }

            $sumtotal =  array_sum($amount);

            # Update User OR - [Increment]
            $orLog = OrLog::where('user', Auth::user()->id)->first();
            if (!is_null($orLog)) {
                $orLog->last_or = $or + 1;
                $orLog->save();
            } else {
                $orLog = new OrLog();
                $orLog->last_or = $or + 1;
                $orLog->user = Auth::user()->id;
                $orLog->save();
            }

            // $payment = (object) $payment;

            $or_log = new NonEnrolmentFees();
            $or_log->student_id = $currentStudent_id;
            $or_log->payor_name = ($currentStudent_id == 0 ? $payorname : null);
            $or_log->or_number  = $or;
            //$payments->fund_id    = $payment->fund_id;
            $or_log->amount = $sumtotal;
            $or_log->user_id    = Auth::user()->id;
            $or_log->branch     = Auth::user()->branch;
            $or_log->status   = '1';
            $or_log->save();

            foreach ($getItemToCart as $payment) {
                $payment = (object) $payment;
                $payments = new PaymentNonEnroll();
                $payments->or_record_id = $or_log->id;
                $payments->collection_id     = $payment->collection_id;
                $payments->amount  = $payment->amount;

                $payments->save();
            }

            $currency = 'Pesos';
            $converted = $numberConverter->toText($sumtotal, $currency);

            DB::commit();

            return response()->json([
                //  'items' => $getItemToCart,
                'payor' => $payorname,
                'sumamount' => $sumtotal,
                'converted' => $converted,
                'or_id' =>  $or_log->id,
                'or_number' => $or
            ], 200);
        } //try
        catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }
    }

    public function removeToCart(Request $request, NumberConverter $numberConverter)
    {

        DB::beginTransaction();
        try {


            $deleteitemtocart = otherPaymentCart::where('id', $request->cart_id)->first();
            // $deletenerd->delete();
            $deleteitemtocart->delete();

            $getItemToCart = otherPaymentCart::where('student_id', $request->student_id)->where('user_id', Auth::user()->id)->join('otherpayment.collections', 'collections.id', '=', 'cart.collection_id')
                ->select([
                    'cart.*',
                    'collections.c_code',
                    'collections.description as desc'
                ])->get();


            $amount = [];

            foreach ($getItemToCart as $x) {

                $sumAmount = $x->amount;
                array_push($amount, $sumAmount);
                //return $sumAmount; 
            }

            $currency = 'Pesos';
            $sumtotal =  array_sum($amount);
            $converted = $numberConverter->toText($sumtotal, $currency);

            DB::commit();

            return response()->json([
                'cart'      => $getItemToCart,
                'sumamount' => $sumtotal,
                'converted' => $converted,
                'message' => 'Item Canceled!'
            ], 200);
        } //try
        catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }
    }

    public function checkInitialItems(Request $request, NumberConverter $numberConverter)
    {

        $currentStudent_id = $request->student_name;

        $getItemToCart = otherPaymentCart::where('student_id', $currentStudent_id)->where('user_id', Auth::user()->id)
            ->join('otherpayment.collections', 'collections.id', '=', 'cart.collection_id')
            ->select([
                'cart.*',
                'collections.c_code',
                'collections.description as desc'
            ])->get();

        //$payments = new NonEnrolmentFees();
        $myORLog = OrLog::where('user', Auth::user()->id)->first();

        $amount = [];

        foreach ($getItemToCart as $x) {
            $sumAmount = $x->amount;
            array_push($amount, $sumAmount);
            //return $sumAmount; 
        }

        $currency = 'Pesos';

        $sumtotal =  array_sum($amount);
        $converted = $numberConverter->toText($sumtotal, $currency);

        return response()->json([
            'cart'      => $getItemToCart,
            'sumamount' => $sumtotal,
            'converted' => $converted,
            'currentStudent_id' => $currentStudent_id,
            'orNumber' => $myORLog->last_or
        ], 200);
    }
    public function updateAmount(Request $request, NumberConverter $numberConverter)
    {

        $getFee = Fee::where('fee_id', $request->fund_id)->first();
        // return  $getFee;
        $getFee->amount = $request->amount;
        $getFee->save();

        //$amount = [];


        return response()->json([
            'status'      => 'Amount change to',
            // 'sumamount' => $sumtotal,
            // 'converted' => $converted,
            // 'currentStudent_id' => $currentStudent_id
        ], 200);
    }
    public function printReceipt(Request $request)
    {
        // $payments = otherPaymentCart::where('student_id', $request->student_id)->where('user_id', Auth::user()->id)->with('fund', 'fees')->get(); 



        $fund_ids = [];

        $or = $request->or;

        $payments = PaymentNonEnroll::where('or_record_id', $request->orid)->join('otherpayment.collections', 'collections.id', '=', 'payments.collection_id')
            ->select([
                'payments.*',
                'collections.c_code',
                'collections.description as desc'
            ])->get();
        //return $payments;

        $fund_type = $request->fund_type;
        $payor = $request->payor;
        $amount = $request->amount;
        $converted = $request->converted;

        // foreach ($cartitems as $td) {
        //     $td->delete();
        // }

        return view('other-payments.others.receipt', compact(
            'or',
            'payor',
            'amount',
            'payments',
            'fund_type',
            'converted'

        ));
        //return view('other-payments.others.receipt');
    }
    public function get_funds()
    {
        //delete cart items
        otherPaymentCart::where('user_id', Auth::user()->id)->delete();
        //
        $f = DB::connection('dbequipment')->table("funds")->whereIn('id', [1, 2, 6, 7])->get(['id', 'fund_category', 'fund_desc']);

        return $f;
    }
    public function load_collections(Request $request)
    {

        $collection_id = $request->fund_cluster['id'];
        $fund_category = $request->fund_cluster['fund_category'];
        //return $request->fund_cluster['fund_category'];
        $collections = [];



        $col = Collection::where('fund_id', $collection_id)->orderBy('description', 'asc')->get();

        foreach ($col as $c) {
            $a = [
                'id' => $c->id,
                'c_code' => $c->c_code,
                'description' => $c->description . '-' . $c->c_code
            ];

            array_push($collections, $a);
        }

        return $collections;
    }
}
