<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Services\ModerationService;
use Illuminate\Database\QueryException;
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
        $sortable = ['created_at', 'risk_score'];
        $sort = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $order = $request->order === 'asc' ? 'asc' : 'desc';

        $items = $query->orderBy($sort, $order)->paginate(10);

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string|max:5000',
        ]);

        $analysis = $this->moderation->analyze($validated['title'], $validated['content']);

        try {
            $item = Item::create(array_merge($validated, $analysis, ['status' => 'pending']));
        } catch (QueryException $e) {
            return response()->json(['message' => 'Failed to save item. Please try again.'], 500);
        }

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

        try {
            $note = $item->notes()->create(['body' => $validated['note']]);
        } catch (QueryException $e) {
            return response()->json(['message' => 'Failed to save note. Please try again.'], 500);
        }

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

    public function reopen(Item $item): JsonResponse
    {
        if ($item->status === 'pending') {
            return response()->json(['message' => 'Item is already pending.'], 422);
        }

        try {
            $item->update([
                'status'        => 'pending',
                'reviewer_note' => null,
                'reviewed_at'   => null,
            ]);
        } catch (QueryException $e) {
            return response()->json(['message' => 'Failed to reopen item. Please try again.'], 500);
        }

        return response()->json($item->load('notes'));
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

        try {
            $item->update([
                'status'        => $validated['status'],
                'reviewer_note' => $validated['note'] ?? null,
                'reviewed_at'   => now(),
            ]);
        } catch (QueryException $e) {
            return response()->json(['message' => 'Failed to save review. Please try again.'], 500);
        }

        return response()->json($item->load('notes'));
    }
}
