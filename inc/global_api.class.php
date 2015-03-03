
<?php

class GlobalApi {


  private $dbcon;

  public function __construct() {
            	
    require_once 'pc_do_db.class.php';    

 	$dbcon = new connection();
	
  }
  
  public function __destruct() {
  	
  	//$dbconn->close();
  
  }
  
  public function toString() {
  
  	return 'the string';  	
  }

}

?>
