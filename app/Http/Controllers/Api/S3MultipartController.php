<?php

namespace App\Http\Controllers\Api;

use Aws\S3\S3Client;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class S3MultipartController extends Controller
{
    protected function client(): S3Client
    {
        return new S3Client([
            'version' => '2006-03-01',
            'region'  => config('filesystems.disks.s3.region'),
            'credentials' => [
                'key'    => config('filesystems.disks.s3.key'),
                'secret' => config('filesystems.disks.s3.secret'),
            ],
        ]);
    }

    public function create(Request $r)
    {
        $r->validate([
            'filename' => 'required|string|max:255',
            'type'     => 'required|string|max:255',
        ]);

        $key = 'chat/' . date('Y/m/d/') . uniqid() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $r->filename);

        $res = $this->client()->createMultipartUpload([
            'Bucket'      => config('filesystems.disks.s3.bucket'),
            'Key'         => $key,
            'ContentType' => $r->type ?: 'application/octet-stream',
            'ACL'         => 'private',
        ]);

        return response()->json(['uploadId' => $res['UploadId'], 'key' => $key]);
    }

    public function signPart(Request $r)
    {
        $r->validate([
            'key'       => 'required|string',
            'uploadId'  => 'required|string',
            'partNumber'=> 'required|integer|min:1',
        ]);

        $cmd = $this->client()->getCommand('UploadPart', [
            'Bucket'     => config('filesystems.disks.s3.bucket'),
            'Key'        => $r->key,
            'UploadId'   => $r->uploadId,
            'PartNumber' => $r->partNumber,
        ]);

        $req = $this->client()->createPresignedRequest($cmd, '+5 minutes');
        return response()->json(['url' => (string) $req->getUri()]);
    }

    public function complete(Request $r)
    {
        $r->validate([
            'key'      => 'required|string',
            'uploadId' => 'required|string',
            'parts'    => 'required|array|min:1', // [{ETag:"...", PartNumber:n}]
        ]);

        $res = $this->client()->completeMultipartUpload([
            'Bucket'         => config('filesystems.disks.s3.bucket'),
            'Key'            => $r->key,
            'UploadId'       => $r->uploadId,
            'MultipartUpload'=> ['Parts' => $r->parts],
        ]);

        // We store only the "key" in DB and generate time-limited links server-side
        return response()->json(['ok' => true, 'key' => $r->key]);
    }
}
