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
        Schema::table('bills', function (Blueprint $table) {
            // Detailed bill information
            $table->string('signature')->nullable()->after('sign');
            $table->string('session')->nullable()->after('bill_date');
            $table->json('submitters')->nullable()->after('session'); // Array of submitter names
            $table->json('committees')->nullable()->after('submitters'); // Array of committee assignments
            
            // PDF and text content
            $table->string('pdf_url')->nullable()->after('committees');
            $table->string('pdf_filename')->nullable()->after('pdf_url');
            $table->longText('extracted_text')->nullable()->after('pdf_filename');
            $table->timestamp('pdf_downloaded_at')->nullable()->after('extracted_text');
            
            // Additional metadata
            $table->json('reports')->nullable()->after('pdf_downloaded_at'); // Committee reports
            $table->json('stenograms')->nullable()->after('reports'); // Meeting stenograms
            $table->json('opinions')->nullable()->after('stenograms'); // Official opinions
            $table->boolean('is_detailed')->default(false)->after('opinions'); // Flag for detailed data fetched
            
            // Text analysis fields
            $table->integer('word_count')->nullable()->after('is_detailed');
            $table->integer('character_count')->nullable()->after('word_count');
            $table->string('text_language')->nullable()->after('character_count');
            
            // Add index for better search performance
            $table->fullText(['title', 'extracted_text'])->name('bills_fulltext_search');
            $table->index(['is_detailed', 'bill_date'])->name('bills_detailed_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropFullText('bills_fulltext_search');
            $table->dropIndex('bills_detailed_date_idx');
            
            $table->dropColumn([
                'signature',
                'session',
                'submitters',
                'committees',
                'pdf_url',
                'pdf_filename',
                'extracted_text',
                'pdf_downloaded_at',
                'reports',
                'stenograms',
                'opinions',
                'is_detailed',
                'word_count',
                'character_count',
                'text_language'
            ]);
        });
    }
};