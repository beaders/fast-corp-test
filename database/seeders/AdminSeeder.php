<?php

namespace Database\Seeders;

use App\Models\User;
use App\Rules\BcryptPassword;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = Validator::make([
            'name' => config('admin.name'),
            'email' => Str::lower(trim((string) config('admin.email'))),
            'password' => config('admin.password'),
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['bail', 'required', 'string', 'min:8', new BcryptPassword],
        ])->validate();

        $existingUser = User::query()->where('email', $attributes['email'])->first();

        if ($existingUser !== null) {
            if ($existingUser->role !== 'admin') {
                throw new RuntimeException('ADMIN_EMAIL already belongs to a regular user. Configure a different ADMIN_EMAIL.');
            }

            return;
        }

        (new User)->forceFill([
            ...$attributes,
            'role' => 'admin',
        ])->save();
    }
}
