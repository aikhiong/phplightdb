<?php
include_once(dirname(__FILE__).'/lightdb_abstract.php');

class LightDB_MySQLi extends LightDB_abstract {
	protected $timezone;
	protected $max_packet_size;		// in MB
	protected $bind_types;
	
	function __construct($host, $uid, $pwd, $instance, $tz='+00:00'){
		parent::__construct($host, $uid, $pwd, $instance);
		
		$this->timezone = $tz;
		$this->max_packet_size = 0;
		$this->bind_types = array();
	}
	
	public function get_error(){
		return $this->err_message;
	}
	
	
	public function get_connection(){
		return $this->conn;
	}
	
	
	public function set_max_packet_size($packet_size){
		if($packet_size > 0){
			$this->max_packet_size = $packet_size;
		}
	}
	
	
	public function connect(){
		$this->conn = mysqli_connect($this->host, $this->uid, $this->pwd, $this->instance);
		if(mysqli_connect_errno()){
			$this->err_message = array('code' => mysqli_connect_errno(), 'message' => mysqli_connect_error());
			return false;
		}

		
		$rs = mysqli_query($this->conn, 'SET character_set_client=utf8');
		if($rs === false){
			$this->err_message = $this->db_error();
			return false;
		}


		$rs = mysqli_query($this->conn, 'SET character_set_connection=utf8');
		if($rs === false){
			$this->err_message = $this->db_error();
			return false;
		}


		$rs = mysqli_query($this->conn, 'SET character_set_results=utf8');
		if($rs === false){
			$this->err_message = $this->db_error();
			return false;
		}
		
		
		$rs = mysqli_query($this->conn, 'SET time_zone=\''.$this->timezone.'\'');
		if($rs === false){
			$this->err_message = $this->db_error();
			return false;
		}
		
		
		if($this->max_packet_size > 0){
			$rs = mysqli_query($this->conn, 'SET global max_allowed_packet='.($this->max_packet_size * 1024 * 1024));
			if($rs === false){
				$this->err_message = $this->db_error();
				return false;
			}
		} 
		
		
		return true;
	}
	
	
	public function close(){
		$ok = mysqli_stmt($this->stmt);
		if(mysqli_stmt_errno($this->stmt)){
			$this->err_message = array('code' => mysqli_stmt_errno($this->stmt), 'message' => mysqli_stmt_error($this->stmt));
			return false;
		}
		
		
		$ok = mysqli_close($this->conn);
		if($ok === false){
			$this->err_message = $this->db_error();
			return false;
		}
			
			
		return true;
	}
	
	
	public function begin(){
		
		$ok = mysqli_autocommit($this->conn, false);
		if($ok === false){
			$this->err_message = $this->db_error();
			return false;
		}
			
		return true;
	}
	
	
	public function commit(){
		
		$ok = mysqli_commit($this->conn);
		if($ok === false){
			$this->err_message = $this->db_error();
			return false;
		}
			
		return true;
	}
	
	
	public function rollback(){
		//$ok = mysqli_query($this->conn, 'rollback');
		
		$ok = mysqli_rollback($this->conn);
		if($ok === false){
			$this->err_message = $this->db_error();
			return false;
		}
			
		return true;
	}
	
	
	public function prepare($sql){
		$q = preg_replace('/([a-z0-9_]+[\s]*=[\s]*){1,1}(:[A-Za-z0-9_]+){1,1}/', '$1 ?', $sql);
		
		$this->stmt = mysqli_prepare($this->conn, $q);
		if($this->stmt === false){
			$this->err_message = $this->db_error();
			return false;
		}
			
		if($this->debug === true){
			echo '<div>prepare() : '.$sql.'</div>';
			echo '<div>prepared() : '.$q.'</div>';
		}
		
		$this->bind = array();
		
		return $this->stmt;
	}
	
	
	public function bind($param_name, $param_value, $param_type=null){
		
		if($param_type == LIGHTDB_PARAM_TYPE_INT){
			$this->bind_types[$param_name] = 'i';
			
		} else if($param_type == LIGHTDB_PARAM_TYPE_BIGINT || $param_type == LIGHTDB_PARAM_TYPE_FLOAT){
			$this->bind_types[$param_name] = 'd';
			
		} else if($param_type == LIGHTDB_PARAM_TYPE_STRING){
			$this->bind_types[$param_name] = 's';
			
		} else {
			$this->err_message = $this->set_error(LIGHTDB_ERROR_INVALID_PARAM_TYPE);
			return false;
		}
		
		if($this->debug === true){
			echo '<div>bind('.$param_name.', '.$param_value.', \''.$this->bind_types[$param_name].'\')</div>';
		}
		
		$this->bind[$param_name] = $param_value;
		
		return true;
	}
	
	
	public function execute(){
		$str_types = array();
		
		foreach($this->bind_types as $param_name => $param_type){
			$str_types[] = $param_type;
		}
		
		
		$bind_arr = array($this->stmt, implode('', $str_types));
		
		foreach($this->bind as $param_name => $param_value){
			$bind_arr[] = &$param_value;	// mysqli_stmt_bind_param expects parameter to be passed by reference
		}
		
		call_user_func_array('mysqli_stmt_bind_param', $bind_arr);
		
		
		$ok = mysqli_stmt_execute($this->stmt);
		if(mysqli_stmt_errno($this->stmt)){
			$this->err_message = array('code' => mysqli_stmt_errno($this->stmt), 'message' => mysqli_stmt_error($this->stmt));
			return false;
		}
		
		
		$this->rs = mysqli_stmt_get_result($this->stmt);
		if(mysqli_stmt_errno($this->stmt)){
			$this->err_message = array('code' => mysqli_stmt_errno($this->stmt), 'message' => mysqli_stmt_error($this->stmt));
			return false;
		}
		
		return $this->rs;
	}
	
	
	public function query($sql){
		$this->rs = mysqli_query($this->conn, $sql);
		if($this->rs === false){
			$this->err_message = $this->db_error();
			return false;
		}
		
		return $this->rs;
	}
	
	
	public function fetch_assoc($fetch_rs=null){
		
		if($fetch_rs) {
			$grid = mysqli_fetch_array($fetch_rs, MYSQLI_ASSOC);
		} else {
			$grid = mysqli_fetch_array($this->rs, MYSQLI_ASSOC);
		}
		
		
		$this->fetched = $grid;
		
		return $grid;
	}
	
	
	public function field($colname, $fetched_row=null){
		if(is_array($fetched_row)) {
			return $fetched_row[$colname];
		} else {
			return $this->fetched[$colname];
		}
	}
	
	
	public function get_insert_id(){
		return mysqli_insert_id($this->conn);
	}
	
	
	public function getnow(){
		return 'current_timestamp';
	}
	
	
	public function getdate(){
		return 'curdate()';
	}
	
	
	public function db_error($handle=null){
		//$handle => link_identifier
		if($handle == null){
			return  array('code' => mysqli_errno($this->conn), 'message' => mysqli_error($this->conn));
		} else {
			return  array('code' => mysqli_errno($handle), 'message' => mysqli_error($handle));
		}
	}
}

?>