<?php

namespace Enzim\RestOrmBundle\ORM;

use SplFileInfo;
use GuzzleHttp\Psr7\MultipartStream;

class DefaultHttpContextFactory implements HttpContextFactoryInterface
{

    /**
     * @param string             $url
     * @param string             $method
     * @param string|SplFileInfo $content
     *
     * @return array
     */
    function create($url, $method, $content)
    {
        if ($content instanceof SplFileInfo) {
            $multipartStream = new MultipartStream([
                [
                    'name'     => 'UploadedFile',
                    'contents' => fopen($content->getRealPath(), 'r'),
                    'filename' => $content->getBasename(),
                ],
            ]);
            return [
                'body' => $multipartStream
            ];
        } else {
            return [
                'headers' => [
                    'Content-type' => 'application/json',
                ],
                'body'    => $content,
            ];
        }
    }
}
