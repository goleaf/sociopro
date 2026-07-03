<?php

namespace App\Http\Controllers;

use App\Models\Language;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function language()
    {
        $page_data['languages'] = Language::select('name')->groupBy('name')->get();
        $page_data['view_path'] = 'language.lang';

        return view('backend.index', $page_data);
    }

    public function language_add(Request $request)
    {
        $lang = new Language;
        $lang->name = $request->language;
        $lang->phrase = $request->language;
        $lang->translated = $request->language;
        $lang->save();
        flash()->addSuccess('Language Added  Successfully');

        return redirect()->back();
    }

    public function language_update(Request $request, $language)
    {
        Language::query()
            ->where('name', $language)
            ->update(['name' => strtolower((string) $request->language)]);
        flash()->addSuccess('Language Updated  Successfully');

        return redirect()->route('admin.language.settings');
    }

    public function edit_phrase($language)
    {
        $page_data['all_phrase'] = Language::where('name', $language)->get();
        $page_data['view_path'] = 'language.phrase_list';

        return view('backend.index', $page_data);
    }

    public function update_phrase(Request $request, $id)
    {
        $row = Language::query()->findOrFail($id);
        $translated = $request->input('translated');
        $phraseRequiresPlaceholder = str_contains((string) $row->phrase, '____');
        $translationHasPlaceholder = str_contains((string) $translated, '____');

        if (! $phraseRequiresPlaceholder || $translationHasPlaceholder) {
            Language::query()
                ->whereKey($row->id)
                ->update(['translated' => $translated]);

            return true;
        }

        return false;
    }
}
