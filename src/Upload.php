<?php

namespace WyriHaximus\S3ParallelUpload;

class Upload {

    public function __construct($client, LoopInterface $loop, File $file, Uploader $uploader) {
        $this->client = $client;
        $this->loop = $loop;
        $this->file = $file;
        $this->uploader = $uploader;
    }

    public function run()
    {
        $this->uploader->log('readingfile|' . $this->file->getRemote());
        return $this->file->getFile()->getContents()->then(function ($buffer)  {
            $this->uploader->log('read|' . $this->file->getRemote());
            return $this->upload($buffer);
        });
    }

    protected function upload($data) {
        $this->uploader->log('puttingobject|' . $this->file->getRemote());
        return $this->client->putObject([
            'Bucket' => $this->uploader->options['bucket'],
            'Key' => '/' . $this->file->getRemote(),
            'Body' => $data,
            '@future' => true,
        ])->then(function() {
            $this->uploader->log('putobject|' . $this->file->getRemote());
            return $this->uploader->upload();
        }, function() {
            return $this->uploader->retry($this->file);
        });
    }
}