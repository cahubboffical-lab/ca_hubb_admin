<?php

namespace Database\Seeders;

use App\Models\CarInspectionRequest;
use App\Models\CarModel;
use App\Models\City;
use App\Models\SellForMeRequest;
use App\Models\ServicePackage;
use App\Models\ServiceRequest;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use RuntimeException;

class ServiceRequestDemoSeeder extends Seeder
{
    public function run(): void
    {
        $city = City::query()->first();
        $carModel = CarModel::query()->first();

        if (! $city || ! $carModel) {
            throw new RuntimeException('A city and car model are required before seeding service request demo data.');
        }

        $inspectionPackage = ServicePackage::query()->where('type', ServicePackage::TYPE_CAR_INSPECTION)->first();
        $sellForMePackage = ServicePackage::query()->where('type', ServicePackage::TYPE_SELL_FOR_ME)->first();
        $visitDate = Carbon::now('Asia/Karachi')->addDays(2)->toDateString();

        foreach ($this->statuses() as $index => $status) {
            CarInspectionRequest::query()->updateOrCreate(
                ['phone_number' => '+9230011100'.($index + 1), 'visit_date' => $visitDate],
                $this->commonData($city->id, $carModel->id, $inspectionPackage?->id, $status, $index, 'Inspection Customer')
            );

            SellForMeRequest::query()->updateOrCreate(
                ['phone_number' => '+9230022200'.($index + 1), 'visit_date' => $visitDate],
                $this->commonData($city->id, $carModel->id, $sellForMePackage?->id, $status, $index, 'Selling Customer') + [
                    'registration_area' => ['Punjab', 'Sindh', 'KPK'][$index],
                ]
            );
        }
    }

    private function statuses(): array
    {
        return [
            ServiceRequest::STATUS_PENDING,
            ServiceRequest::STATUS_IN_PROGRESS,
            ServiceRequest::STATUS_COMPLETED,
        ];
    }

    private function commonData(int $cityId, int $carModelId, ?int $packageId, string $status, int $index, string $name): array
    {
        $startHour = 10 + $index;

        return [
            'user_id' => null,
            'service_package_id' => $packageId,
            'full_name' => $name.' '.($index + 1),
            'city_id' => $cityId,
            'car_model_id' => $carModelId,
            'model_year' => Carbon::now('Asia/Karachi')->year - $index,
            'car_variant' => ['GLX Automatic', 'Grande 1.8 CVT', 'Premium AWD'][$index],
            'car_condition' => $index === 2 ? 'new' : 'used',
            'visit_area' => ['DHA Phase 6', 'Gulberg III', 'Bahria Town'][$index],
            'visit_start_time' => sprintf('%02d:00:00', $startHour),
            'visit_end_time' => sprintf('%02d:00:00', $startHour + 1),
            'status' => $status,
        ];
    }
}
