<?php

namespace App\Http\Controllers\Dormitories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\RoomRate;
use App\Models\RervationRoomRate;
use App\Models\DormBranch;
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

        $all_records = Reservation::join('dorm_branch', 'dorm_branch.id', 'reservations.dorm_branch_id')->where('dorm_branch.user_id', $user_id)->where('reservations.archive', 0)->count();

        if (empty($request->search)) {
            $s = Reservation::join('dorm_branch', 'dorm_branch.id', 'reservations.dorm_branch_id')
                ->leftJoin('reservations_room_rate', 'reservations_room_rate.reservation_id', 'reservations.id')
                ->leftJoin('dorm_rooms_rate', 'dorm_rooms_rate.id', 'reservations_room_rate.room_rate_id')
                ->leftJoin('prices', 'prices.id', 'dorm_rooms_rate.price_id')
                ->where('dorm_branch.user_id', $user_id)
                ->where('reservations.archive', 0)
                ->select([
                    'reservations.*',
                    'dorm_branch.name as dormitory',
                    //'dorm_rooms_rate.name as room_rate',
                    \DB::raw("CONCAT(dorm_rooms_rate.name, ' (',prices.price,')') as room_rate")
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
                $s = Reservation::join('dorm_branch', 'dorm_branch.id', 'reservations.dorm_branch_id')->where('dorm_branch.user_id', $user_id)
                    ->leftJoin('reservations_room_rate', 'reservations_room_rate.reservation_id', 'reservations.id')
                    ->leftJoin('dorm_rooms_rate', 'dorm_rooms_rate.id', 'reservations_room_rate.room_rate_id')
                    ->leftJoin('prices', 'prices.id', 'dorm_rooms_rate.price_id')
                    ->where('dorm_branch.user_id', $user_id)
                    ->where('reservations.archive', 0)
                    ->where('dorm_branch.description', 'LIKE', "%{$search}%")
                    ->orWhere('dorm_branch.name', 'LIKE', "%{$search}%")
                    ->orWhere('reservations.name', 'LIKE', "%{$search}%")
                    ->orWhere('reservations.email', 'LIKE', "%{$search}%")
                    ->orWhere('reservations.contact', 'LIKE', "%{$search}%")
                    ->offset($start_from)
                    ->limit($per_page_record)
                    ->select([
                        'reservations.*',
                        'dorm_branch.name as dormitory',
                        \DB::raw("CONCAT(dorm_rooms_rate.name, ' (',prices.price,')') as room_rate")
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
        $inireser = Reservation::join('reservations_room_rate', 'reservations_room_rate.reservation_id', 'reservations.id')
            ->leftJoin('dorm_rooms_rate', 'dorm_rooms_rate.id', 'reservations_room_rate.room_rate_id')
            ->where('reservations.id', $id)
            ->select([
                'reservations.*',
                'reservations_room_rate.room_rate_id',
                'dorm_rooms_rate.quantity as room_rate_qty'
            ])->first();
        //find($id);
        // return  $inireser;

        $email = $inireser->email;
        $data = array('name' => $inireser->name, 'ver_code' => $email);

        //
        $total_res = 0;
        $total_avialable = 0;
        //$r = Reservation::where('dorm_branch_id', $rr->dorm_branch_id)->where('archive', 0)->get();
        $rrr = RervationRoomRate::leftJoin('reservations', 'reservations.id', 'reservations_room_rate.reservation_id')
            ->where('reservations_room_rate.room_rate_id', $inireser->room_rate_id)
            ->where('reservations.archive', 0)
            ->where('reservations.status', 2)
            // ->select('room_rate_id', DB::raw('count(*) as total'))
            // ->groupBy('room_rate_id')
            ->get();

        $total_res = $rrr->count();

        $total_avialable = $inireser->room_rate_qty - $total_res;

        if ($total_avialable <= 0) {
            return response()->json(["status" => 0, "message" => "Something went wrong (No Available Unit)."]);
        }
        // 

        $send = Mail::send('mails.confirmreservation', $data, function ($message) use ($email) {
            $message->to($email, 'Reservation')->subject('MMSU iSARAKAN Verification code');
            $message->from('esmalab.mmsu@gmail.com', 'Mariano Marcos State University');
        });

        if ($send) {
            return response()->json(["status" => 0, "message" => "Something went wrong try again later."]);
        }
        $inireser->aprroved = date("Y-m-d");
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

    public function reservation_archive(Request $request)
    {
        //return $request->all();

        DB::beginTransaction();
        try {

            $a = Reservation::find($request->id);

            $a->archive = 1;
            $a->save();

            DB::commit();
        } //try
        catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
            return $e->getMessage();
        }

        return response()->json(["status" => "success", "statcode" => 1, "message" => 'Data Archived!']);
        // return $request->all();

        //return  $name;
    }
    public function automatic_archive()
    {
        $user_id = Auth::user()->id;

        $dorm_b_ids =  DormBranch::where('user_id', $user_id)->pluck('id');
        $res = Reservation::whereIn('dorm_branch_id', $dorm_b_ids)->where('status', 2)->where('archive', 0)->get();
        //    return $res;

        foreach ($res as $r) {
            if ($r->datefrom == null) {
                $date1 = date_create($r->created_at)->format("Y/m/d H:i:s");
                $date2 = date("Y/m/d H:i:s");

                $ts1 = strtotime($date1);
                $ts2 = strtotime($date2);

                $year1 = date('Y', $ts1);
                $year2 = date('Y', $ts2);

                $month1 = date('m', $ts1);
                $month2 = date('m', $ts2);

                $diff = (($year2 - $year1) * 12) + ($month2 - $month1);

                if ($diff >= 3) {
                    Reservation::where('id', $r->id)
                        ->update([
                            'archive' => 1
                        ]);
                }
            } else {
                $date1 = date_create($r->dateto)->format("Y/m/d H:i:s");
                $date2 = date("Y/m/d H:i:s");

                $ts1 = strtotime($date1);
                $ts2 = strtotime($date2);

                if ($ts2 > $ts1) {
                    Reservation::where('id', $r->id)
                        ->update([
                            'archive' => 1
                        ]);
                }
                // else {
                //     return 'no';
                // }
            }
        }

        return 'ok';
    }
}
