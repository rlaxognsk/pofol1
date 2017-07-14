<?php

$Route->get('/favicon.ico', function () {
    return response()->file('favicon.png');
});

$Route->get('/files/:name', 'MainController@files');