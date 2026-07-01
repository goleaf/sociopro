<?php

namespace App\Http\Controllers;

use App\ViewModels\BladeViewData;
use Illuminate\Http\Request;

class ModalController extends Controller
{
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
        $page_data = [];
        foreach ($request->all() as $key => $value) {
            $page_data[$key] = $value;
        }

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

        if (($pageData['event_id'] ?? null) !== null) {
            $pageData['event'] = $viewData->event($pageData['event_id']);
        }

        if (($pageData['product_id'] ?? null) !== null) {
            $pageData['product'] = $viewData->product($pageData['product_id']);
            $pageData['productImages'] = $viewData->productImages($pageData['product']);
        }

        if ($viewPath === 'frontend.events.view-all') {
            $pageData['eventGuestRows'] = $viewData->eventGuestRows($pageData['event'] ?? null);
        }

        return $pageData;
    }
}
