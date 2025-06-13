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
        Schema::create('committee_member', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_id')->constrained('committees')->onDelete('cascade');
            $table->foreignId('parliament_member_id')->constrained('parliament_members')->onDelete('cascade');
            $table->string('position')->nullable(); // председател, заместник-председател, член, etc.
            $table->date('date_from');
            $table->date('date_to');
            $table->timestamps();
            
            $table->unique(['committee_id', 'parliament_member_id', 'date_from']);
            $table->index(['committee_id', 'date_from', 'date_to']);
            $table->index(['parliament_member_id', 'date_from', 'date_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('committee_member');
    }
};
