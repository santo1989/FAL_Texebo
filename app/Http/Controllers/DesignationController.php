<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use App\Models\Division;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
     public function index()
    {
        $designations = Designation::all();
        $divisions = Division::all();
        return view('backend.library.designations.index', compact('designations', 'divisions'));
    }


    public function create()
    {
        $divisions = Division::all();
        return view('backend.library.designations.create', compact('divisions'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|min:2|max:191',
        ]);

        $designations = Designation::all();
        $divisions = Division::all();

        foreach ($designations as $designation) {
            if ($designation->name == $request->name && $designation->division_id == $request->division_id) {
                return redirect()->route('designations.index')->withErrors('Designation already exists!');
            }
        }
        // Data insert
        $designation = new Designation;
        $designation->name = $request->name;
        $designation->division_id = $request->division_id;
        $designation->division_name = Division::findOrFail($request->division_id)->name;

        $designation->save();

        // Redirect
        return redirect()->route('designations.index');
    }


    public function show($id)
    {
        $designation = Designation::findOrFail($id);
        $divisions = Division::all();
        return view('backend.library.designations.show', compact('designation', 'divisions'));
    }


    public function edit($id)
    {
        $designation = Designation::findOrFail($id);
        $divisions = Division::all();
        return view('backend.library.designations.edit', compact('designation' , 'divisions'));
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|min:3|max:191',
        ]);

        // Data update
        $designation = Designation::findOrFail($id);
        $divisions = Division::all();

        if($designation->name == $request->name && $designation->division_id == $request->division_id){
            return redirect()->route('designations.index');
        }else{
            foreach ($divisions as $division) {
                if ($division->name == $request->name && $division->id == $request->division_id) {
                    return redirect()->route('designations.index')->withErrors('Designation already exists!');
                }
            }
        }
        $designation->name = $request->name;
        $designation->division_id = $request->division_id;
        $designation->division_name = Division::findOrFail($request->division_id)->name;
        $designation->save();

        // Redirect
        return redirect()->route('designations.index');
    }


    public function destroy($id)
    {
        Designation::findOrFail($id)->delete();
        return redirect()->route('designations.index');
    }
}
