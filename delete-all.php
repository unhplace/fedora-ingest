<?php
/** 
 * Deletes all resources which are contained by the root container, 
 * i.e., all resources
 */
require __DIR__ . '/chullo/vendor/autoload.php';
define("FEDORA_URI", "http://localhost:8080/fcrepo/rest");

// Instantiated with static factory
$chullo = Islandora\Chullo\FedoraApi::create(FEDORA_URI);

// Get all resources
$headers = array('Accept' => 'application/ld+json');
$resource = $chullo->getResource( $chullo->getBaseUri(), $headers );

// Declare our namespaces
EasyRdf_Namespace::set('ldp', 'http://www.w3.org/ns/ldp#');

// Use an EasyRDF graph to find containers
$graph = $chullo->getGraph( $resource );
$children = $graph->allResources(FEDORA_URI . '/', 'ldp:contains'); // root resource requires trailing slash!

foreach ( $children as $child ) {
  $chullo->deleteResource($child->getUri());
}
?>
<!DOCTYPE html>
<html>
<head></head>
<body>
  <h1>fedora-ingest</h1>
  <h2>delete-all</h2>
  <?php
  foreach ( $children as $child ) {
    echo "Deleted: " . $child->getUri() . "<br>\n";
  } ?>
</body>
</html>