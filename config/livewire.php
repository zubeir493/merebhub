<?php

return [
    'temporary_file_upload' => [
        'rules' => ['required', 'file', 'max:102400'],
        'max_upload_time' => 15,
    ],
];
