<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Masters\CustomerType;
use App\Http\Classes\ActivityLogger;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CustomerTypeController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Customer Types';
        $listUrl = url('/api/masters/customer-types/list');
        $apiFormUrl = url('/api/masters/customer-types/form');

        try {
            $formUrl = route('masters.customer-types.form');
        } catch (\Exception $e) {
            $formUrl = $apiFormUrl;
        }

        return $this->render('pages.masters.customer-types.index', compact('listUrl', 'apiFormUrl', 'formUrl'));
    }

    public function list(Request $request)
    {
        $query = CustomerType::query();
        // If DataTables server-side (sends `draw`) -> return Yajra response
        if ($request->has('draw')) {
            return DataTables::of($query)
                ->addColumn('actions', function ($row) {
                    try {
                        $edit = route('masters.customer-types.form', $row->id);
                    } catch (\Exception $e) {
                        $edit = url('/api/masters/customer-types/form/'.$row->id);
                    }

                    try {
                        $del = route('masters.customer-types.delete', $row->id);
                    } catch (\Exception $e) {
                        $del = url('/api/masters/customer-types/delete/'.$row->id);
                    }

                    return '<a href="'.$edit.'" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>' .
                           ' <a href="'.$del.'" data-id="'.$row->id.'" data-table="customerTypesTable" class="btn btn-sm btn-danger delete-data"><i class="bi bi-trash"></i> Delete</a>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        // Non-DataTables API clients -> return plain JSON
        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            $types = $query->get();
            return response()->json([
                'status' => true,
                'data' => $types,
            ]);
        }

        return $this->render('pages.masters.customer-types.index');
    }

    public function form(Request $request, $id = null)
    {
        $form_data = $id ? CustomerType::findOrFail($id) : new CustomerType();

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'data' => [
                    'form_data' => $form_data,
                ],
            ]);
        }

        try {
            $saveUrl = route('masters.customer-types.save', $form_data->id ?? null);
        } catch (\Exception $e) {
            $saveUrl = url('/api/masters/customer-types/save'.($form_data->id ? '/'.$form_data->id : ''));
        }

        try {
            $backUrl = route('masters.customer-types.index');
        } catch (\Exception $e) {
            $backUrl = url('/masters/customer-types');
        }

        return $this->render('pages.masters.customer-types.form', compact('form_data', 'saveUrl', 'backUrl'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $customerType = $id ? CustomerType::findOrFail($id) : new CustomerType();
        $before = $id ? $customerType->toArray() : null;

        $customerType->name = $request->name;
        $customerType->save();

        $after = $customerType->fresh()->toArray();

        ActivityLogger::writeLog(
            $id ? 'update_customer_type' : 'create_customer_type',
            $id ? 'Updated customer type' : 'Created new customer type',
            $customerType,
            ['before' => $before, 'after' => $after],
            $request->user()
        );

        return $this->setJsonResponse('Customer type saved successfully');
    }

    public function delete($id)
    {
        $customerType = CustomerType::findOrFail($id);

        ActivityLogger::writeLog(
            'delete_customer_type',
            'Deleted customer type',
            $customerType,
            $customerType->toArray(),
            request()->user()
        );

        $customerType->delete();

        return $this->setJsonResponse('Customer type deleted successfully');
    }
}
