<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\PartStockMovement;
use App\Models\Aftersales\RefSparepart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SparepartController extends Controller
{
    public function page(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.spareparts'), 403);

        $this->pageTitle = 'Spareparts';

        return $this->render('pages.aftersales.spareparts.index');
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.spareparts'), 403);

        $query = RefSparepart::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('part_number', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('reorder_only')) {
            $query->whereColumn('stock', '<=', 'min_stock');
        }

        $perPage = (int) $request->input('per_page', 15);
        $paginated = $query->orderBy('name')->paginate($perPage);

        $items = collect($paginated->items())->map(fn (RefSparepart $p) => array_merge($p->toArray(), [
            'stock_status' => $p->stock_status,
        ]));

        return response()->json([
            'status' => 'success',
            'data' => $items,
            'total' => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
        ]);
    }

    public function show(Request $request, RefSparepart $sparepart)
    {
        abort_unless($request->user()?->hasPermission('masters.spareparts'), 403);

        return response()->json([
            'status' => 'success',
            'data' => array_merge($sparepart->toArray(), ['stock_status' => $sparepart->stock_status]),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.spareparts'), 403);

        $validated = $request->validate([
            'part_number' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'rack_location' => 'nullable|string|max:255',
        ]);

        $validated['created_by'] = $request->user()->id;

        $sparepart = RefSparepart::create($validated);

        return $this->setJsonResponse('Sparepart created successfully', ['data' => $sparepart], 201);
    }

    public function update(Request $request, RefSparepart $sparepart)
    {
        abort_unless($request->user()?->hasPermission('masters.spareparts'), 403);

        $validated = $request->validate([
            'part_number' => 'nullable|string|max:255',
            'name' => 'sometimes|required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'rack_location' => 'nullable|string|max:255',
        ]);

        $validated['updated_by'] = $request->user()->id;

        $sparepart->update($validated);

        return $this->setJsonResponse('Sparepart updated successfully', ['data' => $sparepart->fresh()]);
    }

    public function destroy(Request $request, RefSparepart $sparepart)
    {
        abort_unless($request->user()?->hasPermission('masters.spareparts'), 403);

        $sparepart->update(['deleted_by' => $request->user()->id, 'is_deleted' => true]);
        $sparepart->delete();

        return $this->setJsonResponse('Sparepart deleted successfully');
    }

    public function stockMovements(Request $request, RefSparepart $sparepart)
    {
        abort_unless($request->user()?->hasPermission('masters.spareparts'), 403);

        $movements = $sparepart->stockMovements()->latest('id')->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $movements->items(),
            'total' => $movements->total(),
            'current_page' => $movements->currentPage(),
            'last_page' => $movements->lastPage(),
        ]);
    }

    /**
     * Manual stock mutation (opname / restock / adjustment) — not tied to a
     * ticket. Ticket-driven `out` movements are written by TicketCloseController
     * when actual sparepart usage is recorded. See PRD §3.3 / §9.
     */
    public function storeStockMovement(Request $request, RefSparepart $sparepart)
    {
        abort_unless($request->user()?->hasPermission('aftersales.stock.manage'), 403);

        $validated = $request->validate([
            'type' => 'required|in:in,out,adjustment',
            'qty' => 'required|integer|min:1',
            'note' => 'nullable|string',
        ]);

        $movement = DB::transaction(function () use ($validated, $sparepart, $request) {
            $delta = $validated['type'] === 'out' ? -$validated['qty'] : $validated['qty'];

            $locked = RefSparepart::query()->whereKey($sparepart->id)->lockForUpdate()->first();
            $locked->stock = max(0, $locked->stock + $delta);
            $locked->updated_by = $request->user()->id;
            $locked->save();

            return PartStockMovement::create([
                'ref_sparepart_id' => $sparepart->id,
                'type' => $validated['type'],
                'qty' => $validated['qty'],
                'note' => $validated['note'] ?? null,
                'created_by' => $request->user()->id,
            ]);
        });

        return $this->setJsonResponse('Stock movement recorded successfully', [
            'data' => $movement,
            'stock' => $sparepart->fresh()->stock,
        ], 201);
    }
}
