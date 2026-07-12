<?php

namespace App\Http\Controllers;

use App\Models\CafeTable;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminTableController extends Controller
{
    public function index()
    {
        $tables = CafeTable::orderBy('name')->get();

        return view('admin.tables', compact('tables'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        CafeTable::create([
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'code' => Str::upper(Str::slug($validated['name']).'-'.Str::random(5)),
            'is_active' => true,
        ]);

        return redirect()->route('admin.tables')->with('success', 'Meja baru berhasil dibuat.');
    }

    public function toggle(CafeTable $cafeTable)
    {
        $cafeTable->update(['is_active' => ! $cafeTable->is_active]);

        return redirect()->route('admin.tables')->with('success', 'Status meja berhasil diubah.');
    }

    public function print()
    {
        $tables = CafeTable::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.tables-print', compact('tables'));
    }

    public function qr(CafeTable $cafeTable)
    {
        $url = route('customer.menu', ['table' => $cafeTable->code]);
        $renderer = new ImageRenderer(new RendererStyle(360), new SvgImageBackEnd);
        $writer = new Writer($renderer);

        return response($writer->writeString($url), 200, [
            'Content-Type' => 'image/svg+xml',
        ]);
    }
}
