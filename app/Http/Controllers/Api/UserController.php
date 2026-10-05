<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Listar usuarios registrados
     */
    public function index(Request $request)
    {
        $query = User::query()->orderBy('name', 'asc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('cargo', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $usuarios = $query->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role ?? 'archivista',
                'cargo' => $u->cargo,
                'permissions' => $u->permissions ?? [],
                'activo' => (bool)($u->activo ?? true),
                'created_at' => $u->created_at?->format('Y-m-d H:i'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $usuarios,
        ]);
    }

    /**
     * Crear un nuevo usuario con rol y permisos
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:admin,archivista,operador,consulta',
            'cargo' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'activo' => 'boolean',
        ]);

        // Si no se envían permisos explícitos, asignar permisos por defecto según el rol
        $permissions = $validated['permissions'] ?? $this->defaultPermissionsForRole($validated['role']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'cargo' => $validated['cargo'] ?? null,
            'permissions' => $permissions,
            'activo' => $validated['activo'] ?? true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario creado exitosamente',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'cargo' => $user->cargo,
                'permissions' => $user->permissions,
                'activo' => $user->activo,
                'created_at' => $user->created_at?->format('Y-m-d H:i'),
            ],
        ], 201);
    }

    /**
     * Actualizar usuario existente
     */
    public function update(Request $request, User $usuario)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users')->ignore($usuario->id)],
            'password' => 'nullable|string|min:6',
            'role' => 'sometimes|required|string|in:admin,archivista,operador,consulta',
            'cargo' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'activo' => 'boolean',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $usuario->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario actualizado correctamente',
            'data' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'role' => $usuario->role,
                'cargo' => $usuario->cargo,
                'permissions' => $usuario->permissions,
                'activo' => $usuario->activo,
            ],
        ]);
    }

    /**
     * Eliminar usuario
     */
    public function destroy(Request $request, User $usuario)
    {
        if ($request->user() && $request->user()->id === $usuario->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'No puedes eliminar tu propia cuenta en sesión.',
            ], 422);
        }

        $usuario->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario eliminado correctamente',
        ]);
    }

    /**
     * Permisos predeterminados por rol
     */
    private function defaultPermissionsForRole(string $role): array
    {
        return match ($role) {
            'admin' => [
                'crear_expediente',
                'editar_expediente',
                'eliminar_expediente',
                'subir_fojas',
                'desglosar_pdf',
                'gestionar_archivadores',
                'gestionar_usuarios',
            ],
            'archivista' => [
                'crear_expediente',
                'editar_expediente',
                'subir_fojas',
                'desglosar_pdf',
                'gestionar_archivadores',
            ],
            'operador' => [
                'subir_fojas',
                'desglosar_pdf',
                'editar_expediente',
            ],
            'consulta' => [],
            default => [],
        };
    }
}
