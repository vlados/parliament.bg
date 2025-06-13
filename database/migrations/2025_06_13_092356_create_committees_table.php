<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('committees', function (Blueprint $table) {
            $table->id();
            $table->integer('committee_id')->unique();
            $table->integer('committee_type_id');
            $table->string('name');
            $table->integer('active_count')->nullable();
            $table->date('date_from');
            $table->date('date_to');
            $table->string('email')->nullable();
            $table->string('room')->nullable();
            $table->string('phone')->nullable();
            $table->text('rules')->nullable();
            $table->timestamps();
            
            $table->index('committee_id');
            $table->index('committee_type_id');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('committees');
    }
};
