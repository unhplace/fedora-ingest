# fedora-ingest

This PHP library automates the ingest of digital collections metadata and binaries into a [Fedora Commons 4](https://duraspace.org/fedora/) instance. It is known to work with Fedora 4.7.4. 

The relationships created between objects conform to the [Portland Common Data Model](https://github.com/duraspace/pcdm/wiki).

All code conforms to the PSR-2 (Coding Style) and PSR-4 (Autoloading) standards.

## Install

Via Composer

``` bash
$ composer require unhplace/fedora-ingest
```

## Usage

``` php
use UNHPlace\FedoraIngest\FedoraCollection;

// Create a new collection with a named slug
$col = FedoraCollection('col-1');

// Set the local path for binaries
$col->setBinaryPath('data');

// CSV source
$col->ingestCsv('csv/metadata.csv');

// FGDC XML source
$col->ingestFgdcXml('xml/*.xml');

// Print the URI of the created collection
echo $col->getUri();

// Print the number of child objects
echo count( $col->getChildren());
```

## Credits

- [Rob Wolff](https://github.com/paniccc)

## Acknowledgements
This work was supported by the [Institute of Museum and Library Services](https://www.imls.gov).

## License

This library is released under the GPLv3 License. Please see the [License File](LICENSE.md) for more information.