<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
require_once dirname(__FILE__).'/../vendor/autoload.php';
require_once 'config/config.php';

date_default_timezone_set('America/Edmonton');

NDB::$db = 'nterchange_test';
$db = NDB::connect(NDB::serverDSN());
$db->loadModule('Manager');
$db->loadModule('Datatype');

echo '### dropping: '.NDB::$db."\n";
$db->exec(' DROP DATABASE IF EXISTS `'.NDB::$db.'`; ');
echo '### creating: '.NDB::$db."\n";
$db->exec(' CREATE DATABASE `'.NDB::$db.'`; ');

$db->setDatabase(NDB::$db);

$schema_file = dirname(__FILE__).'/fixtures/base_schema.sql';
$data_file   = dirname(__FILE__).'fixtures/base_data.sql';

NDB::seed($db, $schema_file);
NDB::seed($db, $data_file);
