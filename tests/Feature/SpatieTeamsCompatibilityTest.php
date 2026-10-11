<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Models\Permission;
use CoringaWc\FilamentAcl\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Workbench\App\Models\User;

class AclTeamPivot extends MorphPivot {}

class AclCustomPivotUser extends User
{
    protected $table = 'users';

    /** @return BelongsToMany<Model, Model, AclTeamPivot> */
    public function roles(): BelongsToMany
    {
        return parent::roles()->using(AclTeamPivot::class);
    }

    /** @return BelongsToMany<Model, Model, AclTeamPivot> */
    public function permissions(): BelongsToMany
    {
        return parent::permissions()->using(AclTeamPivot::class);
    }
}

foreach ([false, true] as $customPivot) {
    foreach (['removeRole', 'syncRoles', 'revokePermissionTo', 'syncPermissions'] as $operation) {
        test('team isolation during ' . $operation . ' custom pivot=' . (int) $customPivot, function () use ($customPivot, $operation): void {
            // Only the Testbench in-memory database is modified.
            expect(config('database.connections.testing.database'))->toBe(':memory:');
            Schema::table('roles', function (Blueprint $table): void {
                $table->unsignedBigInteger('team_id')->nullable();
            });
            foreach (['acl_team_roles' => 'role_id', 'acl_team_permissions' => 'permission_id'] as $tableName => $relatedKey) {
                Schema::create($tableName, function (Blueprint $table) use ($relatedKey): void {
                    $table->unsignedBigInteger('team_id');
                    $table->unsignedBigInteger($relatedKey);
                    $table->unsignedBigInteger('model_id');
                    $table->string('model_type');
                    $table->primary(['team_id', $relatedKey, 'model_id', 'model_type']);
                });
            }
            config()->set('permission.teams', true);
            config()->set('permission.events_enabled', $customPivot);
            config()->set('permission.table_names.model_has_roles', 'acl_team_roles');
            config()->set('permission.table_names.model_has_permissions', 'acl_team_permissions');
            config()->set('permission.models', ['role' => Role::class, 'permission' => Permission::class]);
            $registrar = app(PermissionRegistrar::class);
            $registrar->setRoleClass(Role::class)->setPermissionClass(Permission::class);
            $registrar->initializeCache();
            $registrar->forgetCachedPermissions();
            $userClass = $customPivot ? AclCustomPivotUser::class : User::class;
            $user = new $userClass(['name' => 'Team fixture', 'email' => 'team@example.test', 'password' => 'test']);
            $user->save();
            $role = Role::create(['name' => 'global-reader', 'guard_name' => 'web', 'team_id' => null]);
            $permission = Permission::findOrCreate('team.read', 'web');

            try {
                foreach ([1, 2] as $teamId) {
                    $registrar->setPermissionsTeamId($teamId);
                    $user->unsetRelation('roles')->unsetRelation('permissions');
                    $user->assignRole($role);
                    $user->givePermissionTo($permission);
                }
                $registrar->setPermissionsTeamId(1);
                $user->unsetRelation('roles')->unsetRelation('permissions');
                match ($operation) {
                    'removeRole' => $user->removeRole($role),
                    'syncRoles' => $user->syncRoles([]),
                    'revokePermissionTo' => $user->revokePermissionTo($permission),
                    'syncPermissions' => $user->syncPermissions([]),
                };
                $isRole = in_array($operation, ['removeRole', 'syncRoles'], true);
                $pivotTable = $isRole ? 'acl_team_roles' : 'acl_team_permissions';
                expect(DB::table($pivotTable)->where('team_id', 1)->count())->toBe(0)
                    ->and(DB::table($pivotTable)->where('team_id', 2)->count())->toBe(1);

                $registrar->setPermissionsTeamId(2);
                $user->unsetRelation('roles')->unsetRelation('permissions');
                expect($isRole ? $user->hasRole($role) : $user->hasPermissionTo($permission))->toBeTrue();
            } finally {
                $registrar->setPermissionsTeamId(null);
                $registrar->forgetCachedPermissions();
            }
        });
    }
}
