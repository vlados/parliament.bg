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
        Schema::create('parliament_members', function (Blueprint $table) {
            $table->id();
            $table->integer('member_id')->unique();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name');
            $table->string('electoral_district')->nullable();
            $table->string('political_party')->nullable();
            $table->string('profession')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            
            $table->index('member_id');
            $table->index('electoral_district');
            $table->index('political_party');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parliament_members');
    }
};
