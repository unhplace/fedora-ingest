<?php
/**
 * A single item collection class for use with Fedora 4.
 *
 * @author Rob Wolff
 * @copyright University of New Hampshire Library
 *
 */
namespace UNHPlace\FedoraIngest;

require __DIR__ . '/vendor/autoload.php';

class FedoraSingleItemCollection extends FedoraCollection
{
    /**
     * Simple constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->uri = FedoraResource::BASE_URL;
    }
}
