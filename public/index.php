<?php

require_once('../vendor/autoload.php');

$client = new \EcomailRaynet\Client('sheetpiano@seznam.cz', 'crm-cef529a7397b4648bb852e0696ed0011', 'sheetpiano');

var_dump($client->makeRequest('GET', 'api/v2/salesOrder/'));