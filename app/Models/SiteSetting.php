<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'brand_name',
    'tagline',
    'whatsapp_number',
    'email',
    'address',
    'instagram_url',
    'tiktok_url',
    'hero_headline',
    'hero_subheadline',
    'stat_1_label',
    'stat_1_value',
    'stat_2_label',
    'stat_2_value',
    'stat_3_label',
    'stat_3_value',
    'stat_4_label',
    'stat_4_value',
])]
class SiteSetting extends Model
{
    /**
     * Ambil baris pengaturan tunggal. Membuat default bila belum ada.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'brand_name' => 'Kolam Prima',
            'tagline' => 'Desain, Bangun & Rawat Kolam Renang Impian Anda',
        ]);
    }

    /**
     * Daftar statistik bilah kepercayaan (label + nilai).
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function stats(): array
    {
        $stats = [];

        for ($i = 1; $i <= 4; $i++) {
            $label = $this->{"stat_{$i}_label"};
            $value = $this->{"stat_{$i}_value"};

            if (filled($label) && filled($value)) {
                $stats[] = ['label' => $label, 'value' => $value];
            }
        }

        return $stats;
    }
}
