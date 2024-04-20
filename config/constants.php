<?php

return [
    'colleges' => [
        'GS'    => 1,
        'CAFSD' => 2,
        'CASAT' => 3,
        'CAS'   => 4,
        'CBEA'  => 5,
        'COE'   => 6,
        'CHS'   => 7,
        'CIT'   => 8,
        'CTE'   => 9,
        'COL'   => 10,
        'COM'   => 11
    ],

    'exCourseTypes' => [
        4,
        21,
        22
    ],

    'registration' => [
        '16' => [
            'start' => '2017-07-28',
            'end' => '2017-08-07'
        ],
        '17' => [
            'start' => '2018-01-04',
            'end' => '2018-01-17'
        ]
    ],

    'api_uri' => env('API_URI', 'http://127.0.0.1:8050/api/'),
    'api_token' => env('API_TOKEN', 'fHrNhRqU2AA49jZhsTWr5PueIxk6bvn0f9Ue36BZMgEmLwV0lRjar1Lr6UD0'),

];
