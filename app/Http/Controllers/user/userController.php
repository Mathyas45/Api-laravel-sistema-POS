<?php

namespace App\Http\Controllers\user;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class userController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get("search");
        $users = User::where("name", "ilike", "%" . $search . "%")
            ->orderBy("id", "desc")
            ->get();
        return response()->json([
            "users" => $users->map(function ($user) {
                return [
                    "id" => $user->id,
                    "name" => $user->name,
                    "surname" => $user->surname,
                    "full_name" => $user->name . " " . $user->surname,
                    "email" => $user->email,
                    "role_id" => $user->role_id,
                    "role" => $user->role->name,
                    "phone" => $user->phone,
                    "sucursal_id" => $user->sucursal_id,
                    "sucursal" => $user->sucursal->name,
                    "avatar" => $user->avatar ? env("APP_URL") . "storage/" . $user->avatar : null,
                    "type_document" => $user->type_document,
                    "n_document" => $user->n_document,
                    "gender" => $user->gender,
                    "created_at" => $user->created_at->format("Y/m/d h:i:s"),
                    "reg_estado" => $user->reg_estado,

                ];
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            "name" => "required|string|max:255",
            "email" => "required|string|email|max:255|unique:users,email",
            "password" => "required|string|min:8",
            "surname" => "required|string|max:255",
            "role_id" => "required|integer",
            "sucursal_id" => "required|integer",
            "phone" => "string|max:255",
            "type_document" => "string|max:255",
            "n_document" => "string|max:255",
            "gender" => "string|max:255",
        ]);
        $is_user_exist = User::where("email", $request->email)->first();
        if ($is_user_exist) {
            return response()->json([
                "message" => 403,
                "message_text" => "El usuario ya existe",
            ]);
        }

        if ($request->hasFile("imagen")) {
            $path = Storage::putFile("users", $request->file("imagen"));
            $request->request->add(["avatar", $path]);
        }
        if ($request->password) {
            $request->request->add(["password", bcrypt($request->password)]);
        }
        $user = User::create($request->all());
        $role = Role::findOrFail($request->role_id);
        $user->assignRole($role);
        return response()->json([
            "message" => 200,
            "user" => [
                "id" => $user->id,
                "name" => $user->name,
                "surname" => $user->surname,
                "full_name" => $user->name . " " . $user->surname,
                "email" => $user->email,
                "role_id" => $user->role_id,
                "role" => $user->role->name,
                "phone" => $user->phone,
                "sucursal_id" => $user->sucursal_id,
                "sucursal" => $user->sucursal->name,
                "avatar" => $user->avatar ? env("APP_URL") . "storage/" . $user->avatar : null,
                "type_document" => $user->type_document,
                "n_document" => $user->n_document,
                "gender" => $user->gender,
                "created_at" => $user->created_at->format("Y/m/d h:i:s"),
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
            "name" => "required|string|max:255",
            "email" => "required|string|email|max:255|unique:users,email",
            "password" => "required|string|min:8",
            "surname" => "required|string|max:255",
            "role_id" => "required|integer",
            "sucursal_id" => "required|integer",
            "phone" => "string|max:255",
            "type_document" => "string|max:255",
            "n_document" => "string|max:255",
            "gender" => "string|max:255",
        ]);
        $is_user_exist = User::where("email", $request->email)->where("id", "<>", $id)->first();
        if ($is_user_exist) {
            return response()->json([
                "message" => 403,
                "message_text" => "El usuario ya existe",
            ]);
        }

        $user = User::findOrFail($id);

        if ($request->hasFile("imagen")) {
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }
            $path = Storage::putFile("users", $request->file("imagen"));
            $request->request->add(["avatar", $path]);
        }
        if ($request->password) {
            $request->request->add(["password", bcrypt($request->password)]);
        }
        $user = User::update($request->all());
        if ($request->role != $user->role_id) {
            $roleOld = Role::findOrFail($user->role_id);
            $user->removeRole($roleOld);

            $roleNew = Role::findOrFail($request->role_id);
            $user->assignRole($roleNew);
        }
        $role = Role::findOrFail($request->role_id);
        $user->assignRole($role);
        return response()->json([
            "message" => 200,
            "user" => [
                "id" => $user->id,
                "name" => $user->name,
                "surname" => $user->surname,
                "full_name" => $user->name . " " . $user->surname,
                "email" => $user->email,
                "role_id" => $user->role_id,
                "role" => $user->role->name,
                "phone" => $user->phone,
                "sucursal_id" => $user->sucursal_id,
                "sucursal" => $user->sucursal->name,
                "avatar" => $user->avatar ? env("APP_URL") . "storage/" . $user->avatar : null,
                "type_document" => $user->type_document,
                "n_document" => $user->n_document,
                "gender" => $user->gender,
                "updated_at" => $user->created_at->format("Y/m/d h:i:s"),
                "reg_estado" => '2',

            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //eliminacion de rol pero eliminacion logica
        $user = User::findOrFail($id);
        if (!$user) {
            return response()->json([
                "message" => 404,
                "message_text" => "El usuario no existe",
            ]);
        }
        $user->update([
            "reg_estado" => '0',
            "deleted_at" => now(),
        ]);
        return response()->json([
            "message" => 200
        ]);
    }
}
