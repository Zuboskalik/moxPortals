<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('portal_id');
            $table->enum('action_type', [
                'stabilize', 'close', 'dispatch_observer', 'mark_under_review',
            ]);
            $table->string('description');
            $table->json('previous_state');
            $table->json('new_state');
            $table->timestamp('timestamp')->useCurrent();

            $table->foreign('portal_id')->references('id')->on('portals')
                ->onDelete('cascade');
            $table->index(['portal_id', 'timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_logs');
    }
};
