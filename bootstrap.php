<?php 

use App\Auth;
session_start();
require_once (__DIR__ . '/vendor/autoload.php');
require_once (__DIR__ . '/generated-conf/config.php');
require_once (__DIR__ . '/dbconf.php');
require_once (__DIR__.'/OlAssets/dbconnect.php');
$auth = new Auth();

?>