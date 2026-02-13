<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Masters\Account;
use App\Models\Masters\Company;
use App\Models\Masters\Bank;
use App\Http\Classes\ActivityLogger;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AccountController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Accounts';
        return $this->render('pages.masters.accounts.index');
    }

    public function list(Request $request)
    {
        $query = Account::with(['company', 'bank']);

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            $accounts = $query->get();
            return response()->json([
                'status' => true,
                'data' => $accounts,
            ]);
        }

        return DataTables::of($query)
            ->addColumn('company_name', fn($row) => $row->company->name ?? '')
            ->addColumn('bank_name', fn($row) => $row->bank->name ?? '')
            ->addColumn('actions', function ($row) {
                $edit = route('masters.accounts.form', $row->id);
                $del  = route('masters.accounts.delete', $row->id);

                return '
                    <a href="'.$edit.'" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                    <a href="'.$del.'" data-id="'.$row->id.'" data-table="accountsTable" class="btn btn-sm btn-danger delete-data"><i class="bi bi-trash"></i> Delete</a>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function form(Request $request, $id = null)
    {
        $form_data = $id ? Account::findOrFail($id) : new Account();
        $companies = Company::all();
        $banks = Bank::all();

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'data' => [
                    'form_data' => $form_data,
                    'companies' => $companies,
                    'banks' => $banks,
                ],
            ]);
        }

        return $this->render('pages.masters.accounts.form', compact('form_data', 'companies', 'banks'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'company_id'     => 'required',
            'bank_id'        => 'required',
            'account_number' => 'required',
            'holder_name'    => 'required',
        ]);

        $account = $id ? Account::findOrFail($id) : new Account();
        $before = $id ? $account->toArray() : null;

        $account->company_id = $request->company_id;
        $account->bank_id = $request->bank_id;
        $account->account_number = $request->account_number;
        $account->holder_name = $request->holder_name;
        $account->save();

        $after = $account->fresh()->toArray();

        ActivityLogger::writeLog(
            $id ? 'update_account' : 'create_account',
            $id ? 'Updated account' : 'Created new account',
            $account,
            ['before' => $before, 'after' => $after],
            $request->user()
        );

        return $this->setJsonResponse('Account saved successfully');
    }

    public function delete($id)
    {
        $account = Account::findOrFail($id);

        ActivityLogger::writeLog(
        'delete_account',
            'Deleted account',
            $account,
            $account->toArray(),
            request()->user()
        );

        $account->delete();

        return $this->setJsonResponse('Account deleted successfully');
    }
}
