<?php

$config = [
    'access_key' => '', // app key
    'secret_key' => '', // secret key
    'bucket' => '', // bucket name
    'endpoint' => 'https://minio.demo.com', // we don't need a trailing slash here
    'proxy_url' => 'https://cdn.demo.com/', // remember the trailing slash here though!
    'temp_dir' => 'tmp/',
    'check_bucket' => false, // set to true if you want to check if the bucket exists before operating (costs 1 API call)
];

/**
 * Standard for S3-compatible storage. Feel free to edit accordingly.
 * For reference, see the aws-php-sdk package.
 */
$s3config = [
    'driver' => 's3',
    'region' => 'us-east-1',
    'bucket' => $config['bucket'],
    'endpoint' => $config['endpoint'],
    'version' => 'latest',
    'credentials' => [
        'key' => $config['access_key'],
        'secret' => $config['secret_key']
    ],
    'use_path_style_endpoint' => true // important for non-Amazon implementations
];