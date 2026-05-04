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
            $accounts = $query->get()->map(function (Account $account) {
                return [
                    'id' => $account->id,
                    'company_name' => $account->company->name ?? '-',
                    'bank_name' => $account->bank->name ?? '-',
                    'account_number' => $account->account_number,
                    'holder_name' => $account->holder_name,
                    'created_at' => $account->created_at,
                    'updated_at' => $account->updated_at,
                    'actions' => $this->buildAccountActions($account),
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $accounts,
            ]);
        }

        return DataTables::of($query)
            ->addColumn('company_name', fn($row) => $row->company->name ?? '')
            ->addColumn('bank_name', fn($row) => $row->bank->name ?? '')
            ->addColumn('actions', function ($row) {
                return $this->buildAccountActions($row);
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    private function buildAccountActions(Account $account): string
    {
        $editUrl = route('masters.accounts.form', $account->id);
        $deleteUrl = route('masters.accounts.delete', $account->id);

        $html  = '<div class="dropdown">';
        $html .= '<button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! text-[#1E1E1E]! hover:text-white! dropdown-toggle" type="button" data-toggle="dropdown">';
        $html .= '<i class="bi bi-three-dots"></i>';
        $html .= '</button>';
        $html .= '<div class="dropdown-menu dropdown-menu-right rounded-lg!">';
        $html .= '<a class="dropdown-item flex! items-center! gap-2! text-[#1E1E1E]!" href="' . e($editUrl) . '">';
        $html .= '<i class="bi bi-pencil"></i> Edit';
        $html .= '</a>';
        $html .= '<button type="button" class="dropdown-item delete-account-data cursor-pointer flex! items-center! gap-2! text-[#900B09]!" data-id="' . e($account->id) . '" data-url="' . e($deleteUrl) . '">';
        $html .= '<i class="bi bi-trash"></i> Delete';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
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
