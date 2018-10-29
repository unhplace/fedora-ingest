<?php
require 'FedoraResource.php';
require 'FedoraItem.php';
require 'FedoraCollection.php';
require 'FedoraSingleItemCollection.php';

switch ( $_REQUEST['col'] ) {
  case 'brown':
    // Brown Company Photos/Mosaics
    $collection = new FedoraCollection('brown');
    $collection->setBinaryPath('data/Brown-photos');
    $collection->ingestCsv('csv/BrownPhotos.csv');
    $collection->setBinaryPath('data/Brown-mosaics');
    $collection->ingestCsv('csv/BrownMosaics.csv');
    break;
  case 'usgs':
    // USGS Topographical Maps
    $collection = new FedoraCollection('usgs');
    $collection->setBinaryPath('data/USGS');
    $collection->ingestXml('xml/*.xml');
    break;
  case 'hitchcock':
    // Hitchcock Atlas
    $collection = new FedoraSingleItemCollection();
    $collection->setBinaryPath('data/Hitchcock');
    $collection->ingestCsv('csv/Hitchcock.csv');
    break;
  case 'hurd':
    // Hurd Atlas
    $collection = new FedoraSingleItemCollection();
    $collection->setBinaryPath('data/Hurd');
    $collection->ingestCsv('csv/Hurd.csv');
    break;
  case 'neigc':
    // NEIGC Guidebooks
    $collection = new FedoraCollection('neigc');
    $collection->setBinaryPath('data/NEIGC');
    $collection->ingestCsv('csv/NEIGC.csv');
    break;
  default:
    break;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
</head>
<body>
  <h1>Ingest Single Collection</h1>
  <p>
    Created container: <a href="<?php echo $collection->getUri(); ?>"><?php echo $collection->getUri(); ?></a><br>
    Created <?php echo count( $collection->getChildren()); ?> child resource(s).
  </p>
</body>
</html>