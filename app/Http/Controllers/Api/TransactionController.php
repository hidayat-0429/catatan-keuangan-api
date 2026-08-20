<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    /**
     * Daftar semua transaksi milik user dengan filter & pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $query = Transaction::query()
            ->where('user_id', $userId)
            ->with('category:id,name,icon,type');

        // Filter type: pemasukan / pengeluaran
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filter category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Filter tanggal
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->input('date_to'));
        }

        // Filter bulan & tahun
        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('transaction_date', $request->input('month'))
                  ->whereYear('transaction_date', $request->input('year'));
        }

        // Search catatan
        if ($request->filled('search')) {
            $query->where('note', 'like', '%' . $request->input('search') . '%');
        }

        $perPage = min((int) $request->input('per_page', 20), 100);

        $transactions = $query
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($transactions);
    }

    /**
     * Buat transaksi baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => [
                'nullable',
                'uuid',
            ],
            'type' => [
                'required',
                Rule::in(['pemasukan', 'pengeluaran']),
            ],
            'amount' => [
                'required',
                'integer',
                'min:1',
            ],
            'note' => [
                'nullable',
                'string',
                'max:500',
            ],
            'transaction_date' => [
                'required',
                'date',
            ],
        ]);

        // Validasi kepemilikan category
        if (!empty($validated['category_id'])) {
            $this->assertCategoryAccessible(
                $request,
                $validated['category_id']
            );
        }

        $transaction = Transaction::create([
            'user_id'          => $request->user()->id,
            'category_id'      => $validated['category_id'] ?? null,
            'type'             => $validated['type'],
            'amount'           => $validated['amount'],
            'note'             => $validated['note'] ?? null,
            'transaction_date' => $validated['transaction_date'],
        ]);

        return response()->json([
            'message' => 'Transaksi berhasil dibuat.',
            'data'    => $transaction->load('category:id,name,icon,type'),
        ], 201);
    }

    /**
     * Detail transaksi milik user.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $transaction = Transaction::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('category:id,name,icon,type')
            ->firstOrFail();

        return response()->json([
            'data' => $transaction,
        ]);
    }

    /**
     * Update transaksi milik user.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $transaction = Transaction::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'category_id' => [
                'nullable',
                'uuid',
            ],
            'type' => [
                'required',
                Rule::in(['pemasukan', 'pengeluaran']),
            ],
            'amount' => [
                'required',
                'integer',
                'min:1',
            ],
            'note' => [
                'nullable',
                'string',
                'max:500',
            ],
            'transaction_date' => [
                'required',
                'date',
            ],
        ]);

        // Validasi kepemilikan category
        if (!empty($validated['category_id'])) {
            $this->assertCategoryAccessible(
                $request,
                $validated['category_id']
            );
        }

        $transaction->update([
            'category_id'      => $validated['category_id'] ?? null,
            'type'             => $validated['type'],
            'amount'           => $validated['amount'],
            'note'             => $validated['note'] ?? null,
            'transaction_date' => $validated['transaction_date'],
        ]);

        return response()->json([
            'message' => 'Transaksi berhasil diperbarui.',
            'data'    => $transaction->fresh()->load('category:id,name,icon,type'),
        ]);
    }

    /**
     * Soft delete transaksi milik user.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $transaction = Transaction::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $transaction->delete();

        return response()->json([
            'message' => 'Transaksi berhasil dihapus.',
        ]);
    }

    /**
     * Pastikan category_id bisa diakses user (milik user atau sistem).
     */
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
