<?php

/**
 * Backblaze B2 CDN Management Utility
 * Manages files on a public bucket for CDN functionality.
 *
 * @author Liam Demafelix
 * @version 1.0.0
 * @license MIT
 */

function is_cli(): bool
{
    if (empty($_SERVER['argv'])) {
        return false;
    }
    return true;
}

function buckets($s3): array
{
    if (!is_object($s3)) {
        return [];
    }

    $s3buckets = $s3->listBuckets();
    $buckets = [];
    foreach ($s3buckets as $s3bucket) {
        foreach ($s3bucket as $s3bucketObj) {
            if (!empty($s3bucketObj['Name'])) {
                $buckets[] = $s3bucketObj['Name'];
            }
        }
    }
    return $buckets;
}

function bucket_exists($name, $s3): bool
{
    $buckets = buckets($s3);
    if (!in_array($name, $buckets)) {
        return false;
    }
    return true;
}