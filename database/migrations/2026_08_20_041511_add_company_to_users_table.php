<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // Akun yang sudah ada dipindahkan ke perusahaan sendiri-sendiri agar
        // datanya tetap terpisah seperti sebelum fitur ini ada.
        foreach (DB::table('users')->whereNull('company_id')->get() as $user) {
            $name = 'Perusahaan '.$user->name;

            $companyId = DB::table('companies')->insertGetId([
                'name' => $name,
                'slug' => Str::slug($name).'-'.$user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')->where('id', $user->id)->update(['company_id' => $companyId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
