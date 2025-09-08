<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Masters\ExpenseType;
use App\Http\Classes\ActivityLogger;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ExpenseTypeController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Expense Types';
        return $this->render('pages.masters.expense-types.index');
    }

    public function list(Request $request)
    {
        return DataTables::of(ExpenseType::query())
            ->addColumn('actions', function ($row) {
                $edit = route('masters.expense-types.form', $row->id);
                $del  = route('masters.expense-types.delete', $row->id);

                return '
                    <a href="'.$edit.'" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                    <a href="'.$del.'" data-id="'.$row->id.'" data-table="expenseTypesTable" class="btn btn-sm btn-danger delete-data"><i class="bi bi-trash"></i> Delete</a>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function form($id = null)
    {
        $form_data = $id ? ExpenseType::findOrFail($id) : new ExpenseType();
        return $this->render('pages.masters.expense-types.form', compact('form_data'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $expenseType = $id ? ExpenseType::findOrFail($id) : new ExpenseType();
        $before = $id ? $expenseType->toArray() : null;

        $expenseType->name = $request->name;
        $expenseType->save();

        $after = $expenseType->fresh()->toArray();

        ActivityLogger::writeLog(
            $id ? 'update_expense_type' : 'create_expense_type',
            $id ? 'Updated expense type' : 'Created new expense type',
            $expenseType,
            ['before' => $before, 'after' => $after],
            $request->user()
        );

        return $this->setJsonResponse('Expense type saved successfully');
    }

    public function delete($id)
    {
        $expenseType = ExpenseType::findOrFail($id);

        ActivityLogger::writeLog(
            'delete_expense_type',
            'Deleted expense type',
            $expenseType,
            $expenseType->toArray(),
            request()->user()
        );

        $expenseType->delete();

        return $this->setJsonResponse('Expense type deleted successfully');
    }
}
