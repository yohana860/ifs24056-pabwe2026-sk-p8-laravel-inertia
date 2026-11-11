<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI
    |--------------------------------------------------------------------------
    |
    | Seluruh nilai dibaca dari environment variable. API key TIDAK PERNAH
    | dikirim ke frontend; pemanggilan OpenAI hanya terjadi di sisi server.
    |
    */

    'api_key' => env('OPENAI_API_KEY'),

    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),

    'base_url' => rtrim((string) env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/'),

    'timeout' => (int) env('OPENAI_TIMEOUT', 60),

    /*
    | Batas jumlah artikel yang boleh dihasilkan dalam satu kali generate.
    | Dipakai oleh validasi backend dan atribut min/max pada input number.
    */

    'min_quantity' => 1,

    'max_quantity' => 5,

];
