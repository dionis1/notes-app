<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateNoteAction;
use App\Actions\DeleteNoteAction;
use App\Actions\RestoreNoteRevisionAction;
use App\Actions\UpdateNoteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\NoteFilterRequest;
use App\Http\Requests\NoteRequest;
use App\Http\Requests\NoteVersionRequest;
use App\Http\Requests\RevisionFilterRequest;
use App\Http\Resources\NoteResource;
use App\Http\Resources\NoteRevisionResource;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class NoteController extends Controller
{
    public function index(NoteFilterRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Note::class);
        $filters = $request->validated();
        $notes = $request->user()->notes()
            ->when(isset($filters['important']), fn ($query) => $query->where('is_important', (bool) $filters['important']))
            ->when(isset($filters['q']) && $filters['q'] !== '', fn ($query) => $query->where(function ($query) use ($filters): void {
                $query->where('title', 'like', '%'.$filters['q'].'%')->orWhere('content', 'like', '%'.$filters['q'].'%');
            }))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 10)->withQueryString();

        return NoteResource::collection($notes);
    }

    public function show(Request $request, int $note): NoteResource
    {
        return new NoteResource($this->owned($request, $note));
    }

    public function store(NoteRequest $request, CreateNoteAction $action): JsonResponse
    {
        return (new NoteResource($action($request->user(), $request->validated())))->response()->setStatusCode(201);
    }

    public function update(NoteRequest $request, int $note, UpdateNoteAction $action): NoteResource
    {
        return new NoteResource($action($request->user(), $this->owned($request, $note), $request->validated()));
    }

    public function destroy(NoteVersionRequest $request, int $note, DeleteNoteAction $action): JsonResponse
    {
        $action($request->user(), $this->owned($request, $note), $request->integer('version'));

        return response()->json(status: 204);
    }

    public function revisions(RevisionFilterRequest $request, int $note): AnonymousResourceCollection
    {
        return NoteRevisionResource::collection($this->owned($request, $note)->revisions()->paginate(10));
    }

    public function restore(NoteVersionRequest $request, int $note, int $revision, RestoreNoteRevisionAction $action): NoteResource
    {
        $record = $this->owned($request, $note);
        $snapshot = $record->revisions()->findOrFail($revision);

        return new NoteResource($action($request->user(), $record, $snapshot, $request->integer('version')));
    }

    private function owned(Request $request, int $id): Note
    {
        $note = $request->user()->notes()->findOrFail($id);
        Gate::authorize('view', $note);

        return $note;
    }
}
