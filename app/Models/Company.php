<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug'])]
class Company extends Model
{
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Cari perusahaan dengan nama setara, atau buat baru.
     * Nama dinormalkan jadi slug supaya "PT Maju Jaya" dan "pt maju jaya"
     * dianggap perusahaan yang sama.
     */
    public static function findOrCreateByName(string $name): self
    {
        $name = trim($name);

        return static::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name],
        );
    }
}
