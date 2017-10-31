<?php
require 'FedoraResource.php';
require 'FedoraItem.php';
require 'FedoraCollection.php';

// CSV source
$csv = new FedoraCollection('csv');
$csv->setBinaryPath('data');
$csv->ingestCsv('csv/metadata.csv');

// XML source
$xml = new FedoraCollection('fgdc');
$xml->setBinaryPath('data');
$xml->ingestXml('xml/*.xml');

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
</head>
<body>
  <h1>Ingest</h1>
  <h2>CSV</h2>
  <p>
    Created container: <a href="<?php echo $csv->getUri(); ?>"><?php echo $csv->getUri(); ?></a><br>
    Created <?php echo count( $csv->getChildren()); ?> child resource(s).
  </p>
  <h2>XML</h2>
  <p>
    Created container: <a href="<?php echo $xml->getUri(); ?>"><?php echo $xml->getUri(); ?></a><br>
    Created <?php echo count( $xml->getChildren()); ?> child resource(s).
  </p>
</body>
</html>