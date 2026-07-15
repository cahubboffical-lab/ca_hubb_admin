<?php

namespace App\Services;

use App\Models\CarModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CarModelCsvService
{
    private const REQUIRED_COLUMNS = ['name', 'brand_name'];

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'id',
                'name',
                'brand_name',
                'price',
                'created_at',
                'created_by',
                'updated_at',
                'updated_by',
            ], ',', '"', '');

            foreach (CarModel::orderBy('brand_name')->orderBy('name')->cursor() as $carModel) {
                fputcsv($output, [
                    $carModel->id,
                    $carModel->name,
                    $carModel->brand_name,
                    $carModel->price,
                    $carModel->created_at?->toISOString(),
                    $carModel->created_by,
                    $carModel->updated_at?->toISOString(),
                    $carModel->updated_by,
                ], ',', '"', '');
            }

            fclose($output);
        }, 'car-models-'.now()->format('Y-m-d-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(UploadedFile $file, int $userId): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw new InvalidArgumentException(__('The CSV file could not be opened.'));
        }

        try {
            $header = fgetcsv($handle, null, ',', '"', '');
            if ($header === false) {
                throw new InvalidArgumentException(__('The CSV file is empty.'));
            }

            $header = array_map($this->normalizeHeader(...), $header);
            $missingColumns = array_diff(self::REQUIRED_COLUMNS, $header);
            if ($missingColumns !== []) {
                throw new InvalidArgumentException(
                    __('Missing required CSV columns: :columns', ['columns' => implode(', ', $missingColumns)])
                );
            }

            $records = $this->readRecords($handle, $header);
        } finally {
            fclose($handle);
        }

        return DB::transaction(function () use ($records, $userId) {
            $created = 0;
            $updated = 0;

            foreach ($records as $record) {
                $carModel = CarModel::firstOrNew([
                    'name' => $record['name'],
                    'brand_name' => $record['brand_name'],
                ]);

                $carModel->price = $record['price'];
                if ($carModel->exists) {
                    $carModel->updated_by = $userId;
                    $updated++;
                } else {
                    $carModel->created_by = $userId;
                    $created++;
                }
                $carModel->save();
            }

            return ['created' => $created, 'updated' => $updated, 'total' => count($records)];
        });
    }

    private function readRecords($handle, array $header): array
    {
        $records = [];
        $line = 1;

        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $line++;
            if ($this->isEmptyRow($row)) {
                continue;
            }

            if (count($row) !== count($header)) {
                throw new InvalidArgumentException(__('CSV row :line has an incorrect number of columns.', ['line' => $line]));
            }

            $record = array_combine($header, $row);
            $name = trim((string) ($record['name'] ?? ''));
            $brandName = trim((string) ($record['brand_name'] ?? ''));
            $price = trim((string) ($record['price'] ?? ''));

            if ($name === '' || $brandName === '') {
                throw new InvalidArgumentException(__('CSV row :line requires name and brand_name.', ['line' => $line]));
            }
            if (mb_strlen($name) > 255 || mb_strlen($brandName) > 255) {
                throw new InvalidArgumentException(__('CSV row :line contains a name longer than 255 characters.', ['line' => $line]));
            }
            if ($price !== '' && (filter_var($price, FILTER_VALIDATE_INT) === false || (int) $price < 0)) {
                throw new InvalidArgumentException(__('CSV row :line price must be an integer of zero or greater.', ['line' => $line]));
            }

            $records[] = [
                'name' => $name,
                'brand_name' => $brandName,
                'price' => $price === '' ? null : (int) $price,
            ];
        }

        if ($records === []) {
            throw new InvalidArgumentException(__('The CSV file does not contain any car models.'));
        }

        return $records;
    }

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);

        return strtolower(str_replace([' ', '-'], '_', trim($header)));
    }

    private function isEmptyRow(array $row): bool
    {
        return count(array_filter($row, static fn ($value) => trim((string) $value) !== '')) === 0;
    }
}
