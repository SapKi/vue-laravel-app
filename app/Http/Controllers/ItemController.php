<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Services\ModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function __construct(private ModerationService $moderation) {}

    public function index(Request $request): JsonResponse
    {
        $query = Item::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by title or content
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('content', 'like', "%{$term}%");
            });
        }

        // Sort
        $sortable = ['created_at', 'risk_score', 'title'];
        $sort = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $order = $request->order === 'asc' ? 'asc' : 'desc';

        $items = $query->orderBy($sort, $order)->get();

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string|max:5000',
        ]);

        $analysis = $this->moderation->analyze($validated['title'], $validated['content']);

        $item = Item::create(array_merge($validated, $analysis, ['status' => 'pending']));

        return response()->json($item, 201);
    }

    public function show(Item $item): JsonResponse
    {
        return response()->json($item->load('notes'));
    }

    public function saveNote(Request $request, Item $item): JsonResponse
    {
        $validated = $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $note = $item->notes()->create(['body' => $validated['note']]);

        return response()->json($note, 201);
    }

    public function destroyNote(Item $item, \App\Models\ItemNote $note): JsonResponse
    {
        abort_if($note->item_id !== $item->id, 404);
        $note->delete();
        return response()->json(null, 204);
    }

    public function destroy(Item $item): JsonResponse
    {
        $item->delete();
        return response()->json(null, 204);
    }

    public function review(Request $request, Item $item): JsonResponse
    {
        if ($item->status !== 'pending') {
            return response()->json(
                ['message' => 'Item has already been reviewed.'],
                422
            );
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'note'   => 'nullable|string|max:1000',
        ]);

        $item->update([
            'status'        => $validated['status'],
            'reviewer_note' => $validated['note'] ?? null,
            'reviewed_at'   => now(),
        ]);

        return response()->json($item);
    }
}
