<?php

namespace Tests\Feature;

use App\Models\Note;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use DatabaseMigrations;

    public function test_backup_restores_notes_and_passes_integrity_check(): void
    {
        $note = Note::factory()->create(['title' => 'Restore me']);
        $path = sys_get_temp_dir().'/notes-restore-'.bin2hex(random_bytes(6)).'.sqlite';
        try {
            $this->artisan('notes:backup', ['path' => $path])->assertSuccessful();
            $restored = new \PDO('sqlite:'.$path);
            $this->assertSame('ok', $restored->query('PRAGMA integrity_check')->fetchColumn());
            $this->assertSame('Restore me', $restored->query('SELECT title FROM notes')->fetchColumn());
            $this->assertSame($note->user_id, (int) $restored->query('SELECT user_id FROM notes')->fetchColumn());
            $this->artisan('notes:backup', ['path' => $path])->assertFailed();
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
