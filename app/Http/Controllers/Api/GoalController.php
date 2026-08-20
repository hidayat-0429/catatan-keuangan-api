<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialGoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    /**
     * Daftar semua target keuangan milik user.
     */
    public function index(Request $request): JsonResponse
    {
        $goals = FinancialGoal::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($goal) => $this->formatGoal($goal));

        return response()->json([
            'data' => $goals,
        ]);
    }

    /**
     * Buat target keuangan baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:1',
                'max:100',
            ],
            'target_amount' => [
                'required',
                'integer',
                'min:1',
            ],
            'current_amount' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'deadline' => [
                'nullable',
                'date',
                'after:today',
            ],
        ]);

        $goal = FinancialGoal::create([
            'user_id'        => $request->user()->id,
            'name'           => trim($validated['name']),
            'target_amount'  => $validated['target_amount'],
            'current_amount' => $validated['current_amount'] ?? 0,
            'deadline'       => $validated['deadline'] ?? null,
        ]);

        return response()->json([
            'message' => 'Target keuangan berhasil dibuat.',
            'data'    => $this->formatGoal($goal),
        ], 201);
    }

    /**
     * Detail target keuangan milik user.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $goal = FinancialGoal::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'data' => $this->formatGoal($goal),
        ]);
    }

    /**
     * Update target keuangan milik user.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $goal = FinancialGoal::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:1',
                'max:100',
            ],
            'target_amount' => [
                'required',
                'integer',
                'min:1',
            ],
            'deadline' => [
                'nullable',
                'date',
            ],
        ]);

        $goal->update([
            'name'          => trim($validated['name']),
            'target_amount' => $validated['target_amount'],
            'deadline'      => $validated['deadline'] ?? null,
        ]);

        return response()->json([
            'message' => 'Target keuangan berhasil diperbarui.',
            'data'    => $this->formatGoal($goal->fresh()),
        ]);
    }

    /**
     * Hapus target keuangan milik user.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $goal = FinancialGoal::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $goal->delete();

        return response()->json([
            'message' => 'Target keuangan berhasil dihapus.',
        ]);
    }

    /**
     * Tambah tabungan ke target.
     */
    public function addSaving(Request $request, string $id): JsonResponse
    {
        $goal = FinancialGoal::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($goal->current_amount >= $goal->target_amount) {
            return response()->json([
                'message' => 'Target keuangan sudah tercapai.',
            ], 409);
        }

        $validated = $request->validate([
            'amount' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $newAmount = $goal->current_amount + $validated['amount'];

        // Tidak melebihi target
        $goal->update([
            'current_amount' => min($newAmount, $goal->target_amount),
        ]);

        return response()->json([
            'message' => 'Tabungan berhasil ditambahkan.',
            'data'    => $this->formatGoal($goal->fresh()),
        ]);
    }

    /**
     * Format goal dengan computed attributes.
     */
    private function formatGoal(FinancialGoal $goal): array
    {
        $progress = $goal->target_amount > 0
            ? round(($goal->current_amount / $goal->target_amount) * 100, 2)
            : 0;

        return [
            'id'             => $goal->id,
            'user_id'        => $goal->user_id,
            'name'           => $goal->name,
            'target_amount'  => $goal->target_amount,
            'current_amount' => $goal->current_amount,
            'deadline'       => $goal->deadline?->toDateString(),
            'progress'       => $progress,
            'remaining'      => max(0, $goal->target_amount - $goal->current_amount),
            'is_completed'   => $goal->current_amount >= $goal->target_amount,
            'created_at'     => $goal->created_at,
            'updated_at'     => $goal->updated_at,
        ];
    }
}
