<?php
require 'FedoraResource.php';
require 'FedoraItem.php';
require 'FedoraCollection.php';
require 'FedoraSingleItemCollection.php';

// Brown Company Photos/Mosaics
$brown = new FedoraCollection('brown');
$brown->setBinaryPath('data/Brown-photos');
$brown->ingestCsv('csv/BrownPhotos.csv');
$brown->setBinaryPath('data/Brown-mosaics');
$brown->ingestCsv('csv/BrownMosaics.csv');

// USGS Topographical Maps
$usgs = new FedoraCollection('usgs');
$usgs->setBinaryPath('data/USGS');
$usgs->ingestXml('xml/*.xml');

// Hitchcock Atlas
$hitchcock = new FedoraSingleItemCollection();
$hitchcock->setBinaryPath('data/Hitchcock');
$hitchcock->ingestCsv('csv/Hitchcock.csv');

// Hurd Atlas
$hurd = new FedoraSingleItemCollection();
$hurd->setBinaryPath('data/Hurd');
$hurd->ingestCsv('csv/Hurd.csv');

// NEIGC Guidebooks
$guidebooks = new FedoraCollection('neigc');
$guidebooks->setBinaryPath('data/NEIGC');
$guidebooks->ingestCsv('csv/NEIGC.csv');

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
</head>
<body>
  <h1>Ingest</h1>
  <h2>Brown Company Aerial Photographs/Mosaics</h2>
  <p>
    Created container: <a href="<?php echo $brown->getUri(); ?>"><?php echo $brown->getUri(); ?></a><br>
    Created <?php echo count( $brown->getChildren()); ?> child resource(s).
  </p>
  <h2>Historic USGS Topographical Quad Maps</h2>
  <p>
    Created container: <a href="<?php echo $usgs->getUri(); ?>"><?php echo $usgs->getUri(); ?></a><br>
    Created <?php echo count( $usgs->getChildren()); ?> child resource(s).
  </p>
  <h2>Hitchcock Atlas</h2>
  <p>
    Created container: <a href="<?php echo $hitchcock->getUri(); ?>"><?php echo $hitchcock->getUri(); ?></a><br>
    Created <?php echo count( $hitchcock->getChildren()); ?> child resource(s).
  </p>
  <h2>Hurd Atlas</h2>
  <p>
    Created container: <a href="<?php echo $hurd->getUri(); ?>"><?php echo $hurd->getUri(); ?></a><br>
    Created <?php echo count( $hurd->getChildren()); ?> child resource(s).
  </p>
  <h2>NEIGC Guidebooks</h2>
  <p>
    Created container: <a href="<?php echo $guidebooks->getUri(); ?>"><?php echo $guidebooks->getUri(); ?></a><br>
    Created <?php echo count( $guidebooks->getChildren()); ?> child resource(s).
  </p>
</body>
</html>