<?php
include_once(dirname(__FILE__).'/lightdb_abstract.php');

class LightDB_MySQL extends LightDB_abstract {
	protected $timezone;
	protected $max_packet_size;		// in MB
	
	function __construct($host, $uid, $pwd, $instance, $tz='+00:00'){
		parent::__construct($host, $uid, $pwd, $instance);
		
		$this->timezone = $tz;
		$this->max_packet_size = 0;
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
		$this->conn = mysql_connect($this->host, $this->uid, $this->pwd);
		if(!$this->conn){
			$this->err_message = $this->db_error();
			return false;
		}


		$rs = mysql_select_db($this->instance, $this->conn);
		if (!$rs) {
			$this->err_message = $this->db_error();
			return false;
		}



		$rs = mysql_query('SET character_set_client=utf8', $this->conn);
		if($rs === false){
			$this->err_message = $this->db_error();
			return false;
		}


		$rs = mysql_query('SET character_set_connection=utf8', $this->conn);
		if($rs === false){
			$this->err_message = $this->db_error();
			return false;
		}


		$rs = mysql_query('SET character_set_results=utf8', $this->conn);
		if($rs === false){
			$this->err_message = $this->db_error();
			return false;
		}
		
		
		$rs = mysql_query('SET time_zone=\''.$this->timezone.'\'', $this->conn);
		if($rs === false){
			$this->err_message = $this->db_error();
			return false;
		}
		
		
		if($this->max_packet_size > 0){
			$rs = mysql_query('SET global max_allowed_packet='.($this->max_packet_size * 1024 * 1024), $this->conn);
			if($rs === false){
				$this->err_message = $this->db_error();
				return false;
			}
		}
		
		
		return true;
	}
	
	
	public function close(){
		$ok = mysql_close($this->conn);
		if($ok === false){
			$this->err_message = $this->db_error();
			return false;
		}
			
			
		return true;
	}
	
	
	public function begin(){
		$rs = mysql_query('start transaction', $this->conn);
		if($rs === false){
			$this->err_message = $this->db_error();
			return false;
		}
			
		return true;
	}
	
	
	public function commit(){
		$rs = mysql_query('commit', $this->conn);
		if($rs === false){
			$this->err_message = $this->db_error();
			return false;
		}
			
		return true;
	}
	
	
	public function rollback(){
		$rs = mysql_query('rollback', $this->conn);
		if($rs === false){
			$this->err_message = $this->db_error();
			return false;
		}
			
		return true;
	}
	
	
	public function prepare($sql){
		$this->stmt = $sql;
			
		if($this->debug === true){
			echo '<div>prepare() : '.$this->stmt.'</div>';
		}
		
		$this->bind = array();
		
		return true;
	}
	
	
	public function bind($param_name, $param_value, $param_type=null){
		if($param_type == LIGHTDB_PARAM_TYPE_INT){
			$this->stmt = str_replace($param_name, '%d', $this->stmt);
			
		} else if($param_type == LIGHTDB_PARAM_TYPE_BIGINT){
			$this->stmt = str_replace($param_name, '%0.0f', $this->stmt);
			
		} else if($param_type == LIGHTDB_PARAM_TYPE_FLOAT){
			$this->stmt = str_replace($param_name, '%f', $this->stmt);
			
		} else if($param_type == LIGHTDB_PARAM_TYPE_STRING){
			$this->stmt = str_replace($param_name, '\'%s\'', $this->stmt);
			
		} else {
			$this->err_message = $this->set_error(LIGHTDB_ERROR_INVALID_PARAM_TYPE);
			return false;
		}
		
		if($this->debug === true){
			echo '<div>bind('.$param_name.', '.$param_value.') : '.$this->stmt.'</div>';
		}
		
		
		$this->bind[$param_name] = $param_value;
		return true;
	}
	
	
	public function execute(){
		$escape_string = '';
		
		foreach($this->bind as $param_name => $param_value){
			if(strlen($escape_string) > 0) {
				$escape_string .= ', mysql_real_escape_string($this->bind[\''.$param_name.'\'], $this->conn)';
			} else {
				$escape_string = 'mysql_real_escape_string($this->bind[\''.$param_name.'\'], $this->conn)';
			}
		}
		
		if(strlen($escape_string) > 0){
			$ex = '$q = sprintf($this->stmt, '.$escape_string.');';
			if($this->debug === true){
				echo '<div>'.$ex.'</div>';
			}
			
			eval($ex);
		} else {
			$q = $this->stmt;
		}
		
		if($this->debug === true)
			echo '<div>execute() : '.$q.'</div>';
		
		
		$this->rs = mysql_query($q, $this->conn);
		if($this->rs === false){
			$this->err_message = $this->db_error();
			return false;
		}
		
		
		return $this->rs;
	}
	
	
	public function query($sql){
		$this->rs = mysql_query($sql, $this->conn);
		if($this->rs === false){
			$this->err_message = $this->db_error();
			return false;
		}
		
		return $this->rs;
	}
	
	
	public function fetch_assoc($fetch_rs=null){
		
		if($fetch_rs) {
			$grid = mysql_fetch_assoc($fetch_rs);
		} else {
			$grid = mysql_fetch_assoc($this->rs);
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
		return mysql_insert_id($this->conn);
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
			return  array('code' => mysql_errno(), 'message' => mysql_error());
		} else {
			return  array('code' => mysql_errno($handle), 'message' => mysql_error($handle));
		}
	}
}

?>