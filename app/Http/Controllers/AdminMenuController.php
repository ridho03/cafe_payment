<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminMenuController extends Controller
{
    public function index()
    {
        $cafeId = $this->currentCafeId();

        $categories = MenuCategory::with(['items' => fn ($query) => $query
            ->withCount('orderItems')
            ->orderBy('sort_order')
            ->orderBy('name')])
            ->withCount('items')
            ->where('cafe_id', $cafeId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.menu', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $cafeId = $this->currentCafeId();
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('menu_categories', 'name')->where('cafe_id', $cafeId),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        MenuCategory::create([
            'cafe_id' => $cafeId,
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? (MenuCategory::where('cafe_id', $cafeId)->max('sort_order') + 10),
        ]);

        return redirect()->route('admin.menu')->with('success', 'Kategori menu berhasil ditambahkan.');
    }

    public function store(Request $request)
    {
        $cafeId = $this->currentCafeId();
        $validated = $request->validate([
            'menu_category_id' => ['nullable', 'exists:menu_categories,id'],
            'category_name' => ['nullable', 'string', 'max:60'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:1000', 'max:9999999'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'image_upload' => ['nullable', 'image', 'max:4096'],
            'variants' => ['nullable', 'array'],
            'variants.*' => ['nullable', 'string', 'max:40'],
            'custom_variants' => ['nullable', 'string', 'max:255'],
            'variant_price_deltas' => ['nullable', 'array'],
        ]);

        $categoryId = $validated['menu_category_id'] ?? null;

        if (! $categoryId) {
            $category = MenuCategory::firstOrCreate(
                [
                    'cafe_id' => $cafeId,
                    'name' => ($validated['category_name'] ?? null) ?: 'Menu Baru',
                ],
                ['sort_order' => MenuCategory::where('cafe_id', $cafeId)->max('sort_order') + 10]
            );

            $categoryId = $category->id;
        } else {
            abort_unless(MenuCategory::whereKey($categoryId)->where('cafe_id', $cafeId)->exists(), 403);
        }

        MenuItem::create([
            'menu_category_id' => $categoryId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'image_url' => $this->imageUrlFromRequest($request, $validated['image_url'] ?? null),
            'variants' => MenuItem::normalizeVariantGroups(
                $validated['variants'] ?? [],
                $validated['custom_variants'] ?? null,
                $validated['variant_price_deltas'] ?? []
            ),
            'is_available' => true,
            'sort_order' => MenuItem::where('menu_category_id', $categoryId)->max('sort_order') + 10,
        ]);

        return redirect()->route('admin.menu')->with('success', 'Menu baru berhasil ditambahkan.');
    }

    public function update(Request $request, MenuItem $menuItem)
    {
        $this->ensureMenuItemBelongsToCurrentCafe($menuItem);
        $cafeId = $this->currentCafeId();

        $validated = $request->validate([
            'menu_category_id' => ['required', 'exists:menu_categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:1000', 'max:9999999'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'image_upload' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
            'variants' => ['nullable', 'array'],
            'variants.*' => ['nullable', 'string', 'max:40'],
            'custom_variants' => ['nullable', 'string', 'max:255'],
            'variant_price_deltas' => ['nullable', 'array'],
        ]);

        abort_unless(MenuCategory::whereKey($validated['menu_category_id'])->where('cafe_id', $cafeId)->exists(), 403);

        $imageUrl = $menuItem->image_url;

        if ($request->hasFile('image_upload')) {
            $this->deleteUploadedImage($menuItem->image_url);
            $imageUrl = $this->storeUploadedImage($request);
        } elseif ($request->boolean('remove_image')) {
            $this->deleteUploadedImage($menuItem->image_url);
            $imageUrl = null;
        } elseif (array_key_exists('image_url', $validated)) {
            $newUrl = $validated['image_url'] ?? null;

            if ($newUrl && $newUrl !== $menuItem->image_url) {
                $this->deleteUploadedImage($menuItem->image_url);
                $imageUrl = $newUrl;
            } elseif (! $newUrl && $menuItem->image_url && ! $this->isUploadedImage($menuItem->image_url)) {
                $imageUrl = null;
            }
        }

        $variants = $request->has('variants') || $request->has('custom_variants')
            ? MenuItem::normalizeVariantGroups(
                $validated['variants'] ?? [],
                $validated['custom_variants'] ?? null,
                $validated['variant_price_deltas'] ?? []
            )
            : $menuItem->availableVariantGroups();

        $menuItem->update([
            'menu_category_id' => $validated['menu_category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'image_url' => $imageUrl,
            'variants' => $variants,
        ]);

        return redirect()->route('admin.menu')->with('success', 'Menu berhasil diperbarui.');
    }

    public function updateCategory(Request $request, MenuCategory $menuCategory)
    {
        $this->ensureMenuCategoryBelongsToCurrentCafe($menuCategory);
        $cafeId = $this->currentCafeId();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('menu_categories', 'name')
                    ->where('cafe_id', $cafeId)
                    ->ignore($menuCategory->id),
            ],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ]);

        $menuCategory->update($validated);

        return redirect()->route('admin.menu')->with('success', 'Kategori menu berhasil diperbarui.');
    }

    public function toggle(MenuItem $menuItem)
    {
        $this->ensureMenuItemBelongsToCurrentCafe($menuItem);

        $menuItem->update(['is_available' => ! $menuItem->is_available]);

        return redirect()->route('admin.menu')->with('success', 'Status menu berhasil diubah.');
    }

    public function destroy(MenuItem $menuItem)
    {
        $this->ensureMenuItemBelongsToCurrentCafe($menuItem);

        abort_if($menuItem->orderItems()->exists(), 422, 'Menu sudah pernah dipesan, ubah menjadi Habis agar histori transaksi tetap aman.');

        $this->deleteUploadedImage($menuItem->image_url);
        $menuItem->delete();

        return redirect()->route('admin.menu')->with('success', 'Menu berhasil dihapus.');
    }

    public function destroyCategory(MenuCategory $menuCategory)
    {
        $this->ensureMenuCategoryBelongsToCurrentCafe($menuCategory);

        abort_if($menuCategory->items()->exists(), 422, 'Kategori masih berisi menu. Pindahkan atau hapus menu terlebih dahulu.');

        $menuCategory->delete();

        return redirect()->route('admin.menu')->with('success', 'Kategori menu berhasil dihapus.');
    }

    private function ensureMenuCategoryBelongsToCurrentCafe(MenuCategory $menuCategory): void
    {
        abort_unless(! $this->currentCafeId() || $menuCategory->cafe_id === $this->currentCafeId(), 403);
    }

    private function imageUrlFromRequest(Request $request, ?string $fallbackUrl): ?string
    {
        if ($request->hasFile('image_upload')) {
            return $this->storeUploadedImage($request);
        }

        return $fallbackUrl;
    }

    private function storeUploadedImage(Request $request): string
    {
        $file = $request->file('image_upload');
        $directory = public_path('uploads/menu');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'uploads/menu/'.$filename;
    }

    private function deleteUploadedImage(?string $imageUrl): void
    {
        if (! $this->isUploadedImage($imageUrl)) {
            return;
        }

        $path = parse_url($imageUrl, PHP_URL_PATH);

        if (! $path) {
            return;
        }

        $fullPath = public_path(ltrim($path, '/'));

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }

    private function isUploadedImage(?string $imageUrl): bool
    {
        return filled($imageUrl) && (str_contains($imageUrl, '/uploads/menu/') || str_starts_with($imageUrl, 'uploads/menu/'));
    }
}
