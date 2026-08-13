<?php

namespace App\Http\Controllers;

use App\Models\CarInspectionRequest;
use App\Models\SellForMeRequest;
use App\Models\ServiceRequest;
use App\Services\CustomerContactService;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceRequestAdminController extends Controller
{
    private const SECTIONS = [
        'car-inspection' => [
            'model' => CarInspectionRequest::class,
            'list_permission' => 'car-inspection-request-list',
            'update_permission' => 'car-inspection-request-update',
        ],
        'sell-for-me' => [
            'model' => SellForMeRequest::class,
            'list_permission' => 'sell-for-me-request-list',
            'update_permission' => 'sell-for-me-request-update',
        ],
    ];

    public function table(Request $request, string $section)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenSendJson($config['list_permission']);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(ServiceRequest::statuses())],
        ]);

        $status = $validated['status'] ?? ServiceRequest::STATUS_PENDING;
        $limit = max(1, min((int) $request->input('limit', 10), 100));
        $offset = max(0, (int) $request->input('offset', 0));
        $sort = in_array($request->input('sort'), ['id', 'full_name', 'phone_number', 'model_year', 'visit_date', 'created_at'], true)
            ? $request->input('sort')
            : 'id';
        $order = strtolower((string) $request->input('order')) === 'asc' ? 'asc' : 'desc';

        /** @var class-string<ServiceRequest> $modelClass */
        $modelClass = $config['model'];
        $query = $modelClass::query()
            ->with(['servicePackage:id,name,price', 'city:id,name', 'carModel:id,name,brand_name'])
            ->where('status', $status);

        $this->applySearch($query, trim((string) $request->input('search')));

        $total = (clone $query)->count();
        $requests = $query->orderBy($sort, $order)->skip($offset)->take($limit)->get();
        $canUpdate = $request->user()->can($config['update_permission']);

        return response()->json([
            'total' => $total,
            'rows' => $requests->map(fn (ServiceRequest $serviceRequest) => $this->tableRow($section, $serviceRequest, $canUpdate))->values(),
        ]);
    }

    public function show(string $section, int $requestId)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenSendJson($config['list_permission']);
        $serviceRequest = $this->findRequest($config['model'], $requestId);

        $serviceRequest->load(['user:id,name,email,mobile', 'servicePackage:id,name,price,type', 'city:id,name', 'carModel:id,name,brand_name']);

        return response()->json([
            'error' => false,
            'data' => $this->detailData($serviceRequest),
        ]);
    }

    public function updateStatus(Request $request, string $section, int $requestId)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenSendJson($config['update_permission']);
        $serviceRequest = $this->findRequest($config['model'], $requestId);

        $request->merge(['admin_notes' => trim((string) $request->input('admin_notes'))]);
        $validated = $request->validate([
            'status' => ['required', Rule::in(ServiceRequest::statuses())],
            'admin_notes' => ['required', 'string', 'max:2000'],
        ]);

        $targetStatus = $validated['status'];
        $allowed = $targetStatus === ServiceRequest::STATUS_CANCELED
            ? $serviceRequest->canCancel()
            : $targetStatus === $serviceRequest->nextStatus();
        if (! $allowed) {
            return response()->json([
                'error' => true,
                'message' => __('This status change is not allowed. Requests must move from Pending to In Process, then to Completed.'),
            ], 422);
        }

        $serviceRequest->update([
            'status' => $targetStatus,
            'admin_notes' => $validated['admin_notes'],
        ]);

        return response()->json([
            'error' => false,
            'message' => __('Request status updated successfully.'),
            'data' => [
                'id' => $serviceRequest->id,
                'status' => $serviceRequest->status,
                'status_label' => $this->statusLabel($serviceRequest->status),
            ],
        ]);
    }

    private function sectionConfig(string $section): array
    {
        abort_unless(array_key_exists($section, self::SECTIONS), 404);

        return self::SECTIONS[$section];
    }

    /** @param class-string<ServiceRequest> $modelClass */
    private function findRequest(string $modelClass, int $requestId): ServiceRequest
    {
        return $modelClass::query()->findOrFail($requestId);
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';
        $query->where(function (Builder $builder) use ($like) {
            $builder->where('id', 'LIKE', $like)
                ->orWhere('full_name', 'LIKE', $like)
                ->orWhere('phone_number', 'LIKE', $like)
                ->orWhere('car_variant', 'LIKE', $like)
                ->orWhere('visit_area', 'LIKE', $like)
                ->orWhereHas('servicePackage', fn (Builder $relation) => $relation->where('name', 'LIKE', $like))
                ->orWhereHas('city', fn (Builder $relation) => $relation->where('name', 'LIKE', $like))
                ->orWhereHas('carModel', fn (Builder $relation) => $relation->where('name', 'LIKE', $like)->orWhere('brand_name', 'LIKE', $like));
        });
    }

    private function tableRow(string $section, ServiceRequest $serviceRequest, bool $canUpdate): array
    {
        return [
            'id' => $serviceRequest->id,
            'full_name' => e($serviceRequest->full_name),
            'phone_number' => e($serviceRequest->phone_number),
            'package_name' => e($serviceRequest->servicePackage?->name ?? '-'),
            'city_name' => e($serviceRequest->city?->name ?? '-'),
            'car' => e(trim(($serviceRequest->carModel?->brand_name ?? '').' '.($serviceRequest->carModel?->name ?? '')) ?: '-'),
            'model_year' => $serviceRequest->model_year,
            'visit_date' => $serviceRequest->visit_date?->format('Y-m-d'),
            'visit_time' => e(substr((string) $serviceRequest->visit_start_time, 0, 5).' - '.substr((string) $serviceRequest->visit_end_time, 0, 5)),
            'created_at' => $serviceRequest->created_at?->format('Y-m-d H:i'),
            'operate' => $this->renderActions($section, $serviceRequest, $canUpdate),
        ];
    }

    private function renderActions(string $section, ServiceRequest $serviceRequest, bool $canUpdate): string
    {
        $nextStatus = $serviceRequest->nextStatus();
        $viewUrl = route('service-requests.show', ['section' => $section, 'requestId' => $serviceRequest->id]);
        $statusUrl = route('service-requests.update-status', ['section' => $section, 'requestId' => $serviceRequest->id]);
        $phone = CustomerContactService::phoneUri($serviceRequest->phone_number);
        $whatsAppUrl = CustomerContactService::whatsAppUrl($serviceRequest->phone_number, __('Hello :name, we are contacting you regarding your :service request on CA Hubb.', [
            'name' => $serviceRequest->full_name,
            'service' => $serviceRequest->serviceType() === 'car_inspection' ? __('Car Inspection') : __('Sell for Me'),
        ]));

        $actions = '<div class="service-request-actions">'
            .'<button type="button" class="btn btn-sm btn-outline-primary view-request" data-url="'.e($viewUrl).'">'
            .'<i class="fas fa-eye me-1"></i>'.e(__('View')).'</button>'
            .'<a class="btn btn-sm btn-outline-success" href="tel:'.e($phone).'">'
            .'<i class="fas fa-phone-alt me-1"></i>'.e(__('Call')).'</a>'
            .'<a class="btn btn-sm btn-outline-info" href="sms:'.e($phone).'">'
            .'<i class="fas fa-comment-alt me-1"></i>'.e(__('SMS')).'</a>'
            .'<a class="btn btn-sm btn-success" href="'.e($whatsAppUrl).'" target="_blank" rel="noopener noreferrer">'
            .'<i class="fab fa-whatsapp me-1"></i>'.e(__('WhatsApp')).'</a>';

        if ($canUpdate && $nextStatus !== null) {
            $statusIcon = $nextStatus === ServiceRequest::STATUS_COMPLETED ? 'fas fa-check-circle' : 'fas fa-play-circle';
            $statusClass = $nextStatus === ServiceRequest::STATUS_COMPLETED ? 'btn-success' : 'btn-primary';
            $actions .= '<button type="button" class="btn btn-sm '.$statusClass.' update-request-status"'
                .' data-url="'.e($statusUrl).'" data-status="'.e($nextStatus).'" data-label="'.e($this->statusLabel($nextStatus)).'">'
                .'<i class="'.$statusIcon.' me-1"></i>'.e(__('Mark as :status', ['status' => $this->statusLabel($nextStatus)])).'</button>';
        }

        if ($canUpdate && $serviceRequest->canCancel()) {
            $actions .= '<button type="button" class="btn btn-sm btn-outline-danger update-request-status"'
                .' data-url="'.e($statusUrl).'" data-status="'.e(ServiceRequest::STATUS_CANCELED).'" data-label="'.e(__('Canceled')).'">'
                .'<i class="fas fa-times-circle me-1"></i>'.e(__('Cancel Request')).'</button>';
        }

        return $actions.'</div>';
    }

    private function detailData(ServiceRequest $serviceRequest): array
    {
        $data = [
            'id' => $serviceRequest->id,
            'full_name' => $serviceRequest->full_name,
            'phone_number' => $serviceRequest->phone_number,
            'user' => $serviceRequest->user ? [
                'id' => $serviceRequest->user->id,
                'name' => $serviceRequest->user->name,
                'email' => $serviceRequest->user->email,
                'mobile' => $serviceRequest->user->mobile,
            ] : null,
            'service_type' => $serviceRequest->serviceType(),
            'package' => $serviceRequest->servicePackage ? [
                'id' => $serviceRequest->servicePackage->id,
                'name' => $serviceRequest->servicePackage->name,
                'price' => $serviceRequest->servicePackage->price,
            ] : null,
            'city' => $serviceRequest->city?->name,
            'car_model' => $serviceRequest->carModel ? [
                'id' => $serviceRequest->carModel->id,
                'name' => $serviceRequest->carModel->name,
                'brand_name' => $serviceRequest->carModel->brand_name,
            ] : null,
            'model_year' => $serviceRequest->model_year,
            'car_variant' => $serviceRequest->car_variant,
            'car_condition' => $serviceRequest->car_condition,
            'visit_area' => $serviceRequest->visit_area,
            'visit_date' => $serviceRequest->visit_date?->format('Y-m-d'),
            'visit_start_time' => $serviceRequest->visit_start_time,
            'visit_end_time' => $serviceRequest->visit_end_time,
            'status' => $serviceRequest->status,
            'status_label' => $this->statusLabel($serviceRequest->status),
            'admin_notes' => $serviceRequest->admin_notes,
            'created_at' => $serviceRequest->created_at?->format('Y-m-d H:i'),
            'updated_at' => $serviceRequest->updated_at?->format('Y-m-d H:i'),
        ];

        if ($serviceRequest instanceof SellForMeRequest) {
            $data['registration_area'] = $serviceRequest->registration_area;
        }

        return $data;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            ServiceRequest::STATUS_IN_PROGRESS => __('In Process'),
            ServiceRequest::STATUS_CANCELED => __('Canceled'),
            ServiceRequest::STATUS_COMPLETED => __('Completed'),
            default => __('Pending'),
        };
    }
}
