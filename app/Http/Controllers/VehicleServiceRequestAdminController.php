<?php

namespace App\Http\Controllers;

use App\Models\CarOwnershipRequest;
use App\Models\CarRegistrationRequest;
use App\Models\VehicleServiceRequest;
use App\Services\CustomerContactService;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleServiceRequestAdminController extends Controller
{
    private const SECTIONS = [
        'car-registration' => [
            'label' => 'Car Registration',
            'model' => CarRegistrationRequest::class,
            'list_permission' => 'car-registration-request-list',
            'update_permission' => 'car-registration-request-update',
        ],
        'car-ownership' => [
            'label' => 'Car Ownership Transfer',
            'model' => CarOwnershipRequest::class,
            'list_permission' => 'car-ownership-request-list',
            'update_permission' => 'car-ownership-request-update',
        ],
    ];

    public function index(string $section)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenRedirect($config['list_permission']);

        return view('vehicle_service_requests.index', compact('config', 'section'));
    }

    public function table(Request $request, string $section)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenSendJson($config['list_permission']);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(VehicleServiceRequest::statuses())],
        ]);
        $status = $validated['status'] ?? VehicleServiceRequest::STATUS_PENDING;
        $limit = max(1, min((int) $request->input('limit', 10), 100));
        $offset = max(0, (int) $request->input('offset', 0));
        $sort = in_array($request->input('sort'), ['id', 'full_name', 'phone_number', 'model_year', 'registration_place', 'created_at', 'completed_at'], true)
            ? $request->input('sort')
            : 'id';
        $order = strtolower((string) $request->input('order')) === 'asc' ? 'asc' : 'desc';

        /** @var class-string<VehicleServiceRequest> $modelClass */
        $modelClass = $config['model'];
        $query = $modelClass::query()
            ->with('carModel:id,name,brand_name')
            ->where('status', $status);

        $this->applySearch($query, trim((string) $request->input('search')));
        $total = (clone $query)->count();
        $requests = $query->orderBy($sort, $order)->skip($offset)->take($limit)->get();

        return response()->json([
            'total' => $total,
            'rows' => $requests->map(fn (VehicleServiceRequest $serviceRequest) => $this->tableRow($section, $serviceRequest))->values(),
        ]);
    }

    public function show(string $section, int $requestId)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenSendJson($config['list_permission']);
        $serviceRequest = $this->findRequest($config['model'], $requestId);
        $serviceRequest->load(['user:id,name,email,mobile', 'carModel:id,name,brand_name']);

        return response()->json([
            'error' => false,
            'data' => [
                'id' => $serviceRequest->id,
                'full_name' => $serviceRequest->full_name,
                'phone_number' => $serviceRequest->phone_number,
                'is_filer' => $serviceRequest->is_filer,
                'user' => $serviceRequest->user ? [
                    'id' => $serviceRequest->user->id,
                    'name' => $serviceRequest->user->name,
                    'email' => $serviceRequest->user->email,
                    'mobile' => $serviceRequest->user->mobile,
                ] : null,
                'car_model' => $serviceRequest->carModel ? [
                    'id' => $serviceRequest->carModel->id,
                    'name' => $serviceRequest->carModel->name,
                    'brand_name' => $serviceRequest->carModel->brand_name,
                ] : null,
                'model_year' => $serviceRequest->model_year,
                'car_variant' => $serviceRequest->car_variant,
                'registration_place' => $serviceRequest->registration_place,
                'status' => $serviceRequest->status,
                'status_label' => $this->statusLabel($serviceRequest->status),
                'admin_notes' => $serviceRequest->admin_notes,
                'created_at' => $serviceRequest->created_at?->format('Y-m-d H:i'),
                'updated_at' => $serviceRequest->updated_at?->format('Y-m-d H:i'),
                'completed_at' => $serviceRequest->completed_at?->format('Y-m-d H:i'),
            ],
        ]);
    }

    public function updateStatus(Request $request, string $section, int $requestId)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenSendJson($config['update_permission']);
        $serviceRequest = $this->findRequest($config['model'], $requestId);
        $request->merge(['admin_notes' => trim((string) $request->input('admin_notes'))]);
        $validated = $request->validate([
            'status' => ['required', Rule::in(VehicleServiceRequest::statuses())],
            'admin_notes' => ['required', 'string', 'max:2000'],
        ]);

        $targetStatus = $validated['status'];
        $allowed = $targetStatus === VehicleServiceRequest::STATUS_CANCELED
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
            'completed_at' => $targetStatus === VehicleServiceRequest::STATUS_COMPLETED ? now() : null,
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

    /** @param class-string<VehicleServiceRequest> $modelClass */
    private function findRequest(string $modelClass, int $requestId): VehicleServiceRequest
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
                ->orWhere('registration_place', 'LIKE', $like)
                ->orWhereHas('carModel', fn (Builder $relation) => $relation
                    ->where('name', 'LIKE', $like)
                    ->orWhere('brand_name', 'LIKE', $like));
        });
    }

    private function tableRow(string $section, VehicleServiceRequest $serviceRequest): array
    {
        return [
            'id' => $serviceRequest->id,
            'full_name' => e($serviceRequest->full_name),
            'phone_number' => e($serviceRequest->phone_number),
            'is_filer' => $serviceRequest->is_filer ? e(__('Yes')) : e(__('No')),
            'car' => e(trim(($serviceRequest->carModel?->brand_name ?? '').' '.($serviceRequest->carModel?->name ?? '')) ?: '-'),
            'model_year' => $serviceRequest->model_year,
            'car_variant' => e($serviceRequest->car_variant),
            'registration_place' => e($serviceRequest->registration_place),
            'created_at' => $serviceRequest->created_at?->format('Y-m-d H:i'),
            'completed_at' => $serviceRequest->completed_at?->format('Y-m-d H:i') ?? '-',
            'operate' => $this->renderActions($section, $serviceRequest),
        ];
    }

    private function renderActions(string $section, VehicleServiceRequest $serviceRequest): string
    {
        $nextStatus = $serviceRequest->nextStatus();
        $viewUrl = route('vehicle-service-requests.show', compact('section') + ['requestId' => $serviceRequest->id]);
        $statusUrl = route('vehicle-service-requests.update-status', compact('section') + ['requestId' => $serviceRequest->id]);
        $phone = CustomerContactService::phoneUri($serviceRequest->phone_number);
        $whatsAppUrl = CustomerContactService::whatsAppUrl($serviceRequest->phone_number, __('Hello :name, we are contacting you regarding your :service request on CA Hubb.', [
            'name' => $serviceRequest->full_name,
            'service' => $serviceRequest->serviceLabel(),
        ]));

        $actions = '<div class="vehicle-service-request-actions">'
            .'<button type="button" class="btn btn-sm btn-outline-primary view-request" data-url="'.e($viewUrl).'"><i class="fas fa-eye me-1"></i>'.e(__('View')).'</button>'
            .'<a class="btn btn-sm btn-outline-success" href="tel:'.e($phone).'"><i class="fas fa-phone-alt me-1"></i>'.e(__('Call')).'</a>'
            .'<a class="btn btn-sm btn-outline-info" href="sms:'.e($phone).'"><i class="fas fa-comment-alt me-1"></i>'.e(__('SMS')).'</a>'
            .'<a class="btn btn-sm btn-success" href="'.e($whatsAppUrl).'" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp me-1"></i>'.e(__('WhatsApp')).'</a>';

        if ($nextStatus !== null) {
            $statusIcon = $nextStatus === VehicleServiceRequest::STATUS_COMPLETED ? 'fas fa-check-circle' : 'fas fa-play-circle';
            $statusClass = $nextStatus === VehicleServiceRequest::STATUS_COMPLETED ? 'btn-success' : 'btn-primary';
            $actions .= '<button type="button" class="btn btn-sm '.$statusClass.' update-request-status" data-url="'.e($statusUrl).'" data-status="'.e($nextStatus).'" data-label="'.e($this->statusLabel($nextStatus)).'">'
                .'<i class="'.$statusIcon.' me-1"></i>'.e(__('Mark as :status', ['status' => $this->statusLabel($nextStatus)])).'</button>';
        }

        if ($serviceRequest->canCancel()) {
            $actions .= '<button type="button" class="btn btn-sm btn-outline-danger update-request-status" data-url="'.e($statusUrl).'" data-status="'.e(VehicleServiceRequest::STATUS_CANCELED).'" data-label="'.e(__('Canceled')).'">'
                .'<i class="fas fa-times-circle me-1"></i>'.e(__('Cancel Request')).'</button>';
        }

        return $actions.'</div>';
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            VehicleServiceRequest::STATUS_IN_PROGRESS => __('In Process'),
            VehicleServiceRequest::STATUS_CANCELED => __('Canceled'),
            VehicleServiceRequest::STATUS_COMPLETED => __('Completed'),
            default => __('Pending'),
        };
    }
}
