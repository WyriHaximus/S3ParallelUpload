<?php

namespace WyriHaximus\S3ParallelUpload;

use Aws\S3\S3Client;
use React\Filesystem\Filesystem;
use React\Promise\Deferred;
use React\Promise\FulfilledPromise;
use React\Promise\RejectedPromise;
use React\Stream\WritableStreamInterface;

class Uploader {

    protected $filesystem;
    protected $nodes;
    protected $logfile;

    public function __construct(S3Client $client, LoopInterface $loop, $options = []) {
        $this->client = $client;
        $this->loop = $loop;
        $this->options = array_merge([
            'concurrency' => 3,
            'base-dir' => '',
            'logfile' => null,
        ], $options);

        $this->filesystem = Filesystem::create($this->loop);
    }

    public function setup()
    {
        if ($this->options['logfile'] !== null) {
            return $this->filesystem->file($this->options['logfile'])->open('cwt')->then(function (WritableStreamInterface $stream) {
                $this->logfile = $stream;
                return $this->run();
            });
        }

        return $this->run();
    }

    protected function run()
    {
        $this->log('listingfiles');
        return $this->filesystem->dir($this->options['base-dir'])->lsRecursive()->then(function(\SplObjectStorage $nodes) {
            $this->log('listedfiles');
            $this->nodes = $nodes;
            $this->nodes->rewind();

            $promises = [];
            for ($i = 0; $i <= $this->options['concurrency']; $i++) {
                $promises[] = $this->upload();
            }

            return \React\Promise\all($promises);
        })->then(function () {
            $this->log('done');
            if ($this->logfile !== null) {
                $deferred = new Deferred();
                $this->logfile->on('close', function () use ($deferred) {
                    $deferred->resolve();
                });
                $this->logfile->end();
                return $deferred->promise();
            }

            return new FulfilledPromise();
        });
    }

    public function upload() {
        return $this->getNextFile()->then(function ($node) {
            $this->loop->nextTick(function() use ($node) {
                $file = new File($node->getPath(), $this->options['prefix'] . str_replace($this->options['base-dir'] . '/', '', $node->getPath()), $node);
                $this->log('upload|' . $file->getRemote());
                new Upload($this->credentials, $this->loop, $this->guzzle, $file, $this);
            });
        });
    }

    public function retry(File $file) {
        $this->log('retry|' . $file->getRemote());
        return new Upload($this->client, $this->loop, $file, $this);
    }

    protected function getNextFile()
    {
        while ($this->nodes->valid()) {
            $node = $this->nodes->current();
            if ($node instanceof File) {
                return new FulfilledPromise($node);
            }
            $this->nodes->next();
        }

        return new RejectedPromise(new \Exception('No more files'));
    }

    public function log($message)
    {
        if ($this->logfile !== null) {
            $this->logfile->write(microtime() . '|' . $message . PHP_EOL);
        }
    }
}