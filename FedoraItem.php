<?php
/**
 * Defines an item class for use with Fedora 4.
 * 
 * @author Rob Wolff <rob.wolff@unh.edu>
 * @copyright University of New Hampshire Library
 *
 */
require __DIR__ . '/chullo/vendor/autoload.php';
class FedoraItem extends FedoraResource
{
    protected $files = array(); // pcdm:hasFile
    protected $proxy;           // proxy URI for this resource, optional
    private $pages = array();   // FedoraItems

    /**
     * @param string $uri
     * URI accepted by Fedora.
     * 
     * @param string $parent
     * Parent URI.
     */
    public function __construct( $uri, $parent )
    {
        parent::__construct();
        $this->uri = $uri;
        $this->parent = $parent;
    }

    /**
     * Gets the proxy URI.
     *
     * @return string
     */
    public function getProxy() {
        return $this->proxy;
    }

    /**
     * Creates a proxy for this item.
     */
    public function addProxy() {
        $headers = array( 'Content-Type' => 'text/turtle' );
        $query = '<> rdf:type ore:Proxy .
                  <> ore:proxyFor <' . $this->uri . '> .
                  <> ore:proxyIn <' . $this->parent . '> .' . PHP_EOL;
        $res = $this->client->createResource( $this->parent, FedoraResource::PREFIX_TURTLE . $query, $headers );
        $code = $res->getStatusCode();
        if ( $code >= 200 && $code <= 299 ) {
            $uri = (string) $res->getBody();
            $this->proxy = $uri;
        }
    }

    /**
     * Adds a page to this item. 
     * 
     * @param string $page
     * URI for the page.
     */
    public function addPage( $page ) {
        $this->pages[] = $page;

        // Add pcdm:hasMember
        $updateQuery = 'INSERT DATA { <>
            pcdm:hasMember <' . $page->getUri() . '> }';
        $this->client->modifyResource( $this->uri, FedoraResource::PREFIX_SPARQL . $updateQuery );
    }

    /**
     * Gets the number of pages in this item. 
     * 
     * @return int
     */
    public function pageCount() {
        return count( $this->pages );
    }

    /**
     * Updates a proxy for this item.
     * 
     * @param string $prev
     * URI of the previous proxy in order; null indicates this is first.
     * 
     * @param string $next
     * URI of the next proxy in order; null indicates this is last. 
     */
    public function updateProxy( $prev, $next ) {
        $query = 'INSERT DATA { <>' . PHP_EOL;
        if ( $prev != NULL ) {
            $query .= 'iana:prev <' . $prev . '> ;' . PHP_EOL;
        }
        if ( $next != NULL ) {
            $query .= 'iana:next <' . $next . '> ;' . PHP_EOL;
        }
        // remove trailing newline, semicolon and close
        $query = substr( $query, 0, -2 ) . '. }';
        $this->client->modifyResource( $this->proxy, FedoraResource::PREFIX_SPARQL . $query );
    }

    /**
     * Link proxy resources to one another and this item's collection.
     */
    public function linkProxies() {
        // link item to first and last proxy
        $first = $this->pages[0]->getProxy();
        $end = end( $this->pages );
        $last = $end->getProxy();
        $query = 'INSERT DATA { <>
            iana:first <' . $first . '> ;
            iana:last <' . $last . '> . }';
        $this->client->modifyResource( $this->uri, FedoraResource::PREFIX_SPARQL . $query ); 

        // link proxies to each other
        for ( $i=0; $i < count($this->pages); $i++ ) {
            if ( $i == 0 ) {
                $prev = NULL;
                $next = $this->pages[1]->getProxy();
            } else if ( $i == count($this->pages)-1 ) {
                $prev = $this->pages[$i-1]->getProxy();
                $next = NULL;
            } else {
                $prev = $this->pages[$i-1]->getProxy();
                $next = $this->pages[$i+1]->getProxy();
            }
            $this->pages[$i]->updateProxy( $prev, $next );
        }
    }

    /**
     * @param string $file
     * File name, including path.
     */
    public function addFile( $file ) {
        $data = $this->readFile( $file );

        // determine mime type
        $type = mime_content_type( $file );

        // get filename
        $filename = substr( $file, strrpos( $file, '/' )+1 );

        // add binary resource
        $headers = array(
          'Content-Type' => $type,
          'Content-Disposition' => 'attachment; filename="' . $filename . '"');
        $resource = $this->client->createResource( $this->uri, $data, $headers );
        $fileUri = (string) $resource->getBody();

        // register file with parent
        $updateQuery = 'INSERT DATA { <> pcdm:hasFile <' . $fileUri . '> . }';
        $this->client->modifyResource( $this->uri, FedoraResource::PREFIX_SPARQL . $updateQuery );

        // add type pcdm:File
        // target should be to /fcr:metadata as base URI represents the binary itself
        $updateQuery = 'INSERT DATA { <> rdf:type pcdm:File . }';
        $res = $this->client->modifyResource( $fileUri . '/fcr:metadata', FedoraResource::PREFIX_SPARQL . $updateQuery );
    }

    /**
     * @param string $file
     * File name, including path.
     *
     * @return binary
     * File contents.
     */
    protected function readFile( $file ) {
        try {
            $handle = fopen( $file, 'r');
            if ( !$handle ) {
                throw new Exception('File not opened');
            }
            $data = fread( $handle, filesize( $file ));
            if ( !$data ) {
                throw new Exception('File not read');
            }
            fclose( $handle );
        } catch ( Exception $e ) {
            echo 'File ingest failed: ' . $e->getMessage() . ' (' . $file . ')';
        }
        return $data;
    }
}
