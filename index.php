<?php

/**
 * Backblaze B2 CDN Management Utility
 * Manages files on a public bucket for CDN functionality.
 *
 * @author Liam Demafelix
 * @version 1.0.0
 * @license MIT
 */

// Get the configuration file
require "config.php";

// Get the functions helper file
require "functions.php";

// Check if a resource was passed
$newline = "<br>";
$source = null;
$dest = null;
$is_cli = is_cli();
if (!$is_cli) {
    header('Content-Type: text/json');
}
if ($is_cli) {
    $newline = "\n";
    echo "[INFO] You are running b2cdn in CLI mode.{$newline}";
    if (empty($_SERVER['argv'][1])) {
        echo "[ERROR] No source file found{$newline}";
        exit;
    } else {
        $source = $_SERVER['argv'][1];
        echo "[INFO] Source file found: {$source}{$newline}";
        if (empty($_SERVER['argv'][2])) {
            echo "[ERROR] No destination path found{$newline}";
            exit;
        } else {
            $dest = $_SERVER['argv'][2];
        }
    }
} else {
    if (empty($_GET['source'])) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => 'No source found.']);
        exit;
    } else {
        $source = $_GET['source'];
        if (empty($_GET['dest'])) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'No destination path found.']);
            exit;
        } else {
            $dest = $_GET['dest'];
        }
    }
}

// Initialize the s3 client
require "vendor/autoload.php";
use Aws\S3\S3Client;
$s3 = new S3Client($s3config);
if ($is_cli) {
    echo "[INFO] Connected to endpoint{$newline}";
}

// Let's check if our bucket exists
if ($config['check_bucket']) {
    if (!bucket_exists($config['bucket'], $s3)) {
        if ($is_cli) {
            echo "[ERROR] Bucket `{$config['bucket']}` does not exist{$newline}";
        } else {
            echo json_encode(['status' => 404, 'message' => 'Destination bucket does not exist.']);
            exit;
        }
    } else {
        if ($is_cli) {
            echo "[INFO] Bucket `{$config['bucket']}` exists{$newline}";
        }
    }
}
// Do we have a temporary folder ready?
if (!is_dir($config['temp_dir'])) {
    mkdir($config['temp_dir'], 0755, true);
}

// Destination must not start with a '/'
$dest = ltrim($dest, "/");

// Let's check if the destination file exists
$desturl = "{$config['proxy_url']}file/{$config['bucket']}/{$dest}";
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_URL, $desturl);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
$chresp = curl_exec($ch);
$chcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($chcode != 404) {
    if ($is_cli) {
        echo "[ERROR] File exists on destination{$newline}";
    } else {
        http_response_code(409);
        echo json_encode(['status' => 409, 'message' => 'File exists on destination.']);
    }
    exit;
}

// Attempt to download file using copy
$basename = basename($source);
if ($is_cli) {
    echo "[INFO] Attempting to download {$basename} using copy(){$newline}";
}
$attempt_cp = @copy($source, "tmp/" . $basename);
if (!$attempt_cp) {
    if ($is_cli) {
        echo "[ERROR] Failed to download using copy(){$newline}";
    } else {
        echo json_encode(['status' => 500, 'message' => 'Failed to retrieve source file.']);
        exit;
    }
}

// Upload to bucket
if ($is_cli) {
    echo "[INFO] Attempting to upload object to bucket{$newline}";
}
try {
    $fr = fopen("tmp/{$basename}", 'r');
    $s3->putObject([
        'Bucket' => $config['bucket'],
        'Key' => $dest,
        'Body' => $fr,
        'ACL' => 'public-read',
    ]);
    if ($is_cli) {
        echo "[SUCCESS] Object created on bucket{$newline}";
    }
    if ($is_cli) {
        echo "{$newline}Your object URL is {$desturl}{$newline}";
    } else {
        echo json_encode(['status' => 201, 'message' => 'File created.', 'url' => $desturl]);
    }
} catch (\Aws\S3\Exception\S3Exception $e) {
    if ($is_cli) {
        echo "[ERROR] {$e->getMessage()}{$newline}";
    } else {
        echo json_encode(['status' => 500, 'message' => $e->getMessage()]);
        exit;
    }
} finally {
    // Delete the file.
    fclose($fr);
    unlink("tmp/{$basename}");
}