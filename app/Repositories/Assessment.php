<?php

namespace App\Repositories;

use App\Curricula;
use App\Fund;
use App\Preference;
use App\Scholarship;
use App\StudentInfo as Student;
use App\StudentRecord;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

class Assessment
{
    private $internship_count_pharmacy, $degree_type, $free_tuition, $col_undergrad_courses;

    public function __construct()
    {
        $this->internship_count_pharmacy = 0;
        $this->degree_type = 0;
        $this->free_tuition = 0;
        $this->col_undergrad_courses = [
            'is_undergrad' => false,
            'units' => 0,
            'incurriculum' => 0,
            'outcurriculum' => 0,
            'petitioned' => 0,
            'not_counted' => 0,
        ];
    }

    public function assess($student_record_id, $pref_id)
    {
        $client = new Client();

        $result = $client->request(
            'GET',
            config('constants.api_uri') . 'assessment/' . $student_record_id . '/pref/' . $pref_id . '?api_token=' . config('constants.api_token')
            // [
            //     'headers' => [
            //         'accept' => 'application/json',
            //         'authorization' => "Bearer " . config('constants.api_token')
            //     ]
            // ]
        );
        // $result = $client->request(
        //     'GET',
        //     config('constants.api_uri') . 'assessment/' . $student_record_id . '/pref/' . $pref_id,
        //     [
        //         'headers' => [
        //             'accept' => 'application/json',
        //             'authorization' => "Bearer " . config('constants.api_token')
        //         ]
        //     ]
        // );

        $resultDecode = json_decode((string) $result->getBody(), true);

        return $resultDecode;
    }

    public function getAssessment($student_rec_id, $preference_id)
    {
        $record = StudentRecord::where('id', $student_rec_id)->first();
        $preference = Preference::where('id', $preference_id)->first();
        $student = Student::find($record->student_id);

        $this->degree_type = $record->degree->type;

        // try {
        //     // $studentLastEnrolled = $record->enrollments->where('pref_id', $preference->id)->first();
        //     $studentLastEnrolled = $record->enrollments->first();
        //     $enlisted = $record->enlisted->where('pref_id', $preference->id)->first();
        //
        //     if (is_null($enlisted)) {
        //         return null;
        //     }
        // } catch (\Exception $e) {
        //     return null;
        // }

        $studentLastEnrolled = $record->enrollments->first();
        $enlisted = $record->enlisted->where('pref_id', $preference->id)->first();

        $freeTuition = $enlisted->free_tuition;
        $this->free_tuition = $enlisted->free_tuition;
        $status = $this->getStudentStatus($enlisted->status_id);

        $enlistmentDetails = $this->getEnlistmentDetails($record->id, $preference->id);
        $enrollment = $record->enrollments->where('pref_id', $preference->id)->first();
        $enrollmentStatus = !is_null($enrollment);
        $courseTypeIds = $this->getCourseTypeIds($enlistmentDetails);
        $year = $record->enlisted->where('pref_id', $preference->id)->first()->standing;
        $section = $record->enlisted->where('pref_id', $preference->id)->first()->section;
        $units = $this->units($preference->id, $enlistmentDetails, $record);
        $scholarship = $this->scholarship($enlisted->scholarship_id);
        $semFeesList = $this->getSemFeesList($record, $preference, $courseTypeIds, $units, $freeTuition, $year, $scholarship);
        //        $curricula = Curricula::where('id', $record->curricula_id)->get();
        $totalTuition = $this->getTotalTuition($semFeesList->feeslist);
        $totalTuitionPetitioned = $this->getTotalTuition($semFeesList->paymentsList['fullpaymentFeeList']);

        $date = Carbon::now();
        $cys = $preference->cys;

        $arr = [
            'student' => $student,
            'studentRecord' => $record,
            'studentLastEnrolled' => $studentLastEnrolled,
            'enlistmentDetails' => $enlistmentDetails,
            'enrollmentStatus' => $enrollmentStatus,
            'year' => $year,
            'section' => $section,
            'units' => $units,
            'scholarship' => ($scholarship == '' || is_null($scholarship)) ? '--NONE--' : $scholarship->scholarship,
            'semFeesList' => $semFeesList->feeslist,
            'fullPayment' => $semFeesList->fullpayment,
            'downPayment' => $semFeesList->downpayment,
            'secondPayment' => $semFeesList->secondpayment,
            'thirdPayment' => $semFeesList->thirdpayment,
            'paymentsList' => $semFeesList->paymentsList,
            'freeTuition' => (bool) $freeTuition,
            'date' => $date->toDateString(),
            'cy' => $cys->cy,
            'sem' => $this->getSem($preference->sem),
            'totalTuition' => $totalTuition,
            'totalTuitionPetitioned' => $totalTuitionPetitioned,
            'status' => $status,
            'scholarshipDetails' => $scholarship,
            'coveredFreeEducation' => $semFeesList->coveredFreeEducation
        ];
        return $arr;
    }

    public function units($pref, $enlistedDetails, $record)
    {
        $units = 0;
        $complab = 0;
        $complab_minor = 0;
        $totalnoncomplab = 0;
        $noncomplab = 0;
        $physcilab = 0;
        $physcilab_minor = 0;
        $medlab = 0;
        $labwithoutfee = 0;
        $fieldstudy = 0;
        $incurriculum = 0;
        $petitioned = 0;
        $not_counted = 0;

        $preference = Preference::find($pref);
        $curriculum = $this->getCurriculum($record->curricula_id);

        $sy_courses = DB::table('dbiusis16.sy_courses')->pluck('course_id');
        $col_courses = DB::table('dbiusis16.col_undergrad_courses')->pluck('course_id');
        foreach ($enlistedDetails as $key => $value) {
            $units += (float) $value->units;

            if ($record->degree->type == 0) {
                // if (in_array($value->course_id, [5911, 5919])) {
                if (in_array($value->course_type_id, [13])) {
                    $not_counted += (float) $value->units;

                    // check if from COL
                    if ($record->degree->college_id == 10) {
                        if (in_array($value->course_id, $col_courses->toArray())) {
                            $this->col_undergrad_courses['not_counted'] += $value->units;
                        }
                    }
                }
            }

            //------------- Check if course is petitioned ----------------//
            //------------- EXCLUDE SEMS BEFORE SECOND SEM 2017-2018
            if ($preference->id > 16) {
                //------------- EXCLUDE RESIDENCY
                if ($value->course_id != 1993) {
                    $curricula_ys = DB::table('dbiusis16.curricula')
                        ->join('dbiusis16.curricula_yss', 'curricula.id', '=', 'curricula_yss.curricula_id')
                        ->join('dbiusis16.curricula_details', 'curricula_yss.id', '=', 'curricula_details.curricula_ys_id')
                        ->where('curricula.degree_id', $record->degree_id)
                        ->where('curricula.id', $record->curricula_id)
                        ->where('curricula_details.course_id', $value->course_id)
                        ->select('curricula_yss.*')
                        ->first();
                    if (!is_null($curricula_ys)) {
                        if ($curricula_ys->sem != $preference->sem) {
                            $petitioned += (float) $value->units;

                            // check if from COL
                            if ($record->degree->college_id == 10) {
                                if (in_array($value->course_id, $col_courses->toArray())) {
                                    $this->col_undergrad_courses['petitioned'] += $value->units;
                                }
                            }
                        }
                    }
                }
            }
            //--------------------------------------------------------------//

            foreach ($curriculum as $curr) {
                if ($value->course_id == $curr->id) {
                    if ($record->degree->id == 114 && in_array($curr->id, $sy_courses->toArray())) {
                        $incurriculum += $curr->units / 2;
                    } else {
                        $incurriculum += $curr->units;
                    }


                    // check if from COL
                    if ($record->degree->college_id == 10) {
                        if (in_array($curr->id, $col_courses->toArray())) {
                            $this->col_undergrad_courses['incurriculum'] += $curr->units;
                        }
                    }
                }
            }

            if ($value->lab_unit != 0) {
                if ($value->course_type_id == 2 || $value->course_type_id == 3 || $value->course_type_id == 24) {
                    // $complab += (float) $value->lab_unit;

                    if ($value->course_type_id == 2 || $value->course_type_id == 24) {
                        $complab += (float) $value->lab_unit;
                    } else if ($value->course_type_id == 3) {
                        $complab_minor += (float) $value->lab_unit;
                    }
                } else {

                    $fund = Fund::where('course_type_id', $value->course_type_id)->first();
                    if ($fund != null) {
                        if ($fund->fund != null || $fund->fund != '') {
                            $totalnoncomplab += (float) $value->lab_unit;

                            if ($value->college_id == 11) {
                                $medlab += (float) $value->lab_unit;
                            } else {
                                if ($value->course_type_id == 20) {
                                    $physcilab += (float) $value->lab_unit;
                                } else {
                                    if ($value->course_type_id != 4) {
                                        $noncomplab += (float) $value->lab_unit;
                                    }
                                }

                                if ($value->course_type_id == 29) {
                                    $physcilab_minor += (float) $value->lab_unit;
                                }
                            }

                            if ($value->computation != null && $value->computation == 0) {
                                $labwithoutfee += (float) $value->lab_unit;
                            }
                        }
                    }
                }
            } else {
                if ($value->course_type_id == 27) { // FIELD STUDY
                    $fieldstudy += $value->lecture_units;
                }
            }
        }

        $this->col_undergrad_courses['outcurriculum'] = $this->col_undergrad_courses['units'] - $this->col_undergrad_courses['incurriculum'];
        $arr = [
            'totalunits' => $units,
            'complab' => $complab,
            'complab_minor' => $complab_minor,
            'totalcomplab' => $complab + $complab_minor,
            'totalnoncomplab' => $totalnoncomplab,
            'noncomplab' => $noncomplab,
            'physcilab' => $physcilab,
            'physcilab_minor' => $physcilab_minor,
            'medlab' => $medlab,
            'labwithoutfee' => $labwithoutfee,
            'fieldstudy' => $fieldstudy,
            'incurriculum' => $incurriculum,
            'outcurriculum' => $units - $incurriculum,
            'petitioned' => $petitioned,
            'not_counted' => $not_counted
        ];

        return (object) $arr;
    }

    public function scholarship($scholarship_id)
    {
        $scholarship = Scholarship::find($scholarship_id);
        return $scholarship;
    }

    public function getCourseTypeIds($enlistmentDetails)
    {
        $id_list = collect([]);
        foreach ($enlistmentDetails as $key => $value) {
            if ($value->course_type_id != 0) {
                if (!($value->degree_id == 29 && $value->course_type_id == 15)) { // Exclude PT & Internship (PT5)
                    $id_list->push($value->course_type_id);
                }
            }

            if ($value->course_id == 2653) {    // EDUC 200
                if ($value->college_id == 8) {  // CIT
                    $id_list->push(37); // Student Teaching Fee (CIT)
                }

                if ($value->college_id == 9) {  // CTE
                    $id_list->push(34); // Student Teaching Fee (CTE)
                }
            }
        }

        return $id_list;
    }

    public function getSemFeesList($record, $preference, $courseTypeIds, $units, $freeTuition, $year, $scholarship)
    {
        $college = $record->college_id;
        $feesList = collect([]);
        $tuitionList = collect([]);
        $miscFeeList = collect([]);
        $labFeeList = collect([]);
        $rleFeeList = collect([]);
        $petitionedFeeList = collect([]);
        $coveredFeeList = collect([]);

        $tuition = 0;
        $miscfee = 0;
        $labfee = 0;
        $rlefee = 0;
        $petitionedFee = 0;
        $coveredFee = 0;

        $pref_id = $preference->id;
        $list = $this->getFeeDetailsList($record, $preference, $courseTypeIds, $year, $units);
        if ($pref_id >= 22) { // Remove SMS fee starting SY 2019-2020 2nd Sem
            $list = $list->reject(function ($item) {
                return $item->fund == '164-0110';
            });
        }
        $enlisted = $record->enlisted()->where('pref_id', $pref_id)->first();
        $undergrad_tuition = Fund::where('fund_id', 1)->first();
        foreach ($list as $key => $value) {

            switch ($value->fund) {
                case '644': {
                        if (!is_null($value->fees)) {
                            // $amount = (float) $value->fees->amount * (float) $units->totalunits;
                            // $arr = [
                            //     'fund' => $value->fund,
                            //     'fund_desc' => $value->fund_desc,
                            //     'amount' => $amount
                            // ];
                            //
                            // $amount -= $this->getDeductions($scholarship, $preference, $value->fund, $amount);
                            //
                            // if ($amount != 0) {
                            //     $feesList->push((object) $arr);
                            //     $tuitionList->push((object) $arr);
                            //     $tuition += $amount;
                            // }

                            if ($units->outcurriculum > 0) {
                                if ($value->part == $preference->sem) {

                                    if ($this->col_undergrad_courses['outcurriculum'] > 0) {
                                        $amount = (float) $undergrad_tuition->fees->amount * (float) $this->col_undergrad_courses['outcurriculum'];
                                        $amount += (float) $value->fees->amount * (float) ($units->outcurriculum - $this->col_undergrad_courses['petitioned']);
                                    } else {
                                        $amount = (float) $value->fees->amount * (float) $units->outcurriculum;
                                    }

                                    $arr = [
                                        'fund_id' => $value->fund_id,
                                        'fund' => $value->fund,
                                        'fund_desc' => $value->fund_desc,
                                        'amount' => $amount
                                    ];

                                    $amount -= $this->getDeductions($scholarship, $preference, $value->fund_id, $amount);

                                    if ($amount != 0) {
                                        $arr['amount'] = $amount;

                                        // $feesList->push((object) $arr);
                                        // $tuitionList->push((object) $arr);
                                        // $tuition += $amount;
                                        $covered = $this->coveredFreeEducation($record, $pref_id, $value->fund);
                                        if (!$covered) {
                                            $tuitionList->push((object) $arr);
                                            $tuition += $amount;
                                        } else {
                                            $coveredFeeList->push((object) $arr);
                                            $coveredFee += $amount;
                                        }
                                    }
                                }
                            }

                            if ($units->petitioned > 0) {
                                if ($value->part == $preference->sem) {

                                    if ($this->col_undergrad_courses['petitioned'] > 0) {
                                        $amount = (float) $undergrad_tuition->fees->amount * (float) $this->col_undergrad_courses['petitioned'];
                                        $amount += (float) $value->fees->amount * (float) ($units->petitioned - $this->col_undergrad_courses['petitioned']);
                                    } else {
                                        $amount = (float) $value->fees->amount * (float) $units->petitioned;
                                    }
                                    $arr = [
                                        'fund_id' => $value->fund_id,
                                        'fund' => $value->fund,
                                        'fund_desc' => $value->fund_desc,
                                        'amount' => $amount
                                    ];

                                    $amount -= $this->getDeductions($scholarship, $preference, $value->fund_id, $amount);

                                    if ($amount != 0) {
                                        $arr['amount'] = $amount;

                                        $covered = $this->coveredFreeEducation($record, $pref_id, $value->fund);
                                        if (!$covered) {
                                            $petitionedFeeList->push((object) $arr);
                                            $petitionedFee += $amount;
                                        }
                                    }
                                }
                            }


                            if ($this->col_undergrad_courses['not_counted'] > 0) {
                                $not_counted = (float) $undergrad_tuition->fees->amount * (float) $this->col_undergrad_courses['not_counted'];
                                $not_counted += (float) $value->fees->amount * (float) ($units->not_counted - $this->col_undergrad_courses['not_counted']);
                            } else {
                                $not_counted = (float) $value->fees->amount * (float) $units->not_counted;
                            }



                            if ($this->col_undergrad_courses['incurriculum'] > 0) {
                                $amount = (float) $undergrad_tuition->fees->amount * (float) $this->col_undergrad_courses['incurriculum'];
                                $amount += (float) $value->fees->amount * (float) ($units->incurriculum - $this->col_undergrad_courses['incurriculum']);
                            } else {
                                $amount = (float) $value->fees->amount * (float) $units->incurriculum;
                            }

                            $amount -= $not_counted;
                            $arr = [
                                'fund_id' => $value->fund_id,
                                'fund' => $value->fund,
                                'fund_desc' => $value->fund_desc,
                                'amount' => $amount
                            ];

                            $amountDeduct = $amount;
                            $amount -= $this->getDeductions($scholarship, $preference, $value->fund_id, $amount);

                            if (!is_null($scholarship)) {
                                if ($scholarship->id == 97) {
                                    $arr['amount'] = $amountDeduct;
                                    $coveredFeeList->push((object) $arr);
                                    $coveredFee += $amountDeduct;
                                }
                            }


                            if ($amount != 0) {
                                $arr['amount'] = $amount;

                                $feesList->push((object) $arr);

                                $covered = $this->coveredFreeEducation($record, $pref_id, $value->fund);
                                if (!$covered) {
                                    $tuitionList->push((object) $arr);
                                    $tuition += $amount;
                                } else {
                                    $coveredFeeList->push((object) $arr);
                                    $coveredFee += $amount;
                                }
                            }
                        }
                        break;
                    }

                case '644A1': {

                        $count = 0;
                        $covered = $this->coveredFreeEducation($record, $pref_id, $value->fund);

                        foreach ($feesList as $key => $feesListItem) {
                            if ($feesListItem->fund == '644A1') {

                                if (!$covered) {
                                    foreach ($labFeeList as $key => $labFeeListItem) {
                                        if ($labFeeListItem->fund == '644A1') {
                                            $count += 1;

                                            if ($count > 0) {
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

                                                $amount -= $this->getDeductions($scholarship, $preference, $value->fund_id, $amount);

                                                if ($amount != 0) {
                                                    $labfee += $amount;
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    foreach ($coveredFeeList as $key => $coveredFeeListItem) {
                                        if ($coveredFeeListItem->fund == '644A1') {
                                            $count += 1;

                                            if ($count > 0) {
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
                                                $coveredFeeListItem->amount += $amount;

                                                $amount -= $this->getDeductions($scholarship, $preference, $value->fund_id, $amount);

                                                if ($amount != 0) {
                                                    $coveredFee += $amount;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if ($this->checkListDuplicate($value->fund, $coveredFeeList)) {
                            $count += 1;
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

                                $amountDeduct = $amount;

                                $amount -= $this->getDeductions($scholarship, $preference, $value->fund_id, $amount);

                                if (!is_null($scholarship)) {
                                    if ($scholarship->id == 97) {
                                        $arr['amount'] = $amountDeduct;
                                        $coveredFeeList->push((object) $arr);
                                        $coveredFee += $amountDeduct;
                                    }
                                }

                                if ($amount != 0) {
                                    $arr['amount'] = $amount;

                                    $feesList->push((object) $arr);

                                    if (!$covered) {
                                        $labFeeList->push((object) $arr);
                                        $labfee += $amount;
                                    } else {
                                        $coveredFeeList->push((object) $arr);
                                        $coveredFee += $amount;
                                    }
                                }
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

                                        if ($count == 0) {
                                            $unitCount = 0;
                                            if ($value->course_type_id == 2 || $value->course_type_id == 24) {
                                                $unitCount = (float) $units->complab;
                                            } else if ($value->course_type_id == 3) {
                                                $unitCount = (float) $units->complab_minor;
                                            }

                                            $amount = (float) $value->fees->amount * $unitCount;

                                            $feesListItem->amount += $amount;
                                            $labFeeListItem->amount += $amount;

                                            $amount -= $this->getDeductions($scholarship, $preference, $value->fund_id, $amount);

                                            if ($amount != 0) {
                                                $labfee += $amount;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if ($this->checkListDuplicate($value->fund, $coveredFeeList)) {
                            $count += 1;
                        }

                        if ($count == 0) {
                            if (!is_null($value->fees)) {

                                $unitCount = 0;
                                if ($value->course_type_id == 2 || $value->course_type_id == 24) {
                                    $unitCount = (float) $units->complab;
                                } else if ($value->course_type_id == 3) {
                                    $unitCount = (float) $units->complab_minor;
                                }

                                $amount = (float) $value->fees->amount * $unitCount;
                                $arr = [
                                    'fund_id' => $value->fund_id,
                                    'fund' => $value->fund,
                                    'fund_desc' => $value->fund_desc,
                                    'amount' => $amount
                                ];

                                $amount -= $this->getDeductions($scholarship, $preference, $value->fund_id, $amount);

                                if ($amount != 0) {
                                    $arr['amount'] = $amount;

                                    $feesList->push((object) $arr);

                                    $covered = $this->coveredFreeEducation($record, $pref_id, $value->fund);
                                    if (!$covered) {
                                        $labFeeList->push((object) $arr);
                                        $labfee += $amount;
                                    } else {
                                        $coveredFeeList->push((object) $arr);
                                        $coveredFee += $amount;
                                    }
                                }
                            }
                        }

                        break;
                    }

                case '164-0045': {
                        if (!is_null($value->fees)) {
                            $amount = (float) $value->fees->amount * $this->internship_count_pharmacy;
                            $arr = [
                                'fund_id' => $value->fund_id,
                                'fund' => $value->fund,
                                'fund_desc' => $value->fund_desc,
                                'amount' => $amount
                            ];
                            $amountDeduct = $amount;

                            $amount -= $this->getDeductions($scholarship, $preference, $value->fund_id, $amount);

                            if (!is_null($scholarship)) {
                                if ($scholarship->id == 97) {
                                    $arr['amount'] = $amountDeduct;
                                    $coveredFeeList->push((object) $arr);
                                    $coveredFee += $amountDeduct;
                                }
                            }

                            if ($amount != 0) {
                                $arr['amount'] = $amount;

                                $feesList->push((object) $arr);

                                $covered = $this->coveredFreeEducation($record, $pref_id, $value->fund);
                                if (!$covered) {
                                    $miscFeeList->push((object) $arr);
                                    $miscfee += $amount;
                                } else {
                                    $coveredFeeList->push((object) $arr);
                                    $coveredFee += $amount;
                                }
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
                            $amountDeduct = $amount;

                            $amount -= $this->getDeductions($scholarship, $preference, $value->fund_id, $amount);

                            if (!is_null($scholarship)) {
                                if ($scholarship->id == 97) {
                                    $arr['amount'] = $amountDeduct;
                                    $coveredFeeList->push((object) $arr);
                                    $coveredFee += $amountDeduct;
                                }
                            }

                            if ($amount != 0) {
                                $arr['amount'] = $amount;

                                $feesList->push((object) $arr);

                                $covered = $this->coveredFreeEducation($record, $pref_id, $value->fund);
                                if (!$covered) {
                                    $rleFeeList->push((object) $arr);
                                    $rlefee += $amount;
                                } else {
                                    $coveredFeeList->push((object) $arr);
                                    $coveredFee += $amount;
                                }
                            }
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

                                $amount -= $this->getDeductions($scholarship, $preference, $value->fund_id, $amount);

                                if ($amount != 0) {
                                    $arr['amount'] = $amount;

                                    $feesList->push((object) $arr);

                                    $covered = $this->coveredFreeEducation($record, $pref_id, $value->fund);
                                    if (!$covered) {
                                        $miscFeeList->push((object) $arr);
                                        $miscfee += $amount;
                                    } else {
                                        $coveredFeeList->push((object) $arr);
                                        $coveredFee += $amount;
                                    }
                                }
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

                                $amount -= $this->getDeductions($scholarship, $preference, $value->fund_id, $amount);

                                if ($amount != 0) {
                                    $arr['amount'] = $amount;

                                    $feesList->push((object) $arr);

                                    $covered = $this->coveredFreeEducation($record, $pref_id, $value->fund);
                                    if (!$covered) {
                                        $miscFeeList->push((object) $arr);
                                        $miscfee += $amount;
                                    } else {
                                        $coveredFeeList->push((object) $arr);
                                        $coveredFee += $amount;
                                    }
                                }
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
                            $amountDeduct = $amount;

                            $amount -= $this->getDeductions($scholarship, $preference, $value->fund_id, $amount);

                            if (!is_null($scholarship)) {
                                if ($scholarship->id == 97) {
                                    $arr['amount'] = $amountDeduct;
                                    $coveredFeeList->push((object) $arr);
                                    $coveredFee += $amountDeduct;
                                }
                            }

                            if ($amount != 0) {
                                $arr['amount'] = $amount;

                                $feesList->push((object) $arr);

                                $covered = $this->coveredFreeEducation($record, $pref_id, $value->fund);
                                if (!$covered) {
                                    $miscFeeList->push((object) $arr);
                                    $miscfee += $amount;
                                } else {
                                    $coveredFeeList->push((object) $arr);
                                    $coveredFee += $amount;
                                }
                            }
                        } else {

                            // VOLUNTARY CONTRIBUTION
                            if ($value->fund_id == 138) {
                                if (!is_null($enlisted->voluntary_contribution)) {
                                    $amount = (float) $enlisted->voluntary_contribution;
                                    $arr = [
                                        'fund_id' => $value->fund_id,
                                        'fund' => $value->fund,
                                        'fund_desc' => $value->fund_desc,
                                        'amount' => $amount
                                    ];

                                    if ($amount != 0) {
                                        $feesList->push((object) $arr);

                                        $miscFeeList->push((object) $arr);
                                        $miscfee += $amount;
                                    }
                                }
                            }
                        }

                        break;
                    }
            }
        }

        $lists = [
            'tuitionFeeList' => $tuitionList,
            'miscFeeList' => $miscFeeList,
            'labFeeList' => $labFeeList,
            'rleFeeList' => $rleFeeList,
            'petitionedFeeList' => $petitionedFeeList
        ];

        $fees = [
            'tuition' => $tuition,
            'miscfee' => $miscfee,
            'labfee' => $labfee,
            'rlefee' => $rlefee,
            'petitionedFee' => $petitionedFee
        ];

        $coveredFreeEducation = [
            'feeList' => $coveredFeeList,
            'amount' => $coveredFee
        ];

        $feecomputation = $this->getFeeComputations($record, $freeTuition, $preference, $units, (object) $lists, (object) $fees);

        $arr = [
            'feeslist' => $feesList,
            'fullpayment' => $feecomputation->fullpayment,
            'downpayment' => $feecomputation->downpayment,
            'secondpayment' => $feecomputation->secondpayment,
            'thirdpayment' => $feecomputation->thirdpayment,
            'paymentsList' => $feecomputation->paymentsList,
            'coveredFreeEducation' => $coveredFreeEducation
        ];

        return (object) $arr;
    }

    public function getFeeComputations($record, $freeTuition, $preference, $units, $lists, $fees)
    {
        $college = $record->college_id;
        $degree = $record->degree_id;

        $tuition = $fees->tuition;
        $miscfee = $fees->miscfee;
        $labfee = $fees->labfee;
        $rlefee = $fees->rlefee;
        $petitionedFee = $fees->petitionedFee;

        $fullpayment = 0;
        $downpayment = 0;
        $secondpayment = 0;
        $thirdpayment = 0;

        $fullpaymentFeeList = collect([]);
        $downpaymentFeeList = collect([]);
        $secondpaymentFeeList = collect([]);
        $thirdpaymentFeeList = collect([]);

        $totalamount = $tuition + $miscfee + $labfee + $rlefee;

        switch ($college) {
            case 1: { // GRADUATE SCHOOL
                    if ($freeTuition) {
                        $fullpayment = $totalamount - $tuition;
                        $downpayment = $fullpayment;

                        $fullpaymentFeeList = $fullpaymentFeeList->merge($lists->miscFeeList)->merge($lists->labFeeList);
                        $downpaymentFeeList = $downpaymentFeeList->merge($lists->miscFeeList)->merge($lists->labFeeList);
                    } else {
                        $fullpayment = $totalamount;
                        $downpayment = $miscfee + ((50 / 100) * $tuition);
                        $secondpayment = (50 / 100) * $tuition;

                        $fullpaymentFeeList = $fullpaymentFeeList->merge($lists->tuitionFeeList)->merge($lists->miscFeeList)->merge($lists->labFeeList);
                        $downpaymentFeeList = $downpaymentFeeList->merge($lists->miscFeeList);

                        if (!$lists->tuitionFeeList->isEmpty()) {
                            foreach ($lists->tuitionFeeList as $key => $value) {
                                $arr = [
                                    'fund_id' => $value->fund_id,
                                    'fund' => $value->fund,
                                    'fund_desc' => $value->fund_desc,
                                    'amount' => $value->amount * (50 / 100)
                                ];

                                $downpaymentFeeList->push((object) $arr);
                                $secondpaymentFeeList->push((object) $arr);
                            }
                        }
                    }
                    break;
                }

            case 7: { // COLLEGE OF HEALTH SCIENCES
                    if ($freeTuition) {
                        if ($degree == 29) { // PT ONLY COMPUTATIONS

                            $check = $record->enlisted()->where('pref_id', 20)->first();
                            if (!is_null($check) && $check->standing == 5) {
                                $fullpayment = $totalamount - abs($tuition - $petitionedFee);
                                $downpayment = ($totalamount - $labfee) - abs($tuition - $petitionedFee);
                                $secondpayment = $labfee;

                                $fullpaymentFeeList = $fullpaymentFeeList->merge($lists->petitionedFeeList)->merge($lists->miscFeeList)->merge($lists->labFeeList);
                            } else {
                                $fullpayment = $totalamount - abs($tuition - $petitionedFee);
                                $downpayment = ($totalamount - $labfee) - abs($tuition - $petitionedFee);
                                $secondpayment = $labfee;

                                $fullpaymentFeeList = $fullpaymentFeeList->merge($lists->petitionedFeeList)->merge($lists->miscFeeList)->merge($lists->labFeeList);
                                $downpaymentFeeList = $downpaymentFeeList->merge($lists->petitionedFeeList)->merge($lists->miscFeeList);
                                $secondpaymentFeeList = $secondpaymentFeeList->merge($lists->labFeeList);

                                foreach ($lists->miscFeeList as $key => $value) {
                                    if ($value->fund == '164-0017') { // PT INTERSHIP FUND
                                        $downpayment -= $value->amount;
                                        $secondpayment += $value->amount;

                                        $downpaymentFeeList->forget($key);
                                        $secondpaymentFeeList->push($value);
                                    }
                                }
                            }
                        } else {
                            $fullpayment = $totalamount - abs($tuition - $petitionedFee);
                            $downpayment = ($fullpayment - $labfee) - ((50 / 100) * $rlefee);
                            $secondpayment = $labfee + ((50 / 100) * $rlefee);

                            $fullpaymentFeeList = $fullpaymentFeeList->merge($lists->petitionedFeeList)->merge($lists->miscFeeList)->merge($lists->labFeeList)->merge($lists->rleFeeList);
                            $downpaymentFeeList = $downpaymentFeeList->merge($lists->petitionedFeeList)->merge($lists->miscFeeList);
                            $secondpaymentFeeList = $secondpaymentFeeList->merge($lists->labFeeList);
                            if (!$lists->rleFeeList->isEmpty()) {
                                foreach ($lists->rleFeeList as $key => $value) {
                                    $arr = [
                                        'fund_id' => $value->fund_id,
                                        'fund' => $value->fund,
                                        'fund_desc' => $value->fund_desc,
                                        'amount' => $value->amount * (50 / 100)
                                    ];

                                    $downpaymentFeeList->push((object) $arr);
                                    $secondpaymentFeeList->push((object) $arr);
                                }
                            }
                        }
                    } else {
                        $fullpayment = $totalamount;
                        $downpayment = $miscfee + $labfee + ((50 / 100) * $rlefee);
                        $secondpayment = ((65 / 100) * $tuition) + ((50 / 100) * $rlefee);
                        $thirdpayment = (35 / 100) * $tuition;

                        $fullpaymentFeeList = $fullpaymentFeeList->merge($lists->tuitionFeeList)->merge($lists->miscFeeList)->merge($lists->labFeeList)->merge($lists->rleFeeList);
                        $downpaymentFeeList = $downpaymentFeeList->merge($lists->miscFeeList)->merge($lists->labFeeList);

                        if (!$lists->tuitionFeeList->isEmpty()) {
                            foreach ($lists->tuitionFeeList as $key => $value) {
                                $secondpaymentFeeList->push((object) [
                                    'fund_id' => $value->fund_id,
                                    'fund' => $value->fund,
                                    'fund_desc' => $value->fund_desc,
                                    'amount' => $value->amount * (65 / 100)
                                ]);

                                $thirdpaymentFeeList->push((object) [
                                    'fund_id' => $value->fund_id,
                                    'fund' => $value->fund,
                                    'fund_desc' => $value->fund_desc,
                                    'amount' => $value->amount * (35 / 100)
                                ]);
                            }
                        }

                        if (!$lists->rleFeeList->isEmpty()) {
                            foreach ($lists->rleFeeList as $key => $value) {
                                $downpaymentFeeList->push((object) [
                                    'fund_id' => $value->fund_id,
                                    'fund' => $value->fund,
                                    'fund_desc' => $value->fund_desc,
                                    'amount' => $value->amount * (50 / 100)
                                ]);

                                $secondpaymentFeeList->push((object) [
                                    'fund_id' => $value->fund_id,
                                    'fund' => $value->fund,
                                    'fund_desc' => $value->fund_desc,
                                    'amount' => $value->amount * (50 / 100)
                                ]);
                            }
                        }
                    }
                    break;
                }

            case 11: { // COLLEGE OF MEDICINE
                    if ($freeTuition) {
                        $fullpayment = $totalamount - $tuition;
                        $downpayment = $fullpayment;

                        $fullpaymentFeeList = $fullpaymentFeeList->merge($lists->miscFeeList)->merge($lists->labFeeList);
                        $downpaymentFeeList = $downpaymentFeeList->merge($lists->miscFeeList)->merge($lists->labFeeList);
                    } else {
                        $fullpayment = $totalamount;
                        $downpayment = $miscfee + $labfee;
                        $secondpayment = (65 / 100) * $tuition;
                        $thirdpayment = (35 / 100) * $tuition;

                        $fullpaymentFeeList = $fullpaymentFeeList->merge($lists->tuitionFeeList)->merge($lists->miscFeeList)->merge($lists->labFeeList);
                        $downpaymentFeeList = $downpaymentFeeList->merge($lists->miscFeeList)->merge($lists->labFeeList);

                        if (!$lists->tuitionFeeList->isEmpty()) {
                            foreach ($lists->tuitionFeeList as $key => $value) {
                                $secondpaymentFeeList->push((object) [
                                    'fund_id' => $value->fund_id,
                                    'fund' => $value->fund,
                                    'fund_desc' => $value->fund_desc,
                                    'amount' => $value->amount * (65 / 100)
                                ]);

                                $thirdpaymentFeeList->push((object) [
                                    'fund_id' => $value->fund_id,
                                    'fund' => $value->fund,
                                    'fund_desc' => $value->fund_desc,
                                    'amount' => $value->amount * (35 / 100)
                                ]);
                            }
                        }
                    }
                    break;
                }

            default: {
                    if ($freeTuition) {
                        $fullpayment = $totalamount - abs($tuition - $petitionedFee);
                        $downpayment = $fullpayment - $labfee;
                        $secondpayment = $labfee;

                        $fullpaymentFeeList = $fullpaymentFeeList->merge($lists->petitionedFeeList)->merge($lists->miscFeeList)->merge($lists->labFeeList);
                        $downpaymentFeeList = $downpaymentFeeList->merge($lists->petitionedFeeList)->merge($lists->miscFeeList);
                        $secondpaymentFeeList = $secondpaymentFeeList->merge($lists->labFeeList);
                    } else {
                        $fullpayment = $totalamount;
                        $downpayment = $miscfee + $labfee;
                        $secondpayment = (65 / 100) * $tuition;
                        $thirdpayment = (35 / 100) * $tuition;

                        $fullpaymentFeeList = $fullpaymentFeeList->merge($lists->tuitionFeeList)->merge($lists->miscFeeList)->merge($lists->labFeeList);
                        $downpaymentFeeList = $downpaymentFeeList->merge($lists->miscFeeList)->merge($lists->labFeeList);

                        if (!$lists->tuitionFeeList->isEmpty()) {
                            foreach ($lists->tuitionFeeList as $key => $value) {
                                $secondpaymentFeeList->push((object) [
                                    'fund_id' => $value->fund_id,
                                    'fund' => $value->fund,
                                    'fund_desc' => $value->fund_desc,
                                    'amount' => $value->amount * (65 / 100)
                                ]);

                                $thirdpaymentFeeList->push((object) [
                                    'fund_id' => $value->fund_id,
                                    'fund' => $value->fund,
                                    'fund_desc' => $value->fund_desc,
                                    'amount' => $value->amount * (35 / 100)
                                ]);
                            }
                        }
                    }
                    break;
                }
        }

        $paymentsList = [
            'fullpaymentFeeList' => $fullpaymentFeeList,
            'downpaymentFeeList' => $downpaymentFeeList,
            'secondpaymentFeeList' => $secondpaymentFeeList,
            'thirdpaymentFeeList' => $thirdpaymentFeeList
        ];

        $arr = [
            'fullpayment' => $fullpayment,
            'downpayment' => $downpayment,
            'secondpayment' => $secondpayment,
            'thirdpayment' => $thirdpayment,
            'paymentsList' => $paymentsList
        ];

        return (object) $arr;
    }

    public function getFeeDetailsList($record, $preference, $courseTypeIds, $year, $units)
    {

        if ($units->totalunits != 0) {
            $college = $record->college_id;
            $degree = $record->degree_id;
            $degreetype = $record->degree->type;
            $sem = $preference->sem;
            //            $now = Carbon::now();
            //            $registrationdates = config('constants.registration');

            $enlisted = $record->enlisted->where('pref_id', $preference->id)->first();
            $enlisteddate = $enlisted->created_at;

            // foreach ($registrationdates as $key => $value) {
            //     $date = (object) $value;
            //     if ($key == $preference->id) {
            //
            //         if ($college == 1) {
            //             if ($date->start <= $enlisteddate && $enlisteddate >= '2018-01-20') {
            //                 // $courseTypeIds->push(35);
            //             }
            //         } else {
            //             if ($date->start <= $enlisteddate && $enlisteddate >= $date->end) {
            //                 $courseTypeIds->push(35);
            //             }
            //         }
            //
            //     }
            // }

            if (!is_null($preference->gs_enl_deadline)) {
                if ($enlisteddate >= $preference->gs_enl_deadline) {
                    $courseTypeIds->push(35);
                }
            }

            switch ($college) {
                case 1: { // GRADUATE SCHOOL
                        if ($sem == 1) {
                            if ($year == 1) {
                                if ($this->checkIfFreshman($record->student_id)) {
                                    $courseTypeIds->push(25);
                                }
                            }
                            $courseTypeIds->push(23);
                        } else {
                            $check_enrollment = $record->enrollments->where('pref_id', $preference->id - 1)->first();
                            if ($this->checkIfFreshman($record->student_id) && is_null($check_enrollment)) {
                                $courseTypeIds->push(25);
                                //                            $collegespecific = Fund::where('fund_id', 142);
                            }
                        }

                        $list  = Fund::where('college_id', $college)
                            ->whereIn('course_type_id', array_merge([0], $courseTypeIds->all()))
                            ->whereIn('degree_type', [0, $degreetype])
                            ->orderBy('degree_type', 'desc')
                            ->orderBy('fund_id', 'asc')
                            ->get();

                        return $list;
                    }

                case 10: { // COLLEGE OF LAW
                        $otherlist = null;

                        if ($sem == 1) {
                            if ($year == 1) {
                                if ($this->checkIfFreshman($record->student_id)) {
                                    $courseTypeIds->push(25);
                                }
                            }
                            $courseTypeIds->push(23);
                        } else {
                            $check_enrollment = $record->enrollments->where('pref_id', $preference->id - 1)->first();
                            if ($this->checkIfFreshman($record->student_id) && is_null($check_enrollment)) {
                                $courseTypeIds->push(25);
                                $collegespecific = Fund::where('fund_id', 142);
                            }
                        }

                        $otherlist = Fund::where('college_id', $college)->whereIn('course_type_id', $courseTypeIds);

                        if ($units->outcurriculum > 0) {
                            $outcurriculum = Fund::where('fund', '644')->where('part', $preference->sem);
                        } else {
                            $outcurriculum = null;
                        }

                        if ($this->col_undergrad_courses['is_undergrad']) {

                            $collegespecific = null;
                            $voluntary_contribution = null;
                            if (!is_null($enlisted->voluntary_contribution)) {
                                $voluntary_contribution = Fund::where('fund_id', 138);
                            }

                            $list  = Fund::where('part', $sem)
                                ->orWhereIn('course_type_id', $courseTypeIds->all())
                                ->where('college_id', 0)
                                ->where(function ($q) {
                                    $q->whereNotNull('fund')->where('fund', '<>', '');
                                })
                                ->when($voluntary_contribution, function ($q) use ($voluntary_contribution) {
                                    $q->union($voluntary_contribution);
                                })
                                ->orderBy('opt', 'asc')
                                ->orderBy('fund_id', 'asc')
                                ->get();
                        } else {

                            $list = Fund::where('college_id', $college)
                                ->where('course_type_id', 0)
                                ->whereIn('standing', [0, $year])
                                ->when($otherlist, function ($q) use ($otherlist) {
                                    $q->union($otherlist);
                                })
                                ->when($outcurriculum, function ($q) use ($outcurriculum) {
                                    $q->union($outcurriculum);
                                })
                                ->orderBy('standing', 'asc')
                                ->orderBy('course_type_id', 'asc')
                                ->orderBy('fund', 'desc')
                                ->orderBy('fund_id', 'asc')
                                ->get();
                        }
                        return $list;
                    }

                case 11: { // COLLEGE OF MEDICINE
                        $otherlist = null;
                        $courseTypeIds->push(20);
                        $courseTypeIds->push(23);

                        if ($sem == 1) {
                            if ($year == 1) {
                                if ($this->checkIfFreshman($record->student_id)) {
                                    $courseTypeIds->push(25);
                                }
                            }
                        } else {
                            $check_enrollment = $record->enrollments->where('pref_id', $preference->id - 1)->first();
                            if ($this->checkIfFreshman($record->student_id) && is_null($check_enrollment)) {
                                $courseTypeIds->push(25);
                                $collegespecific = Fund::where('fund_id', 142);
                            }
                        }

                        $otherlist = Fund::where('college_id', $college)->whereIn('course_type_id', $courseTypeIds);

                        if ($year == 4) {
                            $list = Fund::where('college_id', $college)
                                ->where('course_type_id', 0)
                                ->where('standing', $year)
                                ->orderBy('standing', 'asc')
                                ->orderBy('course_type_id', 'asc')
                                ->orderBy('fund', 'desc')
                                ->orderBy('fund_id', 'asc')
                                ->get();
                        } else {
                            $list = Fund::where('college_id', $college)
                                ->where('course_type_id', 0)
                                ->whereIn('standing', [0, $year])
                                ->when($otherlist, function ($q) use ($otherlist) {
                                    $q->union($otherlist);
                                })
                                ->orderBy('standing', 'asc')
                                ->orderBy('course_type_id', 'asc')
                                ->orderBy('fund', 'desc')
                                ->orderBy('fund_id', 'asc')
                                ->get();
                        }

                        if ($sem == 2) {
                            $rm_key = null;
                            foreach ($list as $key => $value) {
                                if ($value->fund == '164-0040') {
                                    $rm_key = $key;
                                }
                            }

                            // REMOVE GROUP INSURANCE FEE FOR 2ND SEM
                            $list->forget($rm_key);
                        }

                        return $list;
                    }

                case 7: { // COLLEGE OF HEALTH SCIENCES

                        if ($sem == 1) {
                            if ($year == 1) {
                                if ($this->checkIfFreshman($record->student_id)) {
                                    $courseTypeIds->push(25);
                                }
                            }
                            $courseTypeIds->push(23);
                        } else {
                            $check_enrollment = $record->enrollments->where('pref_id', $preference->id - 1)->first();
                            if ($this->checkIfFreshman($record->student_id) && is_null($check_enrollment)) {
                                $courseTypeIds->push(25);
                                //                            $collegespecific = Fund::where('fund_id', 142);
                            }
                        }

                        $otherlist = Fund::where('degree_id', $degree)
                            ->where('college_id', $college);

                        if ($degree == 28) {
                            $otherlist->where('standing', $year);
                        } else if ($degree == 29) {
                            $otherlist->where('course_type_id', 0);

                            // PT Internship
                            if ($preference->id == 22) {
                                $otherlist->whereNotIn('fund_id', [31]);
                            }

                            // Remove PT Student Assistant Fee
                            if ($preference->id = 18 && $sem == 3) {
                                if ($units->totalunits == $units->petitioned) {
                                    $otherlist->whereNotIn('fund_id', [32]);
                                }
                            }
                        } else if ($degree == 30) {
                            $otherlist->where('standing', $year);

                            $enlisted_record = $record->enlisted()->where('pref_id', $preference->id)->first();
                            $enlisted_details = $enlisted_record->details;

                            foreach ($enlisted_details as $key => $detail) {
                                $course = $detail->schedule->course;
                                if ($course->course_type_id == 28) {
                                    $this->internship_count_pharmacy++;
                                }
                            }
                        }

                        $voluntary_contribution = null;
                        if (!is_null($enlisted->voluntary_contribution)) {
                            $voluntary_contribution = Fund::where('fund_id', 138);
                        }

                        $list  = Fund::where('part', $sem)
                            ->where(function ($q) {
                                $q->whereNotNull('fund')->where('fund', '<>', '');
                            })
                            ->orWhereIn('course_type_id', $courseTypeIds->all())
                            ->whereIn('college_id', [0, $college])
                            ->when($otherlist, function ($q) use ($otherlist) {
                                $q->union($otherlist);
                            })
                            ->when($voluntary_contribution, function ($q) use ($voluntary_contribution) {
                                $q->union($voluntary_contribution);
                            })
                            ->orderBy('opt', 'asc')
                            ->orderBy('fund_id', 'asc')
                            ->get();

                        return $list;
                    }

                default: {

                        $collegespecific = null;
                        $voluntary_contribution = null;

                        // if ($college == 2) {
                        //     $collegespecific = Fund::where('college_id', $college)->where('fund_id', 46);
                        // }

                        if (!is_null($enlisted->voluntary_contribution)) {
                            $voluntary_contribution = Fund::where('fund_id', 138);
                        }

                        if ($sem == 1) {
                            if ($year == 1) {
                                if ($this->checkIfFreshman($record->student_id)) {
                                    $courseTypeIds->push(25);
                                }
                            }
                            $courseTypeIds->push(23);
                        } else {
                            $check_enrollment = $record->enrollments->where('pref_id', $preference->id - 1)->first();
                            if ($this->checkIfFreshman($record->student_id) && is_null($check_enrollment)) {
                                $courseTypeIds->push(25);
                                $collegespecific = Fund::where('fund_id', 142);
                            }
                        }

                        $list  = Fund::where('part', $sem)
                            ->orWhereIn('course_type_id', $courseTypeIds->all())
                            ->where('college_id', 0)
                            ->where(function ($q) {
                                $q->whereNotNull('fund')->where('fund', '<>', '');
                            })
                            ->when($collegespecific, function ($q) use ($collegespecific) {
                                $q->union($collegespecific);
                            })
                            ->when($voluntary_contribution, function ($q) use ($voluntary_contribution) {
                                $q->union($voluntary_contribution);
                            })
                            ->orderBy('opt', 'asc')
                            ->orderBy('fund_id', 'asc')
                            ->get();

                        return $list;
                    }
            }
        }

        // FOR RESIDENCY
        $list = Fund::whereIn('course_type_id', $courseTypeIds->all())->get();
        return $list;
    }

    public function getSem($pref)
    {
        switch ($pref) {
            case 1:
                return 'First';
            case 2:
                return 'Second';
            case 3:
                return 'Mid-year';
        }
    }

    public function checkIfFreshman($student_id)
    {

        $pref = Preference::where('enlistment', 1)->orderBy('id', 'desc')->first();

        $now = Carbon::now();
        $year = ($pref->sem == 1) ? $now->year : $now->year - 1;
        $prefix =  $year - 2000;
        $pattern = '/' . $prefix . '-\w+/';

        $student = DB::table('dbiusis16.sresu')
            ->select('student_number')
            ->where('id', $student_id)->first();
        $matched = preg_match($pattern, $student->student_number);

        return $matched;
    }

    public function getDeductions($scholarship, $preference, $fund_id, $amount)
    {
        if (!is_null($scholarship)) {
            if (!$scholarship->chargedfull || !$scholarship->deductions->isEmpty()) {
                $sem_charged = ($scholarship->sem_charged == '') ? null : str_split($scholarship->sem_charged);
                if (is_null($sem_charged) || in_array($preference->sem, $sem_charged)) {
                    $deduction = $scholarship->deductions->where('fund_id', $fund_id)->first();
                    if (!is_null($deduction)) {
                        if ($preference->id >= 19 && $this->degree_type == 0) {

                            if ($this->free_tuition) {
                                //exclude ff scholarships
                                if (!in_array($scholarship->id, [1, 40, 8, 9, 32, 13, 12, 41, 11, 10, 65])) {
                                    $totaldeductions =  $amount * ($deduction->percent / 100);
                                    return $totaldeductions;
                                }
                            } else {
                                $totaldeductions =  $amount * ($deduction->percent / 100);
                                return $totaldeductions;
                            }
                        } else {
                            $totaldeductions =  $amount * ($deduction->percent / 100);
                            return $totaldeductions;
                        }
                    }
                }
            }
        }
        return 0;
    }

    public function getTotalTuition($semFeesList)
    {
        $totalTuition = 0;
        foreach ($semFeesList as $key => $value) {
            if ($value->fund == '644') {
                $totalTuition += $value->amount;
            }
        }
        return $totalTuition;
    }

    public function getEnlistmentDetails($student_rec_id, $pref)
    {
        $student_record = StudentRecord::find($student_rec_id);

        $query = DB::table('dbiusis16.student_records')
            ->join('dbiusis16.enlisted', 'student_records.id', '=', 'enlisted.student_rec_id')
            ->join('dbiusis16.enlisted_details', 'enlisted.id', '=', 'enlisted_details.enl_id')
            ->leftJoin('dbiusis16.schedules', 'enlisted_details.sched_id', '=', 'schedules.id')
            ->leftJoin('dbiusis16.courses', 'schedules.course_id', '=', 'courses.id')
            ->leftJoin('dbiusis16.course_types', 'courses.course_type_id', '=', 'course_types.id')
            ->select(
                'student_records.*',
                'enlisted.*',
                //                'courses.id',
                'courses.id AS course_id',
                'courses.code',
                'courses.title',
                'courses.units',
                'courses.lecture_units',
                'courses.lab_unit',
                'courses.course_type_id',
                'courses.genre_id',
                'schedules.section',
                'schedules.time',
                'schedules.day',
                'schedules.room',
                'schedules.bldg',
                'course_types.type',
                'course_types.computation'
            )
            ->where('student_records.id', '=', $student_rec_id)
            ->where('enlisted.pref_id', '=', $pref)
            ->distinct('course_id')
            ->get();

        // check if MED student
        if ($student_record->degree->id == 114) {
            $sy_courses = DB::table('dbiusis16.sy_courses')->pluck('course_id');
            foreach ($query as $key => $enl) {
                // courses  not offered for 1 sem: divide units by 2
                if (in_array($enl->course_id, $sy_courses->toArray())) {
                    $enl->units = $enl->units / 2;
                    $enl->lecture_units = $enl->lecture_units / 2;
                    $enl->lab_unit = $enl->lab_unit / 2;
                }
            }
        }


        // check if from COL
        if ($student_record->degree->college_id == 10) {
            $col_courses = DB::table('dbiusis16.col_undergrad_courses')->pluck('course_id');
            foreach ($query as $key => $enl) {
                if (in_array($enl->course_id, $col_courses->toArray())) {
                    $this->col_undergrad_courses['units'] += $enl->units;
                }
            }
            if ($this->col_undergrad_courses['units'] == $query->sum('units')) {
                $this->col_undergrad_courses['is_undergrad'] = true;
            }
        }
        return $query;
    }

    public function checkIfEnrolled($student_rec_id, $pref)
    {
        $query = DB::table('dbiusis16.student_records')
            ->join('dbiusis16.enrollments', 'student_records.id', '=', 'enrollments.student_rec_id')
            ->where('dbiusis16.student_records.id', '=', $student_rec_id)
            ->where('dbiusis16.enrollments.pref_id', '=', $pref)
            ->get();

        $status = !$query->isEmpty();

        return $status;
    }

    public function getStudentStatus($id)
    {
        $query = DB::table('dbiusis16.status')
            ->where('id', $id)
            ->first();

        return $query;
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

    public function coveredFreeEducation($record, $pref_id, $fund)
    {
        $pref = Preference::find($pref_id);
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

    public function checkListDuplicate($fund, $feesList)
    {
        foreach ($feesList as $key => $value) {
            if ($value->fund == $fund) {
                return true;
            }
        }
        return false;
    }
}
