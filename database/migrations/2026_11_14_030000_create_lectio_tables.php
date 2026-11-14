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
        // 1. Books (Master Katalog)
        Schema::create('books', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('isbn', 20)->nullable()->index();
            $table->string('title');
            $table->string('author');
            $table->string('publisher', 150)->nullable();
            $table->integer('total_pages')->nullable();
            $table->string('genre', 100)->nullable();
            $table->text('cover_url')->nullable();
            $table->text('synopsis')->nullable();
            $table->timestamps();
        });

        // 2. User Books (Rak Buku Pengguna)
        Schema::create('user_books', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('book_id')->constrained('books')->cascadeOnDelete();
            $table->enum('status', ['want_to_read', 'reading', 'finished', 'dnf'])->default('want_to_read');
            $table->enum('format', ['physical', 'ebook', 'audiobook'])->default('physical');
            $table->integer('current_page')->default(0);
            $table->decimal('rating', 2, 1)->nullable();
            $table->text('review')->nullable();
            $table->date('started_at')->nullable();
            $table->date('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'book_id']);
            $table->index(['user_id', 'status']);
        });

        // 3. Reading Sessions (Log & Timer Sesi Membaca)
        Schema::create('reading_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('user_book_id')->constrained('user_books')->cascadeOnDelete();
            $table->integer('start_page')->default(0);
            $table->integer('end_page')->default(0);
            $table->integer('duration_minutes')->default(0);
            $table->date('session_date');
            $table->timestamps();

            $table->index(['user_id', 'session_date']);
        });

        // 4. Reading Goals (Target Harian & Tahunan)
        Schema::create('reading_goals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('goal_type', ['daily_pages', 'daily_minutes', 'yearly_books']);
            $table->integer('target_value');
            $table->integer('year');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'goal_type', 'year']);
        });

        // 5. User Streaks (Streak & Freeze)
        Schema::create('user_streaks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->integer('current_streak')->default(0);
            $table->integer('longest_streak')->default(0);
            $table->date('last_read_date')->nullable();
            $table->integer('freeze_count')->default(1); // Free initial freeze
            $table->timestamps();
        });

        // 6. Notes & Highlights (Kutipan & Catatan)
        Schema::create('notes_and_highlights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_book_id')->constrained('user_books')->cascadeOnDelete();
            $table->integer('page_number')->nullable();
            $table->text('quote_text');
            $table->text('personal_note')->nullable();
            $table->timestamps();
        });

        // 7. Badges (Gamifikasi Lencana)
        Schema::create('badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->text('description');
            $table->text('icon_url')->nullable();
            $table->string('requirement_type', 50); // books_finished, streak_days, pages_read, notes_count
            $table->integer('requirement_value');
            $table->timestamps();
        });

        // 8. User Badges (Lencana Pengguna)
        Schema::create('user_badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('badge_id')->constrained('badges')->cascadeOnDelete();
            $table->timestamp('earned_at');
            $table->timestamps();

            $table->unique(['user_id', 'badge_id']);
        });

        // 9. Collections (Rak Kustom)
        Schema::create('collections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 10. Collection Items
        Schema::create('collection_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('collection_id')->constrained('collections')->cascadeOnDelete();
            $table->foreignUuid('user_book_id')->constrained('user_books')->cascadeOnDelete();
            $table->timestamp('added_at')->useCurrent();

            $table->unique(['collection_id', 'user_book_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_items');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('notes_and_highlights');
        Schema::dropIfExists('user_streaks');
        Schema::dropIfExists('reading_goals');
        Schema::dropIfExists('reading_sessions');
        Schema::dropIfExists('user_books');
        Schema::dropIfExists('books');
    }
};

