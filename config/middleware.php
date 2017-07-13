<?php

return [
    /*
     * Basic Middleware
     */
    Pofol\Middleware\CSRFTokenHandleMiddleware::class,

    /*s
     * User defined Middleware
     */
    App\Middleware\BasicMiddleware::class,
];
