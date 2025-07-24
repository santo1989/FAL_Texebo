<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\Company;
use App\Models\Division;
use Illuminate\Http\Request;

class BuyerController extends Controller
{
    public function index()
    {
        $buyers = Buyer::all();
        $companies = Company::all();
        $divisions = Division::all();
        return view('backend.library.buyers.index', compact('buyers', 'companies', 'divisions'));
    }


    public function create()
    {
        $buyers = Buyer::all();
        $companies = Company::all();
        $divisions = Division::all();
        return view('backend.library.buyers.create', compact('buyers', 'companies', 'divisions'));
    }


    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'name' => 'required|array|min:1|max:191',
            'company_id' => 'required',
            'division_id' => 'required'
        ]);

        $buyers = Buyer::all();
        $companies = Company::all();
        $divisions = Division::all();

        // Loop through the 'name' array and save each buyer name
        foreach ($request->name as $name) {
            foreach ($buyers as $buyer) {
                if (
                    $buyer->name == strtoupper($name) &&
                    $buyer->company_id == $request->company_id &&
                    $buyer->division_id == $request->division_id
                ) {
                    return redirect()->route('buyers.index')->withErrors('Buyer already exists!');
                }
            }
            // dd($request->all());
            // Data insert
            $buyer = new Buyer;
            $buyer->name = strtoupper($name);
            $buyer->division_id = $request->division_id;
            $buyer->company_id = $request->company_id;
            $buyer->company_name = Company::findorFail($request->company_id)->name;
            $buyer->save();
        } 

        

        // Redirect
        return redirect()->route('buyers.index')->with('message', 'Buyer created successfully!');
    }


    public function show($id)
    {
        $buyer = Buyer::findOrFail($id);
        $companies = Company::all();
        $divisions = Division::all();
        return view('backend.library.buyers.show', compact('buyer', 'companies', 'divisions'));
    }


    public function edit($id)
    {
        $buyer = Buyer::findOrFail($id);
        $companies = Company::all();
        $divisions = Division::all();
        return view('backend.library.buyers.edit', compact('buyer', 'companies', 'divisions'));
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|min:1|max:191',
        ]);

        // Data update
        $buyer = Buyer::findOrFail($id);
        $buyers = Buyer::all();
        $companies = Company::all();
        $divisions = Division::all();

        if ($buyer->name == strtoupper($request->name) && $buyer->company_id == $request->company_id && $buyer->division_id == $request->division_id) {
            return redirect()->route('buyers.index')->withErrors('No changes made!');
        } else {
            foreach ($buyers as $buyer) {
                if ($buyer->name == strtoupper($request->name) && $buyer->company_id == $request->company_id && $buyer->division_id == $request->division_id) {
                    return redirect()->route('buyers.index')->withErrors('Buyer already exists!');
                }
            }
        }
        $buyer->name = strtoupper($request->name);
        $buyer->company_id = $request->company_id;
        $buyer->company_name = Company::findOrFail($request->company_id)->name;
        $buyer->division_id = $request->division_id;
        $buyer->division_name = Division::findOrFail($request->division_id)->name;
        $buyer->save();

        // Redirect
        return redirect()->route('buyers.index')->with('message', 'Buyer updated successfully!');
    }


    public function destroy($id)
    {
        Buyer::findOrFail($id)->delete();
        return redirect()->route('buyers.index')->with('message', 'Buyer deleted successfully!');
    }

    public function buyer_active($id)
    {
        $buyer = Buyer::findOrFail($id);
        if ($buyer->is_active == 0) {
            $buyer->is_active = 1;
            $buyer->save();
            return redirect()->route('buyers.index')->with('message', 'Buyer deactivated successfully!');
        } else {
            $buyer->is_active = 0;
            $buyer->save();
            return redirect()->route('buyers.index')->with('message', 'Buyer activated successfully!');
        }
    }

    public function get_buyer(Request $request)
    {
        $buyer = Buyer::where('company_id', $request->company_id)->get();
        return response()->json($buyer);
    }
}
