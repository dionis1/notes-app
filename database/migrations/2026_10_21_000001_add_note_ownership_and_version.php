<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            // Existing anonymous notes stay unowned and inaccessible. Never assign them to a new registrant.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->index(['user_id', 'created_at', 'id']);
            $table->index(['user_id', 'is_important', 'created_at', 'id']);
        });
    }

    public function down(): void
    {
        // Laravel 10 cannot drop SQLite foreign keys in place. Rebuild the legacy schema.
        if (DB::getDriverName() === 'sqlite') {
            Schema::create('notes_before_ownership', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('content');
                $table->boolean('is_important')->default(false);
                $table->timestamps();
            });
            DB::statement('INSERT INTO notes_before_ownership (id, title, content, is_important, created_at, updated_at) SELECT id, title, content, is_important, created_at, updated_at FROM notes');
            Schema::drop('notes');
            Schema::rename('notes_before_ownership', 'notes');

            return;
        }

        Schema::table('notes', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at', 'id']);
            $table->dropIndex(['user_id', 'is_important', 'created_at', 'id']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('version');
        });
    }
};
