S3ParallelUpload
================

[![Build Status](https://travis-ci.org/WyriHaximus/S3ParallelUpload.png)](https://travis-ci.org/WyriHaximus/S3ParallelUpload)
[![Latest Stable Version](https://poser.pugx.org/WyriHaximus/s3-parallel-upload/v/stable.png)](https://packagist.org/packages/WyriHaximus/s3-parallel-upload)
[![Total Downloads](https://poser.pugx.org/WyriHaximus/s3-parallel-upload/downloads.png)](https://packagist.org/packages/WyriHaximus/s3-parallel-upload)
[![Coverage Status](https://coveralls.io/repos/WyriHaximus/S3ParallelUpload/badge.png)](https://coveralls.io/r/WyriHaximus/S3ParallelUpload)
[![License](https://poser.pugx.org/wyrihaximus/s3-parallel-upload/license.png)](https://packagist.org/packages/wyrihaximus/s3-parallel-upload)

S3 upload tool using Guzzle and ReactPHP

## Installation ##

Installation is easy with composer just run.

```sh
composer require wyrihaximus/s3-parallel-upload
```

## Basic Usage ##

```php
<?php

require 'vendor/autoload.php';

use Aws\Common\Credentials\Credentials;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use React\EventLoop\Factory as EventLoopFactory;
use WyriHaximus\S3ParallelUpload\Uploader;
use WyriHaximus\React\Guzzle\HttpClientAdapter;

$concurrentUploads = 13; // Number of async uploads
$sourceDirectory = 'sea'; // The source directory
$prefix = ''; // Filename prefix just in case
$bucketName = 's3-bucket-name'; // The bucket we're uploading to
$region = 'eu-west-1'; // The region the bucket resides in
$credentials = new Credentials('YOUR', 'CREDENTIALS');

$loop = EventLoopFactory::create();

$guzzle = new Client([
    'adapter' => new HttpClientAdapter($loop),
]);

(new Uploader($credentials, $loop, $guzzle, [
    'concurrency' => $concurrentUploads,
    'base-dir' => $sourceDirectory,
    'prefix' => $prefix,
    'bucket' => $bucketName,
    'region' => $region,
]))->setup(new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceDirectory, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
));

$loop->run();
```

## License ##

Copyright 2014 [Cees-Jan Kiewiet](http://wyrihaximus.net/)

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
