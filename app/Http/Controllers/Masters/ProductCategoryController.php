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
        $listUrl = url('/api/masters/product-categories/list');
        $apiFormUrl = url('/api/masters/product-categories/form');

        try {
            $formUrl = route('masters.product-categories.form');
        } catch (\Exception $e) {
            $formUrl = $apiFormUrl;
        }

        return $this->render('pages.masters.product-categories.index', compact('listUrl', 'apiFormUrl', 'formUrl'));
    }

    public function list(Request $request)
    {
        $query = ProductCategory::query();
        // If DataTables server-side (sends `draw`) -> return Yajra DataTables response
        if ($request->has('draw')) {
            return DataTables::of($query)
                ->addColumn('actions', function ($row) {
                    try {
                        $edit = route('masters.product-categories.form', $row->id);
                    } catch (\Exception $e) {
                        $edit = url('/api/masters/product-categories/form/'.$row->id);
                    }

                    try {
                        $del = route('masters.product-categories.delete', $row->id);
                    } catch (\Exception $e) {
                        $del = url('/api/masters/product-categories/delete/'.$row->id);
                    }

                    return '<a href="'.$edit.'" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>' .
                           ' <a href="'.$del.'" data-id="'.$row->id.'" data-table="productCategoriesTable" class="btn btn-sm btn-danger delete-data"><i class="bi bi-trash"></i> Delete</a>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        // Non-DataTables API clients -> return plain JSON
        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            $categories = $query->get();
            return response()->json([
                'status' => true,
                'data' => $categories,
            ]);
        }

        // Fallback: render view
        return $this->render('pages.masters.product-categories.index');
    }

    public function form(Request $request, $id = null)
    {
        $form_data = $id ? ProductCategory::findOrFail($id) : new ProductCategory();

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'data' => [
                    'form_data' => $form_data,
                ],
            ]);
        }

        try {
            $saveUrl = route('masters.product-categories.save', $form_data->id ?? null);
        } catch (\Exception $e) {
            $saveUrl = url('/api/masters/product-categories/save'.($form_data->id ? '/'.$form_data->id : ''));
        }

        try {
            $backUrl = route('masters.product-categories.index');
        } catch (\Exception $e) {
            $backUrl = url('/masters/product-categories');
        }

        return $this->render('pages.masters.product-categories.form', compact('form_data', 'saveUrl', 'backUrl'));
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
