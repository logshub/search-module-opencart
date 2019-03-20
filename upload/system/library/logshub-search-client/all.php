<?php

function include_all_php($folder){
    foreach (glob($folder.'/*.php') as $filename){
        include_once $filename;
    }
}

include_once __DIR__ . '/Exception.php';
include_once __DIR__ . '/Model/SendableAbstract.php';
include_once __DIR__ . '/Response/ResponseAbstract.php';
include_once __DIR__ . '/Request/RequestInterface.php';

include_all_php(__DIR__ . '/Model');
include_all_php(__DIR__ . '/Csv');
include_all_php(__DIR__ . '/Response');
include_all_php(__DIR__ . '/Request');

include_once __DIR__ . '/Client.php';
include_once __DIR__ . '/GuzzleClient.php';
