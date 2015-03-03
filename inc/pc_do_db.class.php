<?PHP

/*
---------------------------------------------------------
"class connection" provides public functionality to deal with the
actual database and result set.
---------------------------------------------------------
*/
class row {

}

class connection {

	public $connection;
	public $error;
	public $errorText;
	public $resultSet = array();
	public $numRows;
	public $stmt;
	public $sql;

	public function open()  {
	
		global $config;
		
		$dbUsername = $config->dbuser; 
		$dbPassword = $config->dbpass;; 
		$dbName     = '(DESCRIPTION=(ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = localhost)(PORT = 5521)))(CONNECT_DATA=(SID=GLOBALTE)))';
		
		if ( (!$dbUsername) || (!$dbPassword) || (!$dbName) ) {
			$this->error = array("message"=>"Incomplete Database Credentials.");
			return $this->manage_error();
		}
							
		$this->connection=OCILogon($dbUsername, $dbPassword, $dbName);
		
		if (!$this->connection) {
		
			$this->error=OCIError();	
			return $this->manage_error();
		
		}
		//echo "server ".OCIServerVersion($this->connection)."<BR>\n";

		return true;
		
	}
	
	public function isOpen() {
		if ($this->connection) return 'open';
		return 'closed';
	}
	
	public function close() {
	
		if ($this->connection) {
			OCILogOff($this->connection);
		}
	
	}
	
	public function execute_sql($sql) { 
		global $authentication;
		//echo "--> execute $sql<br>";
		
		//  Auditing Statements
		
		$audit = false;
		
		if ( preg_match('/^insert\s+into\s/i', $sql) ) {
		
			$type = "INSERT";
			$audit = true;
		
		} else if ( ( preg_match('/^update\s/i', $sql) ) && ( !preg_match('/\sprs_applicants_profile\s/i', $sql) ) ) {
		
			$type = "UPDATE";
			$audit = true;
						
		} else if ( preg_match('/^delete\s/i', $sql) ) {
		
			$type = "DELETE";
			$audit = true; 
						
		}
		
		if ($audit) {
		
			$tempsql = htmlspecialchars($sql, ENT_QUOTES);
			
			$tempstr = "to_clob('";
			for ($char=0;$char<ceil(strlen($tempsql)/3000);$char++) {
				if ($char > 0) {
					$tempstr .= "') || to_clob('";
				}
				$tempstr .= substr($tempsql, ($char * 3000), 3000);
				
			}
			$tempstr .= " ')";
			$tempsql = $tempstr;
			
			if ( (is_object($authentication)) && ($authentication->userid) ) {
				$userid = $authentication->userid;
			}
		
			$auditsql = "insert into PC_DO_TRANSACTION_LOG (TRANSACTIONTYPE, TRANSACTIONDATE, USERID, TRANSACTIONDETAILS)
						 values ('".$type."', SYSDATE, '".$authentication->userid."', $tempsql)";
						 
			$auditstmt = OCIParse($this->connection,$auditsql);
			
			if (!OCIExecute($auditstmt)) {
				
				$this->error = array("message"=>"<p><strong>DB Auditing Error</strong><br />Statement: {$auditstmt}</p>");	
				return $this->manage_error();	
			
			}
					
		}		
		
		//  End auditing
		
		
		$this->sql = $sql;
		$this->stmt = OCIParse($this->connection,$sql);
		
		
		if (!OCIExecute($this->stmt)) {
		
			$this->error = OCIError($this->stmt);
			return $this->manage_error();
		
		} else {
				
			$this->numRows = OCIRowCount($this->stmt);
			return true;
			
		}
		
	}
	
	public function get_num_rows() {
	
		return $this->numRows;
	
	}
	
	public function get_result_set() {
	
		return $this->resultSet;
	
	}	
	
	public function commit() {
	
		$this->sql = "";
		$this->stmt = "";
		if (OCICommit($this->connection)) return true;
		$this->error=OCIError();
		return $this->manage_error();
	
	}
	
	public function rollback() {
	
		$this->sql = "";
		$this->stmt = "";
		if (OCIRollback($this->connection)) return true;
		$this->error=OCIError();
		return $this->manage_error();
	
	}

	public function select_data($sql) {
		//echo "--> select: $sql<br>";
		$this->numRows = 0;
		$this->sql = $sql;

		$this->stmt = OCIParse($this->connection, $sql);

		$this->resultSet = array();
		//$this->stmt = OCIParse($this->connection, "select AUTHCODE from PRS_SYSUSER"); 
		//OCIExecute($this->stmt, OCI_DEFAULT);
		//OCIExecute($this->stmt);
        
		if (OCIExecute($this->stmt)) {

			$iCounter = 0;
			$aGeneric = Array();
			unset($TempClass);
			while (($res = oci_fetch_array($this->stmt, OCI_ASSOC))) {
			//while(OCIFetchInto($this->stmt, $res, OCI_RETURN_NULLS + OCI_ASSOC)) {
				//echo "yo!<br>";
				$TempClass = new row();
				foreach ($res as $sKey => $sVal) {
					//echo $sVal."+ man!<br>";
					//$sVal = "".$sVal;
					$TempClass->{$sKey} = $sVal;//html_entity_decode($sVal, ENT_QUOTES);
					//echo $sKey.": ".$sVal."<br />"; 
				}
				$this->resultSet[$iCounter] = $TempClass;
				$iCounter++;
			}
			//echo nl2br(print_r($this->resultSet,true));
			
		} /*else {
			$this->error=OCIError($this->stmt);
			return false;
		} */

		$this->numRows = $iCounter;
		$this->error=OCIError($this->stmt);
		if ($this->error) return $this->manage_error();
		
		//echo " return true <br>";
		return true;
	}
	
	public function manage_error() {
		$this->errorText = "<p><strong>DB Error</strong><br />Statement: {$this->stmt}</p><p>Error: ".$this->error["message"]."</p>";	
		throw new Exception($this->errorText);
	}
	
}
?>
