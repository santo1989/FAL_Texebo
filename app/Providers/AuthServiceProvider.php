<?php

namespace App\Providers;

use App\Models\OrderData;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //

        // Gate for Admin
        Gate::define('Admin', function (User $user) {
            return $user->role()->where('name', 'Admin')->exists();
        });

        // Gate for General
        Gate::define('General', function (User $user) {
            return $user->role()->where('name', 'General')->exists();
        });

        // Gate for Cutting
        Gate::define('Cutting', function (User $user) {
            return $user->role()->where('name', 'Cutting')->exists();
        });

        // Gate for Print Send
        Gate::define('Print Send', function (User $user) {
            return $user->role()->where('name', 'Print Send')->exists();
        });

        // Gate for Print Receive
        Gate::define('Print Receive', function (User $user) {
            return $user->role()->where('name', 'Print Receive')->exists();
        });

        // Gate for Input
        Gate::define('Input', function (User $user) {
            return $user->role()->where('name', 'Input')->exists();
        });

        // Gate for Output
        Gate::define('Output', function (User $user) {
            return $user->role()->where('name', 'Output')->exists();
        });

        // Gate for Packing
        Gate::define('Packing', function (User $user) {
            return $user->role()->where('name', 'Packing')->exists();
        });

        // Gate for Shipment
        Gate::define('Shipment', function (User $user) {
            return $user->role()->where('name', 'Shipment')->exists();
        });

        // Gate for HR
        Gate::define('HR', function (User $user) {
            return $user->role()->where('name', 'HR')->exists();
        });

        // Gate for Supervisor
        Gate::define('Supervisor', function (User $user) {
            return $user->role()->where('name', 'Supervisor')->exists();
        });

        // Gate for QC
        Gate::define('QC', function (User $user) {
            return $user->role()->where('name', 'QC')->exists();
        });

        // Optional: Super Admin Gate - Bypasses all other gates
        Gate::before(function (User $user) {
            if ($user->role()->where('name', 'Admin')->exists()) {
                return true;
            }
        });
        // OrderDataEntry
        Gate::define('OrderDataEntry', function (User $user) {
            return $user->role()->where('name', 'OrderDataEntry')->exists();
        });

        // DB::table('role')->get()->each(function ($role) {
        //     Gate::define($role->name, function (User $user) use ($role) {
        //         return $user->role_id == $role->id;
        //     });
        // });

    //     Gate::define('QC-CURD', function ($user) {
    //         return in_array($user->role->name, ['QC', 'Admin', 'Supervisor']);
    //     });
    //     Gate::define('QC-EDIT', function ($user) {
    //         return in_array($user->role->name, ['Admin', 'Supervisor']);
    //     });
   }
}
