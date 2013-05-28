<?php
define('LIGHTDB_NAME_MYSQL', 'mysql');
define('LIGHTDB_NAME_MYSQLI', 'mysqli');
define('LIGHTDB_NAME_ORACLE', 'oracle');

class LightDB {
	public $err_message;
	
	protected $dbname;
	protected $db;
	
	function __construct($name, $host, $uid, $pwd, $instance, $tz='+00:00'){
		$this->err_message = array();
		$this->dbname = $name;
		
		switch($this->dbname){
			case LIGHTDB_NAME_MYSQL:
				include_once(dirname(__FILE__).'/lightdb/mysql.php');
				$this->db = new LightDB_MySQL($host, $uid, $pwd, $instance, $tz);
				break;
				
			case LIGHTDB_NAME_MYSQLI:
				include_once(dirname(__FILE__).'/lightdb/mysqli.php');
				$this->db = new LightDB_MySQLi($host, $uid, $pwd, $instance, $tz);
				break;
				
			case LIGHTDB_NAME_ORACLE:
				include_once(dirname(__FILE__).'/lightdb/oracle.php');
				$this->db = new LightDB_Oracle($host, $uid, $pwd, $instance);
				break;
			
			default:
				break;
		}
	}
	
	
	public function set_debug($val){
		$this->db->set_debug($val);
	}
	
	
	public function error(){
		return $this->db->get_error();
	}
	
	
	public function error_code(){
		$error = $this->db->get_error();
		return $error['code'];
	}
	
	
	public function error_message(){
		$error = $this->db->get_error();
		return $error['message'];
	}
	
	
	public function get_connection(){
		return $this->db->get_connection();
	}
	
	
	public function set_max_packet_size($packet_size){
		if($this->dbname == LIGHTDB_NAME_MYSQL){
			$this->db->set_max_packet_size($packet_size);
		}
	}
	
	
	public function connect(){
		return $this->db->connect();
	}
	
	
	public function close(){
		return $this->db->close();
	}
	
	
	public function begin(){
		return $this->db->begin();
	}
	
	
	public function commit(){
		return $this->db->commit();
	}
	
	
	public function rollback(){
		return $this->db->rollback();
	}
	
	
	public function prepare($sql){
		return $this->db->prepare($sql);
	}
	
	public function stmt_close($stmt=null){
		if($this->dbname == LIGHTDB_NAME_MYSQLI || $this->dbname == LIGHTDB_NAME_ORACLE){
			return $this->db->stmt_close($stmt);
		}
	}
	
	public function bind($param_name, $param_value, $param_type=null){
		return $this->db->bind($param_name, $param_value, $param_type);
	}
	
	
	public function execute(){
		return $this->db->execute();
	}
	
	
	public function stmt_bind($stmt, $param_name, $param_value, $param_type=null, &$bind_types, &$bind){
		if($this->dbname == LIGHTDB_NAME_MYSQL){
			return $this->db->stmt_bind($stmt, $param_name, $param_value, $param_type, $bind_types, $bind);
		} else if($this->dbname == LIGHTDB_NAME_MYSQLI){
			return $this->db->stmt_bind($stmt, $param_name, $param_value, $param_type, $bind_types, $bind);
		} else if($this->dbname == LIGHTDB_NAME_ORACLE){
			return $this->db->stmt_bind($stmt, $param_name, $param_value);
		}
	}
	
	
	public function stmt_execute($stmt, $bind_types=array(), $bind=array(), $get_result=false){
		if($this->dbname == LIGHTDB_NAME_MYSQL){
			return $this->db->stmt_execute($stmt, $bind_types, $bind);
		} else if($this->dbname == LIGHTDB_NAME_MYSQLI){
			return $this->db->stmt_execute($stmt, $bind_types, $bind, $get_result);
		} else if($this->dbname == LIGHTDB_NAME_ORACLE){
			return $this->db->stmt_execute($stmt);
		}
	}
	
	
	public function query($sql){
		return $this->db->query($sql);
	}
	
	
	public function bind_query($sql, $bind=array()){
		return $this->db->bind_query($sql, $bind);
	}
	
	
	public function fetch_assoc($fetch_rs=null){
		return $this->db->fetch_assoc($fetch_rs);
	}
	
	
	public function field($colname, $fetched_row=null){
		return $this->db->field($colname, $fetched_row);
	}
	
	
	public function get_insert_id($sequence_name=null){
		return $this->db->get_insert_id($sequence_name);
	}
	
	
	public function getnow(){
		return $this->db->getnow();
	}
	
	
	public function getdate(){
		return $this->db->getdate();
	}
	
	
	protected function set_error($err_code){
		if($err_code == LIGHTDB_ERROR_INVALID_DBNAME){
			return array('code' => LIGHTDB_ERROR_INVALID_DBNAME, 'message' => 'Invalid database name.');
		}
	}
}

?>