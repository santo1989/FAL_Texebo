<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::all();
        $companies = Company::all();
        return view('backend.library.departments.index', compact('departments', 'companies'));
    }


    public function create()
    {
        $companies = Company::all();
        return view('backend.library.departments.create', compact('companies'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|min:2|max:191',
        ]);

        $departments = Department::all();
        $companies = Company::all();

        foreach ($departments as $department) {
            if ($department->name == $request->name && $department->company_id == $request->company_id) {
                return redirect()->route('departments.index')->withErrors('Department already exists!');
            }
        }

        // Data insert
        $department = new Department;
        $department->name = $request->name;
        $department->company_id = $request->company_id;
        $department->company_name = Company::findOrFail($request->company_id)->name;

        $department->save();

        // Redirect
        return redirect()->route('departments.index');
    }


    public function show($id)
    {
        $department = Department::findOrFail($id);
        $companies = Company::all();
        return view('backend.library.departments.show', compact('department', 'companies'));
    }


    public function edit($id)
    {
        $department = Department::findOrFail($id);
        $companies = Company::all();
        return view('backend.library.departments.edit', compact('department', 'companies'));
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|min:2|max:191',
        ]);

        // Data update
        $department = Department::findOrFail($id);
        $departments = Department::all();
        if ($department->name == $request->name && $department->company_id == $request->company_id) {
            return redirect()->route('departments.index')->withErrors('No changes made!');
        } else {
            foreach ($departments as $department) {
                if ($department->name == $request->name && $department->company_id == $request->company_id) {
                    return redirect()->route('departments.index')->withErrors('Department already exists!');
                }
            }
        }

        
        $department->name = $request->name;
        $department->company_id = $request->company_id;
        $department->company_name = Company::findOrFail($request->company_id)->name;
        $department->save();

        // Redirect
        return redirect()->route('departments.index');
    }


    public function destroy($id)
    {
        Department::findOrFail($id)->delete();
        return redirect()->route('departments.index');
    }
}
