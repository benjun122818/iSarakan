<?php

namespace App\Http\Controllers\Registrar;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CAD;
use App\Preference;
use App\Scholarship;
use App\StudentRecord;
use App\Sresu;
use App\Remark;
use App\Enlistment;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Exception\GuzzleException;
use DB;
use File;
use PDF;
use Zipper;

class Form5GenerationController extends Controller
{

    public function retryDecider()
    {
        return function (
            $retries,
            Psr7Request $request,
            Psr7Response $response = null,
            RequestException $exception = null
        ) {
            // Limit the number of retries to 5
            if ($retries >= 5) {
                return false;
            }

            // Retry connection exceptions
            if ($exception instanceof ConnectException) {
                return true;
            }

            if ($response) {
                // Retry on server errors
                if ($response->getStatusCode() >= 500) {
                    return true;
                }
            }

            return false;
        };
    }

    /**
     * delay 1s 2s 3s 4s 5s
     *
     * @return Closure
     */
    public function retryDelay()
    {
        return function ($numberOfRetries) {
            return 1000 * $numberOfRetries;
        };
    }

    public function single()
    {
        $logs = null;
        $preferences = Preference::orderByDesc('id')->get()->pluck('id', 'id');

        $arr = [
            'preferences' => $preferences,
            'logs' => $logs
        ];

        return view('registrar.generate.single', $arr);
    }

    public function batch()
    {
        $colleges = DB::table('colleges')->get()->pluck('college', 'id');
        $preferences = Preference::orderByDesc('id')->get()->pluck('id', 'id');
        $standing = [
            '0' => 'ALL',
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5'
        ];
        $limits = [
            '0' => 'ALL',
            '5' => '5'
        ];

        $arr = [
            'colleges' => $colleges,
            'preferences' => $preferences,
            'standing' => $standing,
            'limits' => $limits
        ];

        return view('registrar.generate.batch', $arr);
    }

    public function singleGenerate(Request $request)
    {
        $this->validate($request, [
            'student_number' => 'required',
            'preference_id' => 'required'
        ]);


        $student = Sresu::where('student_number', $request->student_number)->first();

        $student_info = $student->info;


        // return $logs;
        if (!is_null($student)) {
            $uri = config('constants.api_uri') . "form5/{$student->record->id}/pref/{$request->preference_id}";
            $token = config('constants.api_token');

            $client = new Client([
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$token}"
                ]
            ]);

            // dd($uri);

            $path = storage_path("/app/public/form5");
            if (!File::exists($path)) {
                $result = File::makeDirectory($path);
            } else {
                $delete_dir = File::deleteDirectory($path);
                if (!File::exists($path)) {
                    $result = File::makeDirectory($path);
                }
            }
            // dd($client->get($uri));
            try {
                $response = $client->get($uri);
                $result = json_decode($response->getBody());
                $decoded = base64_decode($result->pdf);



                $pref = Preference::where('id', $request->preference_id)->first();

                /* Lname_Gname_Mname_01_MMSU_2019_2_1

                RegionCode_SUC_AY begins_Term_Batch  
                $student_info->firstname .' '. $student_info->surname  */
                $str = $pref->cys->cy;
                $a = explode("-", $str);
                $start = $a[0];
                //return $start;

                $path = "form5/{$student_info->surname}_{$student_info->firstname}_01_MMSU_{$start}_{$pref->sem}_1.pdf";

                $filepath = storage_path("app/public/{$path}");
                file_put_contents($filepath, $decoded);

                if (file_exists($filepath)) {
                    // compact('logs')

                    return response()->download($filepath)->deleteFileAfterSend(true);
                    //return redirect()->route('form.generation.single', compact('logs'));
                }
            } catch (GuzzleException $e) {
                $request->session()->flash('alert-warning', $e);
                return redirect()->route('form.generation.single');
            }

            $request->session()->flash('alert-warning', '<strong>Oops!</strong> Failed to generate form 5!');
            return redirect()->route('form.generation.single');
        }

        $request->session()->flash('alert-warning', '<strong>Oops!</strong> Student not found!');
        return redirect()->route('form.generation.single');
    }

    public function batchGenerate(Request $request)
    {
        //set_time_limit(3600);
        ini_set('max_execution_time', -1);

        ini_set('memory_limit', '-1');
        set_time_limit(-1);

        $this->validate($request, [
            'college_id' => 'required',
            'preference_id' => 'required',
            'standing' => 'required',
            'limit' => 'required',
            'offset' => 'required'
        ]);

        $col = DB::table('colleges')->where('id', $request->college_id)->first();
        $college = $col->college;

        $students = DB::table('enrollments')
            ->join('student_records', 'enrollments.student_rec_id', '=', 'student_records.id')
            ->join('student_info', 'student_records.student_id', '=', 'student_info.student_id')
            ->where('student_records.college_id', '=', $request->college_id)
            ->where('enrollments.pref_id', '=', $request->preference_id)
            ->whereNull('enrollments.deleted_at')
            ->when(isset($request->standing), function ($query) use ($request) {
                if ($request->standing != 0) {
                    $query->where('enrollments.standing', $request->standing);
                }
            })
            ->orderBy('student_info.surname', 'asc')
            ->when(isset($request->offset), function ($query) use ($request) {
                if ($request->offset != 0) {
                    $query->skip($request->offset);
                }
            })
            ->when(isset($request->limit), function ($query) use ($request) {
                if ($request->limit != 0) {
                    $query->take($request->limit);
                }
            })
            ->get(['student_records.id']);
        //return $students; 

        if (isset($request->generate_list)) {
            return redirect()->route('form.generation.batch')
                ->with([
                    'count' => $students->count(),
                    'college_id' => $request->college_id,
                    'preference_id' => $request->preference_id,
                    'standing' => $request->standing,
                    'limit' => $request->limit,
                    'offset' => $request->offset
                ]);
        }

        $date = Carbon::now()->toDateString();
        $path = storage_path("/app/public/$college - " . preg_replace('/[-:\s]/', '', $date));

        //return $path;

        if (!File::exists($path)) {
            $result = File::makeDirectory($path);
        } else {
            $delete_dir = File::deleteDirectory($path);
            if (!File::exists($path)) {
                $result = File::makeDirectory($path);
            }
        }

        foreach ($students as $key => $student_record) {
            $srecord = StudentRecord::find($student_record->id);
            $pref = Preference::find($request->preference_id);
            $student_name = $student_record->id;

            if (!is_null($srecord)) {
                // $student_middlename = is_null($srecord->info->middlename) ? '' : ' ' . preg_replace('/[.\/]/', '', $srecord->info->middlename);
                $student_middlename = is_null($srecord->info->middlename) ? '' : '_' . preg_replace('/[.\/]/', '', $srecord->info->middlename);
                // $student_name = $srecord->info->surname . ', ' . preg_replace('/[.\/]/', '', $srecord->info->firstname) . $student_middlename;
                $student_name = $srecord->info->surname . '_' . preg_replace('/[.\/]/', '', $srecord->info->firstname) . $student_middlename;
            }

            $uri = config('constants.api_uri') . "form5/{$student_record->id}/pref/{$request->preference_id}";
            $token = config('constants.api_token');

            $handlerStack = HandlerStack::create(new CurlHandler());
            $handlerStack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));

            $client = new Client([
                'handler' => $handlerStack,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$token}"
                ]
            ]);

            try {

                // $retryCounter = 0;
                // $maxRetryCount = 10;
                // while ($retryCounter < $maxRetryCount) {
                //   $response = $client->get($uri);
                //   if ($response->getStatusCode() == 200) {
                //     break;
                //   }
                //   $retryCounter++;
                // }

                $response = $client->get($uri);

                $result = json_decode($response->getBody());
                $decoded = base64_decode($result->pdf);

                /* Lname_Gname_Mname_01_MMSU_2019_2_1

                RegionCode_SUC_AY begins_Term_Batch  
                $student_info->firstname .' '. $student_info->surname  */

                //$batch = ++$key;
                $str = $pref->cys->cy;
                $a = explode("-", $str);
                $start = $a[0];
                //return $start;

                $filepath = "{$path}/{$student_name}_01_MMSU_{$start}_{$pref->sem}_1.pdf";

                file_put_contents($filepath, $decoded);
            } catch (\Error $e) {
                file_put_contents("{$path}/_Error.txt", $srecord->info->student_number . " " . $e . PHP_EOL . PHP_EOL, FILE_APPEND);
                continue;
            } catch (GuzzleException $e) {
                file_put_contents("{$path}/_GuzzleException.txt", $srecord->info->student_number . " " . $e . PHP_EOL . PHP_EOL, FILE_APPEND);
                continue;
                // $request->session()->flash('alert-warning', $e);
                // return redirect()->route('form.generation.batch');
            }
        }

        $files = glob("$path/*");
        $offset = ($request->limit == 0) ? '' : '-' . ($request->limit + $request->offset);

        if ($request->offset != 0) {
            $offset = "-$request->limit-$request->offset";
        } else {
            $offset = ($request->limit == 0) ? '' : "-$request->limit";
        }

        $zipname = $col->collegeabbr . preg_replace('/[-:\s]/', '', $date) . "-$request->standing$offset.zip";
        $zippath = storage_path('/app/public/' . $zipname);

        if (File::exists($zippath)) {
            $delete_file = File::delete($zippath);
        }

        $zip = Zipper::make($zippath)->add($files)->close();

        $delete_dir = File::deleteDirectory($path);
        if (File::exists($zippath)) {
            return response()->download(public_path("storage/$zipname"))->deleteFileAfterSend(true);
        } else {
            $request->session()->flash('alert-warning', '<strong>Oops!</strong> Failed generating forms!');
            return redirect()->route('form.generation.batch');
        }
    }

    public function cadSingle()
    {
        $preferences = Preference::orderByDesc('id')->get()->pluck('id', 'id');

        $arr = [
            'preferences' => $preferences,
        ];

        return view('registrar.generate.cad.single', $arr);
    }

    public function cadBatch()
    {
        $colleges = DB::table('colleges')->get()->pluck('college', 'id');
        $preferences = Preference::orderByDesc('id')->get()->pluck('id', 'id');
        $standing = [
            '0' => 'ALL',
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5'
        ];
        $limits = [
            '0' => 'ALL',
            '5' => '5'
        ];

        $arr = [
            'colleges' => $colleges,
            'preferences' => $preferences,
            'standing' => $standing,
            'limits' => $limits
        ];

        return view('registrar.generate.cad.batch', $arr);
    }

    public function cadSingleGenerate(Request $request)
    {
        $this->validate($request, [
            'student_number' => 'required',
            'preference_id' => 'required'
        ]);

        $student = Sresu::where('student_number', $request->student_number)->first();

        $student_info = $student->info;

        if (!is_null($student)) {
            $uri = config('constants.api_uri') . "cad/{$student->record->id}/pref/{$request->preference_id}";
            $token = config('constants.api_token');

            $client = new Client([
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$token}"
                ]
            ]);

            $path = storage_path("/app/public/form5c");
            if (!File::exists($path)) {
                $result = File::makeDirectory($path);
            } else {
                $delete_dir = File::deleteDirectory($path);
                if (!File::exists($path)) {
                    $result = File::makeDirectory($path);
                }
            }

            try {
                $response = $client->get($uri);
                $result = json_decode($response->getBody());
                $decoded = base64_decode($result->pdf);

                $pref = Preference::where('id', $request->preference_id)->first();

                /* Lname_Gname_Mname_01_MMSU_2019_2_1

                RegionCode_SUC_AY begins_Term_Batch  
                $student_info->firstname .' '. $student_info->surname  */
                $str = $pref->cys->cy;
                $a = explode("-", $str);
                $start = $a[0];
                //return $start;

                // $path = "form5/{$student_info->surname}_{$student_info->firstname}_01_MMSU_{$start}_{$pref->sem}_1.pdf";

                $path = "form5c/{$student_info->surname}_{$student_info->firstname}_01_MMSU_{$start}_{$pref->sem}_1.pdf";

                $filepath = storage_path("app/public/{$path}");
                file_put_contents($filepath, $decoded);

                if (file_exists($filepath)) {
                    return response()->download($filepath)->deleteFileAfterSend(true);
                }
            } catch (GuzzleException $e) {
                $request->session()->flash('alert-warning', $e);
                return redirect()->route('cad.generation.single');
            }

            $request->session()->flash('alert-warning', '<strong>Oops!</strong> Failed to generate form 5!');
            return redirect()->route('cad.generation.single');
        }

        $request->session()->flash('alert-warning', '<strong>Oops!</strong> Student not found!');
        return redirect()->route('cad.generation.single');
    }

    // public function cadBatchGenerate(Request $request) {
    //     set_time_limit(3600);
    //     ini_set('max_execution_time', 3600);
    //
    //     $this->validate($request, [
    //         'college_id' => 'required',
    //         'preference_id' => 'required',
    //         'limit' => 'required',
    //         'offset' => 'required'
    //     ]);
    //
    //     $col = DB::table('colleges')->where('id', $request->college_id)->first();
    //     $college = $col->college;
    //
    //     $students = DB::table('cad')
    //                     ->join('student_records', 'cad.student_rec_id', '=', 'student_records.id')
    //                     // ->join('student_info', 'student_records.student_id', '=', 'student_info.student_id')
    //                     ->where('student_records.college_id', '=', $request->college_id)
    //                     ->where('cad.pref_id', '=', $request->preference_id)
    //                     ->where('cad.dac_status', '=', 5)
    //                     // ->orderBy('student_info.surname', 'asc')
    //                     ->when(isset($request->offset), function($query) use($request) {
    //                         if ($request->offset != 0) {
    //                             $query->skip($request->offset);
    //                         }
    //                     })
    //                     ->when(isset($request->limit), function($query) use($request) {
    //                         if ($request->limit != 0) {
    //                             $query->take($request->limit);
    //                         }
    //                     })
    //                     ->get(['student_records.id']);
    //
    //     if (isset($request->generate_list)) {
    //         return redirect()->route('cad.generation.batch')
    //                 ->with([
    //                     'count' => $students->count(),
    //                     'college_id' => $request->college_id,
    //                     'preference_id' => $request->preference_id,
    //                     'limit' => $request->limit,
    //                     'offset' => $request->offset
    //                 ]);
    //     }
    //
    //     $date = Carbon::now()->toDateString();
    //     $path = storage_path("/app/public/CAD - $college - " . preg_replace('/[-:\s]/', '', $date));
    //
    //     if (!File::exists($path)) {
    //         $result = File::makeDirectory($path);
    //     } else {
    //         $delete_dir = File::deleteDirectory($path);
    //         if (!File::exists($path)) {
    //             $result = File::makeDirectory($path);
    //         }
    //     }
    //
    //     foreach ($students as $key => $student) {
    //         $srecord = StudentRecord::find($student->id);
    //         $student_name = $student->id;
    //
    //         if (!is_null($srecord)) {
    //             $student_middlename = is_null($srecord->info->middlename) ? '' : '_' . preg_replace('/[.\/]/', '', $srecord->info->middlename);
    //             // $student_name = $srecord->info->surname . ', ' . preg_replace('/[.\/]/', '', $srecord->info->firstname) . $student_middlename;
    //             $student_name = $srecord->info->surname . '_' . preg_replace('/[.\/]/', '', $srecord->info->firstname) . $student_middlename;
    //         }
    //
    //         $uri = config('constants.api_uri') . "/cad/{$student->id}/pref/{$request->preference_id}";
    //         $token = config('constants.api_token');
    //
    //         $client = new Client([
    //             'headers' => [
    //                 'Content-Type' => 'application/json',
    //                 'Authorization' => "Bearer {$token}"
    //             ]
    //         ]);
    //
    //         try {
    //             $response = $client->get($uri);
    //             $result = json_decode($response->getBody());
    //             $decoded = base64_decode($result->pdf);
    //
    //             $filepath = "{$path}/{$student_name}_01_MMSU_2018_1_1.pdf";
    //
    //             file_put_contents($filepath, $decoded);
    //
    //         } catch (GuzzleException $e) {
    //             continue;
    //         }
    //     }
    //
    //     $files = glob("$path/*");
    //     $zipname = "CAD-" . $col->collegeabbr . preg_replace('/[-:\s]/', '', $date) . ".zip";
    //     $zippath = storage_path('/app/public/' . $zipname);
    //
    //     if (File::exists($zippath)) {
    //         $delete_file = File::delete($zippath);
    //     }
    //
    //     $zip = Zipper::make($zippath)->add($files)->close();
    //
    //     if (File::exists($zippath)) {
    //         $delete_dir = File::deleteDirectory($path);
    //         return response()->download(public_path("storage/$zipname"))->deleteFileAfterSend(true);
    //     } else {
    //         $delete_dir = File::deleteDirectory($path);
    //         $request->session()->flash('alert-warning', '<strong>Oops!</strong> Failed generating forms!');
    //         return redirect()->route('cad.generation.batch');
    //     }
    // }

    public function cadBatchGenerate(Request $request)
    {
        set_time_limit(3600);
        ini_set('max_execution_time', 3600);

        $this->validate($request, [
            'college_id' => 'required',
            'preference_id' => 'required',
            'limit' => 'required',
            'offset' => 'required'
        ]);

        $col = DB::table('colleges')->where('id', $request->college_id)->first();
        $college = $col->college;

        $students = DB::table('cad')
            ->join('student_records', 'cad.student_rec_id', '=', 'student_records.id')
            // ->join('student_info', 'student_records.student_id', '=', 'student_info.student_id')
            ->where('student_records.college_id', '=', $request->college_id)
            ->where('cad.pref_id', '=', $request->preference_id)
            ->where('cad.dac_status', '=', 5)
            // ->orderBy('student_info.surname', 'asc')
            ->when(isset($request->offset), function ($query) use ($request) {
                if ($request->offset != 0) {
                    $query->skip($request->offset);
                }
            })
            ->when(isset($request->limit), function ($query) use ($request) {
                if ($request->limit != 0) {
                    $query->take($request->limit);
                }
            })
            ->get(['student_records.id']);

        if (isset($request->generate_list)) {
            return redirect()->route('cad.generation.batch')
                ->with([
                    'count' => $students->count(),
                    'college_id' => $request->college_id,
                    'preference_id' => $request->preference_id,
                    'limit' => $request->limit,
                    'offset' => $request->offset
                ]);
        }

        $date = Carbon::now()->toDateString();
        $path = storage_path("/app/public/CAD - $college - " . preg_replace('/[-:\s]/', '', $date));

        if (!File::exists($path)) {
            $result = File::makeDirectory($path);
        } else {
            $delete_dir = File::deleteDirectory($path);
            if (!File::exists($path)) {
                $result = File::makeDirectory($path);
            }
        }

        foreach ($students as $key => $student) {
            $srecord = StudentRecord::find($student->id);
            $pref = Preference::find($request->preference_id);
            $student_name = $student->id;

            if (!is_null($srecord)) {
                $student_middlename = is_null($srecord->info->middlename) ? '' : '_' . preg_replace('/[.\/]/', '', $srecord->info->middlename);
                // $student_name = $srecord->info->surname . ', ' . preg_replace('/[.\/]/', '', $srecord->info->firstname) . $student_middlename;
                $student_name = $srecord->info->surname . '_' . preg_replace('/[.\/]/', '', $srecord->info->firstname) . $student_middlename;
            }

            $uri = config('constants.api_uri') . "cad/{$student->id}/pref/{$request->preference_id}";
            $token = config('constants.api_token');

            $client = new Client([
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$token}"
                ]
            ]);

            try {
                $response = $client->get($uri);
                $result = json_decode($response->getBody());
                $decoded = base64_decode($result->pdf);

                $str = $pref->cys->cy;
                $a = explode("-", $str);
                $start = $a[0];
                //return $start;

                $filepath = "{$path}/{$student_name}_01_MMSU_{$start}_{$pref->sem}_1.pdf";

                //$filepath = "{$path}/{$student_name}_01_MMSU_2018_1_1.pdf";

                file_put_contents($filepath, $decoded);
            } catch (GuzzleException $e) {
                continue;
            }
        }

        $files = glob("$path/*");
        $zipname = "CAD-" . $col->collegeabbr . preg_replace('/[-:\s]/', '', $date) . ".zip";
        $zippath = storage_path('/app/public/' . $zipname);

        if (File::exists($zippath)) {
            $delete_file = File::delete($zippath);
        }

        $zip = Zipper::make($zippath)->add($files)->close();

        if (File::exists($zippath)) {
            $delete_dir = File::deleteDirectory($path);
            return response()->download(public_path("storage/$zipname"))->deleteFileAfterSend(true);
        } else {
            $delete_dir = File::deleteDirectory($path);
            $request->session()->flash('alert-warning', '<strong>Oops!</strong> Failed generating forms!');
            return redirect()->route('cad.generation.batch');
        }
    }


    public function cadIdSingle()
    {
        $preferences = Preference::orderByDesc('id')->get()->pluck('id', 'id');

        $arr = [
            'preferences' => $preferences,
        ];

        return view('registrar.generate.cad-by-id.single', $arr);
    }

    public function cadIdBatch()
    {
        $colleges = DB::table('colleges')->get()->pluck('college', 'id');
        $preferences = Preference::orderByDesc('id')->get()->pluck('id', 'id');
        $standing = [
            '0' => 'ALL',
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5'
        ];
        $limits = [
            '0' => 'ALL',
            '5' => '5'
        ];

        $arr = [
            'colleges' => $colleges,
            'preferences' => $preferences,
            'standing' => $standing,
            'limits' => $limits
        ];

        return view('registrar.generate.cad-by-id.batch', $arr);
    }

    public function cadIdSingleGenerate(Request $request)
    {
        $this->validate($request, [
            'cad_id' => 'required'
        ]);

        $cad = CAD::find($request->cad_id);

        if (!is_null($cad)) {
            $uri = config('constants.api_uri') . "/form5c/{$request->cad_id}";
            $token = config('constants.api_token');

            $client = new Client([
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$token}"
                ]
            ]);

            $path = storage_path("/app/public/form5c");
            if (!File::exists($path)) {
                $result = File::makeDirectory($path);
            } else {
                $delete_dir = File::deleteDirectory($path);
                if (!File::exists($path)) {
                    $result = File::makeDirectory($path);
                }
            }

            try {
                $response = $client->get($uri);
                $result = json_decode($response->getBody());
                $decoded = base64_decode($result->pdf);

                $pref = Preference::where('id', $cad->pref_id)->first();

                $path = "form5c/{$cad->id}_form5c_{$pref->cys->cy}_{$pref->sem}.pdf";

                $filepath = storage_path("app/public/{$path}");
                file_put_contents($filepath, $decoded);

                if (file_exists($filepath)) {
                    return response()->download($filepath)->deleteFileAfterSend(true);
                }
            } catch (GuzzleException $e) {
                $request->session()->flash('alert-warning', $e);
                return redirect()->route('cad.generation.single');
            }

            $request->session()->flash('alert-warning', '<strong>Oops!</strong> Failed to generate form 5!');
            return redirect()->route('cad.generation.single');
        }

        $request->session()->flash('alert-warning', '<strong>Oops!</strong> Student not found!');
        return redirect()->route('cad.generation.single');
    }

    public function cadIdBatchGenerate(Request $request)
    {
        set_time_limit(3600);
        ini_set('max_execution_time', 3600);

        $this->validate($request, [
            'college_id' => 'required',
            'preference_id' => 'required',
            'limit' => 'required',
            'offset' => 'required'
        ]);

        $col = DB::table('colleges')->where('id', $request->college_id)->first();
        $college = $col->college;

        $cads = DB::table('cad')
            ->join('student_records', 'cad.student_rec_id', '=', 'student_records.id')
            // ->join('student_info', 'student_records.student_id', '=', 'student_info.student_id')
            ->where('student_records.college_id', '=', $request->college_id)
            ->where('cad.pref_id', '=', $request->preference_id)
            ->where('cad.dac_status', '=', 5)
            // ->orderBy('student_info.surname', 'asc')
            ->when(isset($request->offset), function ($query) use ($request) {
                if ($request->offset != 0) {
                    $query->skip($request->offset);
                }
            })
            ->when(isset($request->limit), function ($query) use ($request) {
                if ($request->limit != 0) {
                    $query->take($request->limit);
                }
            })
            ->get(['cad.id']);

        if (isset($request->generate_list)) {
            return redirect()->route('cadid.generation.batch')
                ->with([
                    'count' => $cads->count(),
                    'college_id' => $request->college_id,
                    'preference_id' => $request->preference_id,
                    'limit' => $request->limit,
                    'offset' => $request->offset
                ]);
        }

        $date = Carbon::now()->toDateString();
        $path = storage_path("/app/public/CAD - $college - " . preg_replace('/[-:\s]/', '', $date));

        if (!File::exists($path)) {
            $result = File::makeDirectory($path);
        } else {
            $delete_dir = File::deleteDirectory($path);
            if (!File::exists($path)) {
                $result = File::makeDirectory($path);
            }
        }

        foreach ($cads as $key => $value) {
            try {
                $cad_record = DB::table('cad')->where('id', $value->id)->first(); //CAD::find($value->id);
                $srecord = StudentRecord::find($cad_record->student_rec_id);
                $student_name = $value->id;
            } catch (\Exception $e) {
                dd($value->id, $cad_record, $srecord);
            }


            if (!is_null($srecord)) {
                $student_middlename = is_null($srecord->info->middlename) ? '' : '_' . preg_replace('/[.\/]/', '', $srecord->info->middlename);
                // $student_name = $srecord->info->surname . ', ' . preg_replace('/[.\/]/', '', $srecord->info->firstname) . $student_middlename;
                $student_name = $srecord->info->surname . '_' . preg_replace('/[.\/]/', '', $srecord->info->firstname) . $student_middlename;
            }

            $uri = config('constants.api_uri') . "/form5c/{$value->id}";
            $token = config('constants.api_token');

            $client = new Client([
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$token}"
                ]
            ]);

            try {
                $response = $client->get($uri);
                $result = json_decode($response->getBody());
                $decoded = base64_decode($result->pdf);

                $filepath = "{$path}/{$student_name}_01_MMSU_2018_1_1 {$value->id}.pdf";

                file_put_contents($filepath, $decoded);
            } catch (GuzzleException $e) {
                continue;
            }
        }

        $files = glob("$path/*");
        $zipname = "CAD-" . $col->collegeabbr . preg_replace('/[-:\s]/', '', $date) . ".zip";
        $zippath = storage_path('/app/public/' . $zipname);

        if (File::exists($zippath)) {
            $delete_file = File::delete($zippath);
        }

        $zip = Zipper::make($zippath)->add($files)->close();

        if (File::exists($zippath)) {
            $delete_dir = File::deleteDirectory($path);
            return response()->download(public_path("storage/$zipname"))->deleteFileAfterSend(true);
        } else {
            $delete_dir = File::deleteDirectory($path);
            $request->session()->flash('alert-warning', '<strong>Oops!</strong> Failed generating forms!');
            return redirect()->route('cadid.generation.batch');
        }
    }

    public function logs_index()
    {
        $logs = null;
        $preferences = Preference::orderByDesc('id')->get()->pluck('id', 'id');

        $arr = [
            'preferences' => $preferences,
            'logs' => $logs
        ];

        return view('registrar.generate.logs', $arr);
    }
    public function logs_generate(Request $request)
    {
        $this->validate($request, [
            'student_number' => 'required',
            'preference_id' => 'required'
        ]);

        $student = Sresu::where('student_number', $request->student_number)->first();

        $student_info = $student->info;

        $student_rec = StudentRecord::where('student_id', $student_info->student_id)->first();

        $enlisted = $student_rec->enlistment()->where('pref_id', $request->preference_id)->orderBy('created_at', 'desc')->first();

        $logs = null;
        if (!is_null($enlisted)) {
            $logs = Remark::where('enl_id', $enlisted->id)->orderBy('created_at', 'desc')->get();
        }
        //return $logs;

        $preferences = Preference::orderByDesc('id')->get()->pluck('id', 'id');

        $arr = [
            'preferences' => $preferences,
            'logs' => $logs
        ];

        session()->flashInput($request->input());

        //return back()->with('logs', $logs);
        return view('registrar.generate.logs', $arr);
    }
}
