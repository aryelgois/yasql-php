#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

$database = file_get_contents(__DIR__ . '/database.yml');

echo aryelgois\YaSql\Controller::generate($database);
