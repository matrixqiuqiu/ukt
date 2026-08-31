<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispensasiSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_path',
        'template_filename',
        'template_mime',
        'updated_by',
    ];

    public static function instance(): static
    {
        $setting = static::first();
        if (!$setting) {
            $setting = static::create([]);
        }
        return $setting;
    }

    public function templateUrl(): ?string
    {
        if (empty($this->template_path)) {
            return null;
        }

        return asset('storage/' . ltrim($this->template_path, '/'));
    }
}
