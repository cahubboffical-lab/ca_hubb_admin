<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveStartupAdRequest;
use App\Models\StartupAd;
use App\Services\BootstrapTableService;
use App\Services\FileService;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class StartupAdAdminController extends Controller
{
    private const SECTIONS = [
        'startup' => [
            'label' => 'Startup Ads',
            'type' => null,
            'permission_prefix' => 'startup-ad',
        ],
        'inspection' => [
            'label' => 'Inspection Ads',
            'type' => StartupAd::TYPE_INSPECTION,
            'permission_prefix' => 'inspection-ad',
        ],
    ];

    private string $uploadFolder = 'startup-ads';

    public function index(string $section)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noAnyPermissionThenRedirect($this->permissions($config));

        return view('startup_ads.index', compact('config', 'section'));
    }

    public function table(Request $request, string $section)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenSendJson($this->permission($config, 'list'));

        $limit = max(1, min((int) $request->input('limit', 10), 100));
        $offset = max(0, (int) $request->input('offset', 0));
        $sortable = ['id', 'name', 'is_active', 'created_at', 'updated_at'];
        $sort = in_array($request->input('sort'), $sortable, true) ? $request->input('sort') : 'id';
        $order = strtolower((string) $request->input('order')) === 'asc' ? 'asc' : 'desc';

        $query = $this->sectionQuery($config)->with(['creator:id,name', 'updater:id,name']);
        $this->applySearch($query, trim((string) $request->input('search')));

        $total = (clone $query)->count();
        $ads = $query->orderBy($sort, $order)->skip($offset)->take($limit)->get();

        return response()->json([
            'total' => $total,
            'rows' => $ads->map(fn (StartupAd $startupAd) => $this->tableRow($startupAd, $section, $config))->values(),
        ]);
    }

    public function create(string $section)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenRedirect($this->permission($config, 'create'));
        $startupAd = new StartupAd(['type' => $config['type'], 'is_active' => true]);

        return view('startup_ads.create', compact('config', 'section', 'startupAd'));
    }

    public function store(SaveStartupAdRequest $request, string $section)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenRedirect($this->permission($config, 'create'));
        $imagePath = FileService::compressAndUpload($request->file('image'), $this->uploadFolder);
        if (! $imagePath) {
            throw new RuntimeException('Unable to store the startup ad image.');
        }

        StartupAd::create([
            'name' => $request->validated('name'),
            'image' => $imagePath,
            'url' => $request->validated('url'),
            'type' => $config['type'],
            'is_active' => $request->boolean('is_active'),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('startup-ads.index', compact('section'))
            ->with('success', __('Ad created successfully.'));
    }

    public function edit(string $section, int $startupAdId)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenRedirect($this->permission($config, 'update'));
        $startupAd = $this->findForSection($startupAdId, $config);

        return view('startup_ads.edit', compact('config', 'section', 'startupAd'));
    }

    public function update(SaveStartupAdRequest $request, string $section, int $startupAdId)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenRedirect($this->permission($config, 'update'));
        $startupAd = $this->findForSection($startupAdId, $config);
        $data = [
            'name' => $request->validated('name'),
            'url' => $request->validated('url'),
            'is_active' => $request->boolean('is_active'),
            'updated_by' => Auth::id(),
        ];

        if ($request->hasFile('image')) {
            $data['image'] = FileService::compressAndReplace(
                $request->file('image'),
                $this->uploadFolder,
                $startupAd->getRawOriginal('image')
            );
            if (! $data['image']) {
                throw new RuntimeException('Unable to replace the startup ad image.');
            }
        }

        $startupAd->update($data);

        return redirect()->route('startup-ads.index', compact('section'))
            ->with('success', __('Ad updated successfully.'));
    }

    public function toggle(Request $request, string $section, int $startupAdId)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenSendJson($this->permission($config, 'update'));
        $validated = $request->validate(['is_active' => ['required', 'boolean']]);
        $startupAd = $this->findForSection($startupAdId, $config);
        $startupAd->update([
            'is_active' => $validated['is_active'],
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'error' => false,
            'message' => __('Ad status updated successfully.'),
            'data' => ['id' => $startupAd->id, 'is_active' => $startupAd->is_active],
        ]);
    }

    public function destroy(string $section, int $startupAdId)
    {
        $config = $this->sectionConfig($section);
        ResponseService::noPermissionThenSendJson($this->permission($config, 'delete'));
        $startupAd = $this->findForSection($startupAdId, $config);
        $rawImage = $startupAd->getRawOriginal('image');
        $startupAd->delete();
        FileService::delete($rawImage);

        return response()->json(['error' => false, 'message' => __('Ad deleted successfully.')]);
    }

    private function sectionConfig(string $section): array
    {
        abort_unless(array_key_exists($section, self::SECTIONS), 404);

        return self::SECTIONS[$section];
    }

    private function sectionQuery(array $config): Builder
    {
        return StartupAd::query()->when(
            $config['type'] === null,
            fn (Builder $query) => $query->whereNull('type'),
            fn (Builder $query) => $query->where('type', $config['type'])
        );
    }

    private function findForSection(int $id, array $config): StartupAd
    {
        return $this->sectionQuery($config)->findOrFail($id);
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';
        $query->where(fn (Builder $builder) => $builder
            ->where('id', 'LIKE', $like)
            ->orWhere('name', 'LIKE', $like)
            ->orWhere('url', 'LIKE', $like));
    }

    private function tableRow(StartupAd $startupAd, string $section, array $config): array
    {
        $operate = '';
        if (Auth::user()->can($this->permission($config, 'update'))) {
            $operate .= BootstrapTableService::editButton(route('startup-ads.edit', [
                'section' => $section,
                'startupAdId' => $startupAd->id,
            ]));
        }
        if (Auth::user()->can($this->permission($config, 'delete'))) {
            $operate .= BootstrapTableService::deleteButton(route('startup-ads.destroy', [
                'section' => $section,
                'startupAdId' => $startupAd->id,
            ]));
        }

        return [
            'id' => $startupAd->id,
            'name' => e($startupAd->name ?? '-'),
            'image' => $startupAd->image,
            'url_link' => $startupAd->url
                ? '<a href="'.e($startupAd->url).'" target="_blank" rel="noopener noreferrer">'.e($startupAd->url).'</a>'
                : '-',
            'is_active' => $startupAd->is_active,
            'toggle_url' => route('startup-ads.toggle', ['section' => $section, 'startupAdId' => $startupAd->id]),
            'created_at' => $startupAd->created_at?->format('Y-m-d H:i'),
            'created_by' => e($startupAd->creator?->name ?? '-'),
            'updated_at' => $startupAd->updated_at?->format('Y-m-d H:i'),
            'updated_by' => e($startupAd->updater?->name ?? '-'),
            'operate' => $operate,
        ];
    }

    private function permission(array $config, string $action): string
    {
        return $config['permission_prefix'].'-'.$action;
    }

    private function permissions(array $config): array
    {
        return collect(['list', 'create', 'update', 'delete'])
            ->map(fn (string $action) => $this->permission($config, $action))
            ->all();
    }
}
