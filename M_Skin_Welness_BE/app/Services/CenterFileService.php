<?php

namespace App\Services;

use App\Models\CenterFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Storage};

class CenterFileService
{
    public function upload(int $centerId, string $type, UploadedFile $file): CenterFile
    {
        return DB::transaction(function () use ($centerId, $type, $file) {
            $this->deleteExisting($centerId, $type);

            $directory = "centers/{$centerId}/branding";
            $filename = $type.'_'.now()->format('YmdHis').'.'.$file->getClientOriginalExtension();

            $path = $file->storeAs($directory, $filename, 'local');

            return CenterFile::create([
                'center_id' => $centerId,
                'type' => $type,
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
            ]);
        });
    }

    public function delete(CenterFile $file): void
    {
        DB::transaction(function () use ($file) {
            Storage::disk('local')->delete($file->path);
            $file->delete();
        });
    }

    private function deleteExisting(int $centerId, string $type): void
    {
        $existing = CenterFile::query()
            ->forCenter($centerId)
            ->where('type', $type)
            ->first();

        if ($existing !== null) {
            Storage::disk('local')->delete($existing->path);
            $existing->delete();
        }
    }
}
