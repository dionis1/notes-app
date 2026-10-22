<?php

namespace Database\Seeders;

use App\Actions\CreateNoteAction;
use App\Actions\UpdateNoteAction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoSeeder extends Seeder
{
    public const PASSWORD = 'NotesDemo!2026';

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Demo accounts are only available in local and testing environments.');
        }
        DB::transaction(function (): void {
            foreach (['writer' => 'Alex Morgan', 'other' => 'Sam Rivera'] as $account => $name) {
                $user = User::firstOrCreate(['email' => $account.'@demo.notes.test'], ['name' => $name, 'password' => self::PASSWORD]);
                // An existing account is never reset or repopulated, even after demo edits/deletes.
                if (! $user->wasRecentlyCreated) {
                    continue;
                }
                if ($account === 'other') {
                    app(CreateNoteAction::class)($user, ['title' => 'A separate private notebook', 'content' => 'This note belongs only to Sam. Alex cannot read its content or revision history.']);

                    continue;
                }
                $notes = [
                    ['Weekly reflection', 'A few things worth remembering: protect time for focused work, write down decisions, and make space to review what changed.'],
                    ['Ideas for the next project', 'Start with the smallest useful workflow. Talk to the people who will use it before adding more features.'],
                    ['Reading list', 'Read one chapter at a time. Keep a short note about the idea you want to put into practice.'],
                    ['A better morning routine', 'Review the calendar, pick one meaningful task, and leave the inbox closed until the first focus block is finished.'],
                    ['Questions for our next conversation', 'What is working well? Where are we making things harder than they need to be? What is the next small improvement?'],
                    ['Weekend plans', 'Take a walk by the water, cook something new, and spend an hour away from screens.'],
                    ['Decisions worth keeping', 'Keep the interface simple, make errors actionable, and preserve work when something goes wrong.'],
                    ['A small learning habit', 'Write a short explanation after learning something new. If the explanation is unclear, revisit the example.'],
                    ['Book club notes', 'Discuss the strongest argument, the question left unanswered, and one idea that changed your perspective.'],
                    ['Things to revisit', 'Review the old sketches, the bookmarked articles, and the ideas that still feel useful a week later.'],
                    ['Travel checklist', 'Check documents, save the itinerary, pack a notebook, and leave enough time for an unplanned stop.'],
                    ['A place for unfinished thoughts', 'An idea does not need to be polished to be worth saving. Capture the question and return to it later.'],
                ];
                foreach ($notes as $index => [$title, $content]) {
                    $note = app(CreateNoteAction::class)($user, ['title' => $title, 'content' => $content, 'is_important' => $index % 3 === 0]);
                    if ($index === 0) {
                        app(UpdateNoteAction::class)($user, $note, ['content' => $content."\n\nNext week: finish one thing before starting another, and ask for feedback early.", 'version' => 1]);
                    }
                }
            }
        });
    }
}
