<?php

namespace App\Http\Controllers\Administrator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ResCenter;
use App\Models\Office;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
        //$this->dbequipment = config('app.dbequipment');
        //$this->dbppmp = config('app.dbppmp');
    }

    public function index()
    {
        return Office::all();

        //    return User::join('ppmp.entities', 'entities.id', '=', 'users.office_id')
        //         ->select([
        //             'users.*',
        //             'entities.department',
        //         ])->get();
    }

    public function user_table(Request $request)
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

        $all_records = User::count();

        if (empty($request->search)) {
            $s = User::offset($start_from)
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
            $s = User::where('name', 'LIKE', "%{$search}%")
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


    public function create()
    {
        //
    }


    public function store(Request $request)
    {
        // return $request->all();
        $this->validate(
            $request,
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],

                'type' => ['required'],
                'user_name' => ['required'],
            ],
            [

                'type.required' => 'Select user type.'
            ]
        );


        $newpass = Hash::make($request->password);


        $user = new User();
        $user->name  = $request->name;
        $user->username  = $request->user_name;
        $user->email = $request->email;
        $user->role = $request->type;
        $user->status = 1;
        $user->password = $newpass;
        $user->save();

        return response()->json([
            'user'    => $user,
            'status' => 1,
            'message'   => 'User successfully created.'
        ], 200);
    }


    public function show($id)
    {
        //
    }


    public function edit($id)
    {
        //
    }


    public function update(Request $request, $id)
    {
    }

    public function get_user_office()
    {
        return Office::orderBy('department')->get();
    }
    public function get_user_rc()
    {
        return ResCenter::orderBy('description')->get();
    }
    public function destroy($id)
    {
        //
    }
}
