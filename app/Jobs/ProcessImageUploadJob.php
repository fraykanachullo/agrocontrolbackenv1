<?php

namespace App\Jobs;

use App\Models\EvidenciaFoto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessImageUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public int $evidenciaId,
        public string $tempPath,
    ) {}

    public function handle(): void
    {
        $evidencia = EvidenciaFoto::find($this->evidenciaId);

        if (!$evidencia) {
            return;
        }

        if (!file_exists($this->tempPath)) {
            return;
        }

        $imageContent = file_get_contents($this->tempPath);

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $imageContent);
        finfo_close($finfo);

        $extension = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $fechaDir = now()->format('Y/m/d');
        $filename = uniqid('ev_', true) . ".{$extension}";
        $path = "evidencias/{$fechaDir}/{$filename}";

        Storage::disk('public')->put($path, $imageContent);

        $evidencia->update([
            'ruta_archivo' => $path,
            'is_synced' => true,
        ]);

        @unlink($this->tempPath);
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error("Error procesando imagen evidencia {$this->evidenciaId}: " . $exception->getMessage());
    }
}
