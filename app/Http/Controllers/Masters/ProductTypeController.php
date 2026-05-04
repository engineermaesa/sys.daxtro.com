<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Masters\ProductType;
use App\Models\Masters\Product;
use App\Http\Classes\ActivityLogger;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProductTypeController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Product Types';
        $listUrl = url('/api/masters/product-types/list');
        $apiFormUrl = url('/api/masters/product-types/form');

        try {
            $formUrl = route('masters.product-types.form');
        } catch (\Exception $e) {
            $formUrl = $apiFormUrl;
        }

        return $this->render('pages.masters.product-types.index', compact('listUrl', 'apiFormUrl', 'formUrl'));
    }

    public function list(Request $request)
    {
        $query = ProductType::query();

        if ($request->has('draw')) {
            return DataTables::of($query)
                ->addColumn('code', function ($row) {
                    return $row->code ?? '';
                })
                ->addColumn('actions', function ($row) {
                    try {
                        $edit = route('masters.product-types.form', $row->id);
                    } catch (\Exception $e) {
                        $edit = url('/api/masters/product-types/form/'.$row->id);
                    }

                    try {
                        $del = route('masters.product-types.delete', $row->id);
                    } catch (\Exception $e) {
                        $del = url('/api/masters/product-types/delete/'.$row->id);
                    }

                    return '<a href="'.$edit.'" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>' .
                           ' <a href="'.$del.'" data-id="'.$row->id.'" data-table="productTypesTable" class="btn btn-sm btn-danger delete-data"><i class="bi bi-trash"></i> Delete</a>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            $types = $query->get();
            return response()->json([
                'status' => true,
                'data' => $types,
            ]);
        }

        return $this->render('pages.masters.product-types.index');
    }

    public function form(Request $request, $id = null)
    {
        $form_data = $id ? ProductType::findOrFail($id) : new ProductType();

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'data' => [
                    'form_data' => $form_data,
                ],
            ]);
        }

        try {
            $saveUrl = route('masters.product-types.save', $form_data->id ?? null);
        } catch (\Exception $e) {
            $saveUrl = url('/api/masters/product-types/save'.($form_data->id ? '/'.$form_data->id : ''));
        }

        try {
            $backUrl = route('masters.product-types.index');
        } catch (\Exception $e) {
            $backUrl = url('/masters/product-types');
        }

        return $this->render('pages.masters.product-types.form', compact('form_data', 'saveUrl', 'backUrl'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $type = $id ? ProductType::findOrFail($id) : new ProductType();
        $before = $id ? $type->toArray() : null;

        $type->name = $request->name;
        $type->save();

        $after = $type->fresh()->toArray();

        ActivityLogger::writeLog(
            $id ? 'update_product_type' : 'create_product_type',
            $id ? 'Updated product type' : 'Created new product type',
            $type,
            ['before' => $before, 'after' => $after],
            $request->user()
        );

        return $this->setJsonResponse('Product type saved successfully');
    }

    public function delete($id)
    {
        $type = ProductType::findOrFail($id);

        $hasRelation = Product::where('product_type_id', $id)->exists();
        if ($hasRelation) {
            return response()->json([
                'status' => false,
                'message' => 'Product type cannot be deleted because it has related products.'
            ], 400);
        }

        ActivityLogger::writeLog(
            'delete_product_type',
            'Deleted product type',
            $type,
            $type->toArray(),
            request()->user()
        );

        $type->delete();

        return $this->setJsonResponse('Product type deleted successfully');
    }
}
