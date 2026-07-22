<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionSheetVerificationRequest;
use App\Models\CarFinanceRequest;
use App\Models\CarInspectionRequest;
use App\Models\CarOwnershipRequest;
use App\Models\CarRegistrationRequest;
use App\Models\SellForMeRequest;
use App\Models\ServiceRequest;
use App\Models\VehicleServiceRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MyServiceBookingController extends Controller
{
    private const TYPES = [
        'car_inspection',
        'sell_for_me',
        'car_registration',
        'car_ownership_transfer',
        'auction_sheet_verification',
        'car_finance',
    ];

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'type' => ['nullable', Rule::in(self::TYPES)],
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'canceled', 'completed'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => __('The given data was invalid.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $filters = $validator->validated();
        $userId = $request->user()->id;
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 15);

        $bookings = collect()
            ->concat($this->appointmentBookings(CarInspectionRequest::class, $userId, 'car_inspection', __('Car Inspection')))
            ->concat($this->appointmentBookings(SellForMeRequest::class, $userId, 'sell_for_me', __('Sell It For Me')))
            ->concat($this->vehicleBookings(CarRegistrationRequest::class, $userId, 'car_registration', __('Car Registration')))
            ->concat($this->vehicleBookings(CarOwnershipRequest::class, $userId, 'car_ownership_transfer', __('Car Ownership Transfer')))
            ->concat($this->auctionBookings($userId))
            ->concat($this->financeBookings($userId));

        if (isset($filters['type'])) {
            $bookings = $bookings->where('type', $filters['type']);
        }
        if (isset($filters['status'])) {
            $bookings = $bookings->where('status', $filters['status']);
        }

        $bookings = $bookings
            ->sortByDesc(static fn (array $booking) => $booking['created_at'] ?? '')
            ->values();

        $total = $bookings->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $items = $bookings->forPage($page, $perPage)->values();

        return response()->json([
            'error' => false,
            'message' => __('Service bookings fetched successfully.'),
            'data' => [
                'bookings' => $items,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => $lastPage,
                    'has_more' => $page < $lastPage,
                ],
            ],
        ]);
    }

    /** @param class-string<ServiceRequest> $modelClass */
    private function appointmentBookings(
        string $modelClass,
        int $userId,
        string $type,
        string $title
    ): Collection {
        return $modelClass::query()
            ->where('user_id', $userId)
            ->latest('created_at')
            ->get()
            ->map(function (ServiceRequest $booking) use ($type, $title): array {
                return $this->commonData($booking, $type, $title) + [
                    'schedule' => [
                        'date' => $booking->visit_date?->format('Y-m-d'),
                        'start_time' => $booking->visit_start_time,
                        'end_time' => $booking->visit_end_time,
                    ],
                    'details' => [
                        'service_package_id' => $booking->service_package_id,
                        'city_id' => $booking->city_id,
                        'car_model_id' => $booking->car_model_id,
                        'model_year' => $booking->model_year,
                        'car_variant' => $booking->car_variant,
                        'car_condition' => $booking->car_condition,
                        'visit_area' => $booking->visit_area,
                        'registration_area' => $type === 'sell_for_me'
                            ? $booking->registration_area
                            : null,
                    ],
                ];
            });
    }

    /** @param class-string<VehicleServiceRequest> $modelClass */
    private function vehicleBookings(
        string $modelClass,
        int $userId,
        string $type,
        string $title
    ): Collection {
        return $modelClass::query()
            ->where('user_id', $userId)
            ->latest('created_at')
            ->get()
            ->map(function (VehicleServiceRequest $booking) use ($type, $title): array {
                return $this->commonData($booking, $type, $title) + [
                    'schedule' => null,
                    'details' => [
                        'is_filer' => $booking->is_filer,
                        'car_model_id' => $booking->car_model_id,
                        'model_year' => $booking->model_year,
                        'car_variant' => $booking->car_variant,
                        'registration_place' => $booking->registration_place,
                    ],
                ];
            });
    }

    private function auctionBookings(int $userId): Collection
    {
        return AuctionSheetVerificationRequest::query()
            ->where('user_id', $userId)
            ->latest('created_at')
            ->get()
            ->map(function (AuctionSheetVerificationRequest $booking): array {
                return $this->commonData(
                    $booking,
                    'auction_sheet_verification',
                    __('Auction Sheet Verification')
                ) + [
                    'schedule' => null,
                    'details' => [
                        'chassis_number' => $booking->chassis_number,
                        'notification_status' => $booking->notification_status,
                        'price_amount' => $booking->price_amount,
                        'currency_code' => $booking->currency_code,
                    ],
                ];
            });
    }

    private function financeBookings(int $userId): Collection
    {
        return CarFinanceRequest::query()
            ->with('bank:id,code,name')
            ->where('user_id', $userId)
            ->latest('created_at')
            ->get()
            ->map(function (CarFinanceRequest $booking): array {
                return $this->commonData($booking, 'car_finance', __('Car Finance')) + [
                    'schedule' => null,
                    'details' => [
                        'finance_type' => $booking->finance_type,
                        'city_id' => $booking->city_id,
                        'car_model_id' => $booking->car_model_id,
                        'bank' => $booking->bank ? [
                            'id' => $booking->bank->id,
                            'code' => $booking->bank->code,
                            'name' => $booking->bank->name,
                        ] : null,
                        'vehicle_price' => $booking->vehicle_price,
                        'tenure_years' => $booking->tenure_years,
                        'down_payment_percent' => (float) $booking->down_payment_percent,
                        'monthly_installment' => $booking->monthly_installment,
                        'currency_code' => 'PKR',
                    ],
                ];
            });
    }

    private function commonData(Model $booking, string $type, string $title): array
    {
        return [
            'booking_key' => $type.':'.$booking->id,
            'id' => $booking->id,
            'type' => $type,
            'title' => $title,
            'status' => $booking->status,
            'created_at' => $booking->created_at?->toIso8601String(),
            'updated_at' => $booking->updated_at?->toIso8601String(),
        ];
    }
}
