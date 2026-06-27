<?php

namespace App\Rules;

use App\Models\User;
use App\Support\ContactHasher;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class UniqueUserEmail implements ValidationRule
{
    public function __construct(
        private readonly ?int $ignoreUserId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $hash = ContactHasher::emailHash(Str::lower(trim((string) $value)));

        $query = User::query()->where('email_hash', $hash);

        if ($this->ignoreUserId !== null) {
            $query->where('id', '!=', $this->ignoreUserId);
        }

        if ($query->exists()) {
            $fail(__('validation.unique', ['attribute' => __('validation.attributes.email')]));
        }
    }
}
