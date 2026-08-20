<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Kategori bawaan - sama persis dengan SQLite mobile.
     * user_id NULL = kategori sistem (tidak bisa dihapus user).
     */
    public function run(): void
    {
        $categories = [
            // Pemasukan
            ['id' => '11111111-1111-1111-1111-100000000001', 'name' => 'Gaji',       'icon' => '💵', 'type' => 'pemasukan'],
            ['id' => '11111111-1111-1111-1111-100000000002', 'name' => 'Freelance',  'icon' => '💻', 'type' => 'pemasukan'],
            ['id' => '11111111-1111-1111-1111-100000000003', 'name' => 'Bonus',      'icon' => '🎁', 'type' => 'pemasukan'],
            ['id' => '11111111-1111-1111-1111-100000000004', 'name' => 'Investasi',  'icon' => '📈', 'type' => 'pemasukan'],
            ['id' => '11111111-1111-1111-1111-100000000005', 'name' => 'Lainnya',    'icon' => '💰', 'type' => 'pemasukan'],

            // Pengeluaran
            ['id' => '22222222-2222-2222-2222-200000000001', 'name' => 'Makanan',       'icon' => '🍔', 'type' => 'pengeluaran'],
            ['id' => '22222222-2222-2222-2222-200000000002', 'name' => 'Transportasi',  'icon' => '🚗', 'type' => 'pengeluaran'],
            ['id' => '22222222-2222-2222-2222-200000000003', 'name' => 'Belanja',       'icon' => '🛍️', 'type' => 'pengeluaran'],
            ['id' => '22222222-2222-2222-2222-200000000004', 'name' => 'Tagihan',       'icon' => '🧾', 'type' => 'pengeluaran'],
            ['id' => '22222222-2222-2222-2222-200000000005', 'name' => 'Hiburan',       'icon' => '🎬', 'type' => 'pengeluaran'],
            ['id' => '22222222-2222-2222-2222-200000000006', 'name' => 'Pendidikan',    'icon' => '📚', 'type' => 'pengeluaran'],
            ['id' => '22222222-2222-2222-2222-200000000007', 'name' => 'Kesehatan',     'icon' => '🏥', 'type' => 'pengeluaran'],
            ['id' => '22222222-2222-2222-2222-200000000008', 'name' => 'Tabungan',      'icon' => '🐷', 'type' => 'pengeluaran'],
            ['id' => '22222222-2222-2222-2222-200000000009', 'name' => 'Lainnya',       'icon' => '💸', 'type' => 'pengeluaran'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['id' => $cat['id']], // Pakai ID pasti supaya sama dengan frontend
                [
                    'user_id' => null,
                    'name'    => $cat['name'],
                    'type'    => $cat['type'],
                    'icon'    => $cat['icon'],
                ]
            );
        }
    }
}
