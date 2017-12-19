#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

$database = file_get_contents(__DIR__ . '/database.yml');

$yasql = new aryelgois\YaSql\YaSql($database);

echo $yasql->output();
