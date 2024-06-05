<?php

namespace App\Http\Controllers\Dormitories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;
use DB;
use Mail;

class ReservationController extends Controller
{
    public function dorm_reser_tbl(Request $request)
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

        $all_records = Reservation::join('dorm_branch', 'dorm_branch.id', 'reservations.dorm_branch_id')->where('dorm_branch.user_id', $user_id)->count();

        if (empty($request->search)) {
            $s = Reservation::join('dorm_branch', 'dorm_branch.id', 'reservations.dorm_branch_id')->where('dorm_branch.user_id', $user_id)
                ->select([
                    'reservations.*',
                    'dorm_branch.name as dormitory',
                ])
                ->offset($start_from)
                ->limit($per_page_record)
                ->orderBy('created_at', 'DESC')->get();

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
            $t = Reservation::join('dorm_branch', 'dorm_branch.id', 'reservations.dorm_branch_id')->where('dorm_branch.user_id', $user_id)->get();
            //return $t;
            $s = [];
            $total_records =  0;
            if (count($t) > 0) {
                $s = Reservation::join('dorm_branch', 'dorm_branch.id', 'reservations.dorm_branch_id')->where('dorm_branch.user_id', $user_id)->where('dorm_branch.description', 'LIKE', "%{$search}%")
                    ->orWhere('dorm_branch.name', 'LIKE', "%{$search}%")
                    ->orWhere('reservations.name', 'LIKE', "%{$search}%")
                    ->orWhere('reservations.email', 'LIKE', "%{$search}%")
                    ->orWhere('reservations.contact', 'LIKE', "%{$search}%")
                    ->offset($start_from)
                    ->limit($per_page_record)
                    ->select([
                        'reservations.*',
                        'dorm_branch.name as dormitory',
                    ])->orderBy('created_at', 'DESC')
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
            }
        }

        // return  $total_pages;


    }

    public function confirm_dorm_reservation(Request $request)
    {

        //return $request->all();

        $id = $request->reservation_id;
        $inireser = Reservation::find($id);


        $email = $inireser->email;
        $data = array('name' => $inireser->name, 'ver_code' => $email);


        $send = Mail::send('mails.confirmreservation', $data, function ($message) use ($email) {
            $message->to($email, 'Reservation')->subject('MMSU iSARAKAN Verification code');
            $message->from('esmalab.mmsu@gmail.com', 'Mariano Marcos State University');
        });

        if ($send) {
            return response()->json(["status" => 0, "message" => "Something went wrong try again later."]);
        }
        $inireser->status = 2;

        $inireser->save();

        // //$check_b->user_id = $user_id;
        // $check_b->name = $request->name;
        // $check_b->dorm_type  =  $request->dtype;
        // $check_b->description  = $request->description;
        // $check_b->region = $request->region;
        // $check_b->prov = $request->prov;
        // $check_b->citymuni = $request->citymuni;
        // $check_b->brgy = $request->brgy;
        // $check_b->contact = $request->contact;
        // $check_b->address = $request->address;
        // $check_b->save();

        return response()->json(["status" => "success", "statcode" => 1, "message" => "Reservation Approved!"]);
    }
}
