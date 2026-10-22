<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('note_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('title');
            $table->text('content');
            $table->boolean('is_important');
            $table->unsignedInteger('restored_from_version')->nullable();
            $table->timestamp('created_at');
            $table->unique(['note_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_revisions');
    }
};
