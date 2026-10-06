<?php

return [

    /*
    | All money values in the API are integers in the smallest unit of this
    | currency (for SAR: halalas), so 2500 means 25.00 SAR.
    */
    'currency' => env('SHIPPING_CURRENCY', 'SAR'),

    'max_weight_grams' => (int) env('SHIPPING_MAX_WEIGHT_GRAMS', 70000),

];
