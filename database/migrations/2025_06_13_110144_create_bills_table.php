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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->integer('bill_id')->unique(); // L_Act_id
            $table->text('title'); // L_ActL_title
            $table->string('sign')->nullable(); // L_Act_sign
            $table->datetime('bill_date'); // L_Act_date
            $table->string('path')->nullable(); // path
            $table->integer('committee_id')->nullable(); // Committee that handles the bill
            $table->timestamps();
            
            $table->index(['bill_date', 'committee_id']);
            $table->index('bill_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
