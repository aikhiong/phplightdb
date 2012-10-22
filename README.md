phplightdb
==========

Light Database Abstract for common MySQL, MySQLi and Oracle query functions


Sample
-------

	include_once(dirname(__FILE__).'/lightdb.php');
	
	global $DB, $DBNAME, $DBHOST, $DBUID, $DBPWD, $DBINSTANCE, $DBTIMEZONE;
	
	/*
		Database Drivers:
		LIGHTDB_NAME_MYSQLI 	=> mysqli
		LIGHTDB_NAME_MYSQL		=> mysql
		LIGHTDB_NAME_ORACLE		=> oracle
	*/
	
	$DBNAME = LIGHTDB_NAME_MYSQLI;
	$DBHOST = '127.0.0.1';
	
	$DBUID = 'db_user';
	$DBPWD = 'db_pwd';
	$DBINSTANCE = 'db_name';
	
	$DB = new LightDB($DBNAME, $DBHOST, $DBUID, $DBPWD, $DBINSTANCE);
	if($DB->connect() === false){
		die($DB->error_message());
	}
	
	
	$sql = 'select * from table_name where col_name_1 = :bind_param';
	
	$ok = $DB->prepare($sql);
	if($ok === false){
		die($DB->error_message());
	}
	
	
	/*
		Parameter types:
		- LIGHTDB_PARAM_TYPE_INT
		- LIGHTDB_PARAM_TYPE_BIGINT
		- LIGHTDB_PARAM_TYPE_FLOAT
		- LIGHTDB_PARAM_TYPE_STRING
	*/
	$DB->bind(':bind_param', 1, LIGHTDB_PARAM_TYPE_INT);
	
	
	$rs = $DB->execute();
	if($rs === false){
		die($DB->error_message());
	}
	
	while($grid = $DB->fetch_assoc($rs)){
		echo $DB->field('col_name_2', $grid);
		echo $DB->field('col_name_3', $grid);
		echo $DB->field('col_name_4', $grid);
	}
	
	
