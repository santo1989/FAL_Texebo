<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Division;
use Illuminate\Http\Request;

class CompanyController extends Controller
{

    public function index()
    {
        $companies = Company::all();
        $divisions = Division::all();
        return view('backend.library.companies.index', compact('companies', 'divisions'));
    }


    public function create()
    {
        $companies = Company::all();
        $divisions = Division::all();
        return view('backend.library.companies.create', compact('companies', 'divisions'));
    }

  
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|min:3|max:191',
        ]);

        $companies = Company::all();
        $divisions = Division::all();

        foreach ($companies as $company) {
            if ($company->name == $request->name && $company->division_id == $request->division_id) {
                return redirect()->route('companies.index')->withErrors('Company already exists!');
            }
        }
        // Data insert
        $company = new Company;
        $company->name = $request->name;
        $company->division_id = $request->division_id;
        $company->division_name = Division::findOrFail($request->division_id)->name;


        $company->save();

        // Redirect
        return redirect()->route('companies.index');
    }

 
    public function show($id)
    {
        $company = Company::findOrFail($id);
        $divisions = Division::all();
        return view('backend.library.companies.show', compact('company', 'divisions'));
    }

  
    public function edit($id)
    {
        $company = Company::findOrFail($id);
        $divisions = Division::all();
        return view('backend.library.companies.edit', compact('company', 'divisions'));
    }

  
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|min:3|max:191',
        ]);

        // Data update
        $company = Company::findOrFail($id);
        $companies = Company::all();

        if($company->name == $request->name && $company->division_id == $request->division_id){
            return redirect()->route('companies.index')->withErrors('No changes made!');
        } else {
            foreach ($companies as $company) {
                if ($company->name == $request->name && $company->division_id == $request->division_id) {
                    return redirect()->route('companies.index')->withErrors('Company already exists!');
                }
            }
        }
        $company->name = $request->name;
        $company->division_id = $request->division_id;
        $company->division_name = Division::findOrFail($request->division_id)->name;
        $company->save();

        // Redirect
        return redirect()->route('companies.index');
    }

    
    public function destroy($id)
    {
        Company::findOrFail($id)->delete();
        return redirect()->route('companies.index');
    }

    public function getCompanyDesignations($id)
    {
        $divisions = Division::findOrFail($id);
        $company = Company::where('division_id', $id)->get();
        $designations = Designation::where('division_id', $id)->get();
        return response()->json([
            'company' => $company,
            'designations' => $designations
        ]);
    }

    public function getdepartments($id)
    {
        $company = Company::findOrFail($id);
        $departments = Department::where('company_id', $id)->get();
        return response()->json([
            'departments' => $departments
        ]);
    }
}

?>