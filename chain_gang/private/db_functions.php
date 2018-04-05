<?php
    try {
        $db = new PDO("mysql:host=".DB_CREDENTIALS['DB_SERVER'].";dbname=".DB_CREDENTIALS['DB_NAME']."",
            DB_CREDENTIALS['DB_USER'],DB_CREDENTIALS['DB_PASS'],$options);
      } catch (Exception $e) {
        die($e->getMessage());
      }

   function db_disconnect($connection){
        if(isset($connection)){
            $connection = null;
        }
   }
?>
