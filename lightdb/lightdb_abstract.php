<?php

define('LIGHTDB_PARAM_TYPE_INT', 'integer');
define('LIGHTDB_PARAM_TYPE_BIGINT', 'bigint');
define('LIGHTDB_PARAM_TYPE_FLOAT', 'float');
define('LIGHTDB_PARAM_TYPE_STRING', 'string');

define('LIGHTDB_ERROR_INVALID_PARAM_TYPE', 1);
define('LIGHTDB_ERROR_INVALID_SEQUENCE', 2);

abstract class LightDB_abstract {
	public $err_message;
	
	protected $conn;
	protected $sql;
	protected $stmt;
	protected $rs;
	protected $fetched;
	
	protected $host;
	protected $uid;
	protected $pwd;
	protected $instance;
	protected $debug;
	
	abstract public function get_connection();
	abstract public function connect();
	abstract public function close();
	abstract public function begin();
	abstract public function commit();
	abstract public function rollback();
	abstract public function prepare($sql);
	abstract public function bind($param_name, $param_value, $param_type=null);
	abstract public function execute();
	abstract public function query($sql);
	abstract public function fetch_assoc($fetch_rs=null);
	abstract public function field($colname, $fetched_row=null);
	abstract public function getnow();
	abstract public function getdate();
	abstract public function db_error($handle=null);
	abstract public function get_error();
	
	function __construct($host, $uid, $pwd, $instance){
		$this->err_message = array();
		
		$this->host = $host;
		$this->uid = $uid;
		$this->pwd = $pwd;
		$this->instance = $instance;
		
		$this->debug = false;
		
		$this->fetched = array();
		
	}
	
	public function set_debug($val){
		$this->debug = (boolean) $val;
	}
	
	protected function set_error($err_code){
		if($err_code == LIGHTDB_ERROR_INVALID_PARAM_TYPE){
			return array('code' => LIGHTDB_ERROR_INVALID_PARAM_TYPE, 'message' => 'Invalid parameter type.');
		} else if($err_code == LIGHTDB_ERROR_INVALID_SEQUENCE){
			return array('code' => LIGHTDB_ERROR_INVALID_SEQUENCE, 'message' => 'Invalid sequence or sequence name not provided.');
		}
	}
}

?>