<?php

namespace App\Http\Controllers;

use App\Models\CafeTable;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminTableController extends Controller
{
    public function index()
    {
        $tables = CafeTable::with('cafe')
            ->withCount('orders')
            ->where('cafe_id', $this->currentCafeId())
            ->orderBy('name')
            ->get();

        return view('admin.tables', compact('tables'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        CafeTable::create([
            'cafe_id' => $this->currentCafeId(),
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'code' => Str::upper(Str::slug($validated['name']).'-'.Str::random(5)),
            'is_active' => true,
        ]);

        return redirect()->route('admin.tables')->with('success', 'Meja baru berhasil dibuat.');
    }

    public function update(Request $request, CafeTable $cafeTable)
    {
        $this->ensureTableBelongsToCurrentCafe($cafeTable);
        $cafeId = $this->currentCafeId();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('cafe_tables', 'name')
                    ->where('cafe_id', $cafeId)
                    ->ignore($cafeTable->id),
            ],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $cafeTable->update($validated);

        return redirect()->route('admin.tables')->with('success', 'Meja berhasil diperbarui.');
    }

    public function toggle(CafeTable $cafeTable)
    {
        $this->ensureTableBelongsToCurrentCafe($cafeTable);

        $cafeTable->update(['is_active' => ! $cafeTable->is_active]);

        return redirect()->route('admin.tables')->with('success', 'Status meja berhasil diubah.');
    }

    public function destroy(CafeTable $cafeTable)
    {
        $this->ensureTableBelongsToCurrentCafe($cafeTable);

        abort_if($cafeTable->orders()->exists(), 422, 'Meja sudah memiliki histori order. Nonaktifkan meja agar histori transaksi tetap aman.');

        $cafeTable->delete();

        return redirect()->route('admin.tables')->with('success', 'Meja berhasil dihapus.');
    }

    public function print()
    {
        $tables = CafeTable::query()
            ->with('cafe')
            ->where('cafe_id', $this->currentCafeId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.tables-print', compact('tables'));
    }

    public function qr(CafeTable $cafeTable)
    {
        $cafeTable->loadMissing('cafe');
        $this->ensureTableBelongsToCurrentCafe($cafeTable);

        $url = route('customer.menu', ['table' => $cafeTable->code]);
        $renderer = new ImageRenderer(new RendererStyle(360), new SvgImageBackEnd);
        $writer = new Writer($renderer);

        return response($writer->writeString($url), 200, [
            'Content-Type' => 'image/svg+xml',
        ]);
    }

    private function ensureTableBelongsToCurrentCafe(CafeTable $cafeTable): void
    {
        abort_unless(! $this->currentCafeId() || $cafeTable->cafe_id === $this->currentCafeId(), 403);
    }
}
