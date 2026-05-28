<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(IranLocationSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => config('education.admin_seed.email')],
            [
                'name' => config('education.admin_seed.name'),
                'password' => Hash::make(config('education.admin_seed.password')),
            ],
        );
        $admin->syncRoles([RoleName::Admin->value]);

        if (app()->environment(['local', 'testing'])) {
            $this->seedDemoUsers();
        }
    }

    private function seedDemoUsers(): void
    {
        $teacher = User::firstOrCreate(
            ['email' => 'teacher@bano.test'],
            ['name' => 'Demo Teacher', 'password' => Hash::make('password')],
        );
        $teacher->syncRoles([RoleName::Teacher->value]);

        $student = User::firstOrCreate(
            ['email' => 'student@bano.test'],
            ['name' => 'Demo Student', 'password' => Hash::make('password')],
        );
        $student->syncRoles([RoleName::Student->value]);

        $counselor = User::firstOrCreate(
            ['email' => 'counselor@bano.test'],
            ['name' => 'Demo Counselor', 'password' => Hash::make('password')],
        );
        $counselor->syncRoles([RoleName::Counselor->value]);
    }
}
