<?php

namespace WyriHaximus\S3ParallelUpload;

use React\Filesystem\Node;

class File {

    public function __construct($local, $remote, Node\File $file) {
        $this->local = $local;
        $this->remote = $remote;
        $this->file = $file;
    }

    public function getLocal() {
        return $this->local;
    }

    public function getRemote() {
        return $this->remote;
    }

    public function getFile() {
        return $this->file;
    }
}