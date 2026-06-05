<?php

namespace App\Services;

use App\Models\UserFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Storage};

class UserFileService
{
    private function storeFile(int $centerId, array $data, UploadedFile $file): string
    {
        $userId = (int) $data['user_id'];
        $category = $data['category'];

        $subdir = $this->resolveSubdir($category, $data['skin_evaluation_id'] ?? null);

        $directory = "centers/{$centerId}/users/{$userId}/{$subdir}";

        $filename = $category.'_'.now()->format('YmdHis').'.'.$file->getClientOriginalExtension();

        return $file->storeAs($directory, $filename, 'local');
    }

    private function resolveSubdir(string $category, ?int $skinEvaluationId): string
    {
        if ($category === UserFile::CATEGORY_AVATAR) {
            return 'avatar';
        }

        if ($category === UserFile::CATEGORY_CONSENT_SIGNATURE) {
            return 'consent_signatures';
        }

        if ($category === UserFile::CATEGORY_CONSENT_PDF) {
            return 'consent_pdfs';
        }

        if ($skinEvaluationId !== null) {
            return "skin_evaluations/{$skinEvaluationId}";
        }

        return 'misc';
    }

    public function upload(int $centerId, array $data, UploadedFile $file): UserFile
    {
        return DB::transaction(function () use ($centerId, $data, $file) {
            //para avatar reemplazamos el anterior si existia (regla de unicidad)
            if ($data['category'] === UserFile::CATEGORY_AVATAR) {
                $this->deleteExistingAvatar($centerId, (int) $data['user_id']);
            }

            $path = $this->storeFile($centerId, $data, $file);

            return UserFile::create([
                'center_id' => $centerId,
                'user_id' => $data['user_id'],
                'skin_evaluation_id' => $data['skin_evaluation_id'] ?? null,
                'category' => $data['category'],
                'path' => $path,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    //variante de upload() para bytes en memoria (firma base64, PDF generado), no para ficheros subidos
    public function storeBinary(int $centerId, array $data, string $contents, string $extension): UserFile
    {
        return DB::transaction(function () use ($centerId, $data, $contents, $extension) {
            $userId = (int) $data['user_id'];
            $category = $data['category'];

            $subdir = $this->resolveSubdir($category, $data['skin_evaluation_id'] ?? null);
            $directory = "centers/{$centerId}/users/{$userId}/{$subdir}";
            $filename = $category.'_'.now()->format('YmdHis').'.'.$extension;
            $path = "{$directory}/{$filename}";

            Storage::disk('local')->put($path, $contents);

            return UserFile::create([
                'center_id' => $centerId,
                'user_id' => $userId,
                'skin_evaluation_id' => $data['skin_evaluation_id'] ?? null,
                'category' => $category,
                'path' => $path,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function delete(UserFile $file): void
    {
        DB::transaction(function () use ($file) {
            Storage::disk('local')->delete($file->path);
            $file->delete();
        });
    }

    private function deleteExistingAvatar(int $centerId, int $userId): void
    {
        $existing = UserFile::query()
            ->forCenter($centerId)
            ->where('user_id', $userId)
            ->where('category', UserFile::CATEGORY_AVATAR)
            ->first();

        if ($existing !== null) {
            Storage::disk('local')->delete($existing->path);
            $existing->delete();
        }
    }
}
