<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Masters\Bank;
use App\Http\Classes\ActivityLogger;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class BankController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Banks';
        return $this->render('pages.masters.banks.index');
    }

    public function list(Request $request)
    {
        return DataTables::of(Bank::query())
            ->addColumn('actions', function ($row) {
                $edit = route('masters.banks.form', $row->id);
                $del  = route('masters.banks.delete', $row->id);
                
                return '
                    <a href="'.$edit.'" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                    <a href="'.$del.'" data-id="'.$row->id.'" data-table="banksTable" class="btn btn-sm btn-danger delete-data"><i class="bi bi-trash"></i> Delete</a>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function form($id = null)
    {
        $form_data = $id ? Bank::findOrFail($id) : new Bank();
        return $this->render('pages.masters.banks.form', compact('form_data'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $bank = $id ? Bank::findOrFail($id) : new Bank();
        $before = $id ? $bank->toArray() : null;

        $bank->name = $request->name;
        $bank->save();

        $after = $bank->fresh()->toArray();

        ActivityLogger::writeLog(
            $id ? 'update_bank' : 'create_bank',
            $id ? 'Updated bank' : 'Created new bank',
            $bank,
            ['before' => $before, 'after' => $after],
            $request->user()
        );

        return $this->setJsonResponse('Bank saved successfully');
    }

   public function delete($id)
    {
        $bank = Bank::findOrFail($id);

        $hasRelation = $bank->accounts()->exists();
        if ($hasRelation) {
            return response()->json([
                'status' => false,
                'message' => 'Company cannot be deleted because it has related.'
            ], 400);
        }

        ActivityLogger::writeLog(
            'delete_bank',
            'Deleted bank',
            $bank,
            $bank->toArray(),
            request()->user()
        );

        $bank->delete();

        return $this->setJsonResponse('Bank deleted successfully');
    }
}
