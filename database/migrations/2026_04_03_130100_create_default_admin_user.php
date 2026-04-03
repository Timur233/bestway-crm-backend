<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateDefaultAdminUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $email = env('ADMIN_EMAIL', 'admin@bestwaycrm.local');

        $existingUser = DB::table('users')->where('email', $email)->first();

        $payload = [
            'name' => env('ADMIN_NAME', 'Bestway Admin'),
            'email' => $email,
            'is_admin' => true,
            'password' => Hash::make(env('ADMIN_PASSWORD', 'change-me-now')),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($existingUser) {
            DB::table('users')->where('id', $existingUser->id)->update([
                'name' => $payload['name'],
                'is_admin' => true,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('users')->insert($payload);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('users')
            ->where('email', env('ADMIN_EMAIL', 'admin@bestwaycrm.local'))
            ->delete();
    }
}
