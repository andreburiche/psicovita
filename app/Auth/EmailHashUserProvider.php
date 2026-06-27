<?php

namespace App\Auth;

use App\Support\ContactHasher;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EmailHashUserProvider extends EloquentUserProvider
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $credentials = array_filter(
            $credentials,
            fn ($value, $key) => ! str_contains((string) $key, 'password'),
            ARRAY_FILTER_USE_BOTH,
        );

        if ($credentials === [] || ! isset($credentials['email'])) {
            return null;
        }

        $email = Str::lower(trim((string) $credentials['email']));
        $model = $this->createModel();
        $table = $model->getTable();

        if (! Schema::hasColumn($table, 'email_hash')) {
            return parent::retrieveByCredentials($credentials);
        }

        $user = $this->newModelQuery()
            ->where('email_hash', ContactHasher::emailHash($email))
            ->first();

        if ($user !== null) {
            return $user;
        }

        // Linhas legadas ainda sem hash (entre migrate e comando de cifra).
        return parent::retrieveByCredentials($credentials);
    }
}
