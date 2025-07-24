<?php

namespace App\Http\Controllers;

use App\Models\Size;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    public function index(Request $request)
    {
        $query = Size::query();

        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $sizes = $query->paginate(10);
        return view('backend.library.sizes.index', compact('sizes'));
    }

    public function create()
    {
        return view('backend.library.sizes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|array|min:1|max:191',
        ]);

        foreach ($request->name as $name) {
            if (Size::where('name', strtoupper($name))->exists()) {
                return redirect()->back()->withErrors('Size "' . $name . '" already exists!');
            }

            Size::create([
                'name' => strtoupper($name)
            ]);
        }

        return redirect()->route('sizes.index')->with('message', 'Size(s) created successfully!');
    }

    public function show($id)
    {
        $size = Size::findOrFail($id);
        return view('backend.library.sizes.show', compact('size'));
    }

    public function edit($id)
    {
        $size = Size::findOrFail($id);
        return view('backend.library.sizes.edit', compact('size'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|min:1|max:191',
        ]);

        $size = Size::findOrFail($id);

        if (Size::where('name', strtoupper($request->name))
            ->where('id', '!=', $id)
            ->exists()
        ) {
            return redirect()->back()->withErrors('Size already exists!');
        }

        $size->update([
            'name' => strtoupper($request->name)
        ]);

        return redirect()->route('sizes.index')->with('message', 'Size updated successfully!');
    }

    public function destroy($id)
    {
        Size::findOrFail($id)->delete();
        return redirect()->route('sizes.index')->with('message', 'Size deleted successfully!');
    }

    public function size_active($id)
    {
        $size = Size::findOrFail($id);
        $size->update(['is_active' => !$size->is_active]);

        $status = $size->is_active ? 'activated' : 'deactivated';
        return redirect()->route('sizes.index')->with('message', "Size {$status} successfully!");
    }
}
