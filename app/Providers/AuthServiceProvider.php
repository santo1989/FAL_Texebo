<?php

namespace App\Providers;

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

        // Gate::define('Admin', function (User $user) {

        //     if ($user->role_id == 1) {
        //         return true;
        //     }

        //     return false;
        // });

        // Gate::define('General', function (User $user) {

        //     if ($user->role_id == 2) {
        //         return true;
        //     }
        //     return false;
        // });

        // Gate::define('Factory', function (User $user) {

        //     if ($user->role_id == 3) {
        //         return true;
        //     }
        //     return false;
        // });

        // Gate::define('SupplyChain', function (User $user) {

        //     if ($user->role_id == 4) {
        //         return true;
        //     }
        //     return false;
        // });

        // Gate::define('Commercial', function (User $user) {

        //     if ($user->role_id == 5) {
        //         return true;
        //     }
        //     return false;
        // });

        // Gate::define('Accounts', function (User $user) {

        //     if ($user->role_id == 6) {
        //         return true;
        //     }
        //     return false;
        // });

        // Gate::define('TNA-Factory', function ($user) {
        //     return in_array($user->role->name, ['SuperVisor', 'Admin', 'Factory Merchandise']);
        // });

        DB::table('roles')->get()->each(function ($role) {
            Gate::define($role->name, function (User $user) use ($role) {
                return $user->role_id == $role->id;
            });
        });

    //     Gate::define('QC-CURD', function ($user) {
    //         return in_array($user->role->name, ['QC', 'Admin', 'Supervisor']);
    //     });
    //     Gate::define('QC-EDIT', function ($user) {
    //         return in_array($user->role->name, ['Admin', 'Supervisor']);
    //     });
   }
}
