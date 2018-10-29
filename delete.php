<?php
/** 
 * Deletes all resources which are contained by the root container, 
 * i.e., all resources
 */
require __DIR__ . '/chullo/vendor/autoload.php';
define("FEDORA_URI", "http://localhost:8080/fcrepo/rest");
define("DELETE_URI", "http://localhost:8080/fcrepo/rest/foo");

// Instantiated with static factory
$chullo = Islandora\Chullo\FedoraApi::create( FEDORA_URI );
$chullo->deleteResource( DELETE_URI );
$chullo->deleteResource( DELETE_URI . '/fcr:tombstone' );
?>
<!DOCTYPE html>
<html>
<head></head>
<body>
  <h1>fedora-ingest</h1>
  <h2>delete</h2>
  <?php
  echo "Deleted: " . DELETE_URI . "<br>\n";
  ?>
</body>
</html>