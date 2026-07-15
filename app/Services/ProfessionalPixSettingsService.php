<?php

namespace App\Services;

use App\Enums\PaymentMethodPreference;
use App\Models\User;
use App\Support\ImageUploadOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfessionalPixSettingsService
{
    public function __construct(
        private readonly ImageUploadOptimizer $optimizer,
    ) {}

    /**
     * @param  array{payment_method_preference?: string, pix_manual_link?: string|null, remove_pix_qrcode?: bool}  $data
     */
    public function update(User $owner, array $data, ?UploadedFile $qrcode = null): User
    {
        if (array_key_exists('payment_method_preference', $data)) {
            $owner->payment_method_preference = PaymentMethodPreference::from($data['payment_method_preference']);
        }

        if (array_key_exists('pix_manual_link', $data)) {
            $link = trim((string) ($data['pix_manual_link'] ?? ''));
            $owner->pix_manual_link = $link !== '' ? $link : null;
        }

        if (! empty($data['remove_pix_qrcode'])) {
            $this->deleteQrcode($owner);
            $owner->pix_qrcode_path = null;
        }

        if ($qrcode instanceof UploadedFile) {
            $this->deleteQrcode($owner);
            $optimized = $this->optimizer->optimize($qrcode, maxEdge: 1200, quality: 88);
            try {
                $path = $optimized->storeAs(
                    'pix-qrcodes/'.$owner->id,
                    Str::uuid()->toString().'.jpg',
                    'public'
                );
            } finally {
                if ($optimized !== $qrcode) {
                    $this->optimizer->cleanupTemp($optimized);
                }
            }
            $owner->pix_qrcode_path = $path;
        }

        $owner->save();

        return $owner->fresh();
    }

    public function deleteQrcode(User $owner): void
    {
        if (filled($owner->pix_qrcode_path)) {
            Storage::disk('public')->delete($owner->pix_qrcode_path);
        }
    }
}
