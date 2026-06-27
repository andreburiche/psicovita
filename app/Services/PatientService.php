<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\User;
use App\Support\ContactHasher;
use App\Support\CpfHasher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PatientService
{
    public function paginateForProfessional(User $actor, ?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Patient::query();

        if (! $actor->isAdmin()) {
            $ownerId = $actor->tenantProfessionalId() ?? ($actor->isProfessional() ? $actor->id : null);
            if ($ownerId === null) {
                return Patient::query()->whereRaw('1 = 0')->paginate($perPage)->withQueryString();
            }
            $query->where('professional_id', $ownerId);
        }

        $paginator = $query
            ->when($search, function (Builder $query) use ($search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%');

                    $email = Str::lower(trim($search));
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $q->orWhere('email_hash', ContactHasher::emailHash($email));
                    }

                    $phoneDigits = only_digits($search);
                    if (strlen($phoneDigits) >= 10) {
                        $q->orWhere('phone_hash', ContactHasher::phoneHash($phoneDigits));
                    }

                    $cpfDigits = only_digits($search);
                    if (strlen($cpfDigits) === 11 && is_valid_cpf($cpfDigits)) {
                        $q->orWhere('cpf_hash', CpfHasher::hash($cpfDigits));
                    }
                });
            })
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();

        $this->hydratePortalUsers($paginator->getCollection());

        return $paginator;
    }

    public function hydratePortalUsers(Collection $patients): void
    {
        $hashes = $patients->pluck('email_hash')->filter()->unique()->values();

        if ($hashes->isEmpty()) {
            $patients->each(fn (Patient $patient) => $patient->setRelation('portalUser', null));

            return;
        }

        $users = User::query()
            ->whereIn('email_hash', $hashes)
            ->get()
            ->keyBy('email_hash');

        $patients->each(function (Patient $patient) use ($users) {
            $patient->setRelation(
                'portalUser',
                $patient->email_hash ? $users->get($patient->email_hash) : null
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Patient
    {
        $data['professional_id'] = $actor->tenantProfessionalId() ?? $actor->id;
        $data = $this->normalizePatientPayload($data);

        return Patient::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Patient $patient, array $data): Patient
    {
        $data = $this->normalizePatientPayload($data);

        $patient->fill($data);
        $patient->save();

        return $patient->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizePatientPayload(array $data): array
    {
        if (array_key_exists('email', $data)) {
            $data['email'] = $this->normalizePatientEmail($data['email'] ?? null);
        }
        if (array_key_exists('phone', $data)) {
            $data['phone'] = $this->normalizePhone($data['phone'] ?? null);
        }
        if (array_key_exists('cpf', $data)) {
            $data['cpf'] = $this->normalizeCpf($data['cpf'] ?? null);
        }
        if (array_key_exists('address_postal_code', $data)) {
            $data['address_postal_code'] = $this->normalizeCep($data['address_postal_code'] ?? null);
        }
        if (array_key_exists('address_state', $data)) {
            $data['address_state'] = $this->normalizeUf($data['address_state'] ?? null);
        }

        return $data;
    }

    private function normalizePhone(mixed $phone): ?string
    {
        if (! is_string($phone)) {
            return null;
        }

        $digits = only_digits($phone);

        return $digits === '' ? null : $digits;
    }

    private function normalizeCpf(mixed $cpf): ?string
    {
        if (! is_string($cpf)) {
            return null;
        }

        $digits = only_digits($cpf);

        return $digits === '' ? null : $digits;
    }

    private function normalizeCep(mixed $cep): ?string
    {
        if (! is_string($cep)) {
            return null;
        }

        $digits = only_digits($cep);

        return strlen($digits) === 8 ? $digits : null;
    }

    private function normalizeUf(mixed $uf): ?string
    {
        if (! is_string($uf)) {
            return null;
        }

        $t = strtoupper(trim($uf));

        return $t === '' ? null : mb_substr($t, 0, 2);
    }

    private function normalizePatientEmail(mixed $email): ?string
    {
        if (! is_string($email)) {
            return null;
        }

        $trimmed = trim($email);

        return $trimmed === '' ? null : Str::lower($trimmed);
    }

    public function delete(Patient $patient): void
    {
        $patient->delete();
    }
}
