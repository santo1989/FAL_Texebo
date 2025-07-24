<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Division;
use App\Http\Requests\StoreDivisionRequest;
use App\Http\Requests\UpdateDivisionRequest;
use App\Models\Company;
use App\Models\Designation;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    public function index()
    {
        $divisions = Division::all();
        return view('backend.library.divisions.index', compact('divisions'));
    }


    public function create()
    {
        $divisions = Division::all();
        return view('backend.library.divisions.create', compact('divisions'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|min:3|max:191',
        ]);

        $divisions = Division::all();
        // Data insert
        $divisions = new Division;
        $divisions->name = $request->name;

        $divisions->save();

        // Redirect
        return redirect()->route('divisions.index');
    }


    public function show($id)
    {
        $divisions = Division::findOrFail($id);
        return view('backend.library.divisions.show', compact('divisions'));
    }


    public function edit($id)
    {
        $divisions = Division::findOrFail($id);
        return view('backend.library.divisions.edit', compact('divisions'));
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|min:3|max:191',
        ]);

        // Data update
        $divisions = Division::findOrFail($id);
        $divisions->name = $request->name;
        $divisions->save();

        // Redirect
        return redirect()->route('divisions.index');
    }


    public function destroy($id)
    {
        $divisions = Division::findOrFail($id);
        $companies = Company::where('division_id', $id); 
        $designations = Designation::where('division_id', $id);
        if ($companies->count() > 0 ||  $designations->count() > 0) {
            $companies->delete(); 
            $designations->delete();
            $divisions->delete();
        } else {
            $divisions->delete();
        }

        return redirect()->route('divisions.index')->withMessage('Division and related data of Company Name, Designation Name  are deleted successfully!');
    } 
}
