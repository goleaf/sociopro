<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\ImportAddonPackageRequest;
use App\Jobs\ImportAddonPackageJob;
use App\Models\Addon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Updater extends Controller
{
    public function update(ImportAddonPackageRequest $request): RedirectResponse
    {
        $package = $request->file('file');
        $storedPath = $package?->storeAs(
            'addon-imports',
            Str::uuid()->toString().'.zip',
            'local'
        );

        if (! is_string($storedPath)) {
            return redirect()->back()->with('error', get_phrase('Addon package could not be stored.'));
        }

        ImportAddonPackageJob::dispatch(Storage::disk('local')->path($storedPath))->afterCommit();

        return redirect()->back()->with('success', get_phrase('Addon import queued successfully.'));
    }

    // addon manager table
    public function addon_manager()
    {
        $page_data['addons'] = Addon::query()
            ->orderByDesc('id')
            ->paginate(10);
        $page_data['view_path'] = 'addons.index';

        return view('backend.index', $page_data);
    }

    public function addon_form()
    {
        return view('backend.admin.addons.install_form');
    }

    public function addon_status($status, $id)
    {
        if ($status == 'activate') {
            Addon::where('id', $id)->update(['status' => 1]);
            $msg = 'Addon activated.';
        } elseif ($status == 'deactivate') {
            Addon::where('id', $id)->update(['status' => 0]);
            $msg = 'Addon deactivated.';
        } else {
            $msg = 'Addon status unchanged.';
        }
        flash()->addSuccess($msg);

        return redirect()->back();
    }

    public function addon_delete($id)
    {
        $addon = Addon::find($id);
        $addon->delete();
        flash()->addSuccess('Addon has been deleted!');

        return redirect()->back();
    }
}
