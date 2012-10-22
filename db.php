<?php
include_once(dirname(__FILE__).'/lightdb.php');

global $DB, $DBNAME, $DBHOST, $DBUID, $DBPWD, $DBINSTANCE, $DBTIMEZONE;

$DBTIMEZONE = '+00:00';
$DBNAME = LIGHTDB_NAME_MYSQLI;
$DBHOST = '127.0.0.1';

$DBUID = 'db_user';
$DBPWD = 'db_pwd';
$DBINSTANCE = 'db_name';

$DB = new LightDB($DBNAME, $DBHOST, $DBUID, $DBPWD, $DBINSTANCE, $DBTIMEZONE);
if($DB->connect() === false){
	die($DB->error_message());
}


?>