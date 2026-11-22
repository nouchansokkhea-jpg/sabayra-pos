<?php

namespace App\Services\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SupabaseStorageService
{
    protected string $baseUrl;
    protected string $serviceKey;
    protected string $anonKey;
    protected string $bucket;
    protected bool $verifySsl;

    public function __construct()
    {
        $rawUrl = config('services.supabase.url') ?? env('SUPABASE_URL', '');
        // Normalize baseUrl by stripping trailing slash or /rest/v1/
        $this->baseUrl = preg_replace('#(/rest/v1/?|/storage/v1/?|/)$#', '', $rawUrl);
        $this->serviceKey = config('services.supabase.service_key') ?? env('SUPABASE_SERVICE_ROLE_KEY', '');
        $this->anonKey = config('services.supabase.anon_key') ?? env('SUPABASE_ANON_KEY', '');
        $this->bucket = config('services.supabase.bucket') ?? env('SUPABASE_STORAGE_BUCKET', 'menuhub-assets');
        $this->verifySsl = (bool) config('services.supabase.verify_ssl', false);
    }

    /**
     * Upload an uploaded file or raw binary content to Supabase Storage bucket.
     *
     * @param UploadedFile|string $file
     * @param string $folder e.g. 'products', 'logos', 'covers'
     * @return string|null Public URL of the uploaded asset
     */
    public function upload(UploadedFile|string $file, string $folder = 'uploads'): ?string
    {
        if (empty($this->baseUrl) || empty($this->serviceKey)) {
            return null;
        }

        $fileName = Str::uuid() . '_' . time();
        $mimeType = 'image/jpeg';
        $contents = '';

        if ($file instanceof UploadedFile) {
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $fileName .= '.' . $extension;
            $mimeType = $file->getMimeType() ?: 'application/octet-stream';
            $contents = file_get_contents($file->getRealPath());
        } else {
            $fileName .= '.jpg';
            $contents = $file;
        }

        $path = "{$folder}/{$fileName}";
        $uploadUrl = "{$this->baseUrl}/storage/v1/object/{$this->bucket}/{$path}";

        $client = Http::withHeaders([
            'Authorization' => "Bearer {$this->serviceKey}",
            'apiKey' => $this->serviceKey,
            'Content-Type' => $mimeType,
        ]);

        if (!$this->verifySsl) {
            $client = $client->withoutVerifying();
        }

        $response = $client->withBody($contents, $mimeType)->post($uploadUrl);

        if ($response->successful()) {
            return $this->getPublicUrl($path);
        }

        // Fallback to local storage disk if Supabase bucket is unavailable
        if ($file instanceof UploadedFile) {
            $storedPath = $file->store("uploads/{$folder}", 'public');
            return "/storage/{$storedPath}";
        }

        return null;
    }

    /**
     * Get the public URL for an asset in Supabase Storage.
     */
    public function getPublicUrl(string $path): string
    {
        $cleanPath = ltrim($path, '/');
        return "{$this->baseUrl}/storage/v1/object/public/{$this->bucket}/{$cleanPath}";
    }

    /**
     * Delete an asset from Supabase Storage.
     */
    public function delete(string $path): bool
    {
        if (empty($this->baseUrl) || empty($this->serviceKey)) {
            return false;
        }

        $deleteUrl = "{$this->baseUrl}/storage/v1/object/{$this->bucket}/{$path}";

        $client = Http::withHeaders([
            'Authorization' => "Bearer {$this->serviceKey}",
            'apiKey' => $this->serviceKey,
        ]);

        if (!$this->verifySsl) {
            $client = $client->withoutVerifying();
        }

        $response = $client->delete($deleteUrl);

        return $response->successful();
    }
}
