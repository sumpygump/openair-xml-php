<?php

namespace OpenAir;

use OpenAir\Base\Command;

class Request extends OpenAir
{
    private $commands = [];
    private $xml;
    private $namespace;
    private $key;
    private $api_ver;
    private $client;
    private $client_ver;
    private $url = 'https://www.openair.com/api.pl';
    private $bDebug = false;

    function __construct($namespace, $key, $api_ver = '1.0', $client = 'test app', $client_ver = '1.1'){
        $this->namespace = $namespace;
        $this->key = $key;
        $this->api_ver = $api_ver;
        $this->client = $client;
        $this->client_ver = $client_ver;
    }

    public function setDebug($bDebug){
        $this->bDebug = $bDebug;
    }

    public function addCommand(Command $command){
        $this->commands[] = $command;
    }

    public function setUrl($companyId, $isSandbox=false)
    {
        // Example URL: https://<company-id>.app.sandbox.openair.com/api.pl

        $companyId = strtolower($companyId);

        $subdomainInfix = "";
        if ($isSandbox) {
            $subdomainInfix = ".sandbox";
        }

        $this->url = sprintf("https://%s.app%s.openair.com/api.pl", $companyId, $subdomainInfix);

        return $this;
    }

    public function execute(){
        $xml = $this->_buildRequest();

        if($this->bDebug)echo "URL: " . $this->url . PHP_EOL;
        if($this->bDebug)echo "REQUEST: " . $xml . PHP_EOL . PHP_EOL;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$xml);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if($httpcode === 200){
            if($this->bDebug){
                $dom = new \DOMDocument();
                $dom->formatOutput = true;
                $dom->loadXML($result);
                echo "RESPONSE: " . $dom->saveXML();
            }
            return new Response($result);
        }else{
            if($this->bDebug)echo "Response: " . $result . PHP_EOL;
            return $httpcode;
        }
    }

    private function _buildRequest(){
        $dom = new \DOMDocument();
        if($this->bDebug)$dom->formatOutput = true;
        $request = $dom->createElement('request');

        // api version
        $apiVer = $dom->createAttribute('API_version');
        $apiVer->value = $this->api_ver;
        $request->appendChild($apiVer);

        // client
        $client = $dom->createAttribute('client');
        $client->value = $this->client;
        $request->appendChild($client);

        // client_ver
        $client_ver = $dom->createAttribute('client_ver');
        $client_ver->value = $this->client_ver;
        $request->appendChild($client_ver);

        // namespace
        $namespace = $dom->createAttribute('namespace');
        $namespace->value = $this->namespace;
        $request->appendChild($namespace);

        // key
        $key = $dom->createAttribute('key');
        $key->value = $this->key;
        $request->appendChild($key);

        foreach($this->commands as $objCommand){
            $xmlCommand = $objCommand->_buildRequest($dom);
            if(!is_null($xmlCommand)){
                $request->appendChild($xmlCommand);
            }
        }

        $dom->appendChild($request);
        $this->xml = $dom->saveXML();
        return $this->xml;
    }
}
