<?php
class holder {

}

class configuration {
	public  $incDir;
	public  $incPrefix;
	public  $incExt;
	public  $classExt;
	public  $testDB = false;
	public  $oracleSID;
	private $oldSID;
	public  $variables;
	public  $dbcon;
	private $oldMemory;
	private $memoryLimitDefault = '32M';
	public $dbuser;
	public $dbpass;
	public $APPTITLE; 
	public $dynConfig;
	public $adminCCList;
	public $welcomeCCList;
	
	public function __construct() {
	
		global $_SERVER, $BASECONFIG, $_GET;
		$this->incDir = $BASECONFIG->incDir;
		$this->incPrefix = $BASECONFIG->incPrefix;
		$this->incExt = $BASECONFIG->incExt;
		$this->classExt = $BASECONFIG->classExt;
		$this->actionVar = $BASECONFIG->actionVar;
		$this->oldSid = getenv('ORACLE_SID');
		$this->oldMemory = getenv('memory_limit'); 
		$this->variables = new holder();		
		$this->APPTITLE = 'PilotCredentials.com Global Recruiter';
		$this->dynConfig = new holder();
		$this->dbuser = $BASECONFIG->dbuser;
		$this->dbpass = $BASECONFIG->dbpass;
	}
	
	public function configure() {
		global $_SERVER;
		
		$this->dbcon = new connection();
		$this->dbcon->open();

	}
	
	public function __destruct() {
		
            if (is_object($this->dbcon)) {
			$this->dbcon->close();
	    }
	}
}
?>
