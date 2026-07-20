<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCarFinanceBankRequest;
use App\Models\CarFinanceBank;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CarFinanceBankController extends Controller
{
    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['car-finance-bank-list', 'car-finance-bank-create', 'car-finance-bank-update', 'car-finance-bank-delete']);

        return view('car_finance.banks.index');
    }

    public function table(Request $request)
    {
        ResponseService::noPermissionThenSendJson('car-finance-bank-list');
        $limit = max(1, min((int) $request->input('limit', 10), 100));
        $offset = max(0, (int) $request->input('offset', 0));
        $sortable = ['id', 'code', 'name', 'finance_rate', 'insurance_rate', 'processing_fee', 'is_active', 'display_order', 'created_at'];
        $sort = in_array($request->input('sort'), $sortable, true) ? $request->input('sort') : 'display_order';
        $order = strtolower((string) $request->input('order')) === 'desc' ? 'desc' : 'asc';
        $query = CarFinanceBank::query();

        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->input('search')).'%';
            $query->where(fn ($builder) => $builder->where('code', 'LIKE', $search)->orWhere('name', 'LIKE', $search));
        }

        $total = (clone $query)->count();
        $banks = $query->orderBy($sort, $order)->orderBy('id')->skip($offset)->take($limit)->get();

        return response()->json([
            'total' => $total,
            'rows' => $banks->map(fn (CarFinanceBank $bank) => $this->tableRow($bank))->values(),
        ]);
    }

    public function create()
    {
        ResponseService::noPermissionThenRedirect('car-finance-bank-create');

        return view('car_finance.banks.create', ['carFinanceBank' => new CarFinanceBank()]);
    }

    public function store(SaveCarFinanceBankRequest $request)
    {
        ResponseService::noPermissionThenSendJson('car-finance-bank-create');
        CarFinanceBank::create($request->validated());
        ResponseService::successResponse(__('Finance bank created successfully.'));
    }

    public function edit(CarFinanceBank $carFinanceBank)
    {
        ResponseService::noPermissionThenRedirect('car-finance-bank-update');

        return view('car_finance.banks.edit', compact('carFinanceBank'));
    }

    public function update(SaveCarFinanceBankRequest $request, CarFinanceBank $carFinanceBank)
    {
        ResponseService::noPermissionThenRedirect('car-finance-bank-update');
        $carFinanceBank->update($request->validated());

        return redirect()->route('car-finance-banks.index')->with('success', __('Finance bank updated successfully.'));
    }

    public function destroy(CarFinanceBank $carFinanceBank)
    {
        ResponseService::noPermissionThenSendJson('car-finance-bank-delete');

        if ($carFinanceBank->requests()->exists()) {
            ResponseService::errorResponse(__('This bank has finance requests and cannot be deleted. Deactivate it instead.'));
        }

        try {
            $carFinanceBank->delete();
            ResponseService::successResponse(__('Finance bank deleted successfully.'));
        } catch (Throwable $exception) {
            report($exception);
            ResponseService::errorResponse(__('Unable to delete this finance bank. Deactivate it instead.'));
        }
    }

    private function tableRow(CarFinanceBank $bank): array
    {
        $operate = '';
        if (Auth::user()->can('car-finance-bank-update')) {
            $operate .= BootstrapTableService::editButton(route('car-finance-banks.edit', $bank));
        }
        if (Auth::user()->can('car-finance-bank-delete')) {
            $operate .= BootstrapTableService::deleteButton(route('car-finance-banks.destroy', $bank));
        }

        return [
            'id' => $bank->id,
            'code' => e($bank->code),
            'name' => e($bank->name),
            'finance_rate' => $bank->finance_rate,
            'insurance_rate' => $bank->insurance_rate,
            'processing_fee' => $bank->processing_fee,
            'accent_color' => e($bank->accent_color ?? '-'),
            'is_active' => $bank->is_active ? e(__('Active')) : e(__('Inactive')),
            'display_order' => $bank->display_order,
            'created_at' => $bank->created_at?->format('Y-m-d H:i'),
            'operate' => $operate,
        ];
    }
}
