<?php
include_once(dirname(__FILE__).'/lightdb_abstract.php');

class LightDB_Oracle extends LightDB_abstract {
	protected $transaction_mode;
	
	function __construct($host, $uid, $pwd, $instance){
		parent::__construct($host, $uid, $pwd, $instance);
		
		$this->transaction_mode = false;
	}
	
	public function get_error(){
		return $this->err_message;
	}
	
	
	public function get_connection(){
		return $this->conn;
	}
	
	
	public function connect(){
		$this->conn = oci_connect($this->uid, $this->pwd, $this->host, 'utf8');
		if(!$this->conn){
			$this->err_message = $this->db_error();
			return false;
		}
		
		return true;
	}
	
	
	public function close(){
		$ok = oci_free_statement($this->stmt);
		if($ok === false){
			$this->err_message = $this->db_error();
			return false;
		}
		
		$ok = oci_close($this->conn);
		if($ok === false){
			$this->err_message = $this->db_error();
			return false;
		}
		
		return true;
	}
	
	
	public function begin(){
		$this->transaction_mode = true;
		
		return true;
	}
	
	
	public function commit(){
		if(oci_commit($this->conn) === false){
			$this->err_message = $this->db_error();
			return false;
		}
		
		return true;
	}
	
	
	public function rollback(){
		if(oci_rollback($this->conn) === false){
			$this->err_message = $this->db_error();
			return false;
		}
		
		return true;
	}
	
	
	public function prepare($sql){
		$this->stmt = oci_parse($this->conn, $sql);
		if($this->stmt === false){
			$this->err_message = $this->db_error($this->stmt);
			return false;
		}
		
		if($this->debug === true){
			echo '<div>prepare() : '.$sql.'</div>';
		}
		
		return $this->stmt;
	}
	
	
	public function stmt_close($stmt=null){
		if($stmt){
			$ok = oci_free_statement($stmt);
		} else {
			$ok = oci_free_statement($this->stmt);
		}
		
		if($ok === false){
			$this->err_message = $this->db_error();
			return false;
		}
		
		return true;
	}
	
	
	public function bind($param_name, $param_value, $param_type=null){
		oci_bind_by_name($this->stmt, $param_name, $param_value);
		
		if($this->debug === true){
			echo '<div>bind('.$param_name.', '.$param_value.') :</div>';
		}
		
		return true;
	}
	
	
	public function execute(){
		if($this->transaction_mode === true){
			if(!oci_execute($this->stmt, OCI_DEFAULT)){
				$this->err_message = $this->db_error($this->stmt);
				return false;
			}
		} else {
			if(!oci_execute($this->stmt)){
				$this->err_message = $this->db_error($this->stmt);
				return false;
			}
		}
		
		return $this->stmt;
	}
	
	
	public function stmt_bind($stmt, $param_name, $param_value){
		oci_bind_by_name($stmt, $param_name, $param_value);
		
		if($this->debug === true){
			echo '<div>bind('.$param_name.', '.$param_value.')</div>';
		}
		
		return true;
	}
	
	
	public function stmt_execute($stmt){
		
		if($this->transaction_mode === true){
			if(!oci_execute($stmt, OCI_DEFAULT)){
				$this->err_message = $this->db_error($stmt);
				return false;
			}
		} else {
			if(!oci_execute($stmt)){
				$this->err_message = $this->db_error($stmt);
				return false;
			}
		}
		
		
		return $stmt;
	}
	
	
	public function query($sql){
		$this->rs = oci_parse($this->conn, $sql);
		
		if(!oci_execute($this->rs)){
			$this->err_message = $this->db_error($this->rs);
			return false;
		}
		
		
		return $this->rs;
	}
	
	
	public function bind_query($sql, $bind=array()){
		/****
			$sql = "select * from table_a 
					where col_int = %d
						and col_varchar = '%s'
						and col_float = %f
						and col_bigint = '%0.0f' ";
		****/
		
		
		$escape_string = array();
		foreach($bind as $param_name => $param_value){
			$escape_string[] = 'addslashes(\''.$param_value.'\')';
		}
		
		if(!empty($escape_string) > 0){
			$ex = '$q = sprintf($sql, '.implode(', ', $escape_string).');';
			if($this->debug === true){
				echo '<div>'.$ex.'</div>';
			}
			
			eval($ex);
		} else {
			$q = $sql;
		}
		
		if($this->debug === true)
			echo '<div>execute() : '.$q.'</div>';
		
		$this->rs = mysqli_query($this->conn, $q);
		if($this->rs === false){
			$this->err_message = $this->db_error();
			return false;
		}
		
		
		$this->rs = oci_parse($this->conn, $q);
		
		if(!oci_execute($this->rs)){
			$this->err_message = $this->db_error($this->rs);
			return false;
		}
		
		return $this->rs;
	}
	
	
	public function fetch_assoc($fetch_rs=null){
		
		if($fetch_rs) {
			$grid = oci_fetch_assoc($fetch_rs);
		} else {
			$grid = oci_fetch_assoc($this->stmt);
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
	
	
	public function get_insert_id($sequence_name=null){
		if($sequence_name == null){
			$this->err_message = $this->set_error(LIGHTDB_ERROR_INVALID_SEQUENCE);
			return false;
		}
		
		$st = oci_parse($this->conn, 'select '.$sequence_name.'.CURRVAL as curr_id from dual');
		if(!oci_execute($st)){
			$this->err_message = $this->db_error($st);
			return false;
		}
		
		$grid = oci_fetch_assoc($st);
		
		return $grid['curr_id'];
	}
	
	
	public function getnow(){
		return 'sysdate';
	}
	
	
	public function getdate(){
		return 'to_date(to_char(sysdate, \'YYYYMMDD\'), \'YYYYMMDD\')';
	}
	
	
	public function db_error($handle=null){
		if($handle == null){
			return oci_error();
		} else {
			return oci_error($handle);
		}
	}
}

?>