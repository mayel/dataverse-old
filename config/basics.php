<?php

if (!$bv->base_path) {
    $bv->base_path = (dirname(dirname(__FILE__))).'/';
}

require_once('secrets.php');
require_once($bv->base_path.'src/misc.php');

if (!$bv->db_path) {
    $bv->db_path = $bv->base_path.'custom/db.txt';
} // fallback to SQLite

$bv->autoloader = require_once($bv->base_path.'vendor/autoload.php');

use RedBeanPHP\R;

class_alias('\RedBeanPHP\R', '\R');

if ($bv->db_type=='mysql') {
    R::setup('mysql:host='.$bv->dbcreds['host'].';dbname='.$bv->db_name, $bv->dbcreds['user'], $bv->dbcreds['pass']);
} elseif ($bv->db_type=='postgres') {
    R::setup('pgsql:host='.$bv->dbcreds['host'].';dbname='.$bv->db_name, $bv->dbcreds['user'], $bv->dbcreds['pass']);
} //postgresql

else {
    R::setup('sqlite:'.$bv->db_path, $bv->dbcreds['user'], $bv->dbcreds['pass']);
} //sqlite


$bv->table_respondent = 'respondent';
$bv->table_response = 'response';
$bv->table_answer = 'answer';
