<?php

namespace App\Http\Controllers\Dormitories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DormBranch;
use App\Models\Region;
use App\Models\Province;
use App\Models\CityMuni;
use App\Models\Brgy;
use App\Models\SupportingDoc;
use App\Models\DormImg;
use App\Models\DormType;
use App\Models\Amenities;
use DB;
use Illuminate\Support\Facades\Auth;

class DormBranchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function dorm_branch_tbl(Request $request)
    {
        $user_id = Auth::user()->id;
        $per_page_record = $request->entries;  // Number of entries to show in a page.   
        //return $user_id;
        // Look for a GET variable page if not found default is 1.        
        if (isset($request->page)) {
            $page  = $request->page;
        } else {
            $page = 1;
        }
        // return $page;
        $start_from = ($page - 1) * $per_page_record;

        $all_records = DormBranch::where('user_id', $user_id)->count();

        if (empty($request->search)) {
            $s = DormBranch::join('dorm_types', 'dorm_types.id', 'dorm_branch.dorm_type')->where('user_id', $user_id)
                ->select([
                    'dorm_branch.*',
                    'dorm_types.des as dorm_type',
                ])
                ->offset($start_from)
                ->limit($per_page_record)
                ->orderBy('created_at', 'desc')
                ->get();

            $pagLink = [];

            $showing = $s->count();
            $total_records = $all_records;

            $total_pages = ceil($total_records / $per_page_record);

            $ends_count = 1;  //how many items at the ends (before and after [...])
            $middle_count = 1;  //how many items before and after current page
            $dots = false;

            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i == $page) {

                    $p = [
                        'status' => ($i == $page ? 1 : 0),
                        'page' => $i,
                        'text' => $i,

                    ];

                    array_push($pagLink, $p);
                    $dots = true;
                } else {
                    if ($i <= $ends_count || ($page && $i >= $page - $middle_count && $i <= $page + $middle_count) || $i > $total_pages - $ends_count) {
                        $p = [
                            'status' => ($i == $page ? 1 : 0),
                            'page' => $i,
                            'text' => $i,

                        ];

                        array_push($pagLink, $p);
                        $dots = true;
                    } elseif ($dots) {
                        $p = [
                            'status' => ($i == $page ? 1 : 0),
                            'page' => $i,
                            'text' => '...',

                        ];

                        array_push($pagLink, $p);
                        $dots = false;
                    }
                }
            }
            return response()->json([
                'records'   => $s,
                'pagLink' => $pagLink,
                'current_page' => $page,
                'all_records' => $all_records,
                'total_records' =>  $showing,
                'total_pages' => $total_pages
            ], 200);
        } else {
            $search = $request->search;
            $t = DormBranch::where('user_id', $user_id)->get();
            //return $t;
            $s = [];
            $total_records =  0;

            $final_ids = [];

            $tmp = DormBranch::join('dorm_types', 'dorm_types.id', 'dorm_branch.dorm_type')->orwhere('dorm_branch.description', 'LIKE', "%{$search}%")
                ->orWhere('dorm_branch.name', 'LIKE', "%{$search}%")
                ->orWhere('dorm_types.des', 'LIKE', "%{$search}%")
                ->offset($start_from)
                ->limit($per_page_record)
                ->select([
                    'dorm_branch.*',
                    'dorm_types.des as dorm_type',
                ])->orderBy('created_at', 'desc')
                ->get();
            //sdfdsf
            foreach ($tmp as $g) {
                if ($user_id == $g->user_id) {
                    $final_ids[] = $g->id;
                }
            }


            //    if (count($t) > 0) {
            $s = DormBranch::whereIn('dorm_branch.id', $final_ids)->join('dorm_types', 'dorm_types.id', 'dorm_branch.dorm_type')
                ->select([
                    'dorm_branch.*',
                    'dorm_types.des as dorm_type',
                ])->orderBy('created_at', 'desc')
                ->get();

            $total_records =  $s->count();

            $pagLink = [];
            $showing = $total_records;

            $total_pages = ceil($total_records / $per_page_record);

            $ends_count = 1;  //how many items at the ends (before and after [...])
            $middle_count = 3;  //how many items before and after current page
            $dots = false;

            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i == $page) {

                    $p = [
                        'status' => ($i == $page ? 1 : 0),
                        'page' => $i,
                        'text' => $i,

                    ];

                    array_push($pagLink, $p);
                    $dots = true;
                } else {
                    if ($i <= $ends_count || ($page && $i >= $page - $middle_count && $i <= $page + $middle_count) || $i > $total_pages - $ends_count) {
                        $p = [
                            'status' => ($i == $page ? 1 : 0),
                            'page' => $i,
                            'text' => $i,

                        ];

                        array_push($pagLink, $p);
                        $dots = true;
                    } elseif ($dots) {
                        $p = [
                            'status' => ($i == $page ? 1 : 0),
                            'page' => $i,
                            'text' => '...',

                        ];

                        array_push($pagLink, $p);
                        $dots = false;
                    }
                }
            }
            return response()->json([
                'records'   => $s,
                'pagLink' => $pagLink,
                'current_page' => $page,
                'all_records' => $all_records,
                'total_records' =>  $showing,
                'total_pages' => $total_pages
            ], 200);
            // }
        }

        // return  $total_pages;


    }

    public function edit(Request $request)
    {

        //return  $request->all();
        $d = DormBranch::find($request->id);

        $sd = SupportingDoc::where('dorm_branch_id',  $d->id)->get();
        $di = DormImg::where('dorm_branch_id',  $d->id)->get();

        $d['supporting_doc'] = $sd;
        $d['dorm_images']    = $di;

        $refprovince = Province::where('regCode', $d->region)->get();
        $refcitymun = CityMuni::where('provCode', $d->prov)->get();
        $refbrgy = Brgy::where('citymunCode', $d->citymuni)->get();


        return response()->json(["dorm" => $d, "refprovince" => $refprovince,  "refcitymun" => $refcitymun, "refbrgy" => $refbrgy]);
    }

    public function update_available(Request $request)
    {

        //return  $request->all();
        $id = $request->id;
        $state = $request->state;

        $d = DormBranch::find($id);
        // return $d;

        if ($state == 1) {
            $d->availability   = 0;
            $d->save();
        } else {
            $d->availability   = 1;
            $d->save();
        }



        return 1;
    }

    public function upload_supporting_doc(Request $request)
    {
        $user_id = Auth::user()->id;
        $file = $request->file;
        // throw new \Exception("There was an error with the storage server.");10485760

        DB::beginTransaction();
        try {

            $name = $file->getClientOriginalName();
            //$extension = Input::file('photo')->getClientOriginalExtension();
            $size = $file->getSize();
            //$mime = Input::file('photo')->getMimeType();
            //$path = Input::file('photo')->getRealPath();
            //$hashname = $file->hashName();

            if ($size > 5242880) {
                throw new \Exception("There was an error with the storage server." . $name . "(File to large).");
            }

            if ($file) {
                $filesystem_name = $file->hashName();


                if (!\Storage::disk("public")->exists('/supportingdocs/')) {
                    if (!\Storage::disk("public")->makeDirectory('/supportingdocs/')) {
                        throw new \Exception("There was an error with the storage server.");
                    }
                }

                if (\Storage::disk("public")->putFile('supportingdocs/', $file)) {

                    $sd = new SupportingDoc();
                    $sd->user_id   = $user_id;
                    $sd->dorm_branch_id   = $request->branch_id;
                    $sd->filesystem_name  = $filesystem_name;
                    $sd->file_name  = $name;


                    $sd->save();
                } else {
                    throw new \Exception("There was an error with the storage server. Unable to upload file!");
                }
            }

            DB::commit();
        } //try
        catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
            return $e->getMessage();
        }

        return response()->json(["status" => "success", "statcode" => 1, "message" => $name . " uploaded!"]);
        // return $request->all();

        //return  $name;
    }
    public function add_amenities(Request $request)
    {
        //return $request->all();
        $check_b = DormBranch::find($request->dorm_branch_id);

        if ($check_b->status == 1 || $check_b->status == 2) {
            return response()->json(["status" => "error", "statcode" => 0,  "message" => "Unable to update!"]);
        }

        DB::beginTransaction();
        try {

            $a = new Amenities();
            $a->dorm_branch_id   = $request->dorm_branch_id;
            $a->description   = $request->name;
            $a->icon  = $request->icon;

            $a->save();

            DB::commit();
        } //try
        catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
            return $e->getMessage();
        }

        return response()->json(["status" => "success", "statcode" => 1, "message" => 'Amenities added!']);
        // return $request->all();

        //return  $name;
    }
    public function remove_amenities(Request $request)
    {
        //return $request->all();

        DB::beginTransaction();
        try {

            $a = Amenities::find($request->branch_id);


            $a->delete();

            DB::commit();
        } //try
        catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
            return $e->getMessage();
        }

        return response()->json(["status" => "success", "statcode" => 1, "message" => 'Amenities deleted!']);
        // return $request->all();

        //return  $name;
    }
    public function get_amenities($id)
    {
        //return $id;

        $a = Amenities::where('dorm_branch_id', $id)->get();

        return  $a;
    }

    public function unlink_doc(Request $request)
    {
        $item = SupportingDoc::find($request->id);

        $file_name = $item->file_name;

        DB::beginTransaction();
        try {

            if (\Storage::disk('public')->delete("supportingdocs/$item->filesystem_name")) {
                $item->delete();
            } else {
                throw new \Exception("There was an error in the storage server. Unable to remove $file_name.");
            }

            DB::commit();
        } //try
        catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
            return $e->getMessage();
        }

        return response()->json(["status" => "success", "statcode" => 1, "message" => "File has been removed!"]);
    }

    public function upload_dorm_img(Request $request)
    {
        $user_id = Auth::user()->id;
        $file = $request->file;
        // throw new \Exception("There was an error with the storage server.");10485760

        DB::beginTransaction();
        try {

            $name = $file->getClientOriginalName();
            //$extension = Input::file('photo')->getClientOriginalExtension();
            $size = $file->getSize();
            //$mime = Input::file('photo')->getMimeType();
            //$path = Input::file('photo')->getRealPath();
            //$hashname = $file->hashName();

            if ($size > 5242880) {
                throw new \Exception("There was an error with the storage server." . $name . "(File to large).");
            }

            if ($file) {
                $filesystem_name = $file->hashName();


                if (!\Storage::disk("public")->exists('/dormimg/')) {
                    if (!\Storage::disk("public")->makeDirectory('/dormimg/')) {
                        throw new \Exception("There was an error with the storage server.");
                    }
                }

                if (\Storage::disk("public")->putFile('dormimg/', $file)) {

                    $sd = new DormImg();
                    $sd->dorm_branch_id   = $request->branch_id;
                    $sd->filesystem_name  = $filesystem_name;
                    $sd->file_name  = $name;


                    $sd->save();
                } else {
                    throw new \Exception("There was an error with the storage server. Unable to upload file!");
                }
            }

            DB::commit();
        } //try
        catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
            return $e->getMessage();
        }

        return response()->json(["status" => "success", "statcode" => 1, "message" => $name . " uploaded!"]);
        // return $request->all();

        //return  $name;
    }
    public function unlink_dorm_img(Request $request)
    {
        $item = DormImg::find($request->id);

        $file_name = $item->file_name;

        DB::beginTransaction();
        try {

            if (\Storage::disk('public')->delete("dormimg/$item->filesystem_name")) {
                $item->delete();
            } else {
                throw new \Exception("There was an error in the storage server. Unable to remove $file_name.");
            }

            DB::commit();
        } //try
        catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
            return $e->getMessage();
        }

        return response()->json(["status" => "success", "statcode" => 1, "message" => "File has been removed!"]);
    }
    public function store_dorm_branch(Request $request)
    {

        // return $request->all();

        $this->validate(
            $request,
            [
                'name' => ['required'],
                'description' => ['required'],
                'contact' => ['required'],
                'region' => ['required'],
                'prov' => ['required'],
                'citymuni' => ['required'],
                'brgy' => ['required'],
                'address' => ['required'],
                'dtype' => ['required'],
            ],
            [

                'prov.required' => 'The province field is required.',
                'citymuni.required' => 'The City/Municipality field is required.',
                'brgy.required' => 'The barangay field is required.',
                'dtype.required' => 'The dorm type field is required.'
            ]
        );

        $user_id = Auth::user()->id;
        $type = 1;

        $check_b = DormBranch::where('user_id', $user_id)->get();

        if (count($check_b) > 1) {
            $type = 2;
        }

        if (count($check_b) >= 3) {
            return response()->json(["status" => "error", "statcode" => 0,  "message" => "You have reach the maximum number of dorm to register!"]);
        }

        $d = new DormBranch();
        $d->user_id = $user_id;
        $d->name = $request->name;
        $d->type  =  $type;
        $d->dorm_type  =  $request->dtype;
        $d->description  = $request->description;
        $d->region = $request->region;
        $d->prov = $request->prov;
        $d->citymuni = $request->citymuni;
        $d->brgy = $request->brgy;
        $d->contact = $request->contact;
        $d->address = $request->address;
        $d->save();

        return response()->json(["status" => "success", "statcode" => 1, "message" => "Dorm Created!"]);
    }
    public function update_dorm_branch(Request $request)
    {

        //return $request->all();

        $this->validate(
            $request,
            [
                'name' => ['required'],
                'description' => ['required'],
                'contact' => ['required'],
                'region' => ['required'],
                'prov' => ['required'],
                'citymuni' => ['required'],
                'brgy' => ['required'],
                'address' => ['required'],
            ],
            [

                'prov.required' => 'The province field is required.',
                'citymuni.required' => 'The City/Municipality field is required.',
                'brgy.required' => 'The barangay field is required.'
            ]
        );

        $user_id = Auth::user()->id;
        $type = 1;
        $id = $request->id;

        $check_b = DormBranch::find($id);


        if ($check_b->status == 1 || $check_b->status == 2) {
            return response()->json(["status" => "error", "statcode" => 0,  "message" => "Unable to update!"]);
        }


        //$check_b->user_id = $user_id;
        $check_b->name = $request->name;
        $check_b->dorm_type  =  $request->dtype;
        $check_b->description  = $request->description;
        $check_b->region = $request->region;
        $check_b->prov = $request->prov;
        $check_b->citymuni = $request->citymuni;
        $check_b->brgy = $request->brgy;
        $check_b->contact = $request->contact;
        $check_b->address = $request->address;
        $check_b->save();

        return response()->json(["status" => "success", "statcode" => 1, "message" => "Dorm updated!"]);
    }
    public function photo_set_primary(Request $request)
    {

        //return $request->all();

        $id = $request->id;
        $branch_id =  $request->branch_id;

        $photo_prima = null;

        $check_b = DormBranch::find($id);


        // if ($check_b->status == 1 || $check_b->status == 2) {
        //     return response()->json(["status" => "error", "statcode" => 0,  "message" => "Unable to update!"]);
        // }

        $check_prima = DormImg::where('dorm_branch_id', $branch_id)->where('prima', 1)->first();


        DB::beginTransaction();
        try {

            if (empty($check_prima)) {
                $set_p = DormImg::find($id);
                $set_p->prima = 1;
                $set_p->save();
            } else {
                $check_prima->prima = 0;
                $check_prima->save();

                $set_p = DormImg::find($id);
                $set_p->prima = 1;
                $set_p->save();
            }

            DB::commit();
        } //try
        catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
            return $e->getMessage();
        }

        $message = "Primary photo set.";

        return response()->json(["status" => "success", "statcode" => 1, "message" =>  $message]);
    }
    public function forapprove_dorm_branch(Request $request)
    {

        //return $request->all();

        $id = $request->id;

        $check_b = DormBranch::find($id);

        $check_b->status = 2;

        $check_b->save();

        return response()->json(["status" => "success", "statcode" => 1, "message" => "Submitted for Approval!"]);
    }

    public function get_common_refregion()
    {
        $refregion = Region::all();
        return $refregion;
        //   return response()->json(["status" => "success", "statcode" => 1,  "dats" => $jev, "message" => "Jev Created!"]);
    }

    public function get_common_refprovince(Request $request)
    {

        // return $request->all();
        $refprovince = Province::where('regCode', $request->regCode)->get();
        return $refprovince;
    }

    public function get_common_refcitymun(Request $request)
    {
        //return $request->all();
        $refcitymun = CityMuni::where('provCode', $request->provCode)->get();
        return $refcitymun;
    }

    public function get_common_refbrgy(Request $request)
    {
        //  return $request->all();
        $refbrgy = Brgy::where('citymunCode', $request->citymunCode)->get();
        return $refbrgy;
    }

    public function dorm_type_get(Request $request)
    {
        //  return $request->all();
        $d = DormType::all();
        return $d;
    }
}
