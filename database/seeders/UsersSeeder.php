<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'name'=> 'miguel angel villanueva',
            'email' => 'miguel.villanueva@cyberpeace.tech',
            'password' => Hash::make('tenshi123'),
        ]);
        DB::table('users')->insert([
            'name'=> 'luis gerardo pineda',
            'email' => 'luis.pineda@cyberpeace.tech',
            'password' => Hash::make('pelon123'),
        ]);
    }
}
