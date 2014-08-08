<?php

namespace WyriHaximus\S3ParallelUpload;

use Aws\Common\Credentials\Credentials;
use GuzzleHttp\Client;
use React\EventLoop\Factory as EventLoopFactory;

class Uploader {

    public function __construct(Credentials $credentials, LoopInterface $loop, Client $guzzle, $options = []) {
        $this->credentials = $credentials;
        $this->loop = $loop;
        $this->guzzle = $guzzle;
        $this->options = array_merge([
            'concurrency' => 3,
            'base-dir' => '',
            'region' => 'eu-west-1',
        ], $options);
    }

    public function setup(\Iterator $files) {
        $this->files = $files;
        $this->files->rewind();

        for ($i = 0; $i < $this->options['concurrency']; $i++) {
            $this->upload();
        }
    }

    public function upload() {
        if (!$this->files->valid()) {
            return;
        }
        $file = $this->files->current();
        $this->files->next();

        $this->loop->nextTick(function() use ($file) {
            $fileObject = new File($file->getPathName(), $this->options['prefix'] . str_replace($this->options['base-dir'] . '/', '', $file->getPathName()), filesize($file->getPathName()));
            new Upload($this->credentials, $this->loop, $this->guzzle, $fileObject, $this);
        });
    }

    public function retry(File $file) {
        new Upload($this->credentials, $this->loop, $this->guzzle, $file, $this);
    }

}