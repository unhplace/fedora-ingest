<?php
/**
 * Class to add items to an existing collection in Fedora 4.
 *
 * @author Rob Wolff
 * @copyright University of New Hampshire Library
 *
 */
namespace UNHPlace\FedoraIngest;

class FedoraExistingCollection extends FedoraCollection
{
    /**
     * Simple constructor.
     */
    public function __construct($slug, $uri)
    {
        parent::__construct();
        $this->slug = $slug;
        $this->uri = $uri;
    }
}
