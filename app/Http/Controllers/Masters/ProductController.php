<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Masters\Product;
use App\Models\Masters\ProductCategory;
use App\Models\Masters\Part;
use App\Http\Classes\ActivityLogger;
use App\Models\Masters\ProductType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Products';
        return $this->render('pages.masters.products.index');
    }

    public function list(Request $request)
    {
        return DataTables::of(Product::with('categories'))
            ->addColumn('product_type_name', function ($row) {
                return $row->type->name ?? '-';
            })
            ->addColumn('category_name', function ($row) {
                return $row->categories->pluck('name')->implode(', ');
            })
            ->addColumn('actions', function ($row) {
                $edit = route('masters.products.form', $row->id);
                $del  = route('masters.products.delete', $row->id);

                return '
                    <a href="'.$edit.'" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                    <a href="'.$del.'" data-id="'.$row->id.'" data-table="productsTable" class="btn btn-sm btn-danger delete-data"><i class="bi bi-trash"></i> Delete</a>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function form($id = null)
    {
        $types = ProductType::all();
        $form_data = $id ? Product::with(['categories', 'parts'])->findOrFail($id) : new Product();
        $categories = ProductCategory::all();
        $parts = Part::all();
        $selectedCategories = $form_data->categories->pluck('id')->toArray();
        $selectedParts = $form_data->parts->pluck('id')->toArray();
        $selectedType = $form_data->product_type_id;


        return $this->render('pages.masters.products.form', compact('form_data', 'categories', 'parts', 'selectedCategories', 'selectedParts', 'selectedType', 'types'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'product_type_id'     => 'required|exists:ref_product_types,id',
            'category_ids'        => 'nullable|array',
            'category_ids.*'      => 'exists:ref_product_categories,id',
            'part_ids'            => 'nullable|array',
            'part_ids.*'          => 'exists:ref_parts,id',
            'sku'                 => 'required',
            'name'                => 'required',
            'description'         => 'nullable',
            'vat'                 => 'nullable|numeric',
            'corporate_price'     => 'nullable|string',
            'government_price'    => 'nullable|string',
            'personal_price'      => 'nullable|string',
            'fob_price'           => 'nullable|string',
            'bdi_price'           => 'nullable|string',
            'warranty_available'  => 'nullable|boolean',
            'warranty_time_month' => 'nullable|integer',
        ]);

        $sanitizeCurrency = fn($value) => $value !== null ? (int) str_replace(',', '', $value) : null;

        $product = $id ? Product::findOrFail($id) : new Product();
        $before = $id ? $product->toArray() : null;

        $product->product_type_id = $request->product_type_id;
        $product->sku = $request->sku;
        $product->name = $request->name;
        $product->description = $request->description;
        $product->vat = $request->vat;
        $product->corporate_price = $sanitizeCurrency($request->corporate_price);
        $product->government_price = $sanitizeCurrency($request->government_price);
        $product->personal_price = $sanitizeCurrency($request->personal_price);
        $product->fob_price = $sanitizeCurrency($request->fob_price);
        $product->bdi_price = $sanitizeCurrency($request->bdi_price);
        $product->warranty_available = $request->boolean('warranty_available');
        $product->warranty_time_month = $request->warranty_available ? $request->warranty_time_month : null;
        $product->save();

        $product->categories()->sync($request->category_ids ?? []);
        $product->parts()->sync($request->part_ids ?? []);

        $after = $product->fresh()->toArray();

        ActivityLogger::writeLog(
            $id ? 'update_product' : 'create_product',
            $id ? 'Updated product' : 'Created new product',
            $product,
            ['before' => $before, 'after' => $after],
            $request->user()
        );

        return $this->setJsonResponse('Product saved successfully');
    }

    public function delete($id)
    {
        $product = Product::findOrFail($id);

        ActivityLogger::writeLog(
            'delete_product',
            'Deleted product',
            $product,
            $product->toArray(),
            request()->user()
        );

        $product->delete();

        return $this->setJsonResponse('Product deleted successfully');
    }
}
