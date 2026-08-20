<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    /**
     * Daftar anggaran milik user dengan info spent & progress.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        $budgets = Budget::query()
            ->where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->with('category:id,name,icon,type')
            ->get();

        // Tambahkan spent, percentage, status per budget
        $budgets->transform(function (Budget $budget) use ($userId, $month, $year) {
            $spent = Transaction::query()
                ->where('user_id', $userId)
                ->where('category_id', $budget->category_id)
                ->where('type', 'pengeluaran')
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month)
                ->sum('amount');

            $percentage = $budget->amount > 0
                ? round(($spent / $budget->amount) * 100, 2)
                : 0;

            $budget->setAttribute('spent', $spent);
            $budget->setAttribute('remaining', max(0, $budget->amount - $spent));
            $budget->setAttribute('percentage', $percentage);
            $budget->setAttribute('is_exceeded', $spent > $budget->amount);
            $budget->setAttribute('is_warning', $percentage >= 80 && $spent <= $budget->amount);

            return $budget;
        });

        return response()->json([
            'data'  => $budgets,
            'month' => $month,
            'year'  => $year,
        ]);
    }

    /**
     * Buat anggaran baru.
     */
    public function store(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'category_id' => [
                'required',
                'uuid',
            ],
            'amount' => [
                'required',
                'integer',
                'min:1',
            ],
            'month' => [
                'required',
                'integer',
                'min:1',
                'max:12',
            ],
            'year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
            ],
        ]);

        // Pastikan category bisa diakses
        $this->assertCategoryAccessible($request, $validated['category_id']);

        // Cek duplikasi budget bulan yang sama
        $exists = Budget::query()
            ->where('user_id', $userId)
            ->where('category_id', $validated['category_id'])
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Anggaran untuk kategori ini di bulan yang sama sudah ada.',
            ], 409);
        }

        $budget = Budget::create([
            'user_id'     => $userId,
            'category_id' => $validated['category_id'],
            'amount'      => $validated['amount'],
            'month'       => $validated['month'],
            'year'        => $validated['year'],
        ]);

        return response()->json([
            'message' => 'Anggaran berhasil dibuat.',
            'data'    => $budget->load('category:id,name,icon,type'),
        ], 201);
    }

    /**
     * Detail anggaran milik user.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $budget = Budget::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('category:id,name,icon,type')
            ->firstOrFail();

        $spent = Transaction::query()
            ->where('user_id', $request->user()->id)
            ->where('category_id', $budget->category_id)
            ->where('type', 'pengeluaran')
            ->whereYear('transaction_date', $budget->year)
            ->whereMonth('transaction_date', $budget->month)
            ->sum('amount');

        $percentage = $budget->amount > 0
            ? round(($spent / $budget->amount) * 100, 2)
            : 0;

        $budget->setAttribute('spent', $spent);
        $budget->setAttribute('remaining', max(0, $budget->amount - $spent));
        $budget->setAttribute('percentage', $percentage);
        $budget->setAttribute('is_exceeded', $spent > $budget->amount);
        $budget->setAttribute('is_warning', $percentage >= 80 && $spent <= $budget->amount);

        return response()->json([
            'data' => $budget,
        ]);
    }

    /**
     * Update anggaran milik user.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $budget = Budget::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'amount' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $budget->update(['amount' => $validated['amount']]);

        return response()->json([
            'message' => 'Anggaran berhasil diperbarui.',
            'data'    => $budget->fresh()->load('category:id,name,icon,type'),
        ]);
    }

    /**
     * Hapus anggaran milik user.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $budget = Budget::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $budget->delete();

        return response()->json([
            'message' => 'Anggaran berhasil dihapus.',
        ]);
    }

    private function assertCategoryAccessible(
        Request $request,
        string $categoryId
    ): void {
        $exists = Category::query()
            ->where('id', $categoryId)
            ->where(function ($q) use ($request) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', $request->user()->id);
            })
            ->exists();

        abort_if(!$exists, 422, 'Kategori tidak valid atau bukan milik Anda.');
    }
}
