<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApiS3MediaController extends Controller
{
    /**
     * 1. S3直接アップロード用の署名付きURL（PUT）を発行
     */
    public function getUploadUrl(Request $request)
    {
        $extension = $request->input('extension', 'jpg');
        $fileKey = 'uploads/' . Str::uuid() . '.' . $extension;

        $client = Storage::disk('s3')->getClient();

        $command = $client->getCommand('PutObject', [
            'Bucket' => config('filesystems.disks.s3.bucket'),
            'Key'    => $fileKey,
        ]);

        $signedRequest = $client->createPresignedRequest($command, '+5 minutes');
        $uploadUrl = (string) $signedRequest->getUri();

        return response()->json([
            'upload_url' => $uploadUrl,
            'file_key'   => $fileKey,
        ]);
    }

    /**
     * 2. S3表示・閲覧用の署名付きURL（GET）を発行
     */
    public function getDownloadUrl(Request $request)
    {
        $fileKey = $request->query('key');

        if (!$fileKey) {
            return response()->json(['error' => 'Key parameter is required'], 400);
        }

        $downloadUrl = Storage::disk('s3')->temporaryUrl(
            $fileKey,
            now()->addMinutes(5)
        );

        return response()->json([
            'download_url' => $downloadUrl,
        ]);
    }
}