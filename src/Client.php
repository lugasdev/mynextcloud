<?php
namespace Lugasdev\Mynextcloud;

use Jackiedo\XmlArray\Xml2Array;
use Lugasdev\Mynextcloud\Exception\ClientException;
use Sabre\DAV\Client as DAVClient;

class Client {

    private $setting = [];
    private $client;

    function __construct($username, $password, $baseUri)
	{
        $this->setting = [
            'baseUri'  => trim($baseUri, "/"),
            'userName' => $username,
            'password' => $password,
            // 'proxy'    => 'locahost:8888',
        ];

        $this->client = new DAVClient($this->setting);
    }

    /**
     * get entire directory
     *
     * @param string $directoryPath
     * @return array
     *
     * @author Lugas Luqman Hakim <lugas.luqman@gmail.com>
     */
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
            throw new ClientException($e->getMessage());
        }

        return $files;
    }

    /**
     * upload file to directory
     *
     * @param string $dirPathTarget target directory
     * @param string $filePath local file path
     * @param string $fileName file name
     * @return array
     * @author Lugas Luqman Hakim <lugas.luqman@gmail.com>
     */
    public function upload($dirPathTarget, $filePath, $fileName = '')
    {
        if (!is_file($filePath)) {
            throw new ClientException('File Path is not valid');
        }

        $file = file_get_contents($filePath);

        if (empty($fileName)) {
            $fileName_ = explode('/', $filePath);
            $fileName = end($fileName_);
        }

        $filePath = trim($dirPathTarget, '/') . "/" . $fileName;
        $urlPath  = $this->setting['baseUri'] . "/files/" . $this->setting['userName'] . '/' . $filePath;

        $response = $this->client->request('PUT', $urlPath , $file);

        return array_merge($response, ["filePath" => $filePath, "urlPath" => $urlPath, 'fileName' => $fileName]);
    }

    /**
     * delete file
     *
     * @param string $filePath nextcloud file path
     * @return string
     * @author Lugas Luqman Hakim <lugas.luqman@gmail.com>
     */
    public function delete($filePath)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => $this->setting['baseUri'] . "/files/". $this->setting['userName'] ."/" . $filePath,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "DELETE",
            CURLOPT_HTTPHEADER     => array(
                "Authorization: Basic " . base64_encode( $this->setting['userName'] . ":" . $this->setting['password']),
            ),
        ));

        $response = curl_exec($curl);

        if ($response != "") {
            $responseArray = Xml2Array::convert($response)->toArray();

            if (isset($responseArray["d:error"])) {
                throw new ClientException(!empty($responseArray["d:error"]["s:message"]) ? $responseArray["d:error"]["s:message"] : "Directory Create Failed");
            }

            var_dump($responseArray);
        }

        curl_close($curl);
        // echo $response;

        return true;
    }

    /**
     * create directory
     *
     * @param string $dirPath directory to create
     * @return boolean
     * @author Lugas Luqman Hakim <lugas.luqman@gmail.com>
     */
    public function createDirectory($dirPath)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => $this->setting['baseUri'] . "/files/" . $this->setting['userName'] . "/" . $dirPath,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "MKCOL",
            CURLOPT_HTTPHEADER     => array(
                "Authorization: Basic " . base64_encode( $this->setting['userName'] . ":" . $this->setting['password']),
            ),
        ));

        $response = curl_exec($curl);

        if ($response != "") {
            $responseArray = Xml2Array::convert($response)->toArray();

            if (isset($responseArray["d:error"])) {
                throw new ClientException(!empty($responseArray["d:error"]["s:message"]) ? $responseArray["d:error"]["s:message"] : "Directory Create Failed");
            }

            var_dump($responseArray);
        }

        curl_close($curl);
        // echo $response;

        return true;
    }
}