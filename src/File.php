<?php

namespace WyriHaximus\S3ParallelUpload;

class File {

    public function __construct($local, $remote, $size) {
        $this->local = $local;
        $this->remote = $remote;
        $this->size = $size;
    }

    public function getLocal() {
        return $this->local;
    }

    public function getRemote() {
        return $this->remote;
    }

    public function getSize() {
        return $this->size;
    }

}