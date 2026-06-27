<?php

return [
    'max_upload_kb' => (int) env('PROFILE_FILE_MAX_KB', 10240),
    'max_files_per_upload' => 10,
    'allowed_mimes' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp'],
];
