<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Masters\ProductCategory;
use App\Http\Classes\ActivityLogger;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Product Categories';
        return $this->render('pages.masters.product-categories.index');
    }

    public function list(Request $request)
    {
        return DataTables::of(ProductCategory::query())
            ->addColumn('actions', function ($row) {
                $edit = route('masters.product-categories.form', $row->id);
                $del  = route('masters.product-categories.delete', $row->id);

                return '
                    <a href="'.$edit.'" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                    <a href="'.$del.'" data-id="'.$row->id.'" data-table="productCategoriesTable" class="btn btn-sm btn-danger delete-data"><i class="bi bi-trash"></i> Delete</a>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function form($id = null)
    {
        $form_data = $id ? ProductCategory::findOrFail($id) : new ProductCategory();
        return $this->render('pages.masters.product-categories.form', compact('form_data'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required',
            'code' => 'required',
        ]);

        $category = $id ? ProductCategory::findOrFail($id) : new ProductCategory();
        $before = $id ? $category->toArray() : null;

        $category->name = $request->name;
        $category->code = $request->code;
        $category->save();

        $after = $category->fresh()->toArray();

        ActivityLogger::writeLog(
            $id ? 'update_product_category' : 'create_product_category',
            $id ? 'Updated product category' : 'Created new product category',
            $category,
            ['before' => $before, 'after' => $after],
            $request->user()
        );

        return $this->setJsonResponse('Product category saved successfully');
    }

    public function delete($id)
    {
        $category = ProductCategory::findOrFail($id);

        $hasRelation = $category->products()->exists();
        if ($hasRelation) {
            return response()->json([
                'status' => false,
                'message' => 'Company cannot be deleted because it has related.'
            ], 400);
        }

        ActivityLogger::writeLog(
            'delete_product_category',
            'Deleted product category',
            $category,
            $category->toArray(),
            request()->user()
        );

        $category->delete();

        return $this->setJsonResponse('Product category deleted successfully');
    }
}
