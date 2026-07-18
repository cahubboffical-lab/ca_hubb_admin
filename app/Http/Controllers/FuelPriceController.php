<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveFuelPriceRequest;
use App\Models\FuelPrice;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FuelPriceController extends Controller
{
    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect([
            'fuel-price-list',
            'fuel-price-create',
            'fuel-price-update',
            'fuel-price-delete',
        ]);

        return view('fuel_prices.index');
    }

    public function table(Request $request)
    {
        ResponseService::noPermissionThenSendJson('fuel-price-list');

        $limit = max(1, min((int) $request->input('limit', 10), 100));
        $offset = max(0, (int) $request->input('offset', 0));
        $sort = in_array($request->input('sort'), array_merge(['id', 'created_at'], FuelPrice::PRICE_FIELDS), true)
            ? $request->input('sort')
            : 'created_at';
        $order = strtolower((string) $request->input('order')) === 'asc' ? 'asc' : 'desc';
        $query = FuelPrice::query();

        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->input('search')).'%';
            $query->where(function ($builder) use ($search) {
                $builder->where('id', 'LIKE', $search);
                foreach (FuelPrice::PRICE_FIELDS as $field) {
                    $builder->orWhere($field, 'LIKE', $search);
                }
            });
        }

        $total = (clone $query)->count();
        $fuelPrices = $query->orderBy($sort, $order)->orderByDesc('id')->skip($offset)->take($limit)->get();

        return response()->json([
            'total' => $total,
            'rows' => $fuelPrices->map(fn (FuelPrice $fuelPrice) => $this->tableRow($fuelPrice))->values(),
        ]);
    }

    public function create()
    {
        ResponseService::noPermissionThenRedirect('fuel-price-create');

        return view('fuel_prices.create', ['fuelPrice' => new FuelPrice()]);
    }

    public function store(SaveFuelPriceRequest $request)
    {
        ResponseService::noPermissionThenSendJson('fuel-price-create');
        FuelPrice::create($request->validated());
        ResponseService::successResponse(__('Fuel prices created successfully.'));
    }

    public function edit(FuelPrice $fuelPrice)
    {
        ResponseService::noPermissionThenRedirect('fuel-price-update');

        return view('fuel_prices.edit', compact('fuelPrice'));
    }

    public function update(SaveFuelPriceRequest $request, FuelPrice $fuelPrice)
    {
        ResponseService::noPermissionThenRedirect('fuel-price-update');
        $fuelPrice->update($request->validated());

        return redirect()->route('fuel-prices.index')->with('success', __('Fuel prices updated successfully.'));
    }

    public function destroy(FuelPrice $fuelPrice)
    {
        ResponseService::noPermissionThenSendJson('fuel-price-delete');
        $fuelPrice->delete();
        ResponseService::successResponse(__('Fuel prices deleted successfully.'));
    }

    private function tableRow(FuelPrice $fuelPrice): array
    {
        $row = [
            'id' => $fuelPrice->id,
            'petrol_super' => $fuelPrice->petrol_super,
            'high_octane' => $fuelPrice->high_octane,
            'high_speed_diesel' => $fuelPrice->high_speed_diesel,
            'lpg' => $fuelPrice->lpg,
            'kerosene_oil' => $fuelPrice->kerosene_oil,
            'created_at' => $fuelPrice->created_at?->format('Y-m-d H:i'),
            'operate' => '',
        ];

        if (Auth::user()->can('fuel-price-update')) {
            $row['operate'] .= BootstrapTableService::editButton(route('fuel-prices.edit', $fuelPrice));
        }
        if (Auth::user()->can('fuel-price-delete')) {
            $row['operate'] .= BootstrapTableService::deleteButton(route('fuel-prices.destroy', $fuelPrice));
        }

        return $row;
    }
}
