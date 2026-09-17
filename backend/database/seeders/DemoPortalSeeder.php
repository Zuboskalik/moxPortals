<?php

namespace Database\Seeders;

use App\Enums\PortalStatus;
use App\Models\Portal;
use Illuminate\Database\Seeder;

class DemoPortalSeeder extends Seeder
{
    public function run(): void
    {
        // Покрывает все edge cases из plan.md §2.5 / specify.md §5:
        // CRITICAL-риск (BR-2), closed-статус (BR-1), creatures_count > 0 (BR-3),
        // безопасный к закрытию портал, и "здоровый" LOW-риск портал.
        Portal::query()->create([
            'name' => 'Врата Пепла',
            'destination_world' => 'Кинжарис',
            'energy_level' => 95,
            'stability' => 0.12,
            'time_to_collapse' => 6,
            'creatures_count' => 0,
            'status' => PortalStatus::Active,
        ]);

        Portal::query()->create([
            'name' => 'Разлом Тишины',
            'destination_world' => 'Мортвель',
            'energy_level' => 40,
            'stability' => 0.55,
            'time_to_collapse' => 45,
            'creatures_count' => 0,
            'status' => PortalStatus::Closed,
        ]);

        Portal::query()->create([
            'name' => 'Звенящая Арка',
            'destination_world' => 'Эфириус',
            'energy_level' => 58,
            'stability' => 0.6,
            'time_to_collapse' => 50,
            'creatures_count' => 3,
            'status' => PortalStatus::Active,
        ]);

        Portal::query()->create([
            'name' => 'Тихая Заводь',
            'destination_world' => 'Ленталь',
            'energy_level' => 25,
            'stability' => 0.8,
            'time_to_collapse' => 90,
            'creatures_count' => 0,
            'status' => PortalStatus::UnderReview,
        ]);

        Portal::query()->create([
            'name' => 'Сад Спокойствия',
            'destination_world' => 'Виридиан',
            'energy_level' => 10,
            'stability' => 0.97,
            'time_to_collapse' => 120,
            'creatures_count' => 0,
            'status' => PortalStatus::Active,
        ]);
    }
}
