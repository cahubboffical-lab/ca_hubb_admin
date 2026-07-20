<?php

namespace App\Http\Controllers;

use App\Models\AuctionSheetVerificationPrice;
use App\Models\AuctionSheetVerificationRequest;
use App\Services\CustomerContactService;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AuctionSheetVerificationAdminController extends Controller
{
    private const LIST_PERMISSION = 'auction-sheet-verification-request-list';
    private const UPDATE_PERMISSION = 'auction-sheet-verification-request-update';

    public function index()
    {
        ResponseService::noPermissionThenRedirect(self::LIST_PERMISSION);
        $price = AuctionSheetVerificationPrice::current();

        return view('auction_sheet_verification.index', compact('price'));
    }

    public function table(Request $request)
    {
        ResponseService::noPermissionThenSendJson(self::LIST_PERMISSION);
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(AuctionSheetVerificationRequest::statuses())],
        ]);

        $limit = max(1, min((int) $request->input('limit', 10), 100));
        $offset = max(0, (int) $request->input('offset', 0));
        $sort = in_array($request->input('sort'), ['id', 'chassis_number', 'phone_number', 'price_amount', 'created_at', 'completed_at'], true)
            ? $request->input('sort')
            : 'id';
        $order = strtolower((string) $request->input('order')) === 'asc' ? 'asc' : 'desc';
        $status = $validated['status'] ?? AuctionSheetVerificationRequest::STATUS_PENDING;

        $query = AuctionSheetVerificationRequest::query()->where('status', $status);
        $this->applySearch($query, trim((string) $request->input('search')));

        $total = (clone $query)->count();
        $requests = $query->orderBy($sort, $order)->skip($offset)->take($limit)->get();

        return response()->json([
            'total' => $total,
            'rows' => $requests->map(fn (AuctionSheetVerificationRequest $verificationRequest) => $this->tableRow($verificationRequest))->values(),
        ]);
    }

    public function show(AuctionSheetVerificationRequest $auctionSheetVerificationRequest)
    {
        ResponseService::noPermissionThenSendJson(self::LIST_PERMISSION);
        $auctionSheetVerificationRequest->load('user:id,name,email,mobile');

        return response()->json([
            'error' => false,
            'data' => [
                'id' => $auctionSheetVerificationRequest->id,
                'chassis_number' => $auctionSheetVerificationRequest->chassis_number,
                'phone_number' => $auctionSheetVerificationRequest->phone_number,
                'status' => $auctionSheetVerificationRequest->status,
                'status_label' => $this->statusLabel($auctionSheetVerificationRequest->status),
                'notification_status' => $auctionSheetVerificationRequest->notification_status,
                'report_url' => $auctionSheetVerificationRequest->report_url,
                'admin_notes' => $auctionSheetVerificationRequest->admin_notes,
                'price_amount' => $auctionSheetVerificationRequest->price_amount,
                'currency_code' => $auctionSheetVerificationRequest->currency_code,
                'notified_at' => $auctionSheetVerificationRequest->notified_at?->format('Y-m-d H:i'),
                'completed_at' => $auctionSheetVerificationRequest->completed_at?->format('Y-m-d H:i'),
                'created_at' => $auctionSheetVerificationRequest->created_at?->format('Y-m-d H:i'),
                'user' => $auctionSheetVerificationRequest->user ? [
                    'id' => $auctionSheetVerificationRequest->user->id,
                    'name' => $auctionSheetVerificationRequest->user->name,
                    'email' => $auctionSheetVerificationRequest->user->email,
                    'mobile' => $auctionSheetVerificationRequest->user->mobile,
                ] : null,
            ],
        ]);
    }

    public function complete(AuctionSheetVerificationRequest $auctionSheetVerificationRequest)
    {
        ResponseService::noPermissionThenSendJson(self::UPDATE_PERMISSION);

        if ($auctionSheetVerificationRequest->status !== AuctionSheetVerificationRequest::STATUS_PENDING) {
            return response()->json([
                'error' => true,
                'message' => __('Only pending requests can be marked as completed.'),
            ], 422);
        }

        $auctionSheetVerificationRequest->update([
            'status' => AuctionSheetVerificationRequest::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return response()->json([
            'error' => false,
            'message' => __('Auction sheet verification request marked as completed.'),
        ]);
    }

    public function cancel(AuctionSheetVerificationRequest $auctionSheetVerificationRequest)
    {
        ResponseService::noPermissionThenSendJson(self::UPDATE_PERMISSION);

        if ($auctionSheetVerificationRequest->status !== AuctionSheetVerificationRequest::STATUS_PENDING) {
            return response()->json([
                'error' => true,
                'message' => __('Only pending requests can be canceled.'),
            ], 422);
        }

        $auctionSheetVerificationRequest->update([
            'status' => AuctionSheetVerificationRequest::STATUS_CANCELED,
            'completed_at' => null,
        ]);

        return response()->json([
            'error' => false,
            'message' => __('Auction sheet verification request canceled.'),
        ]);
    }

    public function updatePrice(Request $request)
    {
        ResponseService::noPermissionThenSendJson(self::UPDATE_PERMISSION);
        $validated = $request->validate([
            'price_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
        ]);

        $price = AuctionSheetVerificationPrice::current();
        $price->update([
            'price_amount' => $validated['price_amount'],
            'currency_code' => 'PKR',
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'error' => false,
            'message' => __('Auction sheet verification price updated successfully.'),
            'data' => [
                'price_amount' => $price->price_amount,
                'currency_code' => $price->currency_code,
            ],
        ]);
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';
        $query->where(fn (Builder $builder) => $builder
            ->where('id', 'LIKE', $like)
            ->orWhere('chassis_number', 'LIKE', $like)
            ->orWhere('phone_number', 'LIKE', $like));
    }

    private function tableRow(AuctionSheetVerificationRequest $verificationRequest): array
    {
        return [
            'id' => $verificationRequest->id,
            'chassis_number' => e($verificationRequest->chassis_number),
            'phone_number' => e($verificationRequest->phone_number),
            'price' => e(trim(($verificationRequest->currency_code ?? 'PKR').' '.($verificationRequest->price_amount ?? '-'))),
            'notification_status' => e(ucfirst($verificationRequest->notification_status)),
            'created_at' => $verificationRequest->created_at?->format('Y-m-d H:i'),
            'completed_at' => $verificationRequest->completed_at?->format('Y-m-d H:i') ?? '-',
            'operate' => $this->renderActions($verificationRequest),
        ];
    }

    private function renderActions(AuctionSheetVerificationRequest $verificationRequest): string
    {
        $showUrl = route('auction-sheet-verification.show', $verificationRequest);
        $completeUrl = route('auction-sheet-verification.complete', $verificationRequest);
        $cancelUrl = route('auction-sheet-verification.cancel', $verificationRequest);
        $phone = CustomerContactService::phoneUri($verificationRequest->phone_number);
        $whatsAppUrl = CustomerContactService::whatsAppUrl(
            $verificationRequest->phone_number,
            __('Hello, we are contacting you regarding auction sheet verification for chassis :chassis on CA Hubb.', [
                'chassis' => $verificationRequest->chassis_number,
            ])
        );

        $actions = '<div class="auction-verification-actions">'
            .'<button type="button" class="btn btn-sm btn-outline-primary view-auction-request" data-url="'.e($showUrl).'">'
            .'<i class="fas fa-eye me-1"></i>'.e(__('View')).'</button>'
            .'<a class="btn btn-sm btn-outline-success" href="tel:'.e($phone).'"><i class="fas fa-phone-alt me-1"></i>'.e(__('Call')).'</a>'
            .'<a class="btn btn-sm btn-outline-info" href="sms:'.e($phone).'"><i class="fas fa-comment-alt me-1"></i>'.e(__('SMS')).'</a>'
            .'<a class="btn btn-sm btn-success" href="'.e($whatsAppUrl).'" target="_blank" rel="noopener noreferrer">'
            .'<i class="fab fa-whatsapp me-1"></i>'.e(__('WhatsApp')).'</a>';

        if ($verificationRequest->status === AuctionSheetVerificationRequest::STATUS_PENDING && Auth::user()->can(self::UPDATE_PERMISSION)) {
            $actions .= '<button type="button" class="btn btn-sm btn-primary complete-auction-request" data-url="'.e($completeUrl).'">'
                .'<i class="fas fa-check-circle me-1"></i>'.e(__('Mark as Completed')).'</button>';
            $actions .= '<button type="button" class="btn btn-sm btn-outline-danger cancel-auction-request" data-url="'.e($cancelUrl).'">'
                .'<i class="fas fa-times-circle me-1"></i>'.e(__('Cancel Request')).'</button>';
        }

        return $actions.'</div>';
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            AuctionSheetVerificationRequest::STATUS_CANCELED => __('Canceled'),
            AuctionSheetVerificationRequest::STATUS_COMPLETED => __('Completed'),
            default => __('Pending'),
        };
    }
}
