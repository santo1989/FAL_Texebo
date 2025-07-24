<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //************************************************************

        // division 1 = Corporate => Company 1 = Common Office

        // division 2 = Factory => Company 2 = TIL // Company 3 = FAL 
        // Company 4 = NCL

        // division 3 = Fabric => Company 5 = TIL 
        // Company 6 = NCL

        //********************************************************************

        // Corporate Division =1
        //1
        Department::create([
            'company_id' => 1,
            'company_name' => 'Corporate',
            'name' => 'Accounts - FAL'
        ]);
        //2
        Department::create([
            'company_id' => 1,
            'company_name' => 'Corporate',
            'name' => 'Accounts - NCL'
        ]);
        //3
        Department::create([
            'company_id' => 1,
            'company_name' => 'Corporate',
            'name' => 'Accounts - TIL'
        ]);
        //4
        Department::create([
            'company_id' => 1,
            'company_name' => 'Corporate',
            'name' => 'Audit'
        ]);
        //5
        Department::create([
            'company_id' => 1,
            'company_name' => 'Corporate',
            'name' => 'Commercial'
        ]);
        //6
        Department::create([
            'company_id' => 1,
            'company_name' => 'Corporate',
            'name' => 'Compliance'
        ]);
        //7
        Department::create([
            'company_id' => 1,
            'company_name' => 'Corporate',
            'name' => 'Corporate Affairs'
        ]);
        //8
        Department::create([
            'company_id' => 1,
            'company_name' => 'Corporate',
            'name' => 'Cost Control'
        ]);
        //9
        Department::create([
            'company_id' => 1,
            'company_name' => 'Corporate',
            'name' => 'Human Resource Management'
        ]);
        //10
        Department::create([
            'company_id' => 1,
            'company_name' => 'Corporate',
            'name' => 'IT'
        ]);
        //11
        Department::create([
            'company_id' => 1,
            'company_name' => 'Corporate',
            'name' => 'Legal Affairs'
        ]);
        //12
        Department::create([
            'company_id' => 1,
            'company_name' => 'Corporate',
            'name' => 'Merchandising - FAL'
        ]);
        //13
        Department::create([
            'company_id' => 1,
            'company_name' => 'Corporate',
            'name' => 'Merchandising - NCL'
        ]);
        //14
        Department::create([
            'company_id' => 1,
            'company_name' => 'Corporate',
            'name' => 'Merchandising - TIL'
        ]);
        //15
        Department::create([
            'company_id' => 1,
            'company_name' => 'Corporate',
            'name' => 'MIS'
        ]);
        //16
        Department::create([
            'company_id' => 1,
            'company_name' => 'Corporate',
            'name' => 'Product Design'
        ]);
        //17
        Department::create([
            'company_id' => 1,
            'company_name' => 'Corporate',
            'name' => 'Supply Chain Management'
        ]);
        //********************************************************************
        // Factory Division => Company 2 = TIL 
        //17
        Department::create([
            'company_id' => 2,
            'company_name' => 'TIL',
            'name' => 'Accounts'
        ]);
        //18
        Department::create([
            'company_id' => 2,
            'company_name' => 'TIL',
            'name' => 'CAD'
        ]);
        //19
        Department::create([
            'company_id' => 2,
            'company_name' => 'TIL',
            'name' => 'Compliance'
        ]);
        //20
        Department::create([
            'company_id' => 2,
            'company_name' => 'TIL',
            'name' => 'Cutting'
        ]);
        //21

        Department::create([
            'company_id' => 2,
            'company_name' => 'TIL',
            'name' => 'Finishing'
        ]);
        //22
        Department::create([
            'company_id' => 2,
            'company_name' => 'TIL',
            'name' => 'HR, Admin & Welfare'
        ]);
        //23
        Department::create([
            'company_id' => 2,
            'company_name' => 'TIL',
            'name' => 'IE'
        ]);
        //24
        Department::create([
            'company_id' => 2,
            'company_name' => 'TIL',
            'name' => 'IT & MIS'
        ]);
        //25
        Department::create([
            'company_id' => 2,
            'company_name' => 'TIL',
            'name' => 'Lab'
        ]);
        //26
        Department::create([
            'company_id' => 2,
            'company_name' => 'TIL',
            'name' => 'Maintenance'
        ]);
        //27
        Department::create([
            'company_id' => 2,
            'company_name' => 'TIL',
            'name' => 'MCD/ Store'
        ]);
        //28
        Department::create([
            'company_id' => 2,
            'company_name' => 'TIL',
            'name' => 'Planning'
        ]);
        //29
        Department::create([
            'company_id' => 2,
            'company_name' => 'TIL',
            'name' => 'Quality'
        ]);
        //30
        Department::create([
            'company_id' => 2,
            'company_name' => 'TIL',
            'name' => 'Sample'
        ]);
        //31
        Department::create([
            'company_id' => 2,
            'company_name' => 'TIL',
            'name' => 'Sewing'
        ]);

        //********************************************************************

        // Factory Division => Company 2 = FAL 
        //32
        Department::create([
            'company_id' => 3,
            'company_name' => 'FAL',
            'name' => 'Accounts'
        ]);
        //33
        Department::create([
            'company_id' => 3,
            'company_name' => 'FAL',
            'name' => 'CAD'
        ]);
        //34
        Department::create([
            'company_id' => 3,
            'company_name' => 'FAL',
            'name' => 'Compliance'
        ]);
        //35
        Department::create([
            'company_id' => 3,
            'company_name' => 'FAL',
            'name' => 'Cutting'
        ]);
        //36

        Department::create([
            'company_id' => 3,
            'company_name' => 'FAL',
            'name' => 'Fabric Merchandising'
        ]);
        //37

        Department::create([
            'company_id' => 3,
            'company_name' => 'FAL',
            'name' => 'Finishing'
        ]);
        //38
        Department::create([
            'company_id' => 3,
            'company_name' => 'FAL',
            'name' => 'HR, Admin & Welfare'
        ]);
        //39
        Department::create([
            'company_id' => 3,
            'company_name' => 'FAL',
            'name' => 'IE'
        ]);
        //40
        Department::create([
            'company_id' => 3,
            'company_name' => 'FAL',
            'name' => 'IT & MIS'
        ]);
        //41
        Department::create([
            'company_id' => 3,
            'company_name' => 'FAL',
            'name' => 'Lab'
        ]);
        //42
        Department::create([
            'company_id' => 3,
            'company_name' => 'FAL',
            'name' => 'Maintenance'
        ]);
        //43
        Department::create([
            'company_id' => 3,
            'company_name' => 'FAL',
            'name' => 'MCD/ Store'
        ]);
        //44
        Department::create([
            'company_id' => 3,
            'company_name' => 'FAL',
            'name' => 'Planning'
        ]);
        //45
        Department::create([
            'company_id' => 3,
            'company_name' => 'FAL',
            'name' => 'Quality'
        ]);
        //46
        Department::create([
            'company_id' => 3,
            'company_name' => 'FAL',
            'name' => 'Sample'
        ]);
        //47
        Department::create([
            'company_id' => 3,
            'company_name' => 'FAL',
            'name' => 'Sewing'
        ]);

        //********************************************************************
        // Factory Division => Company 4 = NCL
        //********************************************************************
        //48
        Department::create([
            'company_id' => 4,
            'company_name' => 'NCL',
            'name' => 'IE'
        ]);
        //49
        Department::create([
            'company_id' => 4,
            'company_name' => 'NCL',
            'name' => 'Planning'
        ]);
        //50
        Department::create([
            'company_id' => 4,
            'company_name' => 'NCL',
            'name' => 'Maintenance'
        ]);
        //51
        Department::create([
            'company_id' => 4,
            'company_name' => 'NCL',
            'name' => 'Cutting'
        ]);
        //52
        Department::create([
            'company_id' => 4,
            'company_name' => 'NCL',
            'name' => 'Audit'
        ]);
        //53
        Department::create([
            'company_id' => 4,
            'company_name' => 'NCL',
            'name' => 'Finishing'
        ]);
        //54
        Department::create([
            'company_id' => 4,
            'company_name' => 'NCL',
            'name' => 'IT & MIS'
        ]);
        //55
        Department::create([
            'company_id' => 4,
            'company_name' => 'NCL',
            'name' => 'Merchandising'
        ]);
        //56
        Department::create([
            'company_id' => 4,
            'company_name' => 'NCL',
            'name' => 'Production'
        ]);
        //57
        Department::create([
            'company_id' => 4,
            'company_name' => 'NCL',
            'name' => 'Quality'
        ]);
        //58
        Department::create([
            'company_id' => 4,
            'company_name' => 'NCL',
            'name' => 'Store'
        ]);
        //59
        Department::create([
            'company_id' => 4,
            'company_name' => 'NCL',
            'name' => 'Technical'
        ]);
        //60
        Department::create([
            'company_id' => 4,
            'company_name' => 'NCL',
            'name' => 'HR, Admin & Welfare'
        ]);
        //61
        Department::create([
            'company_id' => 4,
            'company_name' => 'NCL',
            'name' => 'Compliance'
        ]);


        //********************************************************************
        // Fabric Division => Company 5 = TIL
        //********************************************************************
        //62
        Department::create([
            'company_id' => 5,
            'company_name' => 'TIL Fabric',
            'name' => 'Accounts'
        ]);
        //63
        Department::create([
            'company_id' => 5,
            'company_name' => 'TIL Fabric',
            'name' => 'AOP'
        ]);
        //64
        Department::create([
            'company_id' => 5,
            'company_name' => 'TIL Fabric',
            'name' => 'Batch'
        ]);
        //65
        Department::create([
            'company_id' => 5,
            'company_name' => 'TIL Fabric',
            'name' => 'Circular knitting'
        ]);

        //66
        Department::create([
            'company_id' => 5,
            'company_name' => 'TIL Fabric',
            'name' => 'Dyeing'
        ]);
        //67
        Department::create([
            'company_id' => 5,
            'company_name' => 'TIL Fabric',
            'name' => 'Fabric Merchandiser'
        ]);
        //68
        Department::create([
            'company_id' => 5,
            'company_name' => 'TIL Fabric',
            'name' => 'Knitting'
        ]);
        //69
        Department::create([
            'company_id' => 5,
            'company_name' => 'TIL Fabric',
            'name' => 'Garments Wash'
        ]);
        //70
        Department::create([
            'company_id' => 5,
            'company_name' => 'TIL Fabric',
            'name' => 'IT & MIS'
        ]);
        //71
        Department::create([
            'company_id' => 5,
            'company_name' => 'TIL Fabric',
            'name' => 'Maintenance'
        ]);
        //72
        Department::create([
            'company_id' => 5,
            'company_name' => 'TIL Fabric',
            'name' => 'Mechanical Finishing'
        ]);
        //73
        Department::create([
            'company_id' => 5,
            'company_name' => 'TIL Fabric',
            'name' => 'Physical Lab'
        ]);
        //74
        Department::create([
            'company_id' => 5,
            'company_name' => 'TIL Fabric',
            'name' => 'Planning'
        ]);
        //75
        Department::create([
            'company_id' => 5,
            'company_name' => 'TIL Fabric',
            'name' => 'Pretreatment'
        ]);
        //76
        Department::create([
            'company_id' => 5,
            'company_name' => 'TIL Fabric',
            'name' => 'Quality'
        ]);
        //77
        Department::create([
            'company_id' => 5,
            'company_name' => 'TIL Fabric',
            'name' => 'Sample'
        ]);
        //78
        Department::create([
            'company_id' => 5,
            'company_name' => 'TIL Fabric',
            'name' => 'Store'
        ]);

        //********************************************************************
        // Fabric Division => Company 6 = NCL BSCIC
        //********************************************************************
        //79
        Department::create([
            'company_id' => 6,
            'company_name' => 'NCL BSCIC',
            'name' => 'HR, Admin & Accounts'
        ]);
        //80
        Department::create([
            'company_id' => 6,
            'company_name' => 'NCL BSCIC',
            'name' => 'Dyeing Finishing'
        ]);
        //81
        Department::create([
            'company_id' => 6,
            'company_name' => 'NCL BSCIC',
            'name' => 'Dyeing'
        ]);
        //82
        Department::create([
            'company_id' => 6,
            'company_name' => 'NCL BSCIC',
            'name' => 'knitting'
        ]);
        //83
        Department::create([
            'company_id' => 6,
            'company_name' => 'NCL BSCIC',
            'name' => 'Labratory'
        ]);
    }
}
