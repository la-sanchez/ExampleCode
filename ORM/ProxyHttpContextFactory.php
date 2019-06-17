<?php

namespace Enzim\RestOrmBundle\ORM;

use SplFileInfo;
use Symfony\Bridge\Monolog\Logger;
use GuzzleHttp\Psr7\MultipartStream;

class ProxyHttpContextFactory implements HttpContextFactoryInterface
{

    /** @var Logger */
    private $logger;

    /** @var array */
    private $proxyData;

    public function __construct($logger, $proxyData)
    {
        $this->logger = $logger;
        $this->proxyData = $proxyData;
    }

    /**
     * @param string             $url
     * @param string             $method
     * @param string|SplFileInfo $content
     *
     * @return resource
     */
    public function create($url, $method, $content)
    {
        switch ($method) {
            case 'GET':
                return self::isHttps($url) ? $this->getHTTPSContext() : $this->getContext();
            case 'POST':
                if ($content instanceof SplFileInfo) {
                    $filename = $content->getRealPath();

                    return self::isHttps($url) ?
                        $this->getHTTPSPostFileContext($filename) :
                        $this->getPostFileContext($filename);
                }

                return self::isHttps($url) ? $this->getHTTPSPostContext($content) : $this->getPostContext($content);
            case 'PUT':
                if ($content instanceof SplFileInfo) {
                    $this->logger->error("HTTP method not supported for files: $method");

                    return null;
                }

                return self::isHttps($url) ? $this->getHTTPSPutContext($content) : $this->getPutContext($content);
            default:
                $this->logger->error("HTTP method not supported: $method");

                return null;
        }
    }

    public function getContext()
    {
        return [
            'auth' => [$this->proxyData['user'], $this->proxyData['password']],
            'http_errors' => false
        ];

    }

    public function getHTTPSContext()
    {
        return [
            'auth' => [$this->proxyData['user'], $this->proxyData['password']],
            'http_errors' => false
        ];
    }

    public function getPostContext($postContent)
    {
        return [
            'auth' => [$this->proxyData['user'], $this->proxyData['password']],
            'headers' => [
                'Content-type' => 'application/ld+json',
            ],
            'body'    => $postContent
        ];
    }

    public function getHTTPSPostContext($postContent)
    {
        return [
            'auth' => [$this->proxyData['user'], $this->proxyData['password']],
            'headers' => [
                'Content-type' => 'application/ld+json',
            ],
            'body'    => $postContent
        ];
    }

    public function getHTTPSPostFileContext($filename, $fieldName = "UploadedFile")
    {
        $multipartStream = new MultipartStream([
            [
                'name'     => $fieldName,
                'contents' => file_get_contents($filename),
                'filename' => basename($filename),
            ],
        ]);
        return [
            'auth' => [$this->proxyData['user'], $this->proxyData['password']],
            'body' => $multipartStream
        ];
    }

    public function getPostFileContext($filename, $fieldName = "UploadedFile[]")
    {
        $multipartStream = new MultipartStream([
            [
                'name'     => $fieldName,
                'contents' => file_get_contents($filename),
                'filename' => basename($filename),
            ],
        ]);
        return [
            'auth' => [$this->proxyData['user'], $this->proxyData['password']],
            'body' => $multipartStream
        ];
    }

    public function getPutContext($putContent)
    {
        return [
            'auth' => [$this->proxyData['user'], $this->proxyData['password']],
            'headers' => [
                'Content-type' => 'application/ld+json',
            ],
            'body'    => $putContent
        ];
    }

    public function getHTTPSPutContext($putContent)
    {
        return [
            'auth' => [$this->proxyData['user'], $this->proxyData['password']],
            'headers' => [
                'Content-type' => 'application/ld+json',
            ],
            'body'    => $putContent
        ];
    }

    private static function isHttps($url)
    {
        return strstr($url, 'https') !== false;
    }

}
