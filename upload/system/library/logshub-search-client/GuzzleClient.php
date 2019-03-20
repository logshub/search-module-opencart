<?php
namespace Logshub\SearchClient;

class GuzzleClient extends \GuzzleHttp\Client
{
    /**
     * Guzzle in opencart is little bit old, and interface is different
     * This is the reason for this method
     */
    public function request($method, $uri = '', array $options = [])
    {
        return $this->send($this->createRequest($method, $uri, $options));
    }
}
