<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\NoteRequest;
use App\Http\Resources\NoteResource;
use App\Services\NoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class NoteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'important' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $notes = $request->user()->notes()
            ->when(isset($filters['important']), fn ($query) => $query->where('is_important', (bool) $filters['important']))
            ->when(! empty($filters['q']), fn ($query) => $query->where(function ($query) use ($filters) {
                $query->where('title', 'like', '%'.$filters['q'].'%')
                    ->orWhere('content', 'like', '%'.$filters['q'].'%');
            }))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 10)->withQueryString();

        return NoteResource::collection($notes);
    }

    public function show(Request $request, int $note, NoteService $service): NoteResource
    {
        return new NoteResource($service->find($request->user(), $note));
    }

    public function store(NoteRequest $request, NoteService $service): JsonResponse
    {
        $note = $service->save($request->user(), null, null, $request->validated());

        return (new NoteResource($note))->response()->setStatusCode(201);
    }

    public function update(NoteRequest $request, int $note, NoteService $service): NoteResource
    {
        $data = $request->validated();
        $version = $data['version'];
        unset($data['version']);

        try {
            $saved = $service->save($request->user(), $note, $version, $data);
        } catch (ValidationException $exception) {
            if ($exception->errors()['conflict'] ?? false) {
                throw new ConflictHttpException($exception->errors()['conflict'][0]);
            }

            throw $exception;
        }

        return new NoteResource($saved);
    }

    public function destroy(Request $request, int $note, NoteService $service): JsonResponse
    {
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);

        try {
            $service->delete($request->user(), $note, $data['version']);
        } catch (ValidationException $exception) {
            if ($exception->errors()['conflict'] ?? false) {
                throw new ConflictHttpException($exception->errors()['conflict'][0]);
            }

            throw $exception;
        }

        return response()->json(status: 204);
    }
}
