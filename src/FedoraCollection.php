<?php
/**
 * Defines a collection class for use with Fedora 4.
 * 
 * @author Rob Wolff
 * @copyright University of New Hampshire Library
 * 
 */
require __DIR__ . '/vendor/autoload.php';
class FedoraCollection extends FedoraResource
{
    protected $slug;
    protected $binaryPath = 'data'; 

    private $prevItem, $item; // temporary vars for linking proxies
    private $page; // temporary var for adding subpages

    /**
     * @param string $slug
     * Proposed shortened identifier; may not be accepted by Fedora
     */
    public function __construct( $slug = '' )
    {
        parent::__construct();
        $this->slug = $slug;

        // Create resource
        $query = "<> rdf:type pcdm:Collection .
                  <> rdf:type ldp:DirectContainer .";
        $headers = array('Content-Type' => 'text/turtle');
        if ( strlen( $slug ) > 0 ) {
            $headers['Slug'] = $slug;
        }
        $resource = $this->client->createResource( '', FedoraResource::PREFIX_TURTLE . $query, $headers );
        $this->uri = (string) $resource->getBody();

        // Use ldp:membershipResource to automate adding pcdm:hasMember for each child resource
        $updateQuery = "INSERT DATA { <>
            ldp:membershipResource <> ;
            ldp:hasMemberRelation pcdm:hasMember . }";
        $this->client->modifyResource( $this->uri, FedoraResource::PREFIX_SPARQL . $updateQuery );
    }

    /** 
     * Sets the binary path for this collection. 
     * 
     * @param string $path
     * Relative or absolute path. 
     */ 
    public function setBinaryPath( $path ) {
        $this->binaryPath = $path;
    }

    /**
     * Parses a CSV file for metadata and creates child resources.
     * Multiple pages are signalled by the Order column which provides
     * ordinal page position.
     *
     * @param string $file
     * CSV file name
     */
    public function ingestCsv( $file ) 
    {
        ini_set('auto_detect_line_endings', true);
        $this->item = NULL;
        $this->prevItem = NULL;
        $handle = fopen( $file, 'r' );
        $metadata = array(); // Single object metadata, field => array(values)
        $header = array(); // CSV column headers, i.e., metadata field names
        $csv = array();
        $i = 0;
        while ( $row = fgets( $handle )) {
            $csv = str_getcsv( $row );
            if ( $i == 0 ) {
                // First row has column headers
                $header = $csv;
            } else {
                $metadata = array();
                for ( $j=0; $j < count($header); $j++ ) {
                    if ( $header[$j] != NULL && $csv[$j] != NULL ) {
                        if ( $this->isValidField( $header[$j] )) {
                            // creates an array of values if there are duplicate keys
                            $metadata = array_merge_recursive( array($header[$j] => $csv[$j]), $metadata );
                        } else {
                            echo '<p><strong>Error</strong>: Invalid field ' . $header[$j] . ' in ' . $this->slug . '</p>';
                        }
                    }
                }
                // Ensure scalar items are single value arrays for consistency
                foreach ( $metadata as $field => $value ) {
                    if ( is_scalar( $value )) {
                        $metadata[$field] = array( $value );
                    }
                }
                $this->processMetadata( $metadata );
            }
            $i++;
        }
        if ( isset( $metadata['Order'] )) {
            $this->finalizeIngest();
        }
    }

    /**
     * Parses FGDC XML and creates child resources.
     *
     * @param string $filename
     * XML file name(s), wildcards allowed.
     */
    public function ingestXml( $filename ) 
    {
        $this->item = NULL;
        $this->prevItem = NULL;
        $metadata = array(); // one resource
        foreach ( glob( $filename ) as $file ) {
            $xml = simplexml_load_file( $file );
            if ( $xml != NULL ) {
                foreach ( FedoraResource::DC_TO_FGDC as $dc => $fgdc ) {
                    $metadata[$dc] = $xml->xpath( $fgdc );
                }
                // Ensure scalar items are single value arrays for consistency
                foreach ( $metadata as $field => $value ) {
                    if ( is_scalar( $value )) {
                        $metadata[$field] = array( (string) $value );
                    }
                }
                $this->processMetadata( $metadata );
            }
        }
        if ( isset( $metadata['Order'] )) {
            $this->finalizeIngest();
        }
    }

    /** 
     * Complete the ingest. 
     */
    protected function finalizeIngest() {
        // Link the proxies for the remaining items
        if ( isset( $this->prevItem )) {
            if ( $this->prevItem->pageCount() >= 2 ) {
                $this->prevItem->linkProxies();
            }
        }
        if ( isset( $this->item )) {
            if ( $this->item->pageCount() >= 2 ) {
                $this->item->linkProxies();
            }
        }
    }

    /** 
     * Process metadata.
     * 
     * @param array $metadata
     * Single object metadata, field => value or field => array(values)
     */
    protected function processMetadata( $metadata ) {
        // Add additional identifiers based on collection
        if ( isset( $metadata['dcterms:identifier'] )) {
            foreach ( $metadata['dcterms:identifier'] as $identifier ) {
                $id = $this->addId( $identifier );
                if ( $id != NULL ) {
                    $metadata['dcterms:identifier'][] = $id;
                }
            }
        }

        // Add children
        // Order indicates page-level resources
        $slug = $this->makeSlug( $metadata['dcterms:identifier'] );
        if ( isset( $metadata['Order'] )) {
            $order = $metadata['Order'][0];
            if ( $order === "0" ) {
                // Item-level resource
                // Link the proxies for the previous item, if present and with pages
                if ( isset( $this->prevItem )) {
                    if ( $this->prevItem->pageCount() >= 2 ) {
                        $this->prevItem->linkProxies();
                    }
                }
                if ( isset( $this->item )) {
                    $this->prevItem = $this->item;
                }
                $this->item = $this->addChild( $this->makeQuery( $metadata ), $slug );
                $this->processFiles( $metadata['dcterms:identifier'], $this->item );
            } else if ( strpos( $order, '.' ) === FALSE ) {
                // Page-level resources
                $this->page = $this->item->addChild( $this->makeQuery( $metadata ), $slug );
                $this->page->addProxy();
                $this->item->addPage( $this->page );
                $this->processFiles( $metadata['dcterms:identifier'], $this->page );
            } else {
                // Sub-page resource
                $subpage = $this->page->addChild( $this->makeQuery( $metadata ), $slug );
                $subpage->addProxy();
                $this->page->addPage( $this->page );
                $this->processFiles( $metadata['dcterms:identifier'], $subpage );
            }
        } else {
            $this->item = $this->addChild( $this->makeQuery( $metadata ), $slug );
            $this->processFiles( $metadata['dcterms:identifier'], $this->item );
        }
    }

    /** 
     * Add files based on identifiers.
     * 
     * @param array $ids
     * Array of identifers
     * 
     * @param FedoraItem $item
     * Item to which any found files will be added. 
     */
    protected function processFiles( $ids, $item ) {
        // poosible file extensions
        $wildcards = array( '*.tif', '*.jpg', 'thumb_*.jpg', '*.xml', '*.pdf', '*.tfw' );
        foreach ( $ids as $id ) {
            foreach ( $wildcards as $wildcard ) {
                $name = str_replace( '*', $id, $wildcard );
                $fileList = $this->searchFiles( $name );
                foreach ( $fileList as $file ) {
                    $item->addFile( $file );
                }
            }
        }
    }

    /** 
     * Search for files based on a basic pattern. 
     * 
     * @param string $pattern
     * Filename to search for. 
     *
     * @return array
     * Array of any found files. 
     */
    protected function searchFiles( $pattern ) {
        $directory = new RecursiveDirectoryIterator( $this->binaryPath );
        $iterator = new RecursiveIteratorIterator( $directory );
        $fileList = array();
        foreach ( $iterator as $file ) {
            // case insensitive
            if ( strcasecmp( $file->getFileName(), $pattern ) === 0 ) {
                $fileList[] = $file->getPathName();
            }
        }
        return $fileList;
    }

    /** 
     * Add custom identifiers based on collection.
     * 
     * @param string $id
     * Existing identifier. 
     * 
     * @return string 
     * New identifier. 
     */
    protected function addId( $id ) {
        $newId = '';
        switch ( $this->slug ) {
            case 'usgs':
                // example: <URL:http://www.granit.unh.edu/data/search?dset=hdrg/hdrg02c_15_1931>
                $cut = '?dset=hdrg/';
                if ( strpos( $id, $cut ) !== FALSE ) {
                    $newId = substr( $id, strpos( $id, $cut ) + strlen( $cut ), -1 );
                }
                break;
            default:
                break;
        }
        return $newId;
    }

    /** 
     * Returns a URL-friendly slug. 
     * 
     * @param string/array(string) $slug 
     * Proposed slug. If array, uses the last item.
     * 
     * @return string
     */
    protected function makeSlug( $id ) {
        $slug = end( $id );
        return urlencode( $slug );
    }

    /**
     * Writes a Turtle query.
     *
     * @param array $metadata
     * Single item.
     *
     * @return string $query
     * Full Turtle query.
     */
    protected function makeQuery( $metadata ) {
        $query = '<> rdf:type pcdm:Object .' . PHP_EOL;
        foreach ( $metadata as $field => $item ) {
            if ( $field !== 'Order' ) {
                if ( is_array( $item )) {
                    foreach ( $item as $value ) {
                        $value = (string) $value; // could be an XML object
                        $query .= $this->makeTriple( $field, $value );
                    }
                } else {
                    $query .= $this->makeTriple( $field, $item );
                }
            }
        }
        return $query;
    }

    /**
     * Writes a Turtle triple for use in a query.
     *
     * @param string $field
     * Metadata field.
     *
     * @param string $value
     * Metadata value.
     *
     * @return string
     * Turtle triple.
     */
    protected function makeTriple( $field, $value ) {
        if ( !is_numeric( $value )) {
            // string escape sequences, ' " \
            // @todo replace with EasyRDF library function
            $value = addcslashes( $value, '\'"\\');
            // triple quotes used for string literals with newlines, etc.
            return '<> ' . $field . ' """' . $value . '""" .' . PHP_EOL;
        } else {
            return '<> ' . $field . ' ' . $value . ' .' . PHP_EOL;
        }
    }

    /**
     * Perform basic validation on the field name. 
     * 
     * @param string $field 
     * Field name, in the form namespace:field. 
     * 
     * @return boolean
     */
    protected function isValidField( $field ) {
        return ( strcmp( $field, 'Order' ) === 0 || 
                 strstr( $field, ':', TRUE ) === 'dcterms' );
    }
}
