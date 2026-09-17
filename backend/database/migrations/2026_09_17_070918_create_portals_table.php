<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('destination_world');
            $table->unsignedTinyInteger('energy_level');
            $table->decimal('stability', 3, 2);
            $table->unsignedInteger('time_to_collapse');
            $table->unsignedInteger('creatures_count')->default(0);
            $table->enum('status', ['active', 'stabilized', 'closed', 'under_review'])
                ->default('active');
            $table->timestamps();

            $table->index('status');
        });

        // CHECK-констрейнты как последний рубеж защиты диапазонов (см. plan.md §1.2).
        // SQLite не поддерживает ALTER TABLE ... ADD CONSTRAINT, поэтому применяем только на MySQL;
        // тестовое окружение (sqlite) полагается на валидацию уровня приложения.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE portals ADD CONSTRAINT chk_energy_level CHECK (energy_level BETWEEN 1 AND 100)');
            DB::statement('ALTER TABLE portals ADD CONSTRAINT chk_stability CHECK (stability >= 0 AND stability <= 1)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portals');
    }
};
