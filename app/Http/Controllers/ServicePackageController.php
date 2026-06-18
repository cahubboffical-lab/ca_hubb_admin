<?php

namespace App\Http\Controllers;

use App\Models\ServicePackage;
use App\Services\BootstrapTableService;
use App\Services\FileService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ServicePackageController extends Controller
{
    private string $uploadFolder = 'service-packages';

    private const SECTIONS = [
        'car-inspection' => [
            'label' => 'Car Inspection',
            'type' => 'car_inspection',
            'request_permission' => 'car-inspection-request-list',
            'package_permissions' => [
                'car-inspection-package-list',
                'car-inspection-package-create',
                'car-inspection-package-update',
                'car-inspection-package-delete',
            ],
            'package_permission_prefix' => 'car-inspection-package',
        ],
        'sell-for-me' => [
            'label' => 'Sell for Me',
            'type' => 'sell_for_me',
            'request_permission' => 'sell-for-me-request-list',
            'package_permissions' => [
                'sell-for-me-package-list',
                'sell-for-me-package-create',
                'sell-for-me-package-update',
                'sell-for-me-package-delete',
            ],
            'package_permission_prefix' => 'sell-for-me-package',
        ],
    ];

    private function sectionConfig(string $section): array
    {
        abort_unless(array_key_exists($section, self::SECTIONS), 404);

        return self::SECTIONS[$section];
    }

    private function packagePermission(string $section, string $action): string
    {
        return $this->sectionConfig($section)['package_permission_prefix'] . '-' . $action;
    }

    private function packagePermissions(string $section): array
    {
        return $this->sectionConfig($section)['package_permissions'];
    }

    private function normalizeFeatures(array $features): array
    {
        return collect($features)
            ->map(static fn ($feature) => trim((string) $feature))
            ->filter()
            ->values()
            ->all();
    }

    private function assertSectionMatchesPackage(string $section, ServicePackage $servicePackage): void
    {
        abort_unless($servicePackage->type === $this->sectionConfig($section)['type'], 404);
    }

    private function renderFeatures(array $features): string
    {
        if (empty($features)) {
            return '-';
        }

        $html = '<div class="d-flex flex-wrap gap-1">';
        foreach ($features as $feature) {
            if (empty($feature)) {
                continue;
            }
            $html .= '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">' . e($feature) . '</span>';
        }
        $html .= '</div>';

        return $html;
    }

    public function requests(string $section)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenRedirect($config['request_permission']);

        return view('service_packages.requests', compact('config'));
    }

    public function index(string $section)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noAnyPermissionThenRedirect($this->packagePermissions($section));

        return view('service_packages.packages.index', compact('config'));
    }

    public function create(string $section)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenRedirect($this->packagePermission($section, 'create'));

        $servicePackage = new ServicePackage([
            'type' => $config['type'],
            'features' => [],
        ]);

        return view('service_packages.packages.create', compact('config', 'servicePackage'));
    }

    public function store(Request $request, string $section)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenSendJson($this->packagePermission($section, 'create'));

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'features' => 'required|array|min:1',
            'features.*' => 'nullable|string|max:255',
            'icon' => 'required|image|mimes:jpg,jpeg,png,webp|max:7168',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $data = [
                'name' => $request->name,
                'features' => $this->normalizeFeatures($request->input('features', [])),
                'price' => $request->price,
                'type' => $config['type'],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ];

            if ($request->hasFile('icon')) {
                $data['icon'] = FileService::compressAndUpload($request->file('icon'), $this->uploadFolder);
            }

            ServicePackage::create($data);

            DB::commit();
            ResponseService::successResponse(__('Service package added successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'ServicePackageController -> store');
            ResponseService::errorResponse();
        }
    }

    public function show(Request $request, string $section)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenSendJson($this->packagePermission($section, 'list'));

        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';

        $sql = ServicePackage::with(['creator', 'updater'])->where('type', $config['type']);

        if (! empty($request->search)) {
            $sql = $sql->search($request->search);
        }

        $total = $sql->count();
        $result = $sql->sort($sort, $order)->skip($offset)->take($limit)->get();

        $rows = [];
        foreach ($result as $row) {
            $tempRow = $row->toArray();
            $tempRow['features_display'] = $this->renderFeatures($row->features ?? []);
            $tempRow['icon_display'] = ! empty($row->icon)
                ? '<img src="' . e($row->icon) . '" alt="' . e($row->name) . '" class="rounded" style="width: 48px; height: 48px; object-fit: cover;">'
                : '-';
            $tempRow['created_at_formatted'] = optional($row->created_at)->format('Y-m-d H:i');
            $tempRow['updated_at_formatted'] = optional($row->updated_at)->format('Y-m-d H:i');
            $tempRow['created_by_name'] = $row->creator?->name ?? '-';
            $tempRow['updated_by_name'] = $row->updater?->name ?? '-';
            $tempRow['type_label'] = $row->type_label;

            $operate = '';
            if (Auth::user()->can($this->packagePermission($section, 'update'))) {
                $operate .= BootstrapTableService::editButton(route('service-packages.packages.edit', [
                    'section' => $section,
                    'servicePackage' => $row->id,
                ]));
            }

            if (Auth::user()->can($this->packagePermission($section, 'delete'))) {
                $operate .= BootstrapTableService::deleteButton(route('service-packages.packages.destroy', [
                    'section' => $section,
                    'servicePackage' => $row->id,
                ]));
            }

            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }

    public function edit(string $section, ServicePackage $servicePackage)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenRedirect($this->packagePermission($section, 'update'));
        $this->assertSectionMatchesPackage($section, $servicePackage);

        return view('service_packages.packages.edit', compact('config', 'servicePackage'));
    }

    public function update(Request $request, string $section, ServicePackage $servicePackage)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenSendJson($this->packagePermission($section, 'update'));
        $this->assertSectionMatchesPackage($section, $servicePackage);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'features' => 'required|array|min:1',
            'features.*' => 'nullable|string|max:255',
            'icon' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:7168',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $data = [
                'name' => $request->name,
                'features' => $this->normalizeFeatures($request->input('features', [])),
                'price' => $request->price,
                'type' => $config['type'],
                'updated_by' => Auth::id(),
            ];

            if ($request->hasFile('icon')) {
                $data['icon'] = FileService::compressAndReplace(
                    $request->file('icon'),
                    $this->uploadFolder,
                    $servicePackage->getRawOriginal('icon')
                );
            }

            $servicePackage->update($data);

            DB::commit();
            ResponseService::successResponse(__('Service package updated successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'ServicePackageController -> update');
            ResponseService::errorResponse();
        }
    }

    public function destroy(string $section, ServicePackage $servicePackage)
    {
        $this->sectionConfig($section);
        ResponseService::noPermissionThenSendJson($this->packagePermission($section, 'delete'));
        $this->assertSectionMatchesPackage($section, $servicePackage);

        try {
            if (! empty($servicePackage->getRawOriginal('icon'))) {
                FileService::delete($servicePackage->getRawOriginal('icon'));
            }

            $servicePackage->delete();

            ResponseService::successResponse(__('Service package deleted successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ServicePackageController -> destroy');
            ResponseService::errorResponse();
        }
    }
}
