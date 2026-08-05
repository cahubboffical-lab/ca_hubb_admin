<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\News;
use App\Services\BootstrapTableService;
use App\Services\FileService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class NewsController extends Controller
{
    private string $uploadFolder;

    public function __construct()
    {
        $this->uploadFolder = 'news';
    }

    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['news-list', 'news-create', 'news-update', 'news-delete']);

        return view('news.index');
    }

    public function create()
    {
        ResponseService::noPermissionThenRedirect('news-create');
        $cities = City::orderBy('name')->get();

        return view('news.create', compact('cities'));
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('news-create');

        $validated = $request->validate([
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'cover_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:7168'],
            'english_html' => ['required', 'string'],
            'urdu_html' => ['required', 'string'],
        ]);

        try {
            DB::beginTransaction();

            News::create([
                'city_id' => $validated['city_id'],
                'cover_image' => FileService::compressAndUpload($request->file('cover_image'), $this->uploadFolder),
                'english_html' => $validated['english_html'],
                'urdu_html' => $validated['urdu_html'],
                'created_by' => Auth::id(),
            ]);

            DB::commit();
            ResponseService::successRedirectResponse('News Added Successfully', route('news.index'));
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorRedirect($th, 'NewsController->store');
            ResponseService::errorRedirectResponse();
        }
    }

    public function show(Request $request, $id = null)
    {
        ResponseService::noPermissionThenSendJson('news-list');

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');

        $sql = News::with(['city:id,name', 'creator:id,name', 'updater:id,name']);

        if (! empty($request->search)) {
            $sql = $sql->search($request->search);
        }

        $total = $sql->count();
        $result = $sql->sort($sort, $order)->skip($offset)->take($limit)->get();

        $rows = [];
        foreach ($result as $index => $row) {
            $operate = '';
            if (Auth::user()->can('news-update')) {
                $operate .= BootstrapTableService::editButton(route('news.edit', $row->id));
            }
            if (Auth::user()->can('news-delete')) {
                $operate .= BootstrapTableService::deleteButton(route('news.destroy', $row->id));
            }

            $tempRow = $row->toArray();
            $tempRow['no'] = $offset + $index + 1;
            $tempRow['city_name'] = $row->city?->translated_name ?? $row->city?->name ?? '-';
            $tempRow['created_by_name'] = $row->creator?->name ?? '-';
            $tempRow['updated_by_name'] = $row->updater?->name ?? '-';
            $tempRow['english_html'] = Str::limit(strip_tags($row->english_html), 120);
            $tempRow['urdu_html'] = Str::limit(strip_tags($row->urdu_html), 120);
            $tempRow['created_at'] = Carbon::parse($row->created_at)->format('d-m-y H:i:s');
            $tempRow['updated_at'] = Carbon::parse($row->updated_at)->format('d-m-y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }

    public function edit($id)
    {
        ResponseService::noPermissionThenRedirect('news-update');

        $news = News::findOrFail($id);
        $cities = City::orderBy('name')->get();

        return view('news.edit', compact('news', 'cities'));
    }

    public function update(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('news-update');

        $news = News::findOrFail($id);

        $validated = $request->validate([
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:7168'],
            'english_html' => ['required', 'string'],
            'urdu_html' => ['required', 'string'],
        ]);

        try {
            DB::beginTransaction();

            $data = [
                'city_id' => $validated['city_id'],
                'english_html' => $validated['english_html'],
                'urdu_html' => $validated['urdu_html'],
                'updated_by' => Auth::id(),
            ];

            if ($request->hasFile('cover_image')) {
                $data['cover_image'] = FileService::compressAndReplace(
                    $request->file('cover_image'),
                    $this->uploadFolder,
                    $news->getRawOriginal('cover_image')
                );
            }

            $news->update($data);

            DB::commit();
            ResponseService::successRedirectResponse('News Updated Successfully', route('news.index'));
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorRedirect($th, 'NewsController->update');
            ResponseService::errorRedirectResponse('Something Went Wrong');
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('news-delete');

        try {
            $news = News::findOrFail($id);
            FileService::delete($news->getRawOriginal('cover_image'));
            $news->delete();

            ResponseService::successResponse('News deleted successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'NewsController->destroy');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }
}
