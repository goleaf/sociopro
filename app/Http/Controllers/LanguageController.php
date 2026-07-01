<?php

namespace App\Http\Controllers;

use App\Models\Language;
use DB;
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
        DB::table('languages')->where('name', $language)->update(['name' => strtolower($request->language)]);
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
        $row = Language::where('id', $id)->first();
        $phrase = $row->phrase;
        if (strpos($row->phrase, '____') == true && strpos($request->translated, '____') == true) {
            DB::table('languages')->where('id', $id)->update(['translated' => $request->translated]);

            return true;
        } elseif (strpos($row->phrase, '____') == false) {
            DB::table('languages')->where('id', $id)->update(['translated' => $request->translated]);

            return true;
        } else {
            return false;
        }
    }
}
