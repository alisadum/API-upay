<?php

return [

    /*
    |--------------------------------------------------------------------------
    | QR Code Backend
    |--------------------------------------------------------------------------
    |
    | Pilih backend untuk generate QR Code.
    | Opsi: 'imagick' atau 'gd'
    | Kita paksa pakai 'gd' biar aman tanpa imagick.
    |
    */

    'backend' => 'gd',

];
