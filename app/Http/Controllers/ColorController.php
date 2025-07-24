<?php

namespace App\Http\Controllers;

use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    public function index(Request $request)
    {
        $query = Color::query();

        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $colors = $query->paginate(10);
        return view('backend.library.colors.index', compact('colors'));
    }

    public function create()
    {
        return view('backend.library.colors.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|array|min:1|max:191',
        ]);

        foreach ($request->name as $name) {
            if (Color::where('name', strtoupper($name))->exists()) {
                return redirect()->back()->withErrors('Color "' . $name . '" already exists!');
            }

            Color::create([
                'name' => strtoupper($name)
            ]);
        }

        return redirect()->route('colors.index')->with('message', 'Color(s) created successfully!');
    }

    public function show($id)
    {
        $color = Color::findOrFail($id);
        return view('backend.library.colors.show', compact('color'));
    }

    public function edit($id)
    {
        $color = Color::findOrFail($id);
        return view('backend.library.colors.edit', compact('color'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|min:1|max:191',
        ]);

        $color = Color::findOrFail($id);

        if (Color::where('name', strtoupper($request->name))
            ->where('id', '!=', $id)
            ->exists()
        ) {
            return redirect()->back()->withErrors('Color already exists!');
        }

        $color->update([
            'name' => strtoupper($request->name)
        ]);

        return redirect()->route('colors.index')->with('message', 'Color updated successfully!');
    }

    public function destroy($id)
    {
        Color::findOrFail($id)->delete();
        return redirect()->route('colors.index')->with('message', 'Color deleted successfully!');
    }

    public function color_active($id)
    {
        $color = Color::findOrFail($id);
        $color->update(['is_active' => !$color->is_active]);

        $status = $color->is_active ? 'activated' : 'deactivated';
        return redirect()->route('colors.index')->with('message', "Color {$status} successfully!");
    }
}
