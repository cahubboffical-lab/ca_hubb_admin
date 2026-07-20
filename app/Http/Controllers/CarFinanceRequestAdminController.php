<?php

namespace App\Http\Controllers;

use App\Models\CarFinanceRequest;
use App\Services\CustomerContactService;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CarFinanceRequestAdminController extends Controller
{
    public function index()
    {
        ResponseService::noPermissionThenRedirect('car-finance-request-list');

        return view('car_finance.requests.index');
    }

    public function table(Request $request)
    {
        ResponseService::noPermissionThenSendJson('car-finance-request-list');
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(CarFinanceRequest::statuses())],
        ]);
        $limit = max(1, min((int) $request->input('limit', 10), 100));
        $offset = max(0, (int) $request->input('offset', 0));
        $sortable = ['id', 'finance_type', 'vehicle_price', 'tenure_years', 'down_payment_percent', 'status', 'created_at', 'completed_at'];
        $sort = in_array($request->input('sort'), $sortable, true) ? $request->input('sort') : 'id';
        $order = strtolower((string) $request->input('order')) === 'asc' ? 'asc' : 'desc';

        $query = CarFinanceRequest::query()
            ->with([
                'user:id,name,email,mobile,country_code',
                'bank:id,name',
                'city:id,name',
                'carModel:id,name,brand_name',
            ])
            ->where('status', $validated['status'] ?? CarFinanceRequest::STATUS_PENDING);

        $this->applySearch($query, trim((string) $request->input('search')));
        $total = (clone $query)->count();
        $requests = $query->orderBy($sort, $order)->skip($offset)->take($limit)->get();

        return response()->json([
            'total' => $total,
            'rows' => $requests->map(fn (CarFinanceRequest $financeRequest) => $this->tableRow($financeRequest))->values(),
        ]);
    }

    public function show(CarFinanceRequest $carFinanceRequest)
    {
        ResponseService::noPermissionThenSendJson('car-finance-request-list');
        $carFinanceRequest->load([
            'user:id,name,email,mobile,country_code',
            'bank:id,code,name',
            'city:id,name',
            'carModel:id,name,brand_name',
        ]);

        return response()->json(['error' => false, 'data' => $this->detailData($carFinanceRequest)]);
    }

    public function updateStatus(Request $request, CarFinanceRequest $carFinanceRequest)
    {
        ResponseService::noPermissionThenSendJson('car-finance-request-update');
        $validated = $request->validate([
            'status' => ['required', Rule::in(CarFinanceRequest::statuses())],
        ]);
        $targetStatus = $validated['status'];
        $allowed = $targetStatus === CarFinanceRequest::STATUS_CANCELED
            ? $carFinanceRequest->canCancel()
            : $targetStatus === $carFinanceRequest->nextStatus();

        if (! $allowed) {
            return response()->json([
                'error' => true,
                'message' => __('This status change is not allowed.'),
            ], 422);
        }

        $carFinanceRequest->update([
            'status' => $targetStatus,
            'completed_at' => $targetStatus === CarFinanceRequest::STATUS_COMPLETED ? now() : null,
            'canceled_at' => $targetStatus === CarFinanceRequest::STATUS_CANCELED ? now() : null,
        ]);

        return response()->json([
            'error' => false,
            'message' => __('Finance request status updated successfully.'),
        ]);
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';
        $query->where(function (Builder $builder) use ($like) {
            $builder->where('id', 'LIKE', $like)
                ->orWhere('finance_type', 'LIKE', $like)
                ->orWhereHas('user', fn (Builder $relation) => $relation
                    ->where('name', 'LIKE', $like)
                    ->orWhere('email', 'LIKE', $like)
                    ->orWhere('mobile', 'LIKE', $like))
                ->orWhereHas('bank', fn (Builder $relation) => $relation->where('name', 'LIKE', $like))
                ->orWhereHas('city', fn (Builder $relation) => $relation->where('name', 'LIKE', $like))
                ->orWhereHas('carModel', fn (Builder $relation) => $relation
                    ->where('name', 'LIKE', $like)
                    ->orWhere('brand_name', 'LIKE', $like));
        });
    }

    private function tableRow(CarFinanceRequest $request): array
    {
        return [
            'id' => $request->id,
            'customer' => e($request->user?->name ?? '-'),
            'phone' => e($this->customerPhone($request)),
            'bank' => e($request->bank?->name ?? '-'),
            'city' => e($request->city?->translated_name ?? $request->city?->name ?? '-'),
            'car' => e(trim(($request->carModel?->brand_name ?? '').' '.($request->carModel?->name ?? '')) ?: '-'),
            'finance_type' => e($request->finance_type === CarFinanceRequest::TYPE_USED ? __('Used Car') : __('New Car')),
            'vehicle_price' => number_format($request->vehicle_price),
            'tenure_years' => $request->tenure_years,
            'down_payment_percent' => rtrim(rtrim($request->down_payment_percent, '0'), '.').'%',
            'monthly_installment' => number_format($request->monthly_installment),
            'created_at' => $request->created_at?->format('Y-m-d H:i'),
            'operate' => $this->renderActions($request),
        ];
    }

    private function renderActions(CarFinanceRequest $request): string
    {
        $showUrl = route('car-finance-requests.show', $request);
        $statusUrl = route('car-finance-requests.update-status', $request);
        $phone = $this->customerPhone($request);
        $actions = '<div class="car-finance-request-actions">'
            .'<button type="button" class="btn btn-sm btn-outline-primary view-finance-request" data-url="'.e($showUrl).'"><i class="fas fa-eye me-1"></i>'.e(__('View')).'</button>';

        if ($phone !== '') {
            $phoneUri = CustomerContactService::phoneUri($phone);
            $whatsAppUrl = CustomerContactService::whatsAppUrl($phone, __('Hello :name, we are contacting you regarding your car finance request on CA Hubb.', [
                'name' => $request->user?->name ?? __('Customer'),
            ]));
            $actions .= '<a class="btn btn-sm btn-outline-success" href="tel:'.e($phoneUri).'"><i class="fas fa-phone-alt me-1"></i>'.e(__('Call')).'</a>'
                .'<a class="btn btn-sm btn-outline-info" href="sms:'.e($phoneUri).'"><i class="fas fa-comment-alt me-1"></i>'.e(__('SMS')).'</a>'
                .'<a class="btn btn-sm btn-success" href="'.e($whatsAppUrl).'" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp me-1"></i>'.e(__('WhatsApp')).'</a>';
        }

        if (Auth::user()->can('car-finance-request-update')) {
            $nextStatus = $request->nextStatus();
            if ($nextStatus !== null) {
                $label = $this->statusLabel($nextStatus);
                $class = $nextStatus === CarFinanceRequest::STATUS_COMPLETED ? 'btn-success' : 'btn-primary';
                $actions .= '<button type="button" class="btn btn-sm '.$class.' update-finance-status" data-url="'.e($statusUrl).'" data-status="'.e($nextStatus).'" data-label="'.e($label).'">'
                    .'<i class="fas fa-arrow-right me-1"></i>'.e(__('Mark as :status', ['status' => $label])).'</button>';
            }
            if ($request->canCancel()) {
                $actions .= '<button type="button" class="btn btn-sm btn-outline-danger update-finance-status" data-url="'.e($statusUrl).'" data-status="canceled" data-label="'.e(__('Canceled')).'">'
                    .'<i class="fas fa-times-circle me-1"></i>'.e(__('Cancel Request')).'</button>';
            }
        }

        return $actions.'</div>';
    }

    private function detailData(CarFinanceRequest $request): array
    {
        return [
            'id' => $request->id,
            'status' => $request->status,
            'status_label' => $this->statusLabel($request->status),
            'user' => $request->user ? [
                'id' => $request->user->id,
                'name' => $request->user->name,
                'email' => $request->user->email,
                'phone' => $this->customerPhone($request),
            ] : null,
            'bank' => $request->bank ? ['id' => $request->bank->id, 'code' => $request->bank->code, 'name' => $request->bank->name] : null,
            'city' => $request->city?->translated_name ?? $request->city?->name,
            'car' => $request->carModel ? trim(($request->carModel->brand_name ?? '').' '.$request->carModel->name) : null,
            'finance_type' => $request->finance_type,
            'model_year' => $request->model_year,
            'car_variant' => $request->car_variant,
            'used_car_price' => $request->used_car_price,
            'vehicle_price' => $request->vehicle_price,
            'price_source' => $request->price_source,
            'tenure_years' => $request->tenure_years,
            'down_payment_percent' => $request->down_payment_percent,
            'finance_rate' => $request->finance_rate,
            'insurance_rate' => $request->insurance_rate,
            'processing_fee' => $request->processing_fee,
            'down_payment_amount' => $request->down_payment_amount,
            'bank_loan' => $request->bank_loan,
            'first_year_insurance' => $request->first_year_insurance,
            'monthly_installment' => $request->monthly_installment,
            'total_initial_deposit' => $request->total_initial_deposit,
            'admin_notes' => $request->admin_notes,
            'created_at' => $request->created_at?->format('Y-m-d H:i'),
            'completed_at' => $request->completed_at?->format('Y-m-d H:i'),
            'canceled_at' => $request->canceled_at?->format('Y-m-d H:i'),
        ];
    }

    private function customerPhone(CarFinanceRequest $request): string
    {
        return trim((string) ($request->user?->country_code ?? '').(string) ($request->user?->mobile ?? ''));
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            CarFinanceRequest::STATUS_IN_PROGRESS => __('In Process'),
            CarFinanceRequest::STATUS_CANCELED => __('Canceled'),
            CarFinanceRequest::STATUS_COMPLETED => __('Completed'),
            default => __('Pending'),
        };
    }
}
