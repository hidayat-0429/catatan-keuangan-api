<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Tampilkan kategori bawaan + kategori milik user.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $categories = Category::query()
            ->where(function ($query) use ($userId) {
                $query
                    ->whereNull('user_id')
                    ->orWhere('user_id', $userId);
            })
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Buat kategori milik user.
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
            'icon' => [
                'required',
                'string',
                'max:20',
            ],
            'type' => [
                'required',
                Rule::in([
                    'pemasukan',
                    'pengeluaran',
                ]),
            ],
        ]);

        $category = Category::create([
            'user_id' => $request->user()->id,
            'name' => trim($validated['name']),
            'icon' => $validated['icon'],
            'type' => $validated['type'],
        ]);

        return response()->json([
            'message' => 'Kategori berhasil dibuat.',
            'data' => $category,
        ], 201);
    }

    /**
     * Tampilkan kategori yang boleh diakses user.
     */
    public function show(
        Request $request,
        string $id
    ): JsonResponse {
        $category = $this->findAccessibleCategory(
            $request,
            $id
        );

        return response()->json([
            'data' => $category,
        ]);
    }

    /**
     * Update kategori milik user.
     *
     * Kategori bawaan (user_id NULL) tidak boleh diubah.
     */
    public function update(
        Request $request,
        string $id
    ): JsonResponse {
        $category = Category::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$category) {
            return response()->json([
                'message' => 'Kategori tidak ditemukan atau tidak dapat diubah.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:1',
                'max:100',
            ],
            'icon' => [
                'required',
                'string',
                'max:20',
            ],
            'type' => [
                'required',
                Rule::in([
                    'pemasukan',
                    'pengeluaran',
                ]),
            ],
        ]);

        $category->update([
            'name' => trim($validated['name']),
            'icon' => $validated['icon'],
            'type' => $validated['type'],
        ]);

        return response()->json([
            'message' => 'Kategori berhasil diperbarui.',
            'data' => $category->fresh(),
        ]);
    }

    /**
     * Hapus kategori milik user.
     */
    public function destroy(
        Request $request,
        string $id
    ): JsonResponse {
        $category = Category::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$category) {
            return response()->json([
                'message' => 'Kategori tidak ditemukan atau tidak dapat dihapus.',
            ], 404);
        }

        if ($category->transactions()->exists()) {
            return response()->json([
                'message' => 'Kategori tidak dapat dihapus karena masih digunakan oleh transaksi.',
            ], 409);
        }

        $category->delete();

        return response()->json([
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }

    /**
     * Cari kategori bawaan atau kategori milik user.
     */
    private function findAccessibleCategory(
        Request $request,
        string $id
    ): Category {
        return Category::query()
            ->where('id', $id)
            ->where(function ($query) use ($request) {
                $query
                    ->whereNull('user_id')
                    ->orWhere(
                        'user_id',
                        $request->user()->id
                    );
            })
            ->firstOrFail();
    }
}