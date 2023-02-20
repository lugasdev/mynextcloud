<?php

namespace Lugasdev\Mynextcloud\Tests;

use Dotenv\Dotenv;
use Lugasdev\Mynextcloud\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private $url;
    private $username;
    private $password;
    private $client;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . "/..");
        $dotenv->load();

        $this->url      = getenv("NEXTCLOUD_BASEURL");
        $this->username = getenv("NEXTCLOUD_USERNAME");
        $this->password = getenv("NEXTCLOUD_PASSWORD");

        $this->client = new Client($this->username, $this->password, $this->url);
    }

    public function testGetDirectory()
    {
        $response = $this->client->getDirectory("");

        $this->assertArrayHasKey("url", $response[0]);
        $this->assertContains($this->username, $response[0]['url']);
    }

    public function testGetDirectoryException()
    {
        try {
            $this->client->getDirectory("asdsa");
        } catch (\Exception $e) {
            $this->assertEquals("Not Found", $e->getMessage());
        }
    }

    public function testCreateDirectory()
    {
        $reponse = $this->client->createDirectory("test_directory");

        $this->assertTrue($reponse);

        $responseDir = $this->client->getDirectory("");

        $this->assertContains("test_directory", json_encode($responseDir));
    }

    public function testCreateDirectoryException()
    {
        try {
            $this->client->createDirectory("test/directory");
        } catch (\Exception $e) {
            $this->assertEquals("Directory Create Failed", $e->getMessage());
        }
    }

    public function testUpload()
    {
        $testFile = strtotime("now") . "_dummy.pdf";

        $response = $this->client->upload("test_directory", __DIR__ . "/dummy.pdf", $testFile);

        $this->assertEquals($response["statusCode"], 201);
        $this->assertEquals($response["fileName"], $testFile);

        $files = $this->client->getDirectory("test_directory");

        foreach ($files as $i => $file) {
            $this->assertContains("dummy.pdf", $file['url']);
        }
    }

    public function testUploadException()
    {
        try {
            $this->client->upload("test_directory", __DIR__ . "/xdummy.pdf", "_dummy.pdf");
        } catch (\Exception $e) {
            $this->assertContains("File Path is not valid", $e->getMessage());
        }
    }

    public function testDeleteFile()
    {
        $files = $this->client->getDirectory("test_directory");

        foreach ($files as $i => $file) {
            $res = $this->client->delete("test_directory/{$file['filename']}");

            $this->assertTrue($res);
        }
    }

    public function testDeleteFileException()
    {
        $filePath = "test_directory/xadsada.pdf";
        try {
            $this->client->delete($filePath);
        } catch (\Exception $e) {
            $this->assertContains("ile with name /{$filePath} could not be located", $e->getMessage());
        }
    }

    public function testDeleteDirectory()
    {
            $res = $this->client->delete("test_directory");

            $this->assertTrue($res);
    }
}

