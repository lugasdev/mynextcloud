<?php
namespace Lugasdev\Mynextcloud;

use Sabre\DAV\Client as DAVClient;

class Client {

    private $setting = [];
    private $client;

    function __construct($username, $password, $baseUri)
	{
        $this->setting = [
            'baseUri'  => $baseUri,
            'userName' => $username,
            'password' => $password,
            // 'proxy'    => 'locahost:8888',
        ];

        $this->client = new DAVClient($this->setting);
    }

    public function getDirectory($directoryPath)
    {
        try {
            $urlPath  = $this->setting['baseUri'] . "/files/" . $this->setting['userName'] . '/' . trim($directoryPath, '/');
            $response = $this->client->propFind($urlPath, [], 1);

            $files = [];
            $last_path = explode('/', trim($directoryPath, '/'));
            foreach ($response as $k => $file) {
                $dir = explode('/', trim($k, '/'));
                if (end($dir) == end($last_path)) {
                    continue;
                }
                $files[] = [
                    'url'           => $k,
                    'filename'      => end($dir),
                    'contenttype'   => (!empty($file['{DAV:}getcontenttype'])) ? $file['{DAV:}getcontenttype'] : null,
                    'contentlength' => (!empty($file['{DAV:}getcontentlength'])) ? $file['{DAV:}getcontentlength'] : null,
                ];
            }

        } catch (\Exception $e) {
            return false;
        }

        return $files;
    }

}