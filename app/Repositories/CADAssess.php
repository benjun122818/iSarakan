<?php

namespace App\Repositories;

use App\CAD;
use App\Curricula;
use App\Course as Courses;
use App\Enrollment as Enrollments;
use App\EnrollmentDetail as EnrollmentDetails;
use App\Fund;
use App\Preference;
use App\Scholarship;
use App\StudentInfo as Student;
use App\StudentRecord;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

class CADAssess
{
    private $srecord;
    private $cad;

    public function assess($cad_id)
    {
        $client = new Client();

        $result = $client->request(
            'GET',
            config('constants.api_uri') . 'cad-assessment/' . $cad_id,
            [
                'headers' => [
                    'accept' => 'application/json',
                    'authorization' => "Bearer " . config('constants.api_token')
                ]
            ]
        );

        $resultDecode = json_decode((string) $result->getBody(), true);

        return (object) $resultDecode;
    }

    public function getAssessment($cad_id)
    {

        $this->cad = CAD::find($cad_id);
        $this->srecord = StudentRecord::where('id', $this->cad->student_rec_id)->orderBy('ay_id', 'desc')->first();
        $enrollment_id = Enrollments::where('student_rec_id', $this->srecord->id)
            ->where('pref_id', $this->cad->pref_id)
            ->where('deleted_at', null)
            ->first()->id;
        $enrollment_details = EnrollmentDetails::where('enrollment_id', $enrollment_id)->get();
        $transaction_count = $this->cad->cad_details->count();

        $cad_course_ids = collect([]);
        $enl_course_ids = collect([]);

        $dropped_changed = collect([]);
        $changed_new = collect([]);
        $added = collect([]);

        foreach ($this->cad->cad_details as $key => $cad_detail) {
            // $enrollment_detail = EnrollmentDetails::find($cad_detail->original);
            $enrollment_detail = DB::table('enrollment_details')->where('id', $cad_detail->original)->first();
            $enl_detail = is_null($enrollment_detail) ? null : $enrollment_detail;

            if ($cad_detail->type == 1) { // Dropped
                if (!is_null($enl_detail)) {
                    $enl_course_ids->push($enl_detail->course_id);

                    $dropped_changed->push((object) [
                        'course_id' => $enl_detail->course_id,
                        'sched_id' => $enl_detail->sched_id,
                        'type' => 1
                    ]);
                }
            } else if ($cad_detail->type == 2) {    // Changed
                if (!is_null($enl_detail)) {
                    $enl_course_ids->push($enl_detail->course_id);
                    $dropped_changed->push((object) [
                        'course_id' => $enl_detail->course_id,
                        'sched_id' => $enl_detail->sched_id,
                        'type' => 2
                    ]);
                }

                $cad_course_ids->push($cad_detail->course_id);
                $changed_new->push((object) [
                    'course_id' => $cad_detail->course_id,
                    'sched_id' => $cad_detail->sched_id,
                    'type' => 4
                ]);
            } else if ($cad_detail->type == 3) {    // Added
                $cad_course_ids->push($cad_detail->course_id);

                $added->push((object) [
                    'course_id' => $cad_detail->course_id,
                    'sched_id' => $cad_detail->sched_id,
                    'type' => 3
                ]);
            }
        }

        $enlisted_new = $this->getNewEnlistment($enrollment_details, $dropped_changed, $changed_new, $added);

        $cad_units = $this->units($this->cad->pref_id, $cad_course_ids);
        $enl_units = $this->units($this->cad->pref_id, $enl_course_ids);

        $cad_feedetaillist = $this->getFeeDetailsList($cad_course_ids, $cad_units, 1);
        $enl_feedetaillist = $this->getFeeDetailsList($enl_course_ids, $enl_units, 0);

        $cad_feelist = $this->getFeeList($cad_units, $cad_feedetaillist);
        $enl_feelist = $this->getFeeList($enl_units, $enl_feedetaillist);

        $new_list = collect([]);
        if ($enl_feelist->isEmpty()) {
            foreach ($cad_feelist as $cad_feelist_key => $cad_item) {
                $new_list->push((object) [
                    'fund_id' => $cad_item->fund_id,
                    'fund' => $cad_item->fund,
                    'fund_desc' => $cad_item->fund_desc,
                    'amount' => $cad_item->amount
                ]);
            }
        } else {
            foreach ($enl_feelist as $enl_feelist_key => $enl_item) {
                // $count = 0;
                foreach ($cad_feelist as $cad_feelist_key => $cad_item) {
                    if ($enl_item->fund == $cad_item->fund) {
                        // $count += 1;

                        $count2 = 0;
                        foreach ($new_list as $new_list_key => $new_item) {
                            if ($new_item->fund == $cad_item->fund) {
                                // $count2 += 1;
                                // $new_item->amount = abs($enl_item->amount - $cad_item->amount);
                                $new_amount = $enl_item->amount - $cad_item->amount;
                                if ($new_amount < 0) {
                                    $count2 += 1;
                                    $new_item->amount = abs($new_amount);
                                }
                            }
                        }

                        if ($count2 == 0) {
                            // $new_list->push((object) [
                            //     'fund_id' => $enl_item->fund_id,
                            //     'fund' => $enl_item->fund,
                            //     'fund_desc' => $enl_item->fund_desc,
                            //     'amount' => abs($enl_item->amount - $cad_item->amount)
                            // ]);
                            $new_amount = $enl_item->amount - $cad_item->amount;
                            if ($new_amount < 0) {
                                $new_list->push((object) [
                                    'fund_id' => $enl_item->fund_id,
                                    'fund' => $enl_item->fund,
                                    'fund_desc' => $enl_item->fund_desc,
                                    'amount' => abs($new_amount)
                                ]);
                            }
                        }
                    } else {
                        $count3 = 0;
                        foreach ($new_list as $key => $new_item) {
                            if ($new_item->fund == $cad_item->fund) {
                                $count3 += 1;
                            }
                        }

                        if ($count3 == 0) {
                            $new_list->push((object) [
                                'fund_id' => $cad_item->fund_id,
                                'fund' => $cad_item->fund,
                                'fund_desc' => $cad_item->fund_desc,
                                'amount' => $cad_item->amount
                            ]);
                        }
                    }
                }
            }
        }

        $feesList = collect([]);
        $totalAmount = 0;

        $coveredList = collect([]);
        $coveredAmount = 0;

        foreach ($new_list as $key => $value) {
            if ($value->amount != (float) 0.0) {
                if ($value->fund == '644B') {
                    $college = $this->srecord->college_id;

                    $amount = ($college == 1) ? 75 : $value->amount;

                    $value->amount = $amount * $transaction_count;
                }

                $covered = $this->coveredFreeEducation($value->fund);

                if (!$covered) {
                    $feesList->push($value);
                    $totalAmount += (float) $value->amount;
                } else {
                    $coveredList->push($value);
                    $coveredAmount += (float) $value->amount;
                }
            }
        }

        $arr = [
            'schedules' => $enlisted_new['schedules'],
            'units' => $enlisted_new['units'],
            'feesList' => $feesList,
            'totalAmount' => $totalAmount,
            'coveredList' => $coveredList,
            'coveredAmount' => $coveredAmount,
            'courses_dropped_changed' => $this->getCourseCodes($dropped_changed),
            'courses_added' => $this->getCourseCodes($added->merge($changed_new))
        ];

        return (object) $arr;
    }

    public function units($pref, $course_ids, $type = null)
    {
        $units = 0;
        $complab = 0;
        $totalnoncomplab = 0;
        $noncomplab = 0;
        $physcilab = 0;
        $physcilab_minor = 0;
        $medlab = 0;
        $labwithoutfee = 0;
        $fieldstudy = 0;
        $undergrad_units = 0;
        $incurriculum = 0;

        $curriculum = $this->getCurriculum($this->srecord->curricula_id);

        foreach ($course_ids as $key => $value) {
            $course_detail = DB::table('dbiusis16.courses')
                ->leftJoin('dbiusis16.course_types', 'courses.course_type_id', '=', 'course_types.id')
                ->where('courses.id', '=', $value)
                ->first();

            $units += (float) $course_detail->units;

            // for law students
            if ($this->srecord->degree_id == 74) {
                $undergrad_courses = [3152, 4813, 894, 896, 4065, 4785, 2442, 5915, 4806, 4811, 7178, 5907, 4815, 5908, 6296, 6237];
                if (in_array($value, $undergrad_courses)) {
                    $undergrad_units += $course_detail->units;
                } else {
                    $incurriculum += $course_detail->units;
                }
            }

            if ($course_detail->lab_unit != 0) {
                if ($course_detail->course_type_id == 2 || $course_detail->course_type_id == 3 || $course_detail->course_type_id == 24) {
                    $complab += (float) $course_detail->lab_unit;
                } else {

                    $fund = Fund::where('course_type_id', $course_detail->course_type_id)->first();
                    if ($fund != null) {
                        if ($fund->fund != null || $fund->fund != '') {
                            $totalnoncomplab += (float) $course_detail->lab_unit;

                            if ($this->srecord->college_id == 11) {
                                $medlab += (float) $course_detail->lab_unit;
                            } else {
                                if ($course_detail->course_type_id == 20) {
                                    $physcilab += (float) $course_detail->lab_unit;
                                } else {
                                    if ($course_detail->course_type_id != 4) {
                                        $noncomplab += (float) $course_detail->lab_unit;
                                    }
                                }

                                if ($course_detail->course_type_id == 29) {
                                    $physcilab_minor += (float) $course_detail->lab_unit;
                                }
                            }

                            if ($course_detail->computation != null && $course_detail->computation == 0) {
                                $labwithoutfee += (float) $course_detail->lab_unit;
                            }
                        }
                    }
                }
            } else {
                if ($course_detail->course_type_id == 27) { // FIELD STUDY
                    $fieldstudy += $course_detail->lecture_units;
                }
            }
        }

        $arr = [
            'totalunits' => $units,
            'complab' => $complab,
            'totalnoncomplab' => $totalnoncomplab,
            'noncomplab' => $noncomplab,
            'physcilab' => $physcilab,
            'physcilab_minor' => $physcilab_minor,
            'medlab' => $medlab,
            'labwithoutfee' => $labwithoutfee,
            'fieldstudy' => $fieldstudy,
            'undergrad_units' => $undergrad_units,
            'incurriculum' => $incurriculum
        ];

        return (object) $arr;
    }


    public function getFeeDetailsList($course_ids, $units, $type)
    {

        if ($units->totalunits != 0) {

            $college = $this->srecord->college_id;
            $degree = $this->srecord->degree_id;
            $degreetype = $this->srecord->degree->type;

            $sem = Preference::find($this->cad->pref_id)->sem;

            $courseTypeIds = collect([]);
            foreach ($course_ids as $key => $value) {
                $course = Courses::find($value);
                if (!is_null($course)) {
                    if ($course->course_type_id != 0) {
                        $courseTypeIds->push($course->course_type_id);
                    }
                }
            }

            $cadFee = $type ? Fund::where('fund', '644b') : null;

            if ($college == 1) {
                $list = Fund::where('college_id', $college)
                    ->where('fund', '644')
                    ->where('degree_type', $degreetype)
                    ->orWhereIn('course_type_id', $courseTypeIds->all())
                    ->when($cadFee, function ($q) use ($cadFee) {
                        $q->union($cadFee);
                    })
                    ->where(function ($q) {
                        $q->whereNotNull('fund')->where('fund', '<>', '');
                    })
                    ->orderBy('degree_type', 'desc')
                    ->orderBy('fund_id', 'asc')
                    ->get();
            } else if ($college == 7) {
                $otherlist = Fund::whereIn('course_type_id', $courseTypeIds->all());

                $list  = Fund::where('part', $sem)
                    ->where('fund', '644')
                    // ->orWhereIn('course_type_id', $courseTypeIds->all())
                    ->where('college_id', 0)
                    ->when($cadFee, function ($q) use ($cadFee) {
                        $q->union($cadFee);
                    })
                    ->when($otherlist, function ($q) use ($otherlist) {
                        $q->union($otherlist);
                    })
                    ->where(function ($q) {
                        $q->whereNotNull('fund')->where('fund', '<>', '');
                    })
                    ->orderBy('opt', 'asc')
                    ->orderBy('fund_id', 'asc')
                    ->get();
            } else if ($college == 10) {
                $list = Fund::where('college_id', $college)
                    ->where('fund', '644')
                    ->orWhereIn('course_type_id', $courseTypeIds->all())
                    ->when($cadFee, function ($q) use ($cadFee) {
                        $q->union($cadFee);
                    })
                    ->where(function ($q) {
                        $q->whereNotNull('fund')->where('fund', '<>', '');
                    })
                    ->orderBy('degree_type', 'desc')
                    ->orderBy('fund_id', 'asc')
                    ->get();
            } else {
                $list  = Fund::where('part', $sem)
                    ->where('fund', '644')
                    ->orWhereIn('course_type_id', $courseTypeIds->all())
                    ->where('college_id', 0)
                    ->when($cadFee, function ($q) use ($cadFee) {
                        $q->union($cadFee);
                    })
                    ->where(function ($q) {
                        $q->whereNotNull('fund')->where('fund', '<>', '');
                    })
                    ->orderBy('opt', 'asc')
                    ->orderBy('fund_id', 'asc')
                    ->get();
            }

            return $list;
        }

        if ($type == 1 && $units->totalunits == 0) {
            $list = Fund::where('fund', '644b')->get();
            return $list;
        }

        // FOR RESIDENCY
        //        $list = Fund::where('course_type_id', $courseTypeIds->all())->get();
        //        return $list;

    }

    public function getFeeList($units, $feeDetailList)
    {
        $feesList = collect([]);
        $tuitionList = collect([]);
        $miscFeeList = collect([]);
        $labFeeList = collect([]);
        $rleFeeList = collect([]);

        $tuition = 0;
        $miscfee = 0;
        $labfee = 0;
        $rlefee = 0;

        $college = $this->srecord->college_id;

        if (!is_null($feeDetailList)) {
            foreach ($feeDetailList as $key => $value) {

                switch ($value->fund) {
                    case '644': {
                            if (!is_null($value->fees)) {

                                if ($this->srecord->degree_id == 74) {
                                    $amount = ((float) $value->fees->amount * (float) $units->incurriculum) + (100 * $units->undergrad_units);
                                } else {
                                    $amount = (float) $value->fees->amount * (float) $units->totalunits;
                                }

                                $arr = [
                                    'fund_id' => $value->fund_id,
                                    'fund' => $value->fund,
                                    'fund_desc' => $value->fund_desc,
                                    'amount' => $amount
                                ];

                                $feesList->push((object) $arr);
                                $tuitionList->push((object) $arr);
                                $tuition += $amount;
                            }
                            break;
                        }

                    case '644A1': {

                            $count = 0;

                            foreach ($feesList as $key => $feesListItem) {
                                if ($feesListItem->fund == '644A1') {
                                    foreach ($labFeeList as $key => $labFeeListItem) {
                                        if ($labFeeListItem->fund == '644A1') {
                                            $count += 1;

                                            if (in_array($value->course_type_id, [20, 29])) {
                                                if ($value->fund_id == 5) { // phy/sci lab major
                                                    $amount = (float) $value->fees->amount * (float) $units->physcilab;
                                                } else if ($value->fund_id == 117) { // phy/sci lab minor
                                                    $amount = (float) $value->fees->amount * (float) $units->physcilab_minor;
                                                } else if ($value->fund_id == 113) { // med lab
                                                    $amount = (float) $value->fees->amount * (float) $units->medlab;
                                                }
                                            } else {
                                                $amount = (float) $value->fees->amount * (float) $units->noncomplab;
                                            }

                                            $feesListItem->amount += $amount;
                                            $labFeeListItem->amount += $amount;

                                            $labfee += $amount;
                                        }
                                    }
                                }
                            }

                            if ($count == 0) {
                                if (!is_null($value->fees)) {

                                    if (in_array($value->course_type_id, [20, 29])) {
                                        if ($value->fund_id == 5) { // phy/sci lab major
                                            $amount = (float) $value->fees->amount * (float) $units->physcilab;
                                        } else if ($value->fund_id == 117) { // phy/sci lab minor
                                            $amount = (float) $value->fees->amount * (float) $units->physcilab_minor;
                                        } else if ($value->fund_id == 113) { // med lab
                                            $amount = (float) $value->fees->amount * (float) $units->medlab;
                                        }
                                    } else {
                                        $amount = (float) $value->fees->amount * (float) $units->noncomplab;
                                    }

                                    $arr = [
                                        'fund_id' => $value->fund_id,
                                        'fund' => $value->fund,
                                        'fund_desc' => $value->fund_desc,
                                        'amount' => $amount
                                    ];

                                    $feesList->push((object) $arr);
                                    $labFeeList->push((object) $arr);
                                    $labfee += $amount;
                                }
                            }

                            break;
                        }

                    case '644A2': {

                            $count = 0;

                            foreach ($feesList as $key => $feesListItem) {
                                if ($feesListItem->fund == '644A2') {
                                    foreach ($labFeeList as $key => $labFeeListItem) {
                                        if ($labFeeListItem->fund == '644A2') {
                                            $count += 1;

                                            $unitCount = 0;
                                            if ($value->course_type_id == 2 || $value->course_type_id == 24 || $value->course_type_id == 3) {
                                                $unitCount = (float) $units->complab;
                                            }

                                            $amount = (float) $value->fees->amount * $unitCount;

                                            $feesListItem->amount += $amount;
                                            $labFeeListItem->amount += $amount;

                                            $labfee += $amount;
                                        }
                                    }
                                }
                            }

                            if ($count == 0) {
                                if (!is_null($value->fees)) {

                                    $unitCount = 0;
                                    if ($value->course_type_id == 2 || $value->course_type_id == 24 || $value->course_type_id == 3) {
                                        $unitCount = (float) $units->complab;
                                    }

                                    $amount = (float) $value->fees->amount * $unitCount;
                                    $arr = [
                                        'fund_id' => $value->fund_id,
                                        'fund' => $value->fund,
                                        'fund_desc' => $value->fund_desc,
                                        'amount' => $amount
                                    ];

                                    $feesList->push((object) $arr);
                                    $labFeeList->push((object) $arr);
                                    $labfee += $amount;
                                }
                            }

                            break;
                        }

                    case '164-0093': {
                            if (!is_null($value->fees)) {
                                $amount = (float) $value->fees->amount;
                                $arr = [
                                    'fund_id' => $value->fund_id,
                                    'fund' => $value->fund,
                                    'fund_desc' => $value->fund_desc,
                                    'amount' => $amount
                                ];

                                $feesList->push((object) $arr);
                                $rleFeeList->push((object) $arr);
                                $rlefee += $amount;
                            }
                            break;
                        }

                    case '164-0028': { // CIT FIELD STUDY
                            if ($college == 8) {
                                if (!is_null($value->fees)) {
                                    $amount = (float) $value->fees->amount * (float) $units->fieldstudy;
                                    $arr = [
                                        'fund_id' => $value->fund_id,
                                        'fund' => $value->fund,
                                        'fund_desc' => $value->fund_desc,
                                        'amount' => $amount
                                    ];

                                    $feesList->push((object) $arr);
                                    $miscFeeList->push((object) $arr);
                                    $miscfee += $amount;
                                }
                            }
                            break;
                        }

                    case '164-0029': { // CTE FIELD STUDY
                            if ($college == 9) {
                                if (!is_null($value->fees)) {
                                    $amount = (float) $value->fees->amount * (float) $units->fieldstudy;
                                    $arr = [
                                        'fund_id' => $value->fund_id,
                                        'fund' => $value->fund,
                                        'fund_desc' => $value->fund_desc,
                                        'amount' => $amount
                                    ];

                                    $feesList->push((object) $arr);
                                    $miscFeeList->push((object) $arr);
                                    $miscfee += $amount;
                                }
                            }
                            break;
                        }

                    default: {
                            if (!is_null($value->fees)) {
                                $amount = (float) $value->fees->amount;
                                $arr = [
                                    'fund_id' => $value->fund_id,
                                    'fund' => $value->fund,
                                    'fund_desc' => $value->fund_desc,
                                    'amount' => $amount
                                ];

                                $feesList->push((object) $arr);
                                $miscFeeList->push((object) $arr);
                                $miscfee += $amount;
                            }
                            break;
                        }
                }
            }
        }

        $allFeeList = collect([]);
        // $lists = [
        //     'tuitionFeeList' => $tuitionList,
        //     'miscFeeList' => $miscFeeList,
        //     'labFeeList' => $labFeeList,
        //     'rleFeeList' => $rleFeeList,
        //     'feesList' => $feesList,
        //     'forFreeTuitionFeesList' => $allFeeList->merge($miscFeeList)->merge($labFeeList)->merge($rleFeeList),
        // ];

        $forFreeTuition = $allFeeList->merge($miscFeeList)->merge($labFeeList)->merge($rleFeeList);

        // return $this->cad->free_tuition ? $forFreeTuition : $feesList;
        return $feesList;
    }

    public function getNewEnlistment($enrollment_details, $dropped_changed, $changed_new, $added)
    {
        $course_ids = collect([]);
        $course_id_units = collect([]);
        foreach ($enrollment_details as $enrollment_details_key => $enrollment_details_value) {
            $count = 0;
            foreach ($dropped_changed as $dropped_changed_key => $dropped_changed_value) {
                if ($enrollment_details_value->course_id == $dropped_changed_value->course_id) {
                    $count += 1;
                    $course_ids->push((object) [
                        'course_id' => $dropped_changed_value->course_id,
                        'sched_id' => $dropped_changed_value->sched_id,
                        'type' => $dropped_changed_value->type
                    ]);
                }
            }

            if ($count == 0) {
                $course_ids->push((object) [
                    'course_id' => $enrollment_details_value->course_id,
                    'sched_id' => $enrollment_details_value->sched_id,
                    'type' => 0
                ]);

                $course_id_units->push($enrollment_details_value->course_id);
            }
        }

        foreach ($changed_new as $key => $value) {
            $course_id_units->push($value->course_id);
        }

        foreach ($added as $key => $value) {
            $course_id_units->push($value->course_id);
        }

        $course_ids = $course_ids->merge($changed_new)->merge($added);
        $schedules = $this->getSchedules($course_ids);
        $units = $this->units($this->cad->pref_id, $course_id_units);

        $arr = [
            'schedules' => $schedules,
            'units' => $units
        ];

        return $arr;
    }

    public function getSchedules($course_ids)
    {
        $scheds = collect([]);

        foreach ($course_ids as $key => $value) {
            $sched = DB::table('dbiusis16.courses')
                ->join('dbiusis16.schedules', 'courses.id', '=', 'schedules.course_id')
                ->where('schedules.id', $value->sched_id)
                // ->where('schedules.course_id', $value->course_id)
                ->select([
                    'courses.code',
                    'courses.units',
                    'schedules.time',
                    'schedules.day',
                    'schedules.room',
                    'schedules.bldg'
                ])
                ->first();

            $code = '';
            if ($sched != null) {
                if ($value->type == 1) {
                    $code = $sched->code . ' - OD';
                } else if ($value->type == 2) {
                    $code = $sched->code . ' - CH';
                } else {
                    $code = $sched->code;
                }

                $scheds->push((object) [
                    'code' => $code,
                    'units' => $sched->units,
                    'time' => $sched->time,
                    'day' => $sched->day,
                    'room_bldg' => $sched->room . ' ' . $sched->bldg,
                ]);
            }
        }

        return $scheds;
    }

    public function getCourseCodes($course_ids)
    {
        $codes = collect([]);
        foreach ($course_ids as $key => $value) {
            $course = Courses::find($value->course_id);
            if ($course != null) {
                $codes->push((object) [
                    'code' => $course->code,
                    'units' => $course->units
                ]);
            }
        }

        return $codes;
    }

    public function coveredFreeEducation($fund)
    {
        $pref = Preference::find($this->cad->pref_id);
        $record = $this->srecord;
        $degree_type = $record->degree->type;
        if (!(in_array($record->college_id, [1, 10, 11]))) {
            $enlisted = $record->enlisted()->where('pref_id', $pref->id)->first();
            if (!is_null($enlisted)) {
                if ($enlisted->free_tuition) {
                    if ($degree_type == 0) {
                        if ($pref->id >= 19) {
                            $fund = Fund::where('fund', $fund)->where('free_educ', 1)->whereIn('part', [0, $pref->sem])->whereIn('college_id', [0, $record->college->id])->orderBy('free_educ', 'desc')->first();
                            return !is_null($fund);
                        }
                    }
                }
            }
        }
        return false;
    }

    public function getCurriculum($curricula_id)
    {
        $curriculum = DB::table('dbiusis16.curricula')
            ->leftJoin('dbiusis16.curricula_yss', 'curricula.id', '=', 'curricula_yss.curricula_id')
            ->leftJoin('dbiusis16.curricula_details', 'curricula_yss.id', '=', 'curricula_details.curricula_ys_id')
            ->leftJoin('dbiusis16.courses', 'curricula_details.course_id', '=', 'courses.id')
            ->select('courses.*')
            ->where('curricula.id', $curricula_id)
            ->get();

        return $curriculum;
    }
}
