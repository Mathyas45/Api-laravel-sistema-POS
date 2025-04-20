<?php

namespace App\Http\Controllers\Roles;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $search = request()->query("search");
        $roles = Role::where("name", "ilike", "%" . $search . "%")
            ->orderBy("id", "desc")->get();
        return response()->json([
            "roles" => $roles->map(function ($role) {
                return [
                    "id" => $role->id,
                    "name" => $role->name,
                    "created_at" => $role->created_at->format("Y/m/d h:i:s"),
                    "permissions" => $role->permissions->map(function ($permission) {
                        return [
                            "id" => $permission->id,
                            "name" => $permission->name,
                        ];
                    }),
                    //ponerlo en un array simple
                    "permissions_pluck" => $role->permissions->pluck("name"),
                ];
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $exist_role = Role::where("name", $request->name)->first();
        if ($exist_role) {
            return response()->json([
                "message" => "403",
                "message_text" => "El rol ya existe",
            ]);
        }
        $request->validate([
            "name" => "required|string|max:255|unique:roles,name",
        ]);
        $role = Role::create([
            "name" => $request->name,
            "guard_name" => "api"
        ]);
        //enlazar con los permisos que tenga
        $permissions = $request->permissions;
        foreach ($permissions as $permission) {
            $role->givePermissionTo($permission);
        }
        return response()->json([
            "message" => 200,
            "role" => [
                "id" => $role->id,
                "name" => $role->name,
                "created_at" => $role->created_at->format("Y/m/d h:i:s"),
                "permissions" => $role->permissions->map(function ($permission) {
                    return [
                        "id" => $permission->id,
                        "name" => $permission->name,
                    ];
                }),
                //ponerlo en un array simple
                "permissions_pluck" => $role->permissions->pluck("name"),
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            "name" => "required|string|max:255|unique:roles,name," . $id,
        ]);
        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                "message" => "Rol no encontrado",
            ], 404);
        }
        $role->update([
            "name" => $request->name,
        ]);
        //enlazar con los permisos que tenga
        $permissions = $request->permissions;
        $role->syncPermissions($permissions);

        return response()->json([
            "message" => 200,
            "role" => [
                "id" => $role->id,
                "name" => $role->name,
                "created_at" => $role->created_at->format("Y/m/d h:i:s"),
                "permissions" => $role->permissions->map(function ($permission) {
                    return [
                        "id" => $permission->id,
                        "name" => $permission->name,
                    ];
                }),
                //ponerlo en un array simple
                "permissions_pluck" => $role->permissions->pluck("name"),
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //eliminacion de rol pero eliminacion logico
        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                "message" => "Rol no encontrado",
            ], 404);
        }
        $role->delete();
        return response()->json([
            "message" => 200,
            "role" => $role,
        ]);
    }
}
