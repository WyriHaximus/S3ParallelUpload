<?php

namespace WyriHaximus\S3ParallelUpload;

use Aws\Common\Credentials\Credentials;
use Aws\Common\Signature\SignatureV4;
use Guzzle\Http\Message\Request;
use GuzzleHttp\Client;
use React\EventLoop\Factory as EventLoopFactory;
use React\Stream\Stream;

class Upload {

    public function __construct(Credentials $credentials, LoopInterface $loop, Client $guzzle, File $file, Uploader $uploader) {
        $this->credentials = $credentials;
        $this->loop = $loop;
        $this->guzzle = $guzzle;
        $this->file = $file;
        $this->uploader = $uploader;

        $this->getFileContents();
    }

    protected function getFileContents() {
        $readStream = fopen($this->file->getLocal(), 'r');

        stream_set_blocking($readStream, 0);

        $buffer = '';
        $read = new Stream($readStream, $this->loop);
        $read->on('data', function($data) use (&$buffer) {
            $buffer .= $data;
        });
        $read->on('end', function() use (&$buffer) {
            $this->loop->nextTick(function() use ($buffer) {
                $this->upload($buffer);
            });
        });
    }

    protected function upload($data) {
        $method = 'PUT';
        $schema = 'http://';
        $host = $this->uploader->options['bucket'] . '.s3.amazonaws.com';
        $path = '/' . $this->file->getRemote();
        $url = $schema . $host . $path;
        $region = $this->uploader->options['region'];
        $dataLength = $this->file->getSize();

        $request = $this->sign($method, $url, $dataLength, $data, $host, $region);

        $headers = array();
        foreach ($request->getHeaders() as $value) {
            $headers[$value->getName()] = (string) $value;
        }

        $this->guzzle->put($url, [
            'timeout' => 3,
            'headers' => $headers,
            'body' => $data,
        ])->then(function() {
            $this->uploader->upload();
        }, function() {
            $this->uploader->retry($this->file);
        });
    }

    protected function sign($method, $url, $dataLength, $data, $host, $region) {
        $request = new Request($method, $url, [
            'Content-Length' => $dataLength,
            'X-Amz-Content-Sha256' => hash('sha256', $data),
        ]);
        $request->setHost($host);

        $signature = new SignatureV4();
        $signature->setServiceName('s3');
        $signature->setRegionName($region);
        $signature->signRequest($request, $this->credentials);

        return $request;
    }

}