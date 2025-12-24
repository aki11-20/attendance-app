<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class StaffUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $staff = [
            ['name' => '西　伶奈', 'email' => 'reina.n@coachtech.com'],
            ['name' => '山田　太郎', 'email' => 'taro.y@coachtech.com'],
            ['name' => '増田　一世', 'email' => 'issei.m@coachtech.com'],
            ['name' => '山本　敬吉', 'email' => 'keikichi.y@coachtech.com'],
            ['name' => '秋田　朋美', 'email' => 'tomomi.a@coachtech.com'],
            ['name' => '中西　教夫', 'email' => 'norio.n@coachtech.com'],
        ];

        foreach ($staff as $row) {
            User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => Hash::make('password123'),
                    'role' => 0,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
