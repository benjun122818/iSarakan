<?php

namespace App\Http\Controllers\Enrollment;

use App\EnrollmentDetail;
use App\Fund;
use App\Http\Controllers\Controller;
use App\OrRecord;
use App\Preference;
use App\Repositories\NumberConverter;
use App\StudentInfo;
use App\Sresu;
use App\StudentRecord;
use NumberToWords\NumberToWords;
use Illuminate\Http\Request;

class OrController extends Controller
{
    protected $pref;

    public function __construct()
    {
        $this->pref = Preference::where('enlistment', 1)->first();
    }

    public function searchor(Request $request)
    {
        $this->validate(
            $request,
            [
                'search' => 'required',
                'pref' => 'required',

            ],
            [
                'search.required' => 'Type Student number',
                'pref.required' => 'Select Semester'
            ]
        );

        // $ornumber = OrRecord::with('studentInfo')->where('or_number', 'like', '%' .$request->search . '%')->where('status', 1)
        //             ->limit(25)->get(['id','or_number', 'amount', 'student_id','created_at']);
        $pref_id = $request->pref;
        $student = Sresu::where('student_number', $request->search)->first();

        $getornumber = $student->orRecords()->with('studentInfo')->where('pref_id', $pref_id)->get();

        $ornumber = [];

        if (count($getornumber) <= 0) {
            return response()->json([
                //'ppmp_request'    => $ppmp_req,
                'validate'   => '0'
            ], 200);
        } else {

            foreach ($getornumber as  $value) {
                $list = [
                    'id' => $value->id,
                    'name' => $value->studentInfo->surname . ' ' . $value->studentInfo->firstname,
                    'amount' => $value->amount,
                    'or_number' => $value->or_number,
                    'transaction_type' =>  $value->transaction_type,
                    'status' => $value->status,
                    'date' => date("Y-m-d", strtotime($value->created_at))

                ];
                array_push($ornumber, $list);
            }

            return response()->json([
                'validate'   => '1',
                'orresult'   => $ornumber
            ], 200);
        }
    }

    public function show($orid)
    {
        $orRecord = OrRecord::find($orid);
        $pType = null;

        if ($orRecord->payment_type == 1) {
            $pType = 'Full Payment';
        } elseif ($orRecord->payment_type == 2) {
            $pType = 'Down Payment';
        } elseif ($orRecord->payment_type == 3) {
            $pType = 'Second Payment';
        } elseif ($orRecord->payment_type == 4) {
            $pType = 'Third Payment';
        } elseif ($orRecord->payment_type == 5) {
            $pType = 'Scholar';
        } elseif ($orRecord->payment_type == 6) {
            $pType = 'Landbank ePaymentPortal';
        } elseif ($orRecord->payment_type == 7) {
            $pType = 'Free Education';
        } elseif ($orRecord->payment_type == 23) {
            $pType = 'Down & Second Payment';
        }

        return view('enrollment.or.show', compact(
            'orRecord',
            'pType',
            'orid'
        ));
    }

    public function update(Request $request)
    {
        $orRecord = OrRecord::find($request->orid);
        $orRecord->or_number = $request->or;
        $orRecord->save();

        return back()->with('message', 'OR number successfully updated');
    }

    public function reprint($orid, NumberConverter $numberConverter)
    {
        $orRecord = OrRecord::find($orid);
        $student_record = $orRecord->record;
        $studentInfo = $orRecord->info;

        $payList = $orRecord->payments->toArray();

        $newPayList = [];
        foreach ($payList as $payment) {
            $fund = Fund::find($payment['fund']);

            array_push($newPayList, ['fund' => $fund->fund, 'fund_desc' => $fund->fund_desc, 'amount' => $payment['amount']]);
        }

        $payList = $newPayList;

        $or = $orRecord->or_number;

        $t = explode(".", $orRecord->amount);

        $whole = (int) $t[0];
        $decimal = (int) $t[1];
        $currency = $orRecord->currency == 1 ? 'Pesos' : 'US Dollars';



        // dd($whole);
        if ($decimal <= 9) {
            $converted = $numberConverter->toText($whole, $currency);
        } else {
            $converted = $numberConverter->toText($orRecord->amount, $currency);
        }

        //new
        // $numberToWords = new NumberToWords();
        // build a new number transformer using the RFC 3066 language identifier
        //$numberTransformer = $numberToWords->getNumberTransformer('en');
        // $converted = $numberTransformer->toWords($orRecord->amount); // outputs "five th
        //new

        return view('enrollment.or.reprint', compact(
            'or',
            'studentInfo',
            'student_record',
            'payList',
            'orRecord',
            'converted'
        ));
    }

    public function cancel($orid)
    {
        $orRecord = OrRecord::find($orid);

        $od = date('Y-m-d', strtotime($orRecord->created_at));


        if (!is_null($orRecord)) {
            $enrollment = $orRecord->enrollment;

            $ed = date('Y-m-d', strtotime($enrollment->created_at));

            if ($od == $ed) {

                if (!is_null($enrollment)) {
                    $enrollmentDetails = $enrollment->details;

                    if ($enrollmentDetails->count() > 0) {
                        foreach ($enrollmentDetails as $detail) {
                            $detail->delete();
                        }
                    }

                    $enrollment->delete();
                }

                $orRecord->status = 0;
                $orRecord->save();
            } else {

                $orRecord->status = 0;
                $orRecord->save();
            }
        }

        return back()->with('message', 'OR canceled successfully');
    }

    public function scholarCancel($orid)
    {
        $orRecord = OrRecord::find($orid);

        if (!is_null($orRecord)) {
            $enrollment = $orRecord->enrollment;

            if (!is_null($enrollment)) {
                $enrollmentDetails = $enrollment->details;

                if ($enrollmentDetails->count() > 0) {
                    foreach ($enrollmentDetails as $detail) {
                        $detail->delete();
                    }
                }

                $enrollment->delete();
            }

            $orRecord->status = 0;
            $orRecord->save();
        }

        return back()->with('message', 'Scholarship payment canceled successfully');
    }

    public function onlineCancel($orid)
    {
        $orRecord = OrRecord::find($orid);

        if (!is_null($orRecord)) {
            $enrollment = $orRecord->enrollment;

            if (!is_null($enrollment)) {
                $enrollmentDetails = $enrollment->details;

                if ($enrollmentDetails->count() > 0) {
                    foreach ($enrollmentDetails as $detail) {
                        $detail->delete();
                    }
                }

                $enrollment->delete();
            }

            $orRecord->status = 0;
            $orRecord->save();
        }

        return back()->with('message', 'Online payment canceled successfully');
    }

    public function cadCancel($orid)
    {
        $orRecord = OrRecord::find($orid);

        //return $orRecord;

        if (!is_null($orRecord)) {
            $enrollment = $orRecord->enrollment;
            $student_record = $orRecord->record;
            $enlistment = $student_record->enlistment()->where('pref_id', $orRecord->pref_id)->first();
            //$cad_to_delete = $student_record->cad;
            //return $enrollment;

            $course_ids = [];

            //$studentRec = \DB::table('student_records')->where('student_id', $orRecord->student_id)->get();
            $studentRec =  StudentRecord::where('student_id', $orRecord->student_id)->first();

            //return $studentRec;

            $cad_to_delete = \DB::table('cad')->where('student_rec_id', $studentRec->id)->where('pref_id', $orRecord->pref_id)->whereNull('deleted_at')->get();

            $cad_ids = [];
            $cad_ids_to_delete = [];

            foreach ($cad_to_delete as $cads) {

                array_push($cad_ids, $cads->id);
            }

            // $cad_course_id = \DB::table('cad_details')->whereIn('dac_id', $cad_ids)->get();

            $cad_course_id_del = \DB::table('cad_details')->whereIn('dac_id', $cad_ids)->whereIn('type', [1, 3])->get();

            //return  $cad_course_id_del;

            //return $cad_course_id;

            $enrollment_details = \DB::table('enrollment_details')->where('enrollment_id', $orRecord->enrollment_id)->get();

            $enrollment_details_to_delete = [];
            $deleted_at_course_id = [];

            foreach ($cad_course_id_del as $y) {
                if ($y->type == 1) {
                    array_push($deleted_at_course_id, $y->course_id);
                }
            }

            // $enrollment_details_deleted_at = \DB::table('enrollment_details')->where('enrollment_id', $orRecord->enrollment_id)
            //     ->whereIn('course_id', $deleted_at_course_id)->get();

            \DB::table('enrollment_details')->where('enrollment_id', $orRecord->enrollment_id)
                ->whereIn('course_id', $deleted_at_course_id)
                ->update([
                    'deleted_at' => null,
                ]);


            //  return $enrollment_details_deleted_at;

            foreach ($enrollment_details as $x) {
                foreach ($cad_course_id_del as $z) {

                    if ($z->type == 3) {
                        if ($x->course_id == $z->course_id) {
                            array_push($enrollment_details_to_delete, $x->id);
                        }
                    }
                }
            }

            //return $enrollment_details_to_delete;
            // $delete_enrollment_details = \DB::table('enrollment_details')->whereIn('id', $enrollment_details_to_delete)->get();

            \DB::table('enrollment_details')->whereIn('id', $enrollment_details_to_delete)->delete();

            //return $delete_enrollment_details;

            // foreach ($delete_enrollment_details as $d) {
            //     $d->delete();
            // }


            // delete all enrollment details
            // if (!is_null($enrollment))
            // {
            //     $enrollmentDetails = $enrollment->details;

            //     if ($enrollmentDetails->count() > 0)
            //     {
            //         foreach ($enrollmentDetails as $detail)
            //         {
            //             $detail->delete();
            //         }
            //     }
            // }

            // refresh student enrollment details using enlistment details
            // if (!is_null($enlistment) && !is_null($enrollment))
            // {
            //     $enlistmentDetails = $enlistment->details;

            //     foreach ($enlistmentDetails as $enlDetail)
            //     {
            //         $curriculaDetail = $enlDetail->curDetail;

            //         $enrollmentDetails = new EnrollmentDetail();
            //         $enrollmentDetails->enrollment_id = $enrollment->id;
            //         $enrollmentDetails->sched_id = $enlDetail->sched_id;
            //         $enrollmentDetails->curricula_detail_id = $enlDetail->curricula_detail_id;
            //         $enrollmentDetails->course_id = $curriculaDetail->course_id;
            //         $enrollmentDetails->section = $enlDetail->section;
            //         $enrollmentDetails->save();
            //     }
            // }

            $orRecord->status = 0;
            $orRecord->save();

            //$cad = $student_record->cad()->where('pref_id', $orRecord->pref_id)->where('dac_status', 5)->orderBy('id', 'desc')->first();
            //$cad->delete();
        }

        return back()->with('message', 'Adding/Dropping/Changing payment canceled successfully');
    }

    public function deleteor($orid)
    {
        $orRecord = OrRecord::find($orid);

        $orRecord->delete();

        return response()->json([
            'message' => 'Deleted successfully!'
        ], 200);
    }
}
