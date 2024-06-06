<?php

namespace App\Http\Controllers\Dormitories;

use App\Http\Controllers\Controller;
use App\Models\RoomRate;
use App\Models\DormBranch;
use App\Models\Price;
use DB;
use Illuminate\Http\Request;

class RoomRateController extends Controller
{
    public function add_room_rate(Request $request)
    {
        //return $request->all();
        $this->validate(
            $request,
            [
                'name' => ['required', 'string', 'max:255'],
                'des' => ['required', 'max:300'],
                'rate' => ['required'],
                'qty' => ['required']
            ],
            [
                'des.required' => 'Description field is required.',
                'qty.required' => 'Quantity field is required.'
            ]
        );
        $check_b = DormBranch::find($request->dorm_branch_id);

        if ($check_b->status == 1 || $check_b->status == 2) {
            return response()->json(["status" => "error", "statcode" => 0,  "message" => "Unable to update!"]);
        }

        $price_id = null;

        DB::beginTransaction();
        try {

            $a = new RoomRate();
            $a->dorm_branch_id   = $request->dorm_branch_id;
            $a->name   = $request->name;
            $a->quantity  = $request->qty;

            $check_p = Price::where('price',  $request->rate)->first();

            if (is_null($check_p)) {
                $p = new Price();
                $p->price   = $request->rate;
                $p->save();

                $price_id = $p->id;
            } else {
                $price_id = $check_p->id;
            }

            $a->price_id  = $price_id;

            $a->des  = $request->des;

            $a->save();

            DB::commit();
        } //try
        catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
            return $e->getMessage();
        }

        return response()->json(["status" => "success", "statcode" => 1, "message" => 'Rates added!']);
        // return $request->all();

        //return  $name;
    }

    public function get_room_rates($id)
    {
        //return $id;

        $a = RoomRate::join('prices', 'prices.id', 'dorm_rooms_rate.price_id')
            ->where('dorm_branch_id', $id)
            ->where('active', 1)
            ->select([
                'dorm_rooms_rate.*',
                'prices.price as rate'
            ])
            ->get();

        return  $a;
    }

    public function soft_remove_rr(Request $request)
    {
        //return $request->all();

        DB::beginTransaction();
        try {

            $a = RoomRate::find($request->branch_id);

            $a->active = 0;
            $a->save();

            DB::commit();
        } //try
        catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
            return $e->getMessage();
        }

        return response()->json(["status" => "success", "statcode" => 1, "message" => 'Room rate mark as inactive!']);
        // return $request->all();

        //return  $name;
    }
}
