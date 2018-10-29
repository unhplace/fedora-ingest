<?php
/**
 * Defines a generic resource class for use with Fedora 4.
 *
 * @author Rob Wolff
 * @copyright University of New Hampshire Library
 *
 */
namespace UNHPlace\FedoraIngest;

require __DIR__ . '/vendor/autoload.php';

class FedoraResource
{
    protected $uri;                // URI of the collection
    protected $client;             // chullo client
    protected $children = array(); // array of child URIs (pcdm:hasMember, pcdm:hasFile)
    protected $parent;             // parent URI (fedora:hasParent, ore:proxyIn)

    const BASE_URL = 'http://localhost:8080/fcrepo/rest';

    const PREFIX_TURTLE = "@prefix schema: <http://schema.org/> .
        @prefix premis: <http://www.loc.gov/premis/rdf/v1#> .
        @prefix owl: <http://www.w3.org/2002/07/owl#> .
        @prefix pcdm: <http://pcdm.org/models#> .
        @prefix skos: <http://www.w3.org/2004/02/skos/core#> .
        @prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
        @prefix acl: <http://www.w3.org/ns/auth/acl#> .
        @prefix xsi: <http://www.w3.org/2001/XMLSchema-instance> .
        @prefix geo: <http://www.w3.org/2003/01/geo/wgs84_pos#> .
        @prefix xmlns: <http://www.w3.org/2000/xmlns/> .
        @prefix xml: <http://www.w3.org/XML/1998/namespace> .
        @prefix rel: <http://id.loc.gov/vocabulary/relators/> .
        @prefix dcterms: <http://purl.org/dc/terms/> .
        @prefix fedoraconfig: <http://fedora.info/definitions/v4/config#> .
        @prefix prov: <http://www.w3.org/ns/prov#> .
        @prefix foaf: <http://xmlns.com/foaf/0.1/> .
        @prefix cc: <http://creativecommons.org/ns#> .
        @prefix ore: <http://www.openarchives.org/ore/terms/> .
        @prefix test: <info:fedora/test/> .
        @prefix gn: <http://www.geonames.org/ontology#> .
        @prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
        @prefix fedora: <http://fedora.info/definitions/v4/repository#> .
        @prefix ebucore: <http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#> .
        @prefix ldp: <http://www.w3.org/ns/ldp#> .
        @prefix iana: <http://www.iana.org/assignments/relation/> .
        @prefix xs: <http://www.w3.org/2001/XMLSchema> .
        @prefix exif: <http://www.w3.org/2003/12/exif/ns#> .
        @prefix dc: <http://purl.org/dc/elements/1.1/> ." . PHP_EOL;

    const PREFIX_SPARQL = "PREFIX schema: <http://schema.org/>
        PREFIX premis: <http://www.loc.gov/premis/rdf/v1#>
        PREFIX owl: <http://www.w3.org/2002/07/owl#>
        PREFIX pcdm: <http://pcdm.org/models#>
        PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
        PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
        PREFIX acl: <http://www.w3.org/ns/auth/acl#>
        PREFIX xsi: <http://www.w3.org/2001/XMLSchema-instance>
        PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
        PREFIX xmlns: <http://www.w3.org/2000/xmlns/>
        PREFIX xml: <http://www.w3.org/XML/1998/namespace>
        PREFIX rel: <http://id.loc.gov/vocabulary/relators/>
        PREFIX dcterms: <http://purl.org/dc/terms/>
        PREFIX fedoraconfig: <http://fedora.info/definitions/v4/config#>
        PREFIX prov: <http://www.w3.org/ns/prov#>
        PREFIX foaf: <http://xmlns.com/foaf/0.1/>
        PREFIX cc: <http://creativecommons.org/ns#>
        PREFIX ore: <http://www.openarchives.org/ore/terms/>
        PREFIX test: <info:fedora/test/>
        PREFIX gn: <http://www.geonames.org/ontology#>
        PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
        PREFIX fedora: <http://fedora.info/definitions/v4/repository#>
        PREFIX ebucore: <http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#>
        PREFIX ldp: <http://www.w3.org/ns/ldp#>
        PREFIX iana: <http://www.iana.org/assignments/relation/>
        PREFIX xs: <http://www.w3.org/2001/XMLSchema>
        PREFIX exif: <http://www.w3.org/2003/12/exif/ns#>
        PREFIX dc: <http://purl.org/dc/elements/1.1/>" . PHP_EOL;

    // Dublin Core to FGDC xpath mapping
    const DC_TO_FGDC = array(
        'dcterms:title'              => '//metadata/idinfo/citation/citeinfo/title',
        'dcterms:creator'            => '//metadata/idinfo/citation/citeinfo/origin',
        'dcterms:subject'            => '//metadata/idinfo/keywords/theme/themekey',
        'dcterms:description'        => '//metadata/idinfo/descript/abstract',
        'dcterms:publisher'          => '//metadata/metainfo/metc/cntinfo/cntorgp/cntorg',
        'dcterms:contributor'        => '//metadata/idinfo/datacred',
        'dcterms:date'               => '//metadata/idinfo/citation/citeinfo/pubdate',
        'dcterms:type'               => '//metadata/idinfo/citation/citeinfo/geoform',
        'dcterms:format'             => '//metadata/distinfo/stdorder/digform/digtinfo/formname',
        'dcterms:identifier'         => '//metadata/idinfo/citation/citeinfo/onlink',
        'dcterms:source'             => '//metadata/distinfo/resdesc',
        'dcterms:relation'           => '//metadata/idinfo/citation/citeinfo/lworkcit/citeinfo/title',
        'dcterms:coverage.x.min'     => '//metadata/idinfo/spdom/bounding/westbc',
        'dcterms:coverage.x.max'     => '//metadata/idinfo/spdom/bounding/eastbc',
        'dcterms:coverage.y.min'     => '//metadata/idinfo/spdom/bounding/southbc',
        'dcterms:coverage.y.max'     => '//metadata/idinfo/spdom/bounding/northbc',
        'dcterms:coverage.placeName' => '//metadata/idinfo/keywords/place/placekey',
        'dcterms:rights'             => '//metadata/idinfo/accconst',
    );

    /**
     * Simple constructor.
     */
    public function __construct()
    {
        // Generate a chullo client
        $this->client = Islandora\Chullo\FedoraApi::create(FedoraResource::BASE_URL);
    }

    /**
     * Get the URI.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Get the child resource URIs.
     *
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Get the parent URI.
     *
     * @return string
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Adds a child resource.
     *
     * @param array $query
     * Turtle query.
     *
     * @param array $slug
     * Optional slug, which may be rejected by Fedora.
     *
     * @return FedoraItem
     */
    protected function addChild($query, $slug = '')
    {
        $headers = array( 'Content-Type' => 'text/turtle' );
        if ($slug != null) {
            $headers['Slug'] = $slug;
        }
        $res = $this->client->createResource($this->uri, FedoraResource::PREFIX_TURTLE . $query, $headers);
        $code = $res->getStatusCode();
        if ($code >= 200 && $code <= 299) {
            $uri = (string) $res->getBody();
            $this->children[] = $uri;
            return new FedoraItem($uri, $this->uri);
        }
    }
}
