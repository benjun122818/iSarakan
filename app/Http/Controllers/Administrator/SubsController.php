<?php

namespace App\Http\Controllers\Administrator;

use App\Http\Controllers\Controller;
use App\Models\TmpUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Mail;
use DB;

class SubsController extends Controller
{

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function tmpuser_table(Request $request)
    {
        //return User::all();
        $per_page_record = $request->entries;  // Number of entries to show in a page.   
        //return $request->page;
        // Look for a GET variable page if not found default is 1.        
        if (isset($request->page)) {
            $page  = $request->page;
        } else {
            $page = 1;
        }
        // return $page;
        $start_from = ($page - 1) * $per_page_record;

        $all_records = TmpUser::count();

        if (empty($request->search)) {
            $s = TmpUser::offset($start_from)
                ->limit($per_page_record)
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
        } else {
            $search = $request->search;
            $s = TmpUser::where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")
                ->offset($start_from)
                ->limit($per_page_record)
                ->get();

            $pagLink = [];
            $total_records =  $s->count();

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
        }

        // return  $total_pages;

        return response()->json([
            'records'   => $s,
            'pagLink' => $pagLink,
            'current_page' => $page,
            'all_records' => $all_records,
            'total_records' =>  $showing,
            'total_pages' => $total_pages
        ], 200);
    }

    public function approve_subscription(Request $request)
    {
        //return $request->all();

        $id = $request->id;
        $tmp_user = TmpUser::find($id);

        $password = "123qweasdzxc";
        $newpass = Hash::make($password);
        $name = $tmp_user->name;
        $email = $tmp_user->email;

        //return $tmp_user;;

        DB::beginTransaction();
        try {


            $data = array('name' => $name, 'email' => $email, 'password' => $password);

            $send = Mail::send('mails.confirmdoruser', $data, function ($message) use ($email) {
                $message->to($email, 'Reservation')->subject('MMSU iSARAKAN Verification code');
                $message->from('esmalab.mmsu@gmail.com', 'Mariano Marcos State University');
            });

            if ($send) {
                return response()->json(["status" => 0, "message" => "Something went wrong try again later."]);
            }

            $user = new User();
            $user->name  = $tmp_user->name;
            $user->username  = "Subscriber$id";
            $user->email = $tmp_user->email;
            $user->role = 2;
            $user->status = 1;
            $user->password = $newpass;
            $user->save();

            $tmp_user->user_id = $user->id;
            $tmp_user->save();

            /*
                email module here
            */

            DB::commit();
        } //try
        catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }


        return response()->json([
            'user'    => $user,
            'status' => 1,
            "statcode" => 1,
            'message'   => 'User approved.'
        ], 200);
    }
}
