<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\CityMuni;
use App\Models\Brgy;
use App\Models\TmpUser;
use App\Models\DormType;
use App\Models\Featured;
use Illuminate\Http\Request;
use DB;

class AjaxController extends Controller
{

    public function loadInfoSuggest(Request $request)
    {
        $list = [];
        //$suggests = StudentInfo::whereRaw('concat(surname ," ",firstname," ",student_number) like ?', "%{$request->q}%")->limit(20)->get();
        //return $suggests;

        $posts = "";
        $search = $request->search;

        if (trim($request->search)) {

            // $prov = Province::leftjoin('refcitymun', 'refcitymun.provCode', 'refprovince.provCode')->where('provDesc', 'LIKE', "%{$search}%")
            //     ->orwhere('citymunDesc', 'LIKE', "%{$search}%")
            //     ->select([
            //         'refprovince.provDesc',
            //         'refprovince.regCode',
            //         'refcitymun.citymunDesc',
            //         'refcitymun.id as  citymunId',
            //     ])
            //     ->limit(5)
            //     ->get();
            // return $prov;
            $prov = Province::where('provDesc', 'LIKE', "%{$search}%")
                ->select(
                    'id',
                    'provDesc',
                    'provCode',
                    //  \DB::raw('provCode AS leader'),
                    \DB::raw('"1" as type')
                )->limit(5)->get();
            //return $prov;
            $citymuni = CityMuni::where('refcitymun.citymunDesc', 'LIKE', "%{$search}%")
                ->leftjoin('refprovince', 'refprovince.provCode', 'refcitymun.provCode')
                ->select([
                    'refcitymun.id',
                    'refcitymun.citymunDesc',
                    'refcitymun.citymunCode',
                    'refprovince.provDesc AS provmuni',
                    // \DB::raw('citymunCode AS leader'),
                    \DB::raw('"2" as type')
                ])
                ->limit(5)->get();

            $merg1 = $prov->merge($citymuni);
            // return $result;
            $brgy = Brgy::where('refbrgy.brgyDesc', 'LIKE', "%{$search}%")->leftjoin('refcitymun', 'refcitymun.citymunCode', 'refbrgy.citymunCode')
                ->select([
                    'refbrgy.id',
                    'refbrgy.brgyDesc',
                    'refcitymun.citymunDesc AS citybrgy',
                    'refbrgy.brgyCode',
                    // \DB::raw('citymunCode AS leader'),
                    \DB::raw('"3" as type')
                ])
                ->limit(5)->get();
            // return $brgy;
            $final = $merg1->merge($brgy);

            // return $final;

            $des = "";
            $typedes = "";
            if (!empty($final)) {
                $posts = $final->map(function ($post, $key) {

                    $loc_code = '';

                    if ($post['type'] == 1) {
                        $des = $post['provDesc'];
                        $typedes = "Province, Philippines";
                        $loc_code = $post['provCode'];
                    } elseif ($post['type'] == 2) {

                        $y = $post['provmuni'];
                        //$z = "$x $y";
                        $loc_code = $post['citymunCode'];
                        $des = $post['citymunDesc'];
                        $typedes = "$y, City/Municipal, Philippines";
                    } else {

                        $y = $post['citybrgy'];
                        $loc_code = $post['brgyCode'];
                        $des = $post['brgyDesc'];
                        $typedes = "$y, Barangay, Philippines";
                    }


                    return [
                        'id' => $post['id'],
                        'loc_code' => $loc_code,
                        'des' => $des,
                        'src' => $post['type'],
                        'typedes' => $typedes
                    ];
                });


                // return $posts;
            }
        }

        return $posts;
    }
    public function subscribe(Request $request)
    {

        //  return $request->all();
        // $this->validate(
        //     $request,
        //     [

        //         'parti' => ['required'],
        //         'amount' => ['required'],

        //     ],
        //     [

        //         'parti.required' => 'Particular field is required.'
        //     ]
        // );

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:tmp_user'],

        ]);


        //return $user_det;
        DB::beginTransaction();
        try {

            $p = new TmpUser();
            $p->email = $request->email;
            $p->name  = $request->name;

            $p->save();

            DB::commit();
        } //try
        catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }

        return response()->json(["status" => "success", "statcode" => 1, "message" => "Subscription submited! We will be sending you a email once verified."]);
    }
    public function dorm_type_get(Request $request)
    {
        //  return $request->all();
        $d = DormType::all();
        return $d;
    }

    public function get_featured(Request $request)
    {
        //  return $request->all();
        $d = Featured::leftjoin('dorm_img', 'dorm_img.dorm_branch_id', 'featureds.dorm_id')
            ->where('featureds.status', 1)
            ->where('dorm_img.prima', 1)
            ->select([
                'featureds.id',
                'dorm_img.filesystem_name',
            ])->get();
        return $d;
    }

    public function get_muni_in()
    {

        $citymuni = CityMuni::whereIn('refcitymun.id', [5, 12, 8, 16, 9])
            ->leftjoin('refprovince', 'refprovince.provCode', 'refcitymun.provCode')
            ->select([
                'refcitymun.id',
                'refcitymun.citymunDesc',
                'refcitymun.citymunCode AS loc_code',
                'refprovince.provDesc AS provmuni',
                // \DB::raw('citymunCode AS leader'),
                \DB::raw('"2" as src')
            ])
            ->limit(5)->get();

        return $citymuni;
    }
}
