<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\RervationRoomRate;
use DB;
use Mail;

class MailController extends Controller
{
    public function html_email()
    {
        $data = array('name' => "Virat Gandhi");
        Mail::send('mails.vermail', $data, function ($message) {
            $message->to('beniziox44@gmail.com', 'Tutorials Point')->subject('Laravel HTML Testing Mail');
            $message->from('esmalab.mmsu@gmail.com', 'Virat Gandhi');
        });
        echo "HTML Email Sent. Check your inbox.";
    }

    public function ini_reserve(Request $request)
    {
        //return $request->all();
        $request->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255'],
                'contact' => ['required'],
                'rate' => ['required'],
                'vcode' => ['required']
            ],
            [
                'vcode.required' => 'The verifiation code is required.',
                'rate.required' => 'Rate / Room field is required.'
            ]
        );

        $dorm_id = $request->dorm_id;
        $email = $request->email;
        $vcode = $request->vcode;

        DB::beginTransaction();
        try {

            $check_r = Reservation::where('dorm_branch_id', $dorm_id)->where('email', $email)->first();
            // return  $check_r;

            if (!empty($check_r)) {

                if ($check_r->verrification_code == $vcode) {

                    switch ($check_r->status) {
                        case 0:
                            $check_b = Reservation::find($check_r->id);
                            $check_b->name = $request->name;
                            $check_b->contact = $request->contact;
                            $check_b->status = 1;

                            $rrr = new RervationRoomRate();
                            $rrr->reservation_id = $check_r->id;
                            $rrr->room_rate_id = $request->rate;

                            $rrr->save();
                            $check_b->save();
                            DB::commit();
                            return response()->json(["status" => 1, "message" => "Your Reservation has been save. An email will be sent if your reservtion has been accepted"]);
                            break;
                        case 1:
                            //code block;
                            break;
                        case 2:
                            //code block;
                            break;
                        case 3:
                            //code block
                            break;
                    }
                } else {
                    return response()->json(["status" => 0, "message" => "Verification did not match."]);
                }
            } else {
                return response()->json(["status" => 0, "message" => "Oops Verification did not match."]);
            }


            DB::commit();
        } //try
        catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
            return $e->getMessage();
        }

        //echo "HTML Email Sent. Check your inbox.";
        //  return response()->json(["status" => 1, "message" => "Your Reservation has been save. An email will be sent if your reservtion has been accepted"]);
        // return response()->json(["status" => 1, "message" => "Verification code Sent. Check your inbox.", "inireser" =>   $inireser]);
    }

    public function check_email(Request $request)
    {
        //return $request->all();
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255']
        ]);

        DB::beginTransaction();
        try {


            $dorm_id = $request->dorm_id;
            $email = $request->email;

            $ver_code = random_int(100000, 999999);

            $check_r = Reservation::where('dorm_branch_id', $dorm_id)->where('email', $email)->first();
            //  return  $check_r;

            if (empty($check_r)) {
                $data = array('name' => $email, 'ver_code' => $ver_code);

                $send = Mail::send('mails.vermail', $data, function ($message) use ($email) {
                    $message->to($email, 'Reservation')->subject('MMSU iSARAKAN Verification code');
                    $message->from('esmalab.mmsu@gmail.com', 'Mariano Marcos State University');
                });

                if ($send) {
                    return response()->json(["status" => 0, "message" => "Something went wrong try again later."]);
                }

                $inireser = new Reservation();
                $inireser->dorm_branch_id = $request->dorm_id;
                // $inireser->name = $request->name;
                $inireser->email = $email;
                //$inireser->contact = $request->contact;
                $inireser->verrification_code = $ver_code;
                $inireser->save();
            } else {
                // return 'im here';
                // if ($check_r->status == 0) {
                //     $check_b = Reservation::find($check_r->id);
                //     //return  $check_b;
                //     $check_b->delete();
                //     DB::commit();
                //     return response()->json(["status" => 0, "created_vercode" => 0]);
                // }
            }

            DB::commit();
        } //try
        catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
            return $e->getMessage();
        }

        //echo "HTML Email Sent. Check your inbox.";
        return response()->json(["status" => 1, "created_vercode" => 1]);
    }

    public function verify_reserve(Request $request)
    {
        //  return $request->all();
        $request->validate([
            'vcode' => ['required', 'string', 'max:6'],

        ]);

        $id = $request->reservation_id;

        $check_b = Reservation::find($id);

        /*
        verify if $request->verification == $check_b->verrification_code
        */

        DB::beginTransaction();
        try {


            $check_b = Reservation::find($id);

            $check_b->status = 1;

            $check_b->save();

            DB::commit();
        } //try
        catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
            return $e->getMessage();
        }

        //echo "HTML Email Sent. Check your inbox.";
        return response()->json(["status" => 1, "message" => "Your Reservation has been save."]);
    }

    public function testvue_email()
    {
        $data = array('name' => "Virat Gandhi");

        return view("mails.vermail", compact("data"));
    }
}
