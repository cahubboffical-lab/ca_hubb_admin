<?php

namespace App\Http\Controllers;

use App\Models\CarModel;
use App\Services\BootstrapTableService;
use App\Services\CarModelCsvService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CarModelController extends Controller
{
    public function __construct(private readonly CarModelCsvService $csvService)
    {
    }

    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect([
            'car-model-list',
            'car-model-create',
            'car-model-update',
            'car-model-delete',
        ]);

        return view('car-models.index');
    }

    public function create()
    {
        ResponseService::noPermissionThenRedirect('car-model-create');

        return view('car-models.create');
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('car-model-create');

        $validated = $this->validateCarModel($request);

        try {
            CarModel::create($validated + ['created_by' => Auth::id()]);

            return ResponseService::successRedirectResponse(
                'Car Model Created Successfully',
                route('car-models.index')
            );
        } catch (Throwable $throwable) {
            ResponseService::logErrorRedirect($throwable, 'CarModelController->store');

            return ResponseService::errorRedirectResponse('Something Went Wrong');
        }
    }

    public function show(Request $request)
    {
        ResponseService::noPermissionThenSendJson('car-model-list');

        $offset = max((int) $request->input('offset', 0), 0);
        $limit = min(max((int) $request->input('limit', 10), 1), 200);
        $query = CarModel::with(['creator:id,name', 'updater:id,name']);

        if ($request->filled('search')) {
            $query->search($request->string('search')->toString());
        }

        $total = $query->count();
        $carModels = $query
            ->sort($request->input('sort', 'id'), $request->input('order', 'desc'))
            ->skip($offset)
            ->take($limit)
            ->get();

        $rows = $carModels->values()->map(function (CarModel $carModel, int $index) use ($offset) {
            $operate = '';
            if (Auth::user()->can('car-model-update')) {
                $operate .= BootstrapTableService::editButton(route('car-models.edit', $carModel));
            }
            if (Auth::user()->can('car-model-delete')) {
                $operate .= BootstrapTableService::deleteButton(route('car-models.destroy', $carModel));
            }

            return [
                'id' => $carModel->id,
                'no' => $offset + $index + 1,
                'name' => $carModel->name,
                'brand_name' => $carModel->brand_name,
                'price' => $carModel->price,
                'created_by_name' => $carModel->creator?->name ?? 'System',
                'updated_by_name' => $carModel->updater?->name ?? '-',
                'created_at' => Carbon::parse($carModel->created_at)->format('d-m-Y H:i:s'),
                'updated_at' => Carbon::parse($carModel->updated_at)->format('d-m-Y H:i:s'),
                'operate' => $operate,
            ];
        });

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function edit(CarModel $carModel)
    {
        ResponseService::noPermissionThenRedirect('car-model-update');

        return view('car-models.edit', compact('carModel'));
    }

    public function update(Request $request, CarModel $carModel)
    {
        ResponseService::noPermissionThenSendJson('car-model-update');

        $validated = $this->validateCarModel($request, $carModel);

        try {
            $carModel->update($validated + ['updated_by' => Auth::id()]);

            return ResponseService::successRedirectResponse(
                'Car Model Updated Successfully',
                route('car-models.index')
            );
        } catch (Throwable $throwable) {
            ResponseService::logErrorRedirect($throwable, 'CarModelController->update');

            return ResponseService::errorRedirectResponse('Something Went Wrong');
        }
    }

    public function destroy(CarModel $carModel)
    {
        ResponseService::noPermissionThenSendJson('car-model-delete');

        try {
            $carModel->delete();
            ResponseService::successResponse('Car Model Deleted Successfully');
        } catch (Throwable $throwable) {
            ResponseService::logErrorResponse($throwable, 'CarModelController->destroy');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function export(): StreamedResponse
    {
        ResponseService::noPermissionThenRedirect('car-model-list');

        return $this->csvService->export();
    }

    public function import(Request $request)
    {
        ResponseService::noAnyPermissionThenRedirect(['car-model-create', 'car-model-update']);

        $request->validate([
            'csv_file' => [
                'required',
                'file',
                'max:10240',
                static function (string $attribute, UploadedFile $file, $fail) {
                    if (strtolower($file->getClientOriginalExtension()) !== 'csv') {
                        $fail(__('The uploaded file must be a CSV file.'));
                    }
                },
            ],
        ]);

        try {
            $result = $this->csvService->import($request->file('csv_file'), (int) Auth::id());

            return redirect()->route('car-models.index')->with(
                'success',
                __('CSV imported successfully. :created created and :updated updated.', $result)
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withErrors(['csv_file' => $exception->getMessage()]);
        } catch (Throwable $throwable) {
            ResponseService::logErrorRedirect($throwable, 'CarModelController->import');

            return ResponseService::errorRedirectResponse('Something Went Wrong');
        }
    }

    private function validateCarModel(Request $request, ?CarModel $carModel = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('car_models')->where(
                    fn ($query) => $query->where('brand_name', $request->input('brand_name'))
                )->ignore($carModel?->id),
            ],
            'brand_name' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
