<?php

namespace App\Http\Controllers;

use App\Enums\MediaFileType;
use App\Models\Marketplace;
use App\Models\MediaFile;
use App\Models\Page;
use App\Models\PageCategory;
use App\ViewModels\BladeViewData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ModalController extends Controller
{
    private const VIEW_DATA_KEYS = [
        'event_id',
        'group_id',
        'id',
        'image',
        'language',
        'page_id',
        'post_id',
        'product_id',
        'profile_id',
    ];

    private $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth()->user();

            return $next($request);
        });
    }

    public function common_view_function($view_path, Request $request)
    {
        $page_data = $request->only(self::VIEW_DATA_KEYS);

        return view($view_path, $this->modalViewData($view_path, $page_data));
    }

    public function common_view_function2($view_path = '', $page_all_data = '')
    {
        $page_data = [];

        if ($page_all_data != '') {
            $page_data_arrs = explode(',', $page_all_data);
            foreach ($page_data_arrs as $page_data_vals) {
                $page_data_arr = explode('->', $page_data_vals);
                $page_data[$page_data_arr[0]] = $page_data_arr[1];
            }
        }

        return view($view_path, $page_data);
    }

    private function modalViewData(string $viewPath, array $pageData): array
    {
        $viewData = app(BladeViewData::class);

        if (in_array($viewPath, [
            'frontend.pages.edit-modal',
            'frontend.pages.edit-cover-photo',
            'frontend.pages.edit-page-info',
        ], true)) {
            $pageData['page'] = Page::query()->findOrFail((int) ($pageData['page_id'] ?? 0));
            Gate::authorize('update', $pageData['page']);
        }

        if (in_array($viewPath, [
            'frontend.pages.create_page',
            'frontend.pages.edit-modal',
        ], true)) {
            $pageData['pageCategories'] = PageCategory::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get();
        }

        if (($pageData['event_id'] ?? null) !== null) {
            $pageData['event'] = $viewData->event($pageData['event_id']);
        }

        if (($pageData['product_id'] ?? null) !== null) {
            $pageData['product'] = $viewPath === 'frontend.marketplace.edit_product'
                ? Marketplace::findOrFail($pageData['product_id'])
                : Marketplace::find($pageData['product_id']);

            if ($viewPath === 'frontend.marketplace.edit_product') {
                Gate::authorize('update', $pageData['product']);
            }

            $pageData['productImages'] = $pageData['product'] === null
                ? collect()
                : MediaFile::query()
                    ->where('product_id', $pageData['product']->id)
                    ->ofType(MediaFileType::Image)
                    ->get();
        }

        if ($viewPath === 'frontend.events.view-all') {
            $pageData['eventGuestRows'] = $viewData->eventGuestRows($pageData['event'] ?? null);
        }

        return $pageData;
    }
}
