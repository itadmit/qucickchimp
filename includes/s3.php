<?php
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

require ROOT_PATH . '/vendor/autoload.php';

function getS3Client() {
    return new S3Client([
        'version'     => 'latest',
        'region'      => S3_REGION,
        'credentials' => [
            'key'    => S3_ACCESS_KEY,
            'secret' => S3_SECRET_KEY,
        ],
    ]);
}

function uploadToS3($filePath, $s3Key) {
    $s3 = getS3Client();

    try {
        $result = $s3->putObject([
            'Bucket' => S3_BUCKET,
            'Key'    => $s3Key,
            'SourceFile' => $filePath,
            'ACL'    => 'public-read',
        ]);

        return $result['ObjectURL'];
    } catch (S3Exception $e) {
        error_log('S3 Upload Error: ' . $e->getMessage());
        return false;
    }
}
