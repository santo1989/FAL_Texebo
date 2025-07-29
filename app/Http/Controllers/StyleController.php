<?php

namespace App\Http\Controllers;

use App\Models\Style;
use Illuminate\Http\Request;

class StyleController extends Controller
{

    public function index(Request $request)
    {
        $query = Style::query();

        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $styles = $query->paginate(10);
        return view('backend.library.styles.index', compact('styles'));
    }

    public function create()
    {
       
        return view('backend.library.styles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|min:1|max:191',
        ]);

        foreach ($request->name as $name) {
            if (Style::where('name', strtoupper($name))
                ->exists()) {
                return redirect()->back()->withErrors('Style already exists!');
            }
            
            Style::create([
                'name' => strtoupper($name)
            ]);
        } 

        return redirect()->route('styles.index')->with('message', 'Style created successfully!');
    }

    public function show($id)
    {
        $style = Style::findOrFail($id);
        return view('backend.library.styles.show', compact('style'));
    }

    public function edit($id)
    {
        $style = Style::findOrFail($id);
        return view('backend.library.styles.edit', compact('style'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|min:1|max:191',
        ]);

        $style = Style::findOrFail($id);
        
        if (Style::where('name', strtoupper($request->name))
            ->where('id', '!=', $id)
            ->exists()) {
            return redirect()->back()->withErrors('Style already exists!');
        }
        
        $style->update([
            'name' => strtoupper($request->name)
        ]);

        return redirect()->route('styles.index')->with('message', 'Style updated successfully!');
    }

    public function destroy($id)
    {
        Style::findOrFail($id)->delete();
        return redirect()->route('styles.index')->with('message', 'Style deleted successfully!');
    }

    public function style_active($id)
    {
        $style = Style::findOrFail($id);
        $style->update(['is_active' => !$style->is_active]);
        
        $status = $style->is_active ? 'activated' : 'deactivated';
        return redirect()->route('styles.index')->with('message', "Style {$status} successfully!");
    }
}