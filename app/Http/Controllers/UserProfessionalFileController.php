<?php

namespace App\Http\Controllers;

use App\Enums\UserProfessionalFileCategory;
use App\Models\UserProfessionalFile;
use App\Services\UserProfessionalFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserProfessionalFileController extends Controller
{
    public function __construct(
        private readonly UserProfessionalFileService $files,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isProfessional() || $user->isAdmin(), 403);

        $maxKb = (int) config('profile.max_upload_kb', 10240);
        $maxFiles = (int) config('profile.max_files_per_upload', 10);
        $mimes = implode(',', config('profile.allowed_mimes', ['pdf']));

        $validated = $request->validate([
            'category' => ['required', 'in:'.implode(',', array_column(UserProfessionalFileCategory::cases(), 'value'))],
            'title' => ['nullable', 'string', 'max:255'],
            'files' => ['required', 'array', 'min:1', 'max:'.$maxFiles],
            'files.*' => ['file', 'max:'.$maxKb, 'mimes:'.$mimes],
        ]);

        $category = UserProfessionalFileCategory::from($validated['category']);
        $uploaded = $validated['files'] ?? [];

        $this->files->storeMany(
            $user,
            $uploaded,
            $category,
            $validated['title'] ?? null,
        );

        return redirect()
            ->route('profile.edit')
            ->with('status', 'professional-files-uploaded');
    }

    public function download(Request $request, UserProfessionalFile $professionalFile): StreamedResponse
    {
        abort_unless($professionalFile->user_id === $request->user()->clinicalPracticeId(), 403);
        abort_unless(Storage::disk('local')->exists($professionalFile->file_path), 404);

        return Storage::disk('local')->download(
            $professionalFile->file_path,
            $professionalFile->original_name,
        );
    }

    public function destroy(Request $request, UserProfessionalFile $professionalFile): RedirectResponse
    {
        abort_unless($professionalFile->user_id === $request->user()->clinicalPracticeId(), 403);

        $this->files->delete($professionalFile);

        return redirect()
            ->route('profile.edit')
            ->with('status', 'professional-file-deleted');
    }
}
