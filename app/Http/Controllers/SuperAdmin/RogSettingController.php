<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RogSettingController extends Controller
{
    public function index()
    {
        return view('admin.rog.index', ['settings' => Setting::rog()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'rog_registrar_name' => 'nullable|string|max:150',
            'rog_form_code'      => 'required|string|max:50',
            'rog_form_version'   => 'required|string|max:50',
            'rog_effectivity'    => 'required|string|max:50',
            'rog_logo_left'      => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'rog_logo_right'     => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'reset_logo_left'    => 'nullable|boolean',
            'reset_logo_right'   => 'nullable|boolean',
        ]);

        foreach (['rog_registrar_name', 'rog_form_code', 'rog_form_version', 'rog_effectivity'] as $key) {
            Setting::put($key, $data[$key] ?? '');
        }

        $current = Setting::rog();

        foreach (['left', 'right'] as $side) {
            $key = "rog_logo_{$side}";

            if ($request->hasFile($key)) {
                Setting::put($key, $request->file($key)->store('rog', 'public'));
            } elseif ($request->boolean("reset_logo_{$side}")) {
                Setting::put($key, null);
            } else {
                continue;
            }

            if ($current[$key]) {
                Storage::disk('public')->delete($current[$key]);
            }
        }

        return redirect()->route('admin.rog.index')->with('success', 'Report of Grades settings saved.');
    }
}
