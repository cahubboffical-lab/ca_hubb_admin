<?php

namespace App\Http\Controllers;

use App\Models\CarInspectionRequest;
use App\Models\SellForMeRequest;
use App\Models\ServiceRequest;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceRequestAdminController extends Controller
{
    private const SECTIONS = [
        'car-inspection' => [
            'model' => CarInspectionRequest::class,
            'permission' => 'car-inspection-request-list',
        ],
        'sell-for-me' => [
            'model' => SellForMeRequest::class,
            'permission' => 'sell-for-me-request-list',
        ],
    ];

    public function table(Request $request, string $section)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenSendJson($config['permission']);

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

        return response()->json([
            'total' => $total,
            'rows' => $requests->map(fn (ServiceRequest $serviceRequest) => $this->tableRow($section, $serviceRequest))->values(),
        ]);
    }

    public function show(string $section, int $requestId)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenSendJson($config['permission']);
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
        ResponseService::noPermissionThenSendJson($config['permission']);
        $serviceRequest = $this->findRequest($config['model'], $requestId);

        $validated = $request->validate([
            'status' => ['required', Rule::in(ServiceRequest::statuses())],
        ]);

        $nextStatus = $serviceRequest->nextStatus();
        if ($nextStatus === null || $validated['status'] !== $nextStatus) {
            return response()->json([
                'error' => true,
                'message' => __('This status change is not allowed. Requests must move from Pending to In Process, then to Completed.'),
            ], 422);
        }

        $serviceRequest->update(['status' => $nextStatus]);

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

    private function tableRow(string $section, ServiceRequest $serviceRequest): array
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
            'operate' => $this->renderActions($section, $serviceRequest),
        ];
    }

    private function renderActions(string $section, ServiceRequest $serviceRequest): string
    {
        $nextStatus = $serviceRequest->nextStatus();
        $viewUrl = route('service-requests.show', ['section' => $section, 'requestId' => $serviceRequest->id]);
        $statusUrl = route('service-requests.update-status', ['section' => $section, 'requestId' => $serviceRequest->id]);
        $phone = preg_replace('/[^0-9+]/', '', $serviceRequest->phone_number) ?: '';
        $whatsAppNumber = $this->whatsAppNumber($serviceRequest->phone_number);
        $whatsAppMessage = rawurlencode(__('Hello :name, we are contacting you regarding your :service request on CA Hubb.', [
            'name' => $serviceRequest->full_name,
            'service' => $serviceRequest->serviceType() === 'car_inspection' ? __('Car Inspection') : __('Sell for Me'),
        ]));

        $actions = '<div class="dropdown service-request-actions">'
            .'<button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">'
            .'<i class="fas fa-ellipsis-h me-1"></i>'.e(__('Actions')).'</button>'
            .'<ul class="dropdown-menu dropdown-menu-end shadow-sm">'
            .'<li><button type="button" class="dropdown-item view-request" data-url="'.e($viewUrl).'">'
            .'<i class="fas fa-eye text-primary me-2"></i>'.e(__('View Details')).'</button></li>'
            .'<li><hr class="dropdown-divider"></li>'
            .'<li><a class="dropdown-item" href="tel:'.e($phone).'">'
            .'<i class="fas fa-phone-alt text-success me-2"></i>'.e(__('Call Customer')).'</a></li>'
            .'<li><a class="dropdown-item" href="sms:'.e($phone).'">'
            .'<i class="fas fa-comment-alt text-info me-2"></i>'.e(__('Send SMS')).'</a></li>'
            .'<li><a class="dropdown-item" href="https://wa.me/'.e($whatsAppNumber).'?text='.$whatsAppMessage.'" target="_blank" rel="noopener noreferrer">'
            .'<i class="fab fa-whatsapp text-success me-2"></i>'.e(__('Open WhatsApp')).'</a></li>';

        if ($nextStatus !== null) {
            $statusIcon = $nextStatus === ServiceRequest::STATUS_COMPLETED ? 'fas fa-check-circle' : 'fas fa-play-circle';
            $statusClass = $nextStatus === ServiceRequest::STATUS_COMPLETED ? 'text-success' : 'text-primary';
            $actions .= '<li><hr class="dropdown-divider"></li>'
                .'<li><button type="button" class="dropdown-item update-request-status fw-semibold '.$statusClass.'"'
                .' data-url="'.e($statusUrl).'" data-status="'.e($nextStatus).'" data-label="'.e($this->statusLabel($nextStatus)).'">'
                .'<i class="'.$statusIcon.' me-2"></i>'.e(__('Mark as :status', ['status' => $this->statusLabel($nextStatus)])).'</button></li>';
        }

        return $actions.'</ul></div>';
    }

    private function whatsAppNumber(string $phoneNumber): string
    {
        $number = preg_replace('/\D+/', '', $phoneNumber) ?: '';

        if (str_starts_with($number, '00')) {
            return substr($number, 2);
        }

        if (str_starts_with($number, '0')) {
            return '92'.substr($number, 1);
        }

        return $number;
    }

    private function detailData(ServiceRequest $serviceRequest): array
    {
        return [
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
            'registration_area' => $serviceRequest->registration_area,
            'visit_area' => $serviceRequest->visit_area,
            'visit_date' => $serviceRequest->visit_date?->format('Y-m-d'),
            'visit_start_time' => $serviceRequest->visit_start_time,
            'visit_end_time' => $serviceRequest->visit_end_time,
            'status' => $serviceRequest->status,
            'status_label' => $this->statusLabel($serviceRequest->status),
            'created_at' => $serviceRequest->created_at?->format('Y-m-d H:i'),
            'updated_at' => $serviceRequest->updated_at?->format('Y-m-d H:i'),
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            ServiceRequest::STATUS_IN_PROGRESS => __('In Process'),
            ServiceRequest::STATUS_COMPLETED => __('Completed'),
            default => __('Pending'),
        };
    }
}
