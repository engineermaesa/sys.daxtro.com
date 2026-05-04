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
        $query = Bank::query();

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            $banks = $query->get()->map(function (Bank $bank) {
                return [
                    'id' => $bank->id,
                    'name' => $bank->name,
                    'created_at' => $bank->created_at,
                    'updated_at' => $bank->updated_at,
                    'actions' => $this->buildBankActions($bank),
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $banks,
            ]);
        }

        return DataTables::of($query)
            ->addColumn('actions', function ($row) {
                return $this->buildBankActions($row);
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    private function buildBankActions(Bank $bank): string
    {
        $editUrl = route('masters.banks.form', $bank->id);
        $deleteUrl = route('masters.banks.delete', $bank->id);

        $html  = '<div class="dropdown">';
        $html .= '<button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! text-[#1E1E1E]! hover:text-white! dropdown-toggle" type="button" data-toggle="dropdown">';
        $html .= '<i class="bi bi-three-dots"></i>';
        $html .= '</button>';
        $html .= '<div class="dropdown-menu dropdown-menu-right rounded-lg!">';
        $html .= '<a class="dropdown-item flex! items-center! gap-2! text-[#1E1E1E]!" href="' . e($editUrl) . '">';
        $html .= '<i class="bi bi-pencil"></i> Edit';
        $html .= '</a>';
        $html .= '<button type="button" class="dropdown-item delete-bank-data cursor-pointer flex! items-center! gap-2! text-[#900B09]!" data-id="' . e($bank->id) . '" data-url="' . e($deleteUrl) . '">';
        $html .= '<i class="bi bi-trash"></i> Delete';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
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
