<?php

namespace App\Http\Controllers;

use App\Models\FileUploader;
use App\Models\Sponsor;
use Facade\FlareClient\Stacktrace\File;
use Illuminate\Http\Request;
use Image;

class SponsorController extends Controller
{
    public function view_sponsor()
    {
        $page_data['sponsors'] = Sponsor::orderBy('id', 'DESC')->paginate(10);
        $page_data['view_path'] = 'sponsor.index';
        return view('backend.index', $page_data);
    }

    public function create_sponsor()
    {
        $page_data['view_path'] = 'sponsor.create';
        return view('backend.index', $page_data);
    }

    public function save_sponsor(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255|string',
            'image' => ['required', 'mimes:jpg,jpeg,png'],
        ]);

        $file_ext = $request->file('image')->extension();
        $filename = rand(0, 1000) . '.' . $file_ext;

        $sponsor = new Sponsor();
        $sponsor->user_id = auth()->user()->id;
        $sponsor->name = $request->name;
        $sponsor->image = $filename;
        $sponsor->description = $request->description;
        $sponsor->ext_url = $request->ext_url;
        $sponsor->status = 1;
        $done = $sponsor->save();
        if ($done) {

            FileUploader::upload($request->image, 'public/storage/sponsor/thumbnail/' . $filename, 300);

            flash()->addSuccess('Sponsored Post added successfully!');
            return redirect()->back();
        }
    }

    public function edit_sponsor($id)
    {
        $page_data['sponsor'] = Sponsor::find($id);
        $page_data['view_path'] = 'sponsor.edit';
        return view('backend.index', $page_data);
    }

    public function update_sponsor(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|max:255|string',
            'image' => ['mimes:jpg,jpeg,png'],
        ]);

        $sponsor = Sponsor::find($id);
        $sponsor->name = $request->name;
        if ($request->hasFile('image')) {
            $file_ext = $request->file('image')->extension();
            $filename = rand(0, 1000) . '.' . $file_ext;
            $sponsor->image = $filename;
        }
        $sponsor->description = $request->description;
        $sponsor->end_date = $request->end_date;
        $sponsor->ext_url = $request->ext_url;
        $sponsor->status = $request->status;
        $done = $sponsor->save();
        if ($done) {
            if ($request->hasFile('image')) {
                FileUploader::upload($request->image, 'public/storage/sponsor/thumbnail/' . $filename, 300);
            }

            flash()->addSuccess('Sponsored Post Updated successfully!');
            return redirect()->route('admin.view.sponsor');
        }
    }

    public function delete_sponsor($id)
    {
        $sponsor = Sponsor::find($id);
        // store image name for delete file operation
        $imagename = $sponsor->image;

        $done = $sponsor->delete();
        if ($done) {
            flash()->addSuccess('Sponsored Post Deleted successfully!');
            // just put the file name and folder name nothing more :)
            removeFile('sponsor', $imagename);
            return redirect()->back();
        }
    }

    public function ad_status($type, $id)
    {
        $valid = Sponsor::where('id', $id)->value('end_date');

        if ($type == 'active') {
            if (strtotime('now') < $valid) {
                $sponsor = Sponsor::find($id);
                $sponsor->status = 1;
                $sponsor->save();
                flash()->addSuccess('Ad activated');
            } else {
                flash()->addSuccess('Ad date expired.');
            }
        } else {
            if (strtotime('now') < $valid) {
                $sponsor = Sponsor::find($id);
                $sponsor->status = 0;
                $sponsor->save();
                flash()->addSuccess('Ad deactivated');
            } else {
                flash()->addSuccess('Ad date expired.');
            }
        }
        return redirect()->back();
    }






}
