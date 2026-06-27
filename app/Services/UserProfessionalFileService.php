<?php

namespace App\Services;

use App\Enums\UserProfessionalFileCategory;
use App\Models\User;
use App\Models\UserProfessionalFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UserProfessionalFileService
{
    /**
     * @param  array<int, UploadedFile>  $files
     * @return Collection<int, UserProfessionalFile>
     */
    public function storeMany(User $user, array $files, UserProfessionalFileCategory $category, ?string $titlePrefix = null): Collection
    {
        $stored = collect();
        $total = count($files);

        foreach ($files as $index => $file) {
            $stored->push($this->storeOne($user, $file, $category, $titlePrefix, $index, $total));
        }

        return $stored;
    }

    public function storeOne(
        User $user,
        UploadedFile $file,
        UserProfessionalFileCategory $category,
        ?string $titlePrefix = null,
        int $index = 0,
        int $total = 1,
    ): UserProfessionalFile {
        $directory = 'professional-files/'.$user->id;
        $safeName = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $safeName, 'local');

        return UserProfessionalFile::query()->create([
            'user_id' => $user->id,
            'title' => $this->resolveTitle($file, $titlePrefix, $index, $total),
            'category' => $category,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);
    }

    public function delete(UserProfessionalFile $file): void
    {
        $file->delete();
    }

    private function resolveTitle(UploadedFile $file, ?string $titlePrefix, int $index, int $total): string
    {
        if ($titlePrefix !== null && trim($titlePrefix) !== '') {
            $prefix = Str::limit(trim($titlePrefix), 120, '');

            return $total > 1 ? $prefix.' ('.($index + 1).')' : $prefix;
        }

        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $baseName = Str::limit(trim((string) $baseName), 200, '');

        return $baseName !== '' ? $baseName : __('Documento');
    }
}
