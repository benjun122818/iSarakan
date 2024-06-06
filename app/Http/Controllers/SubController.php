<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\DormBranch;
use App\Models\DormImg;
use App\Models\Amenities;
use App\Models\RoomRate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubController extends Controller
{
    //

    public function getSearchItem(Request $request)
    {
        //return $request->all();
        $location_id = $request['datas']['location_id'];
        $src = $request['datas']['src'];
        $dorm_type = $request['datas']['dorm_type'];

        //return $location_id;

        /*
            srs 1 = province
            srs 2 = CityMuni
            srs 3 = Brgy
        */

        $q = DormBranch::leftJoin('dorm_img', 'dorm_img.dorm_branch_id', 'dorm_branch.id')
            ->leftJoin('dorm_types', 'dorm_types.id', 'dorm_branch.dorm_type')
            ->where(function ($qsrc) use ($src, $location_id) {
                if ($src == 1) {
                    $qsrc->where('dorm_branch.prov', $location_id);
                } elseif ($src == 2) {
                    $qsrc->where('dorm_branch.citymuni', $location_id);
                } else {
                    $qsrc->where('dorm_branch.brgy', $location_id);
                }
            })
            ->where(function ($qdtype) use ($dorm_type) {
                if ($dorm_type != 0) {
                    $qdtype->where('dorm_branch.dorm_type', $dorm_type);
                }
            })
            ->where('dorm_branch.status', 1)
            ->where('dorm_img.prima', 1)
            ->select([
                'dorm_branch.*',
                'dorm_types.des AS dorm_type',
                'dorm_img.filesystem_name'
            ])
            ->get();

        $data = [];

        foreach ($q as $i) {
            $obj = [
                'id' => $i->id,
                'name' => $i->name,
                'description' => $i->description,
                'dorm_type' => $i->dorm_type,
                'address' => $i->address,
                'contact' => $i->contact,
                'filesystem_name' => $i->filesystem_name,
                'availability' => $i->availability
            ];

            $img = DormImg::where('dorm_branch_id', $i->id)->get();
            $amenities = Amenities::where('dorm_branch_id', $i->id)->get();
            $roomrates = RoomRate::join('prices', 'prices.id', 'dorm_rooms_rate.price_id')
                ->where('dorm_branch_id', $i->id)
                ->where('active', 1)
                ->select([
                    'dorm_rooms_rate.*',
                    'prices.price as rate'
                ])
                ->get();


            $obj['photos'] = $img;
            $obj['amenities'] = $amenities;
            $obj['roomrates'] = $roomrates;

            $data[] = $obj;
        }

        if (count($q) > 0) {
            return response()->json(["status" => "success", "statcode" => 1, "result" => $data]);
        } else {
            return response()->json(["status" => "error", "statcode" => 0]);
        }


        return $q;
    }
}
