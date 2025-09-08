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
        return $this->render('pages.masters.customer-types.index');
    }

    public function list(Request $request)
    {
        return DataTables::of(CustomerType::query())
            ->addColumn('actions', function ($row) {
                $edit = route('masters.customer-types.form', $row->id);
                $del  = route('masters.customer-types.delete', $row->id);

                return '
                    <a href="'.$edit.'" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                    <a href="'.$del.'" data-id="'.$row->id.'" data-table="customerTypesTable" class="btn btn-sm btn-danger delete-data"><i class="bi bi-trash"></i> Delete</a>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function form($id = null)
    {
        $form_data = $id ? CustomerType::findOrFail($id) : new CustomerType();
        return $this->render('pages.masters.customer-types.form', compact('form_data'));
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
