<?php

namespace App\Http\Controllers;

use App\Actions\Addons\ImportAddonPackage;
use App\Http\Requests\Admin\ImportAddonPackageRequest;
use App\Models\Addon;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class Updater extends Controller
{
    public function update(
        ImportAddonPackageRequest $request,
        ImportAddonPackage $importAddonPackage
    ): RedirectResponse {
        try {
            $result = $importAddonPackage->handle($request->file('file'));
        } catch (RuntimeException $exception) {
            return redirect()->back()->with('error', get_phrase($exception->getMessage()));
        }

        return redirect()->back()->with('success', get_phrase($result->message));
    }

    // addon manager table
    public function addon_manager()
    {
        $page_data['addons'] = Addon::paginate(10);
        $page_data['view_path'] = 'addons.index';

        return view('backend.index', $page_data);
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
