<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gudang dan barang kini milik perusahaan, bukan milik satu akun, sehingga
 * seluruh akun dalam perusahaan yang sama melihat data yang sama.
 *
 * Urutan wajib: foreign key user_id dilepas dulu, baru index unik (user_id, …),
 * baru kolomnya — MySQL menolak membuang index yang masih dipakai foreign key.
 * Tiap langkah diperiksa dulu supaya migrasi ini aman dijalankan ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ([['warehouses', 'code'], ['items', 'sku']] as [$table, $column]) {
            if (! Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
                });
            }

            // Warisi perusahaan dari pemilik lama selagi kolom user_id masih ada.
            if (Schema::hasColumn($table, 'user_id')) {
                DB::statement("UPDATE {$table} t JOIN users u ON u.id = t.user_id SET t.company_id = u.company_id");
            }

            // Baris yatim (pemiliknya sudah terhapus) tidak bisa dipetakan.
            DB::table($table)->whereNull('company_id')->delete();

            $this->dropForeignIfExists($table, "{$table}_user_id_foreign");
            $this->dropIndexIfExists($table, "{$table}_user_id_{$column}_unique");

            if (Schema::hasColumn($table, 'user_id')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('user_id'));
            }

            Schema::table($table, fn (Blueprint $t) => $t->foreignId('company_id')->nullable(false)->change());

            if (! $this->indexExists($table, "{$table}_company_id_{$column}_unique")) {
                Schema::table($table, fn (Blueprint $t) => $t->unique(['company_id', $column]));
            }
        }
    }

    public function down(): void
    {
        foreach ([['warehouses', 'code'], ['items', 'sku']] as [$table, $column]) {
            $this->dropIndexIfExists($table, "{$table}_company_id_{$column}_unique");

            if (! Schema::hasColumn($table, 'user_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
                });
            }

            DB::statement("UPDATE {$table} t JOIN users u ON u.company_id = t.company_id SET t.user_id = u.id");
            DB::table($table)->whereNull('user_id')->delete();

            $this->dropForeignIfExists($table, "{$table}_company_id_foreign");
            $this->dropIndexIfExists($table, "{$table}_company_id_foreign");

            if (Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('company_id'));
            }

            Schema::table($table, fn (Blueprint $t) => $t->unique(['user_id', $column]));
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::selectOne(
            'SELECT 1 AS ada FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            [$table, $index]
        ) !== null;
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }

    private function dropForeignIfExists(string $table, string $constraint): void
    {
        $exists = DB::selectOne(
            'SELECT 1 AS ada FROM information_schema.table_constraints
             WHERE table_schema = DATABASE() AND table_name = ? AND constraint_name = ?
               AND constraint_type = "FOREIGN KEY" LIMIT 1',
            [$table, $constraint]
        );

        if ($exists !== null) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }
};
