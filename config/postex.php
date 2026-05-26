<?php

return [
    'base_url' => env('POSTEX_BASE_URL', 'https://api.postex.pk/services/integration/api'),
    'timeout'  => (int) env('POSTEX_TIMEOUT', 30),
];
