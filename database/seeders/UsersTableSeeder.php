<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;


class UsersTableSeeder extends Seeder
{

    public function run()
    {
        // User::create([
        //     'role_id' => 1,
        //     'name' => 'Admin',
        //     'emp_id' => '0001',
        //     'email' => 'admin@ntg.com.bd',
        //     'email_verified_at' => now(),
        //     'picture' => 'avatar.png',
        //     'dob' => '1989-02-03',
        //     'joining_date' => '2019-02-03',
        //     'division_id' => '1',
        //     'company_id' => '1',
        //     'department_id' => '9',
        //     'designation_id' => '10',
        //     'password' => bcrypt('12345678'),
        //     'password_text' => '12345678', // This is for the user to login with password
        //     'remember_token' => Str::random(10),
        // ]);
        // User::create([
        //     'role_id' => 2,
        //     'name' => 'Nahid Hasan',
        //     'emp_id' => '00422',
        //     'email' => 'nahidhasan@ntg.com.bd',
        //     'email_verified_at' => now(),
        //     'picture' => 'avatar.png',
        //     'dob' => '1984-02-03',
        //     'joining_date' => '2019-02-03',
        //     'division_id' => '1',
        //     'company_id' => '1',
        //     'department_id' => '15',
        //     'designation_id' => '6',
        //     'mobile' => '01810157700',
        //     'password' => bcrypt('nahid@422'),
        //     'password_text' => 'nahid@422', // This is for the user to login with password
        //     'remember_token' => Str::random(10),
        // ]); 

        // User::create([
        //     'role_id' => 3,
        //     'name' => 'ie1',
        //     'emp_id' => 'til-ie1',
        //     'email' => 'ie1@ntg.com.bd',
        //     'picture' => 'avatar.png',
        //     'dob' => '1986-02-03',
        //     'joining_date' => '2019-02-03',
        //     'division_id' => '1',
        //     'company_id' => '1',
        //     'department_id' => '15',
        //     'designation_id' => '11',
        //     'email_verified_at' => now(),
        //     'password' => bcrypt('123'),
        //     'password_text' => '123', // This is for the user to login with password
        //     'remember_token' => Str::random(10),
        // ]);

        // User::create([
        //     'role_id' => 3,
        //     'name' => 'ie2',
        //     'emp_id' => 'til-ie2',
        //     'email' => 'ie2@ntg.com.bd',
        //     'picture' => 'avatar.png',
        //     'dob' => '1986-02-03',
        //     'joining_date' => '2019-02-03',
        //     'division_id' => '1',
        //     'company_id' => '1',
        //     'department_id' => '15',
        //     'designation_id' => '11',
        //     'email_verified_at' => now(),
        //     'password' => bcrypt('123'),
        //     'password_text' => '123', // This is for the user to login with password
        //     'remember_token' => Str::random(10),
        // ]);

        // User::create([
        //     'role_id' => 3,
        //     'name' => 'ie3',
        //     'emp_id' => 'til-ie3',
        //     'email' => 'ie3@ntg.com.bd',
        //     'picture' => 'avatar.png',
        //     'dob' => '1986-02-03',
        //     'joining_date' => '2019-02-03',
        //     'division_id' => '1',
        //     'company_id' => '1',
        //     'department_id' => '15',
        //     'designation_id' => '11',
        //     'email_verified_at' => now(),
        //     'password' => bcrypt('123'),
        //     'password_text' => '123', // This is for the user to login with password
        //     'remember_token' => Str::random(10),
        // ]);

        // User::create([
        //     'role_id' => 3,
        //     'name' => 'ie4',
        //     'emp_id' => 'til-ie4',
        //     'email' => 'ie4@ntg.com.bd',
        //     'picture' => 'avatar.png',
        //     'dob' => '1986-02-03',
        //     'joining_date' => '2019-02-03',
        //     'division_id' => '1',
        //     'company_id' => '1',
        //     'department_id' => '15',
        //     'designation_id' => '11',
        //     'email_verified_at' => now(),
        //     'password' => bcrypt('123'),
        //     'password_text' => '123', // This is for the user to login with password
        //     'remember_token' => Str::random(10),
        // ]);

        // User::create([
        //     'role_id' => 3,
        //     'name' => 'ie5',
        //     'emp_id' => 'til-ie5',
        //     'email' => 'ie5@ntg.com.bd',
        //     'picture' => 'avatar.png',
        //     'dob' => '1986-02-03',
        //     'joining_date' => '2019-02-03',
        //     'division_id' => '1',
        //     'company_id' => '1',
        //     'department_id' => '15',
        //     'designation_id' => '11',
        //     'email_verified_at' => now(),
        //     'password' => bcrypt('123'),
        //     'password_text' => '123', // This is for the user to login with password
        //     'remember_token' => Str::random(10),
        // ]);

        // User::create([
        //     'role_id' => 3,
        //     'name' => 'ie6',
        //     'emp_id' => 'til-ie6',
        //     'email' => 'ie6@ntg.com.bd',
        //     'picture' => 'avatar.png',
        //     'dob' => '1986-02-03',
        //     'joining_date' => '2019-02-03',
        //     'division_id' => '1',
        //     'company_id' => '1',
        //     'department_id' => '15',
        //     'designation_id' => '11',
        //     'email_verified_at' => now(),
        //     'password' => bcrypt('123'),
        //     'password_text' => '123', // This is for the user to login with password
        //     'remember_token' => Str::random(10),
        // ]);

        // User::create([
        //     'role_id' => 3,
        //     'name' => 'ie7',
        //     'emp_id' => 'til-ie7',
        //     'email' => 'ie7@ntg.com.bd',
        //     'picture' => 'avatar.png',
        //     'dob' => '1986-02-03',
        //     'joining_date' => '2019-02-03',
        //     'division_id' => '1',
        //     'company_id' => '1',
        //     'department_id' => '15',
        //     'designation_id' => '11',
        //     'email_verified_at' => now(),
        //     'password' => bcrypt('123'),
        //     'password_text' => '123', // This is for the user to login with password
        //     'remember_token' => Str::random(10),
        // ]);

        // User::create([
        //     'role_id' => 3,
        //     'name' => 'ie8',
        //     'emp_id' => 'til-ie8',
        //     'email' => 'ie8@ntg.com.bd',
        //     'picture' => 'avatar.png',
        //     'dob' => '1986-02-03',
        //     'joining_date' => '2019-02-03',
        //     'division_id' => '1',
        //     'company_id' => '1',
        //     'department_id' => '15',
        //     'designation_id' => '11',
        //     'email_verified_at' => now(),
        //     'password' => bcrypt('123'),
        //     'password_text' => '123', // This is for the user to login with password
        //     'remember_token' => Str::random(10),
        // ]);

        // User::create([
        //     'role_id' => 3,
        //     'name' => 'ie9',
        //     'emp_id' => 'til-ie9',
        //     'email' => 'ie9@ntg.com.bd',
        //     'picture' => 'avatar.png',
        //     'dob' => '1986-02-03',
        //     'joining_date' => '2019-02-03',
        //     'division_id' => '1',
        //     'company_id' => '1',
        //     'department_id' => '15',
        //     'designation_id' => '11',
        //     'email_verified_at' => now(),
        //     'password' => bcrypt('123'),
        //     'password_text' => '123', // This is for the user to login with password
        //     'remember_token' => Str::random(10),
        // ]);



        // User::create([
        //     'role_id' => 4,
        //     'name' => 'SuperVisor',
        //     'emp_id' => 'TIL-SV',
        //     'email' => 'supervisor@ntg.com.bd',
        //     'email_verified_at' => now(),
        //     'picture' => 'avatar.png',
        //     'dob' => '1989-02-03',
        //     'joining_date' => '2019-02-03',
        //     'division_id' => '1',
        //     'company_id' => '1',
        //     'department_id' => '9',
        //     'designation_id' => '10',
        //     'password' => bcrypt('123'),
        //     'password_text' => '123', // This is for the user to login with password
        //     'remember_token' => Str::random(10),
        // ]);

        // This ensures your seeder works even if role IDs change in the future
        $adminRole = Role::where('name', 'Admin')->first()->id ?? 1;
        $generalRole = Role::where('name', 'General')->first()->id ?? 2;
        $cuttingRole = Role::where('name', 'Cutting')->first()->id ?? 3;
        $printSendRole = Role::where('name', 'Print Send')->first()->id ?? 4;
        $printReceiveRole = Role::where('name', 'Print Receive')->first()->id ?? 5;
        $inputRole = Role::where('name', 'Input')->first()->id ?? 6;
        $outputRole = Role::where('name', 'Output')->first()->id ?? 7;
        $packingRole = Role::where('name', 'Packing')->first()->id ?? 8;
        $shipmentRole = Role::where('name', 'Shipment')->first()->id ?? 9;
        $hrRole = Role::where('name', 'HR')->first()->id ?? 10;
        $supervisorRole = Role::where('name', 'Supervisor')->first()->id ?? 11;
        $qcRole = Role::where('name', 'QC')->first()->id ?? 12; // New QC Role

        // Admin User
        User::create([
            'role_id' => $adminRole,
            'name' => 'Admin',
            'emp_id' => '0001',
            'email' => 'admin@ntg.com.bd',
            'email_verified_at' => now(),
            'picture' => 'avatar.png',
            'dob' => '1989-02-03',
            'joining_date' => '2019-02-03',
            'division_id' => '1',
            'company_id' => '1',
            'department_id' => '9',
            'designation_id' => '10',
            'password' => bcrypt('12345678'),
            'password_text' => '12345678',
            'remember_token' => Str::random(10),
        ]);

        // General User - Nahid Hasan
        User::create([
            'role_id' => $generalRole, // Assigned General role
            'name' => 'General User',
            'emp_id' => '00422',
            'email' => 'generaluser@ntg.com.bd',
            'email_verified_at' => now(),
            'picture' => 'avatar.png',
            'dob' => '1984-02-03',
            'joining_date' => '2019-02-03',
            'division_id' => '1',
            'company_id' => '1',
            'department_id' => '15',
            'designation_id' => '6',
            'mobile' => '01810157700',
            'password' => bcrypt('123'),
            'password_text' => '123',
            'remember_token' => Str::random(10),
        ]);

        // Cutting Users (IEs) - Assign the Cutting role
        for ($i = 1; $i <= 9; $i++) {
            User::create([
                'role_id' => $cuttingRole, // Assigned Cutting role
                'name' => 'ie' . $i,
                'emp_id' => 'til-ie' . $i,
                'email' => 'ie' . $i . '@ntg.com.bd',
                'picture' => 'avatar.png',
                'dob' => '1986-02-03',
                'joining_date' => '2019-02-03',
                'division_id' => '1',
                'company_id' => '1',
                'department_id' => '15',
                'designation_id' => '11',
                'email_verified_at' => now(),
                'password' => bcrypt('123'),
                'password_text' => '123',
                'remember_token' => Str::random(10),
            ]);
        }

        // Supervisor User - Changed role_id to Supervisor
        User::create([
            'role_id' => $supervisorRole, // Assigned Supervisor role
            'name' => 'Supervisor',
            'emp_id' => 'TIL-SV',
            'email' => 'supervisor@ntg.com.bd',
            'email_verified_at' => now(),
            'picture' => 'avatar.png',
            'dob' => '1989-02-03',
            'joining_date' => '2019-02-03',
            'division_id' => '1',
            'company_id' => '1',
            'department_id' => '9',
            'designation_id' => '10',
            'password' => bcrypt('123'),
            'password_text' => '123',
            'remember_token' => Str::random(10),
        ]);

        // Example Users for other new roles (you can expand this as needed)

        // Print Send User
        User::create([
            'role_id' => $printSendRole,
            'name' => 'Print Send User',
            'emp_id' => 'PS-001',
            'email' => 'printsend@ntg.com.bd',
            'email_verified_at' => now(),
            'picture' => 'avatar.png',
            'dob' => '1990-01-01',
            'joining_date' => '2020-01-01',
            'division_id' => '1',
            'company_id' => '1',
            'department_id' => '1', // Adjust department/designation as appropriate
            'designation_id' => '1',
            'password' => bcrypt('123'),
            'password_text' => '123',
            'remember_token' => Str::random(10),
        ]);

        // Print Receive User
        User::create([
            'role_id' => $printReceiveRole,
            'name' => 'Print Receive User',
            'emp_id' => 'PR-001',
            'email' => 'printreceive@ntg.com.bd',
            'email_verified_at' => now(),
            'picture' => 'avatar.png',
            'dob' => '1991-02-02',
            'joining_date' => '2020-02-02',
            'division_id' => '1',
            'company_id' => '1',
            'department_id' => '1',
            'designation_id' => '1',
            'password' => bcrypt('123'),
            'password_text' => '123',
            'remember_token' => Str::random(10),
        ]);

        // Input User
        User::create([
            'role_id' => $inputRole,
            'name' => 'Input User',
            'emp_id' => 'IN-001',
            'email' => 'input@ntg.com.bd',
            'email_verified_at' => now(),
            'picture' => 'avatar.png',
            'dob' => '1992-03-03',
            'joining_date' => '2020-03-03',
            'division_id' => '1',
            'company_id' => '1',
            'department_id' => '1',
            'designation_id' => '1',
            'password' => bcrypt('123'),
            'password_text' => '123',
            'remember_token' => Str::random(10),
        ]);

        // Output User
        User::create([
            'role_id' => $outputRole,
            'name' => 'Output User',
            'emp_id' => 'OU-001',
            'email' => 'output@ntg.com.bd',
            'email_verified_at' => now(),
            'picture' => 'avatar.png',
            'dob' => '1993-04-04',
            'joining_date' => '2020-04-04',
            'division_id' => '1',
            'company_id' => '1',
            'department_id' => '1',
            'designation_id' => '1',
            'password' => bcrypt('123'),
            'password_text' => '123',
            'remember_token' => Str::random(10),
        ]);

        // Packing User
        User::create([
            'role_id' => $packingRole,
            'name' => 'Packing User',
            'emp_id' => 'PK-001',
            'email' => 'packing@ntg.com.bd',
            'email_verified_at' => now(),
            'picture' => 'avatar.png',
            'dob' => '1994-05-05',
            'joining_date' => '2020-05-05',
            'division_id' => '1',
            'company_id' => '1',
            'department_id' => '1',
            'designation_id' => '1',
            'password' => bcrypt('123'),
            'password_text' => '123',
            'remember_token' => Str::random(10),
        ]);

        // Shipment User
        User::create([
            'role_id' => $shipmentRole,
            'name' => 'Shipment User',
            'emp_id' => 'SH-001',
            'email' => 'shipment@ntg.com.bd',
            'email_verified_at' => now(),
            'picture' => 'avatar.png',
            'dob' => '1995-06-06',
            'joining_date' => '2020-06-06',
            'division_id' => '1',
            'company_id' => '1',
            'department_id' => '1',
            'designation_id' => '1',
            'password' => bcrypt('123'),
            'password_text' => '123',
            'remember_token' => Str::random(10),
        ]);

        // HR User
        User::create([
            'role_id' => $hrRole,
            'name' => 'HR User',
            'emp_id' => 'HR-001',
            'email' => 'hr@ntg.com.bd',
            'email_verified_at' => now(),
            'picture' => 'avatar.png',
            'dob' => '1996-07-07',
            'joining_date' => '2020-07-07',
            'division_id' => '1',
            'company_id' => '1',
            'department_id' => '1',
            'designation_id' => '1',
            'password' => bcrypt('123'),
            'password_text' => '123',
            'remember_token' => Str::random(10),
        ]);

        // QC User
        User::create([
            'role_id' => $qcRole,
            'name' => 'QC User',
            'emp_id' => 'QC-001',
            'email' => 'qc@ntg.com.bd',
            'email_verified_at' => now(),
            'picture' => 'avatar.png',
            'dob' => '1997-08-08',
            'joining_date' => '2020-08-08',
            'division_id' => '1',
            'company_id' => '1',
            'department_id' => '1',
            'designation_id' => '1',
            'password' => bcrypt('123'),
            'password_text' => '123',
            'remember_token' => Str::random(10),
        ]);
        
    }
}
