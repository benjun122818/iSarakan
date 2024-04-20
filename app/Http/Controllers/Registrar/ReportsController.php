<?php

namespace App\Http\Controllers\Registrar;

use App\Ay;
use App\CAD;
use App\College;
use App\Degree;
use App\Course;
use App\Cy;
use App\Enlistment;
use App\Enrollment;
use App\Fee;
use App\Fund;
use App\OrRecord;
use App\StudentRecord;
use App\Payment;
use App\Preference;
use App\Repositories\Assessment;
use App\Repositories\CADAssess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Exports\MasterlistExport;
use App\Exports\SubMasterlistGenerate;
use App\Exports\PaymentSummaryExport;
use App\Exports\CombinedSubjectPaymentMasterlist;
use App\Exports\ADCAssessmentMasterlist;
use App\Exports\ADCSubjectPaymentMasterlist;
use App\Exports\UnpaidEnlistment;
use App\Exports\UnpaidADC;
use App\Exports\TransactionLogs;
use App\Remark;
use PDF;
use DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends Controller
{
    protected $pref;

    public function __construct()
    {
        $this->pref = Preference::where('enlistment', 1)->first();
    }

    public function index()
    {
        return view('registrar.reports.index');
    }

    public function masterlist()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(-1);

        $collect = Enrollment::join('student_records', 'student_records.id', 'enrollments.student_rec_id')
            ->where('enrollments.pref_id', $this->pref->id)
            ->whereNull('enrollments.deleted_at')->get('student_records.college_id');


        $enrollmentGS = null;
        $enrollmentGScount = null;

        $colleges = College::orderBy('college', 'asc')->pluck('college', 'id');

        //return $colleges;

        $courses = Course::orderBy('code', 'asc')->pluck('code', 'id');

        //        $enrollmentSummary = College::with([
        //            'studentRecord' => function ($query) {
        //
        //                $query->whereHas('enrollment', function ($query) {
        //                    $query->where('pref_id', $this->pref->id);
        //                });
        //            }
        //        ])->get();

        $enrollmentSummary = College::all();


        //        $grandenr = Enrollment::where('pref_id', $this->pref->id)->get();
        $grandenr = count($collect);

        $preferences = [];

        foreach (Preference::orderBy('enlistment', 'desc')->get() as $key => $pref) {
            $sem = "";
            if ($pref->sem == 1) {
                $sem = "id({$pref->id})-{$pref->cys->cy} | First Semester";
            } else if ($pref->sem == 2) {
                $sem = "id({$pref->id})-{$pref->cys->cy} | Second Semester";
            } else if ($pref->sem == 3) {
                $sem = "id({$pref->id})-{$pref->cys->cy} | Mid-year";
            }
            $preferences[$pref->id] = $sem;
        }


        return view('registrar.reports.masterlist', compact(
            'colleges',
            'courses',
            'enrollmentSummary',
            'enrollmentGS',
            'enrollmentGScount',
            'grandenr',
            'collect',
            'preferences'
        ));
    }

    public function masterlistConsolidate(Request $request)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(-1);

        $enrollmentSummary = College::with([
            'studentRecord' => function ($query) {

                $query->whereHas('enrollment', function ($query) {
                    $query->where('pref_id', $this->pref->id);
                });
            }
        ])->get();


        $enrollmentConsolidate = College::with([
            'studentRecord' => function ($query) {
                $query->groupBy('degree_id');
                $query->whereHas('enrollment', function ($query) {
                    $query->where('pref_id', $this->pref->id);
                });
            }
        ])->get();

        $first = Degree::withCount(['studentRecord as approved_comments_count' => function ($query) {

            $query->whereHas('enrollment', function ($query) {
                $query->where('pref_id', $this->pref->id);
                $query->where('standing', '1');
            });
        }])->get();


        $second = Degree::withCount(['studentRecord as approved_comments_count' => function ($query) {

            $query->whereHas('enrollment', function ($query) {
                $query->where('pref_id', $this->pref->id);
                $query->where('standing', '2');
            });
        }])->get();

        $third = Degree::withCount(['studentRecord as approved_comments_count' => function ($query) {

            $query->whereHas('enrollment', function ($query) {
                $query->where('pref_id', $this->pref->id);
                $query->where('standing', '3');
            });
        }])->get();

        $fourth = Degree::withCount(['studentRecord as approved_comments_count' => function ($query) {

            $query->whereHas('enrollment', function ($query) {
                $query->where('pref_id', $this->pref->id);
                $query->where('standing', '4');
            });
        }])->get();

        $fifth = Degree::withCount(['studentRecord as approved_comments_count' => function ($query) {

            $query->whereHas('enrollment', function ($query) {
                $query->where('pref_id', $this->pref->id);
                $query->where('standing', '5');
            });
        }])->get();

        $numenroll = Degree::withCount(['studentRecord as enrollee_count' => function ($query) {

            $query->whereHas('enrollment', function ($query) {
                $query->where('pref_id', $this->pref->id);
            });
        }])->get();

        $grandenr = Enrollment::where('pref_id', $this->pref->id)->get();
        //dd($grandenr->count());


        $pdf = PDF::setOptions(['dpi' => 170, 'defaultFont' => 'sans-serif', 'isJavascriptEnabled' => true, 'isPhpEnabled' => true])->loadview(
            'registrar/reports/masterlist-consolidate',
            [
                'enrollmentConsolidate' => $enrollmentConsolidate,
                'first' => $first,
                'second' => $second,
                'third' =>  $third,
                'fourth' => $fourth,
                'fifth' => $fifth,
                'numenroll' => $numenroll,
                'enrollmentSummary' => $enrollmentSummary,
                'grandenr' => $grandenr->count()
            ]
        );

        // $pdf->set_option("isPhpEnabled", true);
        return $pdf->stream('masterlist-consolidate.pdf');
    }
    public function masterlistGenerate(Request $request)
    {
        #dd($request->all());
        ini_set('memory_limit', '-1');
        set_time_limit(-1);

        // $college = !is_null($request->college) ? (int) $request->college : 0;
        // $degree = !is_null($request->degree) ? (int) $request->degree : 0;
        // $course = isset($request->course) ? (int) $request->course : 0;
        $college = $request->college;
        $degree = $request->degree;
        $course = $request->course;

        // $pref_id = $this->pref->id;
        $pref_id = (int) $request->preffromblade;

        //  return $pref_id;

        ini_set('memory_limit', '-1');
        set_time_limit(-1);
        //dd($this->pref, $this->course, $this->college, $this->degree);
        if (!is_null($college)) {
            $enrollments = Enrollment::whereRaw("enrollments.student_rec_id in (select id from student_records where college_id = '$college')");
        }

        if (!is_null($degree)) {
            $enrollments = Enrollment::whereRaw("enrollments.student_rec_id in (select id from student_records where college_id = '$college' AND degree_id = '$degree' )");
        }

        if (is_null($college) && is_null($degree)) {
            $enrollments = Enrollment::whereRaw("enrollments.student_rec_id in (select id from student_records)");
        }

        $enrollments->with([
            'studentRecord.info',
            'studentRecord.college',
            'studentRecord.degree',
            'studentRecord.pds',
            'studentRecord.family',
            'studentRecord.sresu',
            'scholarship',
            'orRecord',
            'details.course'
        ]);

        //
        //return $enrollments;
        if (!is_null($course)) {
            $myCourse = Course::where('code', $course)->first();

            // $enrollments = $enrollments->whereHas('details', function($query) use ($myCourse){
            //     $query->where('course_id', $myCourse->id);
            // });

            $enrollments = $enrollments->whereRaw("enrollments.id in (select enrollment_id from enrollment_details where course_id = '$myCourse->id')");
        }

        //$enrollments = $enrollments->where('pref_id', $this->pref->id)->get();
        $enrollments = $enrollments->where('pref_id', $pref_id)->orderBy('id', 'DESC')->get();
        //$enrollments = $enrollments->where('pref_id', $this->pref->id)->limit(5)->get();
        //dd($enrollments);
        //    return $enrollments;
        //header
        $headers = [];
        array_push(
            $headers,
            'studno',
            'lastname',
            'firstname',
            'middlename',
            'classification',
            'course',
            'college',
            'level',
            'section',
            'lecUnits',
            'labUnits',
            'gender',
            'dob',
            'pob',
            'fln',
            'ffn',
            'fmn',
            'mln',
            'mfn',
            'mmn',
            'brgy',
            'citymun',
            'prov',
            'zip',
            'mobile',
            'email',
            'scholarship',
            'assessed',
            'paid',
            'balance',
            'or',
            'datePaid',
            'free_tuition',
            'enlisted_date',
            'approve_date'
        );

        //

        // return $headers;

        $enrollmentRecords = collect();
        $data = [];

        foreach ($enrollments as $enrollment) {
            $enrDetails = $enrollment->details;
            $lecUnits = 0;
            $labUnits = 0;

            foreach ($enrDetails as $enrDetail) {
                $course = $enrDetail->course;

                $lecUnits += $course->lecture_units;
                $labUnits += $course->lab_unit;
            }

            $familypds = $enrollment->studentRecord->family;

            $mln = null;
            $mfn = null;
            $mmn = null;
            $fln = null;
            $ffn = null;
            $fmn = null;

            foreach ($familypds as $familyinfo) {
                if ($familyinfo->relations == 1) {
                    $mln = $familyinfo->lname;
                    $mfn = $familyinfo->fname;
                    $mmn = $familyinfo->mname;
                } elseif ($familyinfo->relations == 2) {

                    $fln = $familyinfo->lname;
                    $ffn = $familyinfo->fname;
                    $fmn = $familyinfo->mname;
                } else {
                    //nothing to do here
                }
            }


            try {
                $gender = ($enrollment->studentRecord->pds->sex == 1) ? 'M' : 'F';
            } catch (\Exception $e) {
                $gender = null;
            }

            try {
                $address = $enrollment->studentRecord->info->address()->where('type', 1)->first();
            } catch (\Error $e) {
                $address = null;
            } catch (\Throwable $e) {
                $address = null;
            } catch (\Exception  $e) {
                $address = null;
            }

            $prov = null;
            $citymun = null;
            $brgy = null;
            $zip = null;
            if (!is_null($address)) {
                try {
                    $prov = DB::table('dbcommon.refprovince')
                        ->where('provCode', $address->prov)
                        ->first()->provDesc;
                } catch (\Exception $e) {
                    $prov = null;
                }

                try {
                    $citymun = DB::table('dbcommon.refcitymun')
                        ->where('citymunCode', $address->town)
                        ->first()->citymunDesc;
                } catch (\Exception $e) {
                    $citymun = null;
                }

                try {
                    $brgy = DB::table('dbcommon.refbrgy')
                        ->where('brgyCode', $address->brgy)
                        ->first()->brgyDesc;
                } catch (\Exception $e) {
                    $brgy = null;
                }

                try {
                    $zip = null;
                    if (!is_null($prov) && !is_null($citymun)) {
                        $major_area = DB::table('dbcommon.zipcodes')
                            ->where('major_area', 'like', "{$prov}%")
                            ->get();

                        foreach ($major_area as $key => $value) {
                            $pattern = "/({$value->city})/i";
                            if (preg_match($pattern, $citymun)) {
                                $zip = $value->zip_code;
                                break;
                            }
                        }
                    }
                } catch (\Exception $e) {
                }
            }
            $classification = null;
            if ($enrollment->studentRecord->type == 1) {
                $classification = "Regular/Local";
            } elseif ($enrollment->studentRecord->type == 2) {
                $classification = "ETEEAP";
            } else {
                $classification = "Foreign";
            }

            $enrRec = [
                'studno' => $enrollment->studentRecord->sresu->student_number,
                'lastname' => is_null($enrollment->studentRecord->info) ? '' : $enrollment->studentRecord->info->surname,
                'firstname' => is_null($enrollment->studentRecord->info) ? '' : $enrollment->studentRecord->info->firstname,
                'middlename' => is_null($enrollment->studentRecord->info) ? '' : $enrollment->studentRecord->info->middlename,
                'classification' => $classification,
                'course' => $enrollment->studentRecord->degree->abbr,
                'college' => $enrollment->studentRecord->college->collegeabbr,
                'level' => $enrollment->standing,
                'section' => $enrollment->section,
                'lecUnits' => $lecUnits,
                'labUnits' => $labUnits,
                'gender' => $gender,
                'dob' => is_null($enrollment->studentRecord->pds) ? '' : $enrollment->studentRecord->pds->dob,
                'pob' => is_null($enrollment->studentRecord->pds) ? '' : $enrollment->studentRecord->pds->pob,
                'fln' => is_null($enrollment->studentRecord->family) ? 'no record found' : (!is_null($fln) ? $fln : 'no record found'),
                'ffn' => is_null($enrollment->studentRecord->family) ? 'no record found' : (!is_null($ffn) ? $ffn : 'no record found'),
                'fmn' => is_null($enrollment->studentRecord->family) ? 'no record found' : (!is_null($fmn) ? $fmn : 'no record found'),
                'mln' => is_null($enrollment->studentRecord->family) ? 'no record found' : (!is_null($mln) ? $mln : 'no record found'),
                'mfn' => is_null($enrollment->studentRecord->family) ? 'no record found' : (!is_null($mfn) ? $mfn : 'no record found'),
                'mmn' => is_null($enrollment->studentRecord->family) ? 'no record found' : (!is_null($mmn) ? $mmn : 'no record found'),
                'brgy' => $brgy,
                'citymun' => $citymun,
                'prov' => $prov,
                'zip' => $zip,
                'mobile' => is_null($enrollment->studentRecord->pds) ? '' : $enrollment->studentRecord->pds->contact_number,
                'email' => is_null($enrollment->studentRecord->info) ? '' : $enrollment->studentRecord->info->email,
                'scholarship' => is_null($enrollment->scholarship) ? '-- NONE --' : $enrollment->scholarship->scholarship,
                'assessed' => is_null($enrollment->orRecord) ? '' : (float) $enrollment->orRecord->full,
                'paid' => is_null($enrollment->orRecord) ? '' : (float) $enrollment->orRecord->amount,
                'balance' => is_null($enrollment->orRecord) ? '' : (float) $enrollment->orRecord->balance,
                'or' => is_null($enrollment->orRecord) ? '' : $enrollment->orRecord->or_number,
                'datePaid' => is_null($enrollment->orRecord) ? '' : Carbon::parse($enrollment->orRecord->created_at)->format('Y-m-d'),
                'free_tuition' => $enrollment->free_tuition ? 'Yes' : 'No',
                'enlisted_date' => ($enrollment->studentRecord->enlisted->where('pref_id', $pref_id)->first()) ? date('F d, Y', strtotime($enrollment->studentRecord->enlisted->where('pref_id', $pref_id)->first()->created_at)) : '',
                'approve_date' => ($enrollment->studentRecord->enlisted->where('pref_id', $pref_id)->first()) ? date('F d, Y', strtotime($enrollment->studentRecord->enlisted->where('pref_id', $pref_id)->first()->updated_at)) : '',

            ];

            array_push($data, $enrRec);
        }
        // dd($data);
        //return $data;
        //return collect($enrRec);

        //dd($college, $degree, $course, $pref_id);
        if (count($request->all()) > 0) {

            return Excel::download(new MasterlistExport($headers, $data), 'masterlist.xlsx');
        } else {
            return back()->with('err', 'Please select at least college to generate');
        }
    }

    public function subMasterlist()
    {
        $enrollmentSummary = null;

        $colleges = College::orderBy('college', 'asc')->pluck('college', 'id');

        $courses = Course::orderBy('code', 'asc')->pluck('code', 'id');

        return view('registrar.reports.submasterlist', compact(
            'colleges',
            'courses',
            'enrollmentSummary'
        ));
    }

    public function subMasterlistGenerate(Request $request)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(-1);

        $college = !is_null($request->college) ? $request->college : null;
        $degree = !is_null($request->degree) ? $request->degree : null;

        if (count($request->all()) > 0) {
            $data = [];

            # Excel Headers
            $headers = [];
            array_push(
                $headers,
                'Student No.',
                'Lastname',
                'Firstname',
                'Middlename',
                'Sex',
                'Degree',
                'Subject Code',
                'Subject Description',
                'Units'
            );
            //array_push($data, $headers);
            # Excel Header End

            # Excel Data
            $enrollments = Enrollment::whereHas('studentRecord', function ($query) use ($college, $degree) {

                $query->with([
                    'info',
                    'college',
                    'degree'
                ]);

                if (!is_null($college)) {
                    $query->where('college_id', $college);
                }

                if (!is_null($degree)) {
                    $query->where('degree_id', $degree);
                }
            })->with([
                'studentRecord.info',
                'studentRecord.college',
                'studentRecord.degree',
                'studentRecord.pds',
                'studentRecord.sresu',
                'scholarship',
                'orRecord',
                'details.course'
            ]);

            $enrollments = $enrollments->where('pref_id', $this->pref->id)->get();

            //return $enrollments;

            $enrollmentRecords = collect();
            foreach ($enrollments as $enrollment) {
                $enrDetails = $enrollment->details;
                $lecUnits = 0;
                $labUnits = 0;

                foreach ($enrDetails as $enrDetail) {
                    $course = $enrDetail->course;

                    $enrRec = [
                        'studno' => $enrollment->studentRecord->sresu->student_number,
                        'lname' => $enrollment->studentRecord->info->surname . ', ' . $enrollment->studentRecord->info->firstname . ' ' . $enrollment->studentRecord->info->middlename,
                        'fname' => $enrollment->studentRecord->info->firstname,
                        'mname' => $enrollment->studentRecord->info->middlename,
                        'gender' => ($enrollment->studentRecord->pds->sex == 1) ? 'M' : 'F',
                        //'gender' => ($enrollment->studentRecord->pds->gender == 1) ? 'M' : 'F',
                        // 'gender' => $enrollment->studentRecord->pds->gender,
                        'degree' => $enrollment->studentRecord->degree->abbr,
                        'code' => $course->code,
                        'desc' => $course->title,
                        'units' => $course->units,
                    ];

                    array_push($data, $enrRec);
                }
                // return $data;
                // $enrollmentRecords->push($enrRec);
            }
            # Excel Data End

            # Generate Excel
            $file = 'acctg-subject-payment-masterlist-' . Auth::user()->id;

            return Excel::download(new SubMasterlistGenerate($headers, $data), $file . '.xlsx');
            # Generate Excel End

            return response()->download('storage/excel/registrar-masterlist-' . Auth::user()->id . '.xls');
        } else {
            return back()->with('err', 'Please select at least college to generate');
        }
    }

    public function paymentSummary()
    {
        ini_set('memory_limit', '6144M');
        ini_set('max_execution_time', 0);
        set_time_limit(-1);

        $courses = null;
        $enrollmentSummary = null;

        $colleges = College::orderBy('college', 'desc')->pluck('college', 'id');

        $cys = Cy::orderBy('cy', 'desc')->pluck('cy', 'id');

        return view('registrar.reports.payment-summary', compact(
            'colleges',
            'courses',
            'enrollmentSummary',
            'cys'
        ));
    }

    public function paymentSummaryGenerate(Request $request, Assessment $enrAssess, CADAssess $cadAssess)
    {

        ini_set('memory_limit', '-1');
        set_time_limit(-1);
        //return $request;
        $pref = (int) $request->pref;
        $college = $request->college;
        $degree = $request->degree;
        $enrAssess = $enrAssess;
        //dd($pref);

        // $enrollments = Enrollment::where('pref_id', $pref)->whereHas('studentRecord', function ($query) use ($college) {
        //     $query->where('college_id', $college);
        // })->with([
        //     'orRecords',
        //     // 'cad' => function ($query) use ($pref) {
        //     //     $query->where('pref_id', $pref);
        //     // },
        //     'scholarship',
        //     'studentRecord.info'
        // ])->get();

        $enrollments = Enrollment::join('student_records', 'student_records.id', '=', 'enrollments.student_rec_id')
            ->leftJoin('student_info', 'student_records.student_id', '=', 'student_info.student_id')
            ->leftJoin('scholarships', 'scholarships.id', '=', 'enrollments.scholarship_id')
            ->join('degrees', 'degrees.id', '=', 'student_records.degree_id')
            ->where('enrollments.pref_id', $pref)
            ->where('student_records.college_id', $college)
            ->where(function ($query) use ($degree) {
                if (!is_null($degree)) {
                    $query->where('student_records.degree_id', $degree);
                }
            })
            ->whereNull('enrollments.deleted_at')
            ->select(
                'enrollments.id',
                'enrollments.student_rec_id',
                'scholarships.scholarship',
                'enrollments.free_tuition',
                'student_info.student_number',
                'student_info.surname',
                'student_info.firstname',
                'student_info.middlename',
                'degrees.abbr'
            )
            //->take(10)
            ->get();
        //dd($enrollments);
        $data = [];
        $enlistsri = [];
        $enrolsri = [];

        # Excel Headers
        $headers = [];
        array_push($headers,  'Student No.', 'Name', 'Course');

        /*
        $funds = Fund::whereHas('payments', function($query) use ($pref){
            $query->whereHas('oRec', function ($query) use ($pref){
                $query->where('pref_id', $pref);
            });
        })->orderBy('fund', 'asc')->get();
        */

        $paidFunds = Payment::whereHas('oRec', function ($query) use ($pref) {
            $query->where('pref_id', $pref);
        })->with(['myFund'])->groupBy('fund')->get();

        $fees_stack = [];
        foreach ($paidFunds as $fund) {
            array_push($fees_stack, $fund->fund);
            $fund_id = is_null($fund->myFund) ? '' : $fund->myFund->fund;
            array_push($headers, $fund_id . '|' . $fund->fund);
        }
        array_push($headers, 'FT', 'Scholarship', 'EnrAssess', 'CADAssess');
        //array_push($data, $headers);
        //return $headers;
        # Excel Header End

        # Excel Data
        foreach ($enrollments as $enrollment) {
            //$scholarship = !is_null($enrollment->scholarship) ? $enrollment->scholarship->scholarship : '';
            $scholarship = !is_null($enrollment->scholarship) ? $enrollment->scholarship : '';
            $freeTuition = $enrollment->free_tuition == 1 ? 'Yes' : 'No';

            $feesList = collect();

            # check enrollment

            try {
                $enrAssessment = $enrAssess->getAssessment($enrollment->student_rec_id, $pref);
            } catch (\Exception $e) {
                continue;
            }


            $enrTotal = 0;
            foreach ($enrAssessment['semFeesList'] as $enrFee) {
                $feeArr = [
                    'fund_id' => $enrFee->fund_id,
                    'amount' => $enrFee->amount
                ];
                $feesList->push($feeArr);
            }
            $enrTotal = $enrAssessment['fullPayment'];
            #check enrollment end

            # check CAD
            /*
            $cad = $enrollment->cad;
            if (!is_null($cad))
            {
                $cadAssessment = $cadAssess->getAssessment($cad->id);
                foreach ($cadAssessment->feesList as $cadFee)
                {
                    $feeArr = [
                        'fund_id' => $cadFee->fund_id,
                        'fund' => $cadFee->fund,
                        'amount' => $cadFee->amount
                    ];
                    $feesList->push($feeArr);
                }

                $cadTotal = $cadAssessment->totalAmount;
            }
            */
            $cadTotal = 0;
            // $cadOrRecord = $enrollment->orRecords()->where('transaction_type', 2)->first();
            $cadOrRecord = OrRecord::where('enrollment_id', $enrollment->id)->where('transaction_type', 2)->first();
            if (!is_null($cadOrRecord)) {
                $cadPayments = $cadOrRecord->payments;

                foreach ($cadPayments as $cadPaid) {
                    $feeArr = [
                        'fund_id' => $cadPaid->fund,
                        'amount' => $cadPaid->amount
                    ];
                    $feesList->push($feeArr);
                }
                $cadTotal = $cadOrRecord->amount;
            }
            # check CAD end

            $rowRecord = [];
            $paymentArr = [];

            //$studentNumber = $enrollment->studentRecord->info->student_number;
            //$studentName = $enrollment->studentRecord->info->surname . ' ' . $enrollment->studentRecord->info->firstname . ' ' . $enrollment->studentRecord->info->middlename;
            $studentNumber = $enrollment->student_number;
            $studentName = $enrollment->surname . ' ' . $enrollment->firstname . ' ' . $enrollment->middlename;
            $degree = $enrollment->abbr;
            array_push($rowRecord, $studentNumber, $studentName, $degree);

            foreach ($feesList as $paid) {
                $paymentArr[$paid['fund_id']] = ['amount' => $paid['amount']];
            }

            foreach ($fees_stack as $feeID) {
                if (array_key_exists($feeID, $paymentArr)) {
                    array_push($rowRecord, (float) $paymentArr[$feeID]['amount']);
                } else {
                    array_push($rowRecord, 0);
                }
            }

            array_push($rowRecord, $freeTuition, $scholarship, $enrTotal, $cadTotal);

            array_push($data, $rowRecord);

            // array_push($enrolsri, $enrollment->student_rec_id);

            // $enlist = DB::table('enlisted')->where('pref_id', $pref)->where('student_rec_id', $enrollment->student_rec_id)->get();

            // foreach ($enlist as $en) {
            //     array_push($enlistsri, $en->student_rec_id);
            //     $np = [];
            //     foreach ($enrolsri as $sri) {
            //         if (!in_array($sri, $enlistsri)) {

            //             $sr =  DB::table('student_records')->where('student_records.id', $en->student_rec_id)->where('student_records.college_id', $college)
            //                 ->join('student_info', 'student_records.student_id', '=', 'student_info.student_id')
            //                 ->select(
            //                     'student_info.student_number',
            //                     'student_info.surname',
            //                     'student_info.firstname',
            //                     'student_info.middlename'
            //                 )->first();

            //             // foreach ($sr as $si) {
            //             $npstudentNumber = $sr->student_number;
            //             $npstudentName = $sr->surname . ', ' . $sr->firstname . ' ' . $sr->middlename;
            //             // array_push($data, 'no erolment but enlisted');
            //             array_push($np, $npstudentNumber, $npstudentName, 'no enrollment but enlisted');
            //             //}
            //         }
            //     }
            // }
        }
        // if (!is_null($np)) {
        //     array_push($data, $np);
        // }

        //return $headers;
        //return $data;
        //return $np;
        # Excel Data End

        # Generate Excel
        $file = 'reports-payment-summary-' . Auth::user()->id;

        return Excel::download(new PaymentSummaryExport($headers, $data), $file . '.xlsx');
    }

    public function subjectPayment()
    {
        $colleges = null;
        $cys = null;
        $courses = null;
        $enrollmentSummary = null;

        $colleges = College::orderBy('college', 'desc')->pluck('college', 'id');

        //dd($colleges);

        $cys = Cy::orderBy('cy', 'desc')->pluck('cy', 'id');

        return view('registrar.reports.subject-payment', compact(
            'colleges',
            'courses',
            'enrollmentSummary',
            'cys'
        ));
    }

    public function transaction_bypref()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(-1);

        $collect = Enrollment::join('student_records', 'student_records.id', 'enrollments.student_rec_id')
            ->where('enrollments.pref_id', $this->pref->id)
            ->whereNull('enrollments.deleted_at')->get('student_records.college_id');


        $enrollmentGS = null;
        $enrollmentGScount = null;

        $colleges = College::orderBy('college', 'asc')->pluck('college', 'id');

        //return $colleges;

        $courses = Course::orderBy('code', 'asc')->pluck('code', 'id');

        //        $enrollmentSummary = College::with([
        //            'studentRecord' => function ($query) {
        //
        //                $query->whereHas('enrollment', function ($query) {
        //                    $query->where('pref_id', $this->pref->id);
        //                });
        //            }
        //        ])->get();

        $enrollmentSummary = College::all();


        //        $grandenr = Enrollment::where('pref_id', $this->pref->id)->get();
        $grandenr = count($collect);

        $preferences = [];

        foreach (Preference::orderBy('enlistment', 'desc')->get() as $key => $pref) {
            $sem = "";
            if ($pref->sem == 1) {
                $sem = "id({$pref->id})-{$pref->cys->cy} | First Semester";
            } else if ($pref->sem == 2) {
                $sem = "id({$pref->id})-{$pref->cys->cy} | Second Semester";
            } else if ($pref->sem == 3) {
                $sem = "id({$pref->id})-{$pref->cys->cy} | Mid-year";
            }
            $preferences[$pref->id] = $sem;
        }


        return view('registrar.reports.transactionlogs', compact(
            'colleges',
            'courses',
            'enrollmentSummary',
            'enrollmentGS',
            'enrollmentGScount',
            'grandenr',
            'collect',
            'preferences'
        ));
    }
    public function logs_generate(Request $request)
    {
        //return $request->preffromblade;
        $college = College::find($request->college);
        //return $college;

        $prf = Preference::find($request->preffromblade);
        //return $prf;

        if ($prf->sem == 1) {
            $sem = "{$prf->cys->cy}_First Semester";
        } else if ($prf->sem == 2) {
            $sem = "{$prf->cys->cy}_Second Semester";
        } else if ($prf->sem == 3) {
            $sem = "{$prf->cys->cy}_Mid-year";
        }
        //return $sem;

        $enlistments = Enlistment::where('pref_id', $request->preffromblade)
            ->join('student_records', 'enlisted.student_rec_id', '=', 'student_records.id')
            ->join('student_info', 'student_info.student_id', '=', 'student_records.student_id')
            ->where('student_records.college_id', $request->college)
            ->select([
                'enlisted.id',
                'enlisted.student_rec_id',
                'enlisted.section',
                'enlisted.standing',
                'enlisted.pref_id',
                'student_records.student_id',
                'student_records.college_id',
                'student_info.student_number',
                'student_info.surname',
                'student_info.firstname',
                'student_info.middlename',
            ])
            ->get();

        $final = [];
        foreach ($enlistments as $e) {

            $r = Remark::where('enl_id', $e->id)->get();

            $a = [
                'student_number' => $e->student_number,
                'section' => $e->section,
                'standing' => $e->standing,
                'name' => $e->surname . ' ' . $e->firstname . '' . (!is_null($e->middlename) ? ', ' . $e->middlename : ''),
                'remarks' => $r
            ];
            array_push($final, $a);
        }

        //return $final;
        return Excel::download(new TransactionLogs(
            $college,
            $sem,
            $final
        ), 'enrollment_logs_' . $sem . '.xlsx');

        // return view('registrar.reports.excels.transaction-log', compact(
        //     'college',
        //     'sem',
        //     'final'
        // ));
        //return $enlistments;
    }
    // OLD SUBJECT-PAYMENT GENERATION
    // public function subjectPaymentGenerate(Request $request, Assessment $enrAssess, CADAssess $cadAssess)
    // {
    //     $pref = $request->pref;
    //
    //     $college = !is_null($request->college) ? $request->college : null;
    //     $degree = !is_null($request->degree) ? $request->degree : null;
    //
    //     if (count($request->all()) > 0)
    //     {
    //         $data = [];
    //
    //         # Excel Headers
    //         $headers = [];
    //         array_push($headers,
    //             'Student No.',
    //             'Lastname',
    //             'Firstname',
    //             'Middlename',
    //             'Gender',
    //             'Level',
    //             'Degree',
    //             'Subject Code',
    //             'Subject Description',
    //             'Units',
    //             'Tuition Cost',
    //             'Other Fees',
    //             'Cost'
    //         );
    //         array_push($data, $headers);
    //         # Excel Header End
    //
    //         # Excel Data
    //         $enrollments = Enrollment::whereHas('studentRecord', function($query) use ($college, $degree){
    //
    //             $query->with([
    //                 'info',
    //                 'college',
    //                 'degree'
    //             ]);
    //
    //             if (!is_null($college))
    //             {
    //                 $query->where('college_id', $college);
    //             }
    //
    //             if (!is_null($degree))
    //             {
    //                 $query->where('degree_id', $degree);
    //             }
    //         })->with([
    //             'studentRecord.info',
    //             'studentRecord.college',
    //             'studentRecord.degree',
    //             'studentRecord.pds',
    //             'studentRecord.sresu',
    //             'scholarship',
    //             'orRecord.payments.myFund.fees',
    //             'details.course'
    //         ]);
    //
    //         $enrollments = $enrollments->where('pref_id', $this->pref->id)->get();
    //
    //         $tuitionFeeId = [1, 57, 70, 71, 88, 97, 107];
    //
    //         $enrollmentRecords = collect();
    //         foreach ($enrollments as $enrollment)
    //         {
    //             $enrAssessment = $enrAssess->getAssessment($enrollment->student_rec_id, $pref);
    //             $tuitionPerSub = 0;
    //             foreach ($enrAssessment['semFeesList'] as $assessment)
    //             {
    //                 if (in_array($assessment->fund_id, $tuitionFeeId))
    //                 {
    //                     $fee = Fee::where('fund_id', $assessment->fund_id)->first();
    //                     $tuitionPerSub = $fee->amount;
    //                     break;
    //                 }
    //             }
    //
    //             $enrDetails = $enrollment->details;
    //             $lecUnits = 0;
    //             $labUnits = 0;
    //
    //             if (is_null($enrollment->orRecord))
    //             {
    //                 foreach ($enrDetails as $enrDetail)
    //                 {
    //                     $course = $enrDetail->course;
    //
    //                     try {
    //                         $gender = ($enrollment->studentRecord->pds->gender == 1) ? 'M' : 'F';
    //                     } catch (\Exception $e) {
    //                         $gender = null;
    //                     }
    //
    //                     $enrRec = [
    //                         'studno' => $enrollment->studentRecord->sresu->student_number,
    //                         'lname' => $enrollment->studentRecord->info->surname,
    //                         'fname' => $enrollment->studentRecord->info->firstname,
    //                         'mname' => $enrollment->studentRecord->info->middlename,
    //                         'gender' => $gender,
    //                         'level' => $enrollment->standing,
    //                         'degree' => $enrollment->studentRecord->degree->abbr,
    //                         'code' => $course->code,
    //                         'desc' => $course->title,
    //                         'units' => $course->units,
    //                         'tuition' => $course->lecture_units * $tuitionPerSub,
    //                         'otherFees' => '',
    //                         'otherCost' => ''
    //                     ];
    //
    //                     array_push($data, $enrRec);
    //
    //                 }
    //
    //                 $enrollmentRecords->push($enrRec);
    //             }
    //             else
    //             {
    //                 $payments = $enrollment->orRecord->payments;
    //
    //                 if ($enrDetails->count() > $payments->count())
    //                 {
    //                     $paymentsArrCtr = $payments->count()-1;
    //                     foreach ($enrDetails as $enrDetail)
    //                     {
    //                         $course = $enrDetail->course;
    //
    //                         try {
    //                             $gender = ($enrollment->studentRecord->pds->gender == 1) ? 'M' : 'F';
    //                         } catch (\Exception $e) {
    //                             $gender = null;
    //                         }
    //
    //                         $enrRec = [
    //                             'studno' => $enrollment->studentRecord->sresu->student_number,
    //                             'lname' => $enrollment->studentRecord->info->surname,
    //                             'fname' => $enrollment->studentRecord->info->firstname,
    //                             'mname' => $enrollment->studentRecord->info->middlename,
    //                             'gender' => $gender,
    //                             'level' => $enrollment->standing,
    //                             'degree' => $enrollment->studentRecord->degree->abbr,
    //                             'code' => $course->code,
    //                             'desc' => $course->title,
    //                             'units' => $course->units,
    //                             'tuition' => $course->lecture_units * $tuitionPerSub,
    //                             'otherFees' => $paymentsArrCtr < 0 ? '' : $payments[$paymentsArrCtr]->myFund->fund_desc,
    //                             'otherCost' => $paymentsArrCtr < 0 ? '' : $payments[$paymentsArrCtr]->amount
    //                         ];
    //
    //                         array_push($data, $enrRec);
    //
    //                         $paymentsArrCtr--;
    //                     }
    //
    //                     $enrollmentRecords->push($enrRec);
    //                 }
    //                 else
    //                 {
    //                     $enrDetArrCtr = $enrDetails->count()-1;
    //
    //                     try {
    //                         $gender = ($enrollment->studentRecord->pds->gender == 1) ? 'M' : 'F';
    //                     } catch (\Exception $e) {
    //                         $gender = null;
    //                     }
    //
    //                     foreach ($payments as $payment)
    //                     {
    //                         $enrRec = [
    //                             'studno' => $enrollment->studentRecord->sresu->student_number,
    //                             'lname' => $enrollment->studentRecord->info->surname,
    //                             'fname' => $enrollment->studentRecord->info->firstname,
    //                             'mname' => $enrollment->studentRecord->info->middlename,
    //                             'gender' => $gender,
    //                             'level' => $enrollment->standing,
    //                             'degree' => $enrollment->studentRecord->degree->abbr,
    //                             'code' => $enrDetArrCtr < 0 ? '' : $enrDetails[$enrDetArrCtr]->course->code,
    //                             'desc' => $enrDetArrCtr < 0 ? '' : $enrDetails[$enrDetArrCtr]->course->title,
    //                             'units' => $enrDetArrCtr < 0 ? '' : $enrDetails[$enrDetArrCtr]->course->units,
    //                             'tuition' => $enrDetArrCtr < 0 ? '' : $enrDetails[$enrDetArrCtr]->course->lecture_units * $tuitionPerSub,
    //                             'otherFees' => $payment->myFund->fund_desc,
    //                             'otherCost' => $payment->amount
    //                         ];
    //
    //                         array_push($data, $enrRec);
    //
    //                         $enrDetArrCtr--;
    //                     }
    //
    //                     $enrollmentRecords->push($enrRec);
    //                 }
    //             }
    //
    //
    //         }
    //         # Excel Data End
    //
    //         # Generate Excel
    //         $file = 'acctg-subject-payment-masterlist-'.Auth::user()->id;
    //         \Maatwebsite\Excel\Facades\Excel::create($file, function($excel) use($data) {
    //
    //             $excel->sheet('Report', function($sheet) use($data) {
    //
    //                 $sheet->fromArray($data, null, 'A1', true, false);
    //
    //             });
    //
    //         })->store('xls', storage_path('app/public/excel'));
    //         # Generate Excel End
    //
    //         return response()->download('storage/excel/acctg-subject-payment-masterlist-'.Auth::user()->id.'.xls');
    //     }
    //     else
    //     {
    //         return back()->with('err', 'Please select at least college to generate');
    //     }
    //
    // }

    public function subjectPaymentGenerate(Request $request, Assessment $enrAssess, CADAssess $cadAssess)
    {
        //dd("this one");
        //return $request;
        ini_set('memory_limit', '-1');
        set_time_limit(-1);

        // set_time_limit(3600);
        ini_set('max_execution_time', 7200);

        $pref = $request->pref;

        //  dd($pref);
        $college = !is_null($request->college) ? $request->college : null;
        $degree = !is_null($request->degree) ? $request->degree : null;

        if (count($request->all()) > 0) {
            $data = [];

            # Excel Headers
            $headers = [];
            array_push(
                $headers,
                'Student No.',
                'Lastname',
                'Firstname',
                'Middlename',
                'Sex',
                'Level',
                'Degree',
                'Subject Code',
                'Subject Description',
                'Units',
                'Tuition Cost',
                'Other Fees',
                'Cost'
            );
            // return $headers;
            $paidFunds = Payment::whereHas('oRec', function ($query) use ($pref) {
                $query->where('pref_id', $pref);
            })->with(['myFund'])->groupBy('fund')->get();

            $fees_stack = [];
            foreach ($paidFunds as $fund) {
                array_push($fees_stack, $fund->fund);
                $fund_id = is_null($fund->myFund) ? '' : $fund->myFund->fund;

                array_push($headers, $fund_id . '|' . $fund->fund);
            }

            //to be deleted
            //array_push($headers, '644A1|56');
            //for local use only

            array_push($headers, 'FT', 'Scholarship', 'EnrAssess', 'CADAssess');
            //dd($headers);
            //array_push($data, $headers);
            # Excel Header End
            // dd($data);
            # Excel Data

            $enrollments = Enrollment::whereHas('studentRecord', function ($query) use ($college, $degree) {

                $query->with([
                    'info',
                    'college',
                    'degree'
                ]);

                if (!is_null($college)) {
                    $query->where('college_id', $college);
                }

                if (!is_null($degree)) {
                    $query->where('degree_id', $degree);
                }
            })->with([
                'studentRecord.info',
                'studentRecord.college',
                'studentRecord.degree',
                'studentRecord.pds',
                'studentRecord.sresu',
                'scholarship',
                'orRecord.payments.myFund.fees',
                'details.course'
            ]);

            $enrollments = $enrollments->where('pref_id', $pref)->get();
            //$enrollments = $enrollments->where('pref_id', $pref)->get()->chunk(100);

            // dd($enrollments);
            $tuitionFeeId = [1, 57, 70, 71, 88, 97, 107];

            // $enrollmentRecords = collect();
            foreach ($enrollments as $keyq => $enrollment) {
                $scholarship = !is_null($enrollment->scholarship) ? $enrollment->scholarship->scholarship : '-- NONE --';
                $freeTuition = $enrollment->free_tuition == 1 ? 'Yes' : 'No';

                $feesList = collect();
                // dd($feesList);
                # check enrollment

                try {
                    $enrAssessment = $enrAssess->getAssessment($enrollment->student_rec_id, $pref);
                } catch (\Exception $e) {
                    continue;
                }

                $enrTotal = 0;
                foreach ($enrAssessment['semFeesList'] as $enrFee) {
                    $feeArr = [
                        'fund_id' => $enrFee->fund_id,
                        'amount' => $enrFee->amount
                    ];
                    $feesList->push($feeArr);
                }

                $enrTotal = $enrAssessment['fullPayment'];

                $tuitionPerSub = 0;
                foreach ($enrAssessment['semFeesList'] as $assessment) {
                    if (in_array($assessment->fund_id, $tuitionFeeId)) {
                        $fee = Fee::where('fund_id', $assessment->fund_id)->first();
                        $tuitionPerSub = $fee->amount;
                        break;
                    }
                }

                $enrDetails = $enrollment->details;
                $lecUnits = 0;
                $labUnits = 0;

                if (is_null($enrollment->orRecord)) {
                    foreach ($enrDetails as $key => $enrDetail) {
                        $course = $enrDetail->course;

                        try {
                            $gender = ($enrollment->studentRecord->pds->sex == 1) ? 'M' : 'F';
                            //$gender = ($enrollment->studentRecord->pds->gender == 1) ? 'M' : 'F';
                        } catch (\Exception $e) {
                            $gender = null;
                        }

                        //dd($gender);

                        $enrRec = [
                            'studno' => $enrollment->studentRecord->sresu->student_number,
                            'lname' => $enrollment->studentRecord->info->surname,
                            'fname' => $enrollment->studentRecord->info->firstname,
                            'mname' => $enrollment->studentRecord->info->middlename,
                            'gender' => $gender,
                            'level' => $enrollment->standing,
                            'degree' => $enrollment->studentRecord->degree->abbr,
                            'code' => $course->code,
                            'desc' => $course->title,
                            'units' => $course->units,
                            'tuition' => $course->lecture_units * $tuitionPerSub,
                            'otherFees' => '',
                            'otherCost' => ''
                        ];
                        //   dd($enrRec);
                        if ($key == 0) {
                            # check CAD start
                            $cadTotal = 0;
                            $cadOrRecord = $enrollment->orRecords()->where('transaction_type', 2)->first();
                            if (!is_null($cadOrRecord)) {
                                $cadPayments = $cadOrRecord->payments;

                                foreach ($cadPayments as $cadPaid) {
                                    $feeArr = [
                                        'fund_id' => $cadPaid->fund,
                                        'amount' => $cadPaid->amount
                                    ];
                                    $feesList->push($feeArr);
                                }
                                $cadTotal = $cadOrRecord->amount;
                            }
                            # check CAD end

                            $paymentArr = [];

                            foreach ($feesList as $paid) {
                                $paymentArr[$paid['fund_id']] = ['amount' => $paid['amount']];
                            }

                            foreach ($fees_stack as $feeID) {
                                if (array_key_exists($feeID, $paymentArr)) {
                                    array_push($enrRec, (float) $paymentArr[$feeID]['amount']);
                                } else {
                                    array_push($enrRec, 0);
                                }
                            }

                            array_push($enrRec, $freeTuition, $scholarship, $enrTotal, $cadTotal);
                        }

                        array_push($data, $enrRec);
                    }
                    // dd($data);
                    // $enrollmentRecords->push($enrRec);
                } else {
                    //return  "i was here";
                    $payments = $enrollment->orRecord->payments;

                    if ($enrDetails->count() > $enrAssessment['semFeesList']->count()) {
                        $paymentsArrCtr = $enrAssessment['semFeesList']->count() - 1;
                        foreach ($enrDetails as $key => $enrDetail) {
                            if ($paymentsArrCtr > 0) {
                                if ($enrAssessment['semFeesList'][$paymentsArrCtr]->fund == "644") {
                                    continue;
                                }
                            }

                            $course = $enrDetail->course;

                            try {
                                $gender = ($enrollment->studentRecord->pds->sex == 1) ? 'M' : 'F';
                                // $gender = ($enrollment->studentRecord->pds->gender == 1) ? 'M' : 'F';
                            } catch (\Exception $e) {
                                $gender = null;
                            }

                            $enrRec = [
                                'studno' => $enrollment->studentRecord->sresu->student_number,
                                'lname' => $enrollment->studentRecord->info->surname,
                                'fname' => $enrollment->studentRecord->info->firstname,
                                'mname' => $enrollment->studentRecord->info->middlename,
                                'gender' => $gender,
                                'level' => $enrollment->standing,
                                'degree' => $enrollment->studentRecord->degree->abbr,
                                'code' => $course->code,
                                'desc' => $course->title,
                                'units' => $course->units,
                                'tuition' => $course->lecture_units * $tuitionPerSub,
                                'otherFees' => $paymentsArrCtr < 0 ? '' : $enrAssessment['semFeesList'][$paymentsArrCtr]->fund_desc,
                                'otherCost' => $paymentsArrCtr < 0 ? '' : $enrAssessment['semFeesList'][$paymentsArrCtr]->amount
                            ];

                            // dd($enrRec);

                            if ($key == 0) {
                                # check CAD start
                                $cadTotal = 0;
                                $cadOrRecord = $enrollment->orRecords()->where('transaction_type', 2)->first();
                                if (!is_null($cadOrRecord)) {
                                    $cadPayments = $cadOrRecord->payments;

                                    foreach ($cadPayments as $cadPaid) {
                                        $feeArr = [
                                            'fund_id' => $cadPaid->fund,
                                            'amount' => $cadPaid->amount
                                        ];
                                        $feesList->push($feeArr);
                                    }
                                    $cadTotal = $cadOrRecord->amount;
                                }
                                # check CAD end

                                $paymentArr = [];

                                foreach ($feesList as $paid) {
                                    $paymentArr[$paid['fund_id']] = ['amount' => $paid['amount']];
                                }

                                foreach ($fees_stack as $feeID) {
                                    if (array_key_exists($feeID, $paymentArr)) {
                                        array_push($enrRec, (float) $paymentArr[$feeID]['amount']);
                                    } else {
                                        array_push($enrRec, 0);
                                    }
                                }

                                array_push($enrRec, $freeTuition, $scholarship, $enrTotal, $cadTotal);
                            }

                            array_push($data, $enrRec);

                            $paymentsArrCtr--;
                        }

                        // $enrollmentRecords->push($enrRec);
                    } else {
                        // return "iwas here";
                        $enrDetArrCtr = $enrDetails->count() - 1;

                        try {
                            $gender = ($enrollment->studentRecord->pds->sex == 1) ? 'M' : 'F';
                            //  $gender = ($enrollment->studentRecord->pds->gender == 1) ? 'M' : 'F';
                        } catch (\Exception $e) {
                            $gender = null;
                        }

                        foreach ($enrAssessment['semFeesList'] as $key => $payment) {
                            if ($payment->fund == "644") {
                                continue;
                            }

                            $enrRec = [
                                'studno' => $enrollment->studentRecord->sresu->student_number,
                                'lname' => $enrollment->studentRecord->info->surname,
                                'fname' => $enrollment->studentRecord->info->firstname,
                                'mname' => $enrollment->studentRecord->info->middlename,
                                'gender' => $gender,
                                'level' => $enrollment->standing,
                                'degree' => $enrollment->studentRecord->degree->abbr,
                                'code' => $enrDetArrCtr < 0 ? '' : $enrDetails[$enrDetArrCtr]->course->code,
                                'desc' => $enrDetArrCtr < 0 ? '' : $enrDetails[$enrDetArrCtr]->course->title,
                                'units' => $enrDetArrCtr < 0 ? '' : $enrDetails[$enrDetArrCtr]->course->units,
                                'tuition' => $enrDetArrCtr < 0 ? '' : $enrDetails[$enrDetArrCtr]->course->lecture_units * $tuitionPerSub,
                                'otherFees' => $payment->fund_desc,
                                'otherCost' => $payment->amount
                                //'breakdown' => '0',
                            ];

                            //dd($enrRec);

                            //edited here
                            if ($key == 0) {
                                # check CAD start
                                $cadTotal = 0;
                                $cadOrRecord = $enrollment->orRecords()->where('transaction_type', 2)->first();
                                //$cadOrRecord = $enrollment->orRecords()->first();
                                //dd($cadOrRecord);
                                if (!is_null($cadOrRecord)) {
                                    $cadPayments = $cadOrRecord->payments;

                                    foreach ($cadPayments as $cadPaid) {
                                        $feeArr = [
                                            'fund_id' => $cadPaid->fund,
                                            'amount' => $cadPaid->amount
                                        ];
                                        $feesList->push($feeArr);
                                    }
                                    $cadTotal = $cadOrRecord->amount;
                                }
                                # check CAD end

                                $paymentArr = [];

                                foreach ($feesList as $paid) {
                                    $paymentArr[$paid['fund_id']] = ['amount' => $paid['amount']];
                                }

                                foreach ($fees_stack as $feeID) {
                                    if (array_key_exists($feeID, $paymentArr)) {
                                        array_push($enrRec, (float) $paymentArr[$feeID]['amount']);
                                    } else {
                                        array_push($enrRec, 0);
                                    }
                                }

                                array_push($enrRec, $freeTuition, $scholarship, $enrTotal, $cadTotal);
                            }
                            //
                            elseif (($key == 1)) {
                                # check CAD start
                                $cadTotal = 0;
                                $cadOrRecord = $enrollment->orRecords()->where('transaction_type', 2)->first();
                                //$cadOrRecord = $enrollment->orRecords()->first();
                                //dd($cadOrRecord);
                                if (!is_null($cadOrRecord)) {
                                    $cadPayments = $cadOrRecord->payments;

                                    foreach ($cadPayments as $cadPaid) {
                                        $feeArr = [
                                            'fund_id' => $cadPaid->fund,
                                            'amount' => $cadPaid->amount
                                        ];
                                        $feesList->push($feeArr);
                                    }
                                    $cadTotal = $cadOrRecord->amount;
                                }
                                # check CAD end

                                $paymentArr = [];

                                //  dd($feesList);

                                $pr = Preference::where('id', $pref)->first();

                                if ($pr->sem == 3) {
                                    #for GS
                                    if ($college == 1) {
                                        foreach ($feesList as $paid) {
                                            $paymentArr[$paid['fund_id']] = ['amount' => $paid['amount']];
                                        }
                                    }
                                    // else{
                                    //     foreach ($feesList as $paid) {
                                    //         $paymentArr[$paid['fund_id']] = ['amount' => $paid['amount']];
                                    //     }
                                    // }

                                    #for GS
                                } else {
                                    foreach ($feesList as $paid) {
                                        $paymentArr[$paid['fund_id']] = ['amount' => $paid['amount']];
                                    }
                                }


                                foreach ($fees_stack as $feeID) {
                                    if (array_key_exists($feeID, $paymentArr)) {
                                        array_push($enrRec, (float) $paymentArr[$feeID]['amount']);
                                    } else {
                                        array_push($enrRec, 0);
                                    }
                                }

                                array_push($enrRec, $freeTuition, $scholarship, $enrTotal, $cadTotal);
                            }
                            //

                            array_push($data, $enrRec);
                            //dd($data);
                            $enrDetArrCtr--;
                        }

                        // $enrollmentRecords->push($enrRec);
                    }
                }
            }
            //dd($headers, $data);
            # Excel Data End

            # Generate Excel
            $file = 'acctg-subject-payment-masterlist-' . Auth::user()->id;

            return Excel::download(new CombinedSubjectPaymentMasterlist($headers, $data), $file . '.xlsx');

            // # Generate Excel End


        } else {
            return back()->with('err', 'Please select at least college to generate');
        }
    }


    public function assessment()
    {

        $colleges = College::orderBy('college', 'desc')->pluck('college', 'id');

        $cys = Cy::orderBy('cy', 'desc')->pluck('cy', 'id');

        return view('registrar.reports.assessment', compact(
            'colleges',
            'courses',
            'enrollmentSummary',
            'cys'
        ));
    }

    public function assessmentGenerate(Request $request, Assessment $enrAssess, CADAssess $cadAssess)
    {
        $cy = $request->ay;
        $pref = $request->pref;
        $college = $request->college;
        $degree = !is_null($request->college) ? $request->degree : null;

        $data = [];

        # Excel Headers
        $headers = [];
        array_push(
            $headers,
            'Student No.',
            'Lastname',
            'Firstname',
            'Middlename',
            'Gender',
            'Level',
            'Degree',
            'Subject Code',
            'Subject Description',
            'Units',
            'Tuition Cost',
            'Fees',
            'Cost'
        );

        $funds = Fund::distinct()->select('fund')->where('fund', '!=', '')->get();

        $fees_stack = [];
        foreach ($funds as $fund) {
            array_push($fees_stack, $fund->fund);
            array_push($headers, $fund->fund);
        }

        array_push($headers, 'FT', 'Scholarship', 'EnrAssess');

        array_push($data, $headers);

        $enrollments = Enrollment::whereHas('studentRecord', function ($query) use ($college, $degree) {

            $query->with([
                'info',
                'college',
                'degree'
            ]);

            if (!is_null($college)) {
                $query->where('college_id', $college);
            }

            if (!is_null($degree)) {
                $query->where('degree_id', $degree);
            }
        })->with([
            'studentRecord.info',
            'studentRecord.college',
            'studentRecord.degree',
            'studentRecord.pds',
            'studentRecord.sresu',
            'scholarship',
            // 'orRecord.payments.myFund.fees',
            // 'details.curDetail',
            'details.course'
        ]);

        $enrollments = $enrollments->where('pref_id', $pref)->get();

        $tuitionFeeId = [1, 57, 70, 71, 88, 97, 107];

        foreach ($enrollments as $enrollment) {
            $scholarship = !is_null($enrollment->scholarship) ? $enrollment->scholarship->scholarship : '-- NONE --';
            $freeTuition = $enrollment->free_tuition == 1 ? 'Yes' : 'No';

            $feesList = collect();

            # check enrollment

            try {
                $enrAssessment = $enrAssess->getAssessment($enrollment->student_rec_id, $pref);
            } catch (\Exception $e) {
                continue;
            }

            $enrTotal = 0;
            foreach ($enrAssessment['semFeesList'] as $enrFee) {
                $feeArr = [
                    'fund' => $enrFee->fund,
                    'fund_id' => $enrFee->fund_id,
                    'amount' => $enrFee->amount
                ];
                $feesList->push($feeArr);
            }
            $enrTotal = $enrAssessment['fullPayment'];

            $tuitionPerSub = 0;
            foreach ($enrAssessment['semFeesList'] as $assessment) {
                if (in_array($assessment->fund_id, $tuitionFeeId)) {
                    $fee = Fee::where('fund_id', $assessment->fund_id)->first();
                    $tuitionPerSub = $fee->amount;
                    break;
                }
            }

            $enrDetails = $enrollment->details;
            $semFeesList = $enrAssessment['semFeesList'];
            $lecUnits = 0;
            $labUnits = 0;

            if ($enrDetails->count() > $semFeesList->count()) {
                $paymentsArrCtr = $semFeesList->count() - 1;
                foreach ($enrDetails as $key => $enlDetail) {
                    // if ($semFeesList[$paymentsArrCtr]->fund == "644") {
                    //     continue;
                    // }

                    $course = $enlDetail->course;

                    try {
                        $gender = ($enrollment->studentRecord->pds->gender == 1) ? 'M' : 'F';
                    } catch (\Exception $e) {
                        $gender = null;
                    }

                    $enlRecord = [
                        'studno' => $enrollment->studentRecord->sresu->student_number,
                        'lname' => is_null($enrollment->studentRecord->info) ? '' : $enrollment->studentRecord->info->surname,
                        'fname' => is_null($enrollment->studentRecord->info) ? '' : $enrollment->studentRecord->info->firstname,
                        'mname' => is_null($enrollment->studentRecord->info) ? '' : $enrollment->studentRecord->info->middlename,
                        'gender' => $gender,
                        'level' => $enrollment->standing,
                        'degree' => $enrollment->studentRecord->degree->abbr,
                        'code' => $course->code,
                        'desc' => $course->title,
                        'units' => $course->units,
                        'tuition' => $course->lecture_units * $tuitionPerSub,
                        'otherFees' => $paymentsArrCtr < 0 ? '' : $semFeesList[$paymentsArrCtr]->fund_desc,
                        'otherCost' => $paymentsArrCtr < 0 ? '' : $semFeesList[$paymentsArrCtr]->amount
                    ];

                    if ($key == 0) {
                        $assessmentArr = [];

                        foreach ($feesList as $fee) {
                            $assessmentArr[$fee['fund']] = ['amount' => $fee['amount']];
                        }

                        foreach ($fees_stack as $fund) {
                            if (array_key_exists($fund, $assessmentArr)) {
                                array_push($enlRecord, (float) $assessmentArr[$fund]['amount']);
                            } else {
                                array_push($enlRecord, 0);
                            }
                        }

                        array_push($enlRecord, $freeTuition, $scholarship, $enrTotal);
                    }

                    array_push($data, $enlRecord);

                    $paymentsArrCtr--;
                }
            } else {
                $enlDetArrCtr = $enrDetails->count() - 1;
                foreach ($semFeesList as $key => $payment) {
                    // if ($payment->fund == "644") {
                    //     continue;
                    // }

                    try {
                        $gender = ($enrollment->studentRecord->pds->gender == 1) ? 'M' : 'F';
                    } catch (\Exception $e) {
                        $gender = null;
                    }

                    try {
                        $test = $enrollment->studentRecord->info;
                        $test2 = $enrollment->studentRecord->sresu->student_number;
                    } catch (\Exception $e) {
                        dd($enrollment->studentRecord, $test);
                        // continue;
                    }


                    $enlRecord = [
                        'studno' => $enrollment->studentRecord->sresu->student_number,
                        'lname' => is_null($enrollment->studentRecord->info) ? '' : $enrollment->studentRecord->info->surname,
                        'fname' => is_null($enrollment->studentRecord->info) ? '' : $enrollment->studentRecord->info->firstname,
                        'mname' => is_null($enrollment->studentRecord->info) ? '' : $enrollment->studentRecord->info->middlename,
                        'gender' => $gender,
                        'level' => $enrollment->standing,
                        'degree' => $enrollment->studentRecord->degree->abbr,
                        'code' => $enlDetArrCtr < 0 ? '' : $enrDetails[$enlDetArrCtr]->course->code,
                        'desc' => $enlDetArrCtr < 0 ? '' : $enrDetails[$enlDetArrCtr]->course->title,
                        'units' => $enlDetArrCtr < 0 ? '' : $enrDetails[$enlDetArrCtr]->course->units,
                        'tuition' => $enlDetArrCtr < 0 ? '' : $enrDetails[$enlDetArrCtr]->course->lecture_units * $tuitionPerSub,
                        'otherFees' => $payment->fund_desc,
                        'otherCost' => $payment->amount
                    ];

                    if ($key == 0) {
                        $assessmentArr = [];

                        foreach ($feesList as $fee) {
                            $assessmentArr[$fee['fund']] = ['amount' => $fee['amount']];
                        }

                        foreach ($fees_stack as $fund) {
                            if (array_key_exists($fund, $assessmentArr)) {
                                array_push($enlRecord, (float) $assessmentArr[$fund]['amount']);
                            } else {
                                array_push($enlRecord, 0);
                            }
                        }

                        array_push($enlRecord, $freeTuition, $scholarship, $enrTotal);
                    }

                    array_push($data, $enlRecord);

                    $enlDetArrCtr--;
                }
            }

            # Generate Excel
            $file = 'acctg-assessment-masterlist-' . Auth::user()->id;
            \Maatwebsite\Excel\Facades\Excel::create($file, function ($excel) use ($data) {

                $excel->sheet('Report', function ($sheet) use ($data) {

                    $sheet->fromArray($data, null, 'A1', true, false);
                });
            })->store('xls', storage_path('app/public/excel'));
            # Generate Excel End

            return response()->download("storage/excel/{$file}.xls");
        }
    }

    public function adc()
    {

        $colleges = College::orderBy('college', 'desc')->pluck('college', 'id');

        $cys = Cy::orderBy('cy', 'desc')->pluck('cy', 'id');

        return view('registrar.reports.adc', compact(
            'colleges',
            'cys'
        ));
    }

    public function adcGenerate(Request $request, CADAssess $cadAssess)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(-1);

        // set_time_limit(3600);
        ini_set('max_execution_time', 7200);

        $cy = $request->ay;
        $pref = $request->pref;
        $college = $request->college;
        $degree = !is_null($request->college) ? $request->degree : null;

        $cad = CAD::whereHas('record', function ($query) use ($college, $degree) {

            $query->with([
                'info',
                'college',
                'degree'
            ]);

            if (!is_null($college)) {
                $query->where('college_id', $college);
            }

            if (!is_null($degree)) {
                $query->where('degree_id', $degree);
            }
        })->with([
            'record.info',
            'record.college',
            'record.degree',
            'record.pds',
            'record.sresu',
            'cad_details',
            'cad_details.course'
            // 'scholarship'
            // 'orRecord.payments.myFund.fees',
            // 'details.course'
        ]);

        $data = [];

        # Excel Headers
        $headers = [];
        array_push(
            $headers,
            'Student No.',
            'Lastname',
            'Firstname',
            'Middlename',
            'Gender',
            'Level',
            'Degree'
        );

        $funds = Fund::distinct()->select('fund')->where('fund', '!=', '')->get();

        $fees_stack = [];
        foreach ($funds as $fund) {
            array_push($fees_stack, $fund->fund);
            array_push($headers, $fund->fund);
        }

        array_push($headers, 'FT', 'Scholarship', 'CadAssess', 'FreeEducationAssess', 'UnitsAdded', 'UnitsChanged', 'UnitsDropped', 'ACD Date', 'ACD Approve Date');

        //array_push($data, $headers);

        $cadlist  = $cad->where('pref_id', $pref)->get();
        //return $cadlist;

        foreach ($cadlist as $key => $cad) {

            $date_create = date('F d, Y', strtotime($cad->created_at));
            $date_app = date('F d, Y', strtotime($cad->updated_at));

            $units_dropped = 0;
            $units_changed = 0;
            $units_added = 0;

            foreach ($cad->cad_details as $cad_detail) {
                if ($cad_detail->type == 1) { //dropped
                    $units_dropped += $cad_detail->course->units;
                }
                if ($cad_detail->type == 2) { // changed
                    $units_changed += $cad_detail->course->units;
                }
                if ($cad_detail->type == 3) { // added
                    $units_added += $cad_detail->course->units;
                }
            }

            $scholarship = !is_null($cad->scholarship) ? $cad->scholarship->scholarship : '-- NONE --';
            $freeTuition = $cad->free_tuition == 1 ? 'Yes' : 'No';

            try {
                $assessment = $cadAssess->getAssessment($cad->id);
                if ($assessment->totalAmount == 0) {
                    continue;
                }
            } catch (\Exception $e) {
                continue;
            }

            try {
                $gender = ($cad->record->pds->gender == 1) ? 'M' : 'F';
            } catch (\Exception $e) {
                $gender = null;
            }

            try {
                $studno = $cad->record->sresu->student_number;
            } catch (\Exception $e) {
                $studno = $cad->record->id;
            }

            $record = [
                'studno' => $studno,
                'lname' => is_null($cad->record->info) ? '' : $cad->record->info->surname,
                'fname' => is_null($cad->record->info) ? '' : $cad->record->info->firstname,
                'mname' => is_null($cad->record->info) ? '' : $cad->record->info->middlename,
                'gender' => $gender,
                'level' => $cad->standing,
                'degree' => $cad->record->degree->abbr
            ];

            $assessmentArr = [];

            $feesList = array_merge($assessment->feesList->toArray(), $assessment->coveredList->toArray());
            foreach ($feesList as $fee) {
                $assessmentArr[$fee->fund] = ['amount' => $fee->amount];
            }

            foreach ($fees_stack as $fund) {
                if (array_key_exists($fund, $assessmentArr)) {
                    array_push($record, (float) $assessmentArr[$fund]['amount']);
                } else {
                    array_push($record, 0);
                }
            }

            array_push($record, $freeTuition, $scholarship, $assessment->totalAmount, $assessment->coveredAmount, $units_added, $units_changed, $units_dropped, $date_create, $date_app);

            array_push($data, $record);
        }
        //return $data;
        # Generate Excel
        $file = 'acctg-acd-assessment-masterlist-' . Auth::user()->id;
        return Excel::download(new ADCAssessmentMasterlist($headers, $data), $file . '.xlsx');
        // \Maatwebsite\Excel\Facades\Excel::create($file, function ($excel) use ($data) {

        //     $excel->sheet('Report', function ($sheet) use ($data) {

        //         $sheet->fromArray($data, null, 'A1', true, false);
        //     });
        // })->store('xls', storage_path('app/public/excel'));
        # Generate Excel End

        return response()->download("storage/excel/{$file}.xls");
    }

    public function adcSubjectPayment()
    {

        $colleges = College::orderBy('college', 'desc')->pluck('college', 'id');

        $cys = Cy::orderBy('cy', 'desc')->pluck('cy', 'id');

        return view('registrar.reports.adc-subject-payment', compact(
            'colleges',
            'cys'
        ));
    }

    public function adcSubjectPaymentGenerate(Request $request, CADAssess $cadAssess)
    {
        $cy = $request->ay;
        $pref = $request->pref;
        $college = $request->college;
        $degree = !is_null($request->college) ? $request->degree : null;

        $cad = CAD::whereHas('record', function ($query) use ($college, $degree) {

            $query->with([
                'info',
                'college',
                'degree'
            ]);

            if (!is_null($college)) {
                $query->where('college_id', $college);
            }

            if (!is_null($degree)) {
                $query->where('degree_id', $degree);
            }
        })->with([
            'record.info',
            'record.college',
            'record.degree',
            'record.pds',
            'record.sresu',
            'cad_details',
            'cad_details.course'
            // 'scholarship'
            // 'orRecord.payments.myFund.fees',
            // 'details.course'
        ]);


        $data = [];

        # Excel Headers
        $headers = [];
        array_push(
            $headers,
            'Student No.',
            'Lastname',
            'Firstname',
            'Middlename',
            'Gender',
            'Level',
            'Degree',
            'Subject Code',
            'Subject Description',
            'Units',
            'Tuition Cost',
            'Other Fees',
            'Cost'
        );

        $funds = Fund::distinct()->select('fund')->where('fund', '!=', '')->get();

        $fees_stack = [];
        foreach ($funds as $fund) {
            array_push($fees_stack, $fund->fund);
            array_push($headers, $fund->fund);
        }

        array_push($headers, 'FT', 'Scholarship', 'CadAssess', 'FreeEducationAssess', 'UnitsAdded', 'UnitsChanged', 'UnitsDropped');
        //return $headers;
        //array_push($data, $headers);

        $cadlist  = $cad->where('pref_id', $pref)->get();
        //return $cadlist;
        $tuitionFeeId = [1, 57, 70, 71, 88, 97, 107, 136];

        foreach ($cadlist as $key => $cad) {

            $units_dropped = 0;
            $units_changed = 0;
            $units_added = 0;

            foreach ($cad->cad_details as $cad_detail) {
                if ($cad_detail->type == 1) { //dropped
                    $units_dropped += $cad_detail->course->units;
                }
                if ($cad_detail->type == 2) { // changed
                    $units_changed += $cad_detail->course->units;
                }
                if ($cad_detail->type == 3) { // added
                    $units_added += $cad_detail->course->units;
                }
            }

            $scholarship = !is_null($cad->scholarship) ? $cad->scholarship->scholarship : '-- NONE --';
            $freeTuition = $cad->free_tuition == 1 ? 'Yes' : 'No';

            try {
                //$assessment = $cadAssess->assess($cad->id);
                $assessment = $cadAssess->getAssessment($cad->id);
                //dd($assessment);
                //dd($assessment['totalAmount']);
                if ($assessment->totalAmount == 0) {
                    continue;
                }
            } catch (\Exception $e) {
                continue;
            }

            try {
                $gender = ($cad->record->pds->gender == 1) ? 'M' : 'F';
            } catch (\Exception $e) {
                $gender = null;
            }

            try {
                $studno = $cad->record->sresu->student_number;
            } catch (\Exception $e) {
                $studno = $cad->record->id;
            }

            $record = [
                'studno' => $studno,
                'lname' => is_null($cad->record->info) ? '' : $cad->record->info->surname,
                'fname' => is_null($cad->record->info) ? '' : $cad->record->info->firstname,
                'mname' => is_null($cad->record->info) ? '' : $cad->record->info->middlename,
                'gender' => $gender,
                'level' => $cad->standing,
                'degree' => $cad->record->degree->abbr
            ];

            $assessmentArr = [];
            $otherfees = [];
            //dd($assessment->feesList);

            $tuitionPerSub = 0;
            $feesList = array_merge($assessment->feesList->toArray(), $assessment->coveredList->toArray());
            foreach ($feesList as $fee) {
                $assessmentArr[$fee->fund] = ['amount' => $fee->amount];

                if (in_array($fee->fund_id, $tuitionFeeId)) {
                    $fee = Fee::where('fund_id', $fee->fund_id)->first();
                    $tuitionPerSub = $fee->amount;
                } else {
                    array_push($otherfees, [
                        'otherfees' => $fee->fund_desc,
                        'cost' => $fee->amount
                    ]);
                }
            }

            $cad_details = $cad->cad_details()->whereIn('type', [2, 3])->get();
            $courses = [];
            if (!is_null($cad_details)) {
                foreach ($cad_details as $key => $detail) {
                    array_push($courses, [
                        'scode' => $detail->course->code,
                        'sdescription' => $detail->course->description,
                        'units' => $detail->course->units,
                        'tuition_cost' => $tuitionPerSub * $detail->course->units
                    ]);
                }
            }

            if (count($courses) > count($otherfees)) {
                $counter = 0;
                $coursefees = [];
                foreach ($courses as $courseskey => $course) {
                    $temp = [];
                    $temp_record = [];
                    if ($counter < count($otherfees)) {
                        $temp = array_merge($course, $otherfees[$counter]);
                    } else {
                        $temp = array_merge($course, [
                            'otherfees' => '',
                            'cost' => ''
                        ]);
                    }
                    // array_push($coursefees, $temp);
                    // array_push($record, $temp);
                    $temp_record = array_merge($record, $temp);

                    if ($courseskey == 0) {
                        foreach ($fees_stack as $fund) {
                            if (array_key_exists($fund, $assessmentArr)) {
                                array_push($temp_record, (float) $assessmentArr[$fund]['amount']);
                            } else {
                                array_push($temp_record, 0);
                            }
                        }

                        array_push($temp_record, $freeTuition, $scholarship, $assessment->totalAmount, $assessment->coveredAmount, $units_added, $units_changed, $units_dropped);
                    }

                    array_push($data, $temp_record);
                    $counter++;
                }
            } else {
                $counter = 0;
                $coursefees = [];
                foreach ($otherfees as $otherfeekey => $otherfee) {
                    $temp = [];
                    $temp_record = [];
                    if ($counter < count($courses)) {
                        $temp = array_merge($courses[$counter], $otherfee);
                    } else {
                        $temp = array_merge([
                            'scode' => '',
                            'sdescription' => '',
                            'units' => '',
                            'tuition_cost' => ''
                        ], $otherfee);
                    }
                    // array_push($coursefees, $temp);
                    // array_push($record, $temp);
                    $temp_record = array_merge($record, $temp);

                    if ($otherfeekey == 0) {
                        foreach ($fees_stack as $fund) {
                            if (array_key_exists($fund, $assessmentArr)) {
                                array_push($temp_record, (float) $assessmentArr[$fund]['amount']);
                            } else {
                                array_push($temp_record, 0);
                            }
                        }

                        array_push($temp_record, $freeTuition, $scholarship, $assessment->totalAmount, $assessment->coveredAmount, $units_added, $units_changed, $units_dropped);
                    }

                    array_push($data, $temp_record);
                    $counter++;
                }
            }

            // array_push($data, $record);

        }
        //return $data;
        # Generate Excel
        $file = 'acctg-acd-subject-payment-masterlist-' . Auth::user()->id;
        return Excel::download(new ADCSubjectPaymentMasterlist($headers, $data), $file . '.xlsx');
        // \Maatwebsite\Excel\Facades\Excel::create($file, function ($excel) use ($data) {

        //     $excel->sheet('Report', function ($sheet) use ($data) {

        //         $sheet->fromArray($data, null, 'A1', true, false);
        //     });
        // })->store('xls', storage_path('app/public/excel'));
        // # Generate Excel End

        // return response()->download("storage/excel/{$file}.xls");
    }

    public function unpaidEnlistment()
    {
        $colleges = College::orderBy('college', 'desc')->pluck('college', 'id');

        $cys = Cy::orderBy('cy', 'desc')->pluck('cy', 'id');

        return view('registrar.reports.unpaid-enlistment', compact(
            'colleges',
            'cys'
        ));
    }

    public function unpaidEnlistmentGenerate(Request $request, Assessment $enrAssess)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(-1);
        if (count($request->all()) > 0) {

            $data = [];
            $headers = [];
            array_push(
                $headers,
                [
                    'Student No.',
                    'Lastname',
                    'Firstname',
                    'Middlename',
                    'Degree',
                    'Free Tuition',
                    'Assessment',
                    'Enlistment Status',
                    'Enlistment date',
                    'Enlistment Approve'
                ]
            );

            $lists = DB::table('dbiusis16.enlisted')
                ->join('dbiusis16.enrollments', 'enlisted.student_rec_id', '=', 'enrollments.student_rec_id', 'left outer')
                ->join('dbiusis16.student_records', 'student_records.id', '=', 'enlisted.student_rec_id')
                ->join('dbiusis16.student_info', 'student_records.student_id', '=', 'student_info.student_id')
                ->join('dbiusis16.degrees', 'degrees.id', '=', 'student_records.degree_id')
                ->whereNull('enrollments.student_rec_id')
                ->where('enlisted.pref_id', '=', $request->pref)
                ->when(!is_null($request->college), function ($query) use ($request) {
                    $query->where('degrees.college_id', $request->college);
                })
                ->select(
                    'enlisted.student_rec_id',
                    'student_info.student_number',
                    'student_info.surname',
                    'student_info.firstname',
                    'student_info.middlename',
                    'degrees.degree',
                    'enlisted.free_tuition',
                    'enlisted.enlistment_status',
                    'enlisted.created_at AS enlisted_date',
                    'enlisted.updated_at AS approve_date'
                )
                ->get();

            foreach ($lists as $list) {
                try {
                    $enrAssessment = $enrAssess->getAssessment($list->student_rec_id, $request->pref);
                    $freeTuition = $list->free_tuition == 1 ? 'Yes' : 'No';
                    $enlistment_status = '';
                    switch ($list->enlistment_status) {
                        case 0:
                            $enlistment_status = 'not submitted for approval';
                            break;
                        case 1:
                            $enlistment_status = 'submitted to faculty for approval';
                            break;
                        case 2:
                            $enlistment_status = 'finalized';
                            break;
                        case 3:
                            $enlistment_status = 'returned to student';
                            break;
                        default:
                            $enlistment_status = 'oops';
                            break;
                    }

                    array_push($data, [
                        $list->student_number,
                        $list->surname,
                        $list->firstname,
                        $list->middlename,
                        $list->degree,
                        $freeTuition,
                        $enrAssessment['fullPayment'],
                        $enlistment_status,
                        date('F d, Y', strtotime($list->enlisted_date)),
                        ($list->enlistment_status == 2) ? date('F d, Y', strtotime($list->approve_date)) : ''
                    ]);
                } catch (\Exception $e) {
                    continue;
                }
            }
            //return $data;
            # Generate Excel
            $file = 'unpaid-enlistment-' . Auth::user()->id;
            return Excel::download(new UnpaidEnlistment($headers, $data), $file . '.xlsx');
            // \Maatwebsite\Excel\Facades\Excel::create($file, function ($excel) use ($data) {

            //     $excel->sheet('Report', function ($sheet) use ($data) {

            //         $sheet->fromArray($data, null, 'A1', true, false);
            //     });
            // })->store('xls', storage_path('app/public/excel'));
            // # Generate Excel End

            // return response()->download('storage/excel/unpaid-enlistment-' . Auth::user()->id . '.xls');
        } else {
            return back()->with('err', 'Please select at least college to generate');
        }
    }

    public function unpaidAdc()
    {
        $colleges = College::orderBy('college', 'desc')->pluck('college', 'id');

        $cys = Cy::orderBy('cy', 'desc')->pluck('cy', 'id');

        return view('registrar.reports.unpaid-adc', compact(
            'colleges',
            'cys'
        ));
    }

    public function unpaidAdcGenerate(Request $request, CADAssess $cadAssess)
    {
        if (count($request->all()) > 0) {

            $data = [];
            $headers = [];
            array_push(
                $headers,
                [
                    'Student No.',
                    'Lastname',
                    'Firstname',
                    'Middlename',
                    'Degree',
                    'Free Tuition',
                    'Assessment',
                    'Units',
                    'ADC Status',
                    'ACD Date',
                    'ACD Date Approve'
                ]
            );

            $lists = CAD::join('student_records', 'student_records.id', '=', 'cad.student_rec_id')
                //   ->join('dbiusis16.cad_details', 'cad.id', '=', 'cad_details.dac_id')
                //   ->join('dbiusis16.courses', 'cad_details.course_id', '=', 'courses.id')
                ->join('student_info', 'student_records.student_id', '=', 'student_info.student_id')
                ->join('degrees', 'degrees.id', '=', 'student_records.degree_id')
                ->where('cad.pref_id', '=', $request->pref)
                ->whereNotIn('dac_status', [5])
                ->when(!is_null($request->college), function ($query) use ($request) {
                    $query->where('degrees.college_id', $request->college);
                })

                ->select(
                    'cad.id',
                    'cad.student_rec_id',
                    'student_info.student_number',
                    'student_info.surname',
                    'student_info.firstname',
                    'student_info.middlename',
                    'degrees.degree',
                    'cad.free_tuition',
                    'cad.dac_status',
                    'cad.created_at AS enlisted_date',
                    'cad.updated_at AS approve_date'
                    //'courses.units'
                    //'cad_details.course_id'
                )

                ->get();

            //  return $lists;

            foreach ($lists as $list) {
                // try {
                $total_unit = 0;
                foreach ($list->cad_details as $unit) {
                    $total_unit = $total_unit + $unit->course->units;
                }

                $cadAssessment = $cadAssess->getAssessment($list->id);
                $freeTuition = $list->free_tuition == 1 ? 'Yes' : 'No';
                $dac_status = '';
                switch ($list->dac_status) {
                    case 0:
                        $dac_status = 'newly created';
                        break;
                    case 1:
                        $dac_status = 'sent for approval';
                        break;
                    case 2:
                        $dac_status = 'approved for printing';
                        break;
                    case 3:
                        $dac_status = 'finalized';
                        break;
                    case 4:
                        $dac_status = 'revoked';
                        break;
                    default:
                        $dac_status = 'oops';
                        break;
                }

                array_push($data, [
                    $list->student_number,
                    $list->surname,
                    $list->firstname,
                    $list->middlename,
                    $list->degree,
                    $freeTuition,
                    $cadAssessment->totalAmount,
                    $total_unit,
                    $dac_status,
                    date('F d, Y', strtotime($list->enlisted_date)),
                    date('F d, Y', strtotime($list->approve_date))
                ]);
                // } catch (\Exception $e) {
                //    continue;
                // }
            }
            //return $data;
            # Generate Excel
            $file = 'unpaid-adc-' . Auth::user()->id;
            return Excel::download(new UnpaidADC($headers, $data), $file . '.xlsx');
            // \Maatwebsite\Excel\Facades\Excel::create($file, function ($excel) use ($data) {

            //     $excel->sheet('Report', function ($sheet) use ($data) {

            //         $sheet->fromArray($data, null, 'A1', true, false);
            //     });
            // })->store('xls', storage_path('app/public/excel'));
            // # Generate Excel End

            return response()->download('storage/excel/unpaid-adc-' . Auth::user()->id . '.xls');
        } else {
            return back()->with('err', 'Please select at least college to generate');
        }
    }
}
