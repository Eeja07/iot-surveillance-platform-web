<?php

namespace App\Http\Controllers\Pages\Setting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission; // Import model Permission jika Anda ingin mengelola permission di sini juga
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
  public function __construct()
  {
    // Only Admin can access all methods in this controller
    $this->middleware(['auth', 'role:admin']);
  }

  /**
   * Display a listing of the roles and/or create/edit forms based on query parameters.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function index(Request $request)
  {
    // Always retrieve all roles for the list
    $roles = Role::all();

    $showCreateForm = false;
    $showEditForm = false;
    $editingRole = null;
    // $permissions = Permission::all(); // Uncomment if managing permissions in forms
    // $rolePermissions = []; // Uncomment if managing permissions in forms

    // Check 'action' query parameter
    if ($request->query('action') === 'create') {
      $showCreateForm = true;
    } elseif ($request->query('action') === 'edit' && $request->query('role_id')) {
      $editingRole = Role::find($request->query('role_id'));
      if ($editingRole) {
        $showEditForm = true;
        // $rolePermissions = $editingRole->permissions->pluck('name')->toArray(); // Uncomment if managing permissions
      } else {
        // If role not found, redirect back to the list with an error message
        return redirect()->route('settings.roles.index')->with('error', 'Role to edit not found.');
      }
    }

    // Pass all potentially needed data to the view
    return view('content.pages.settings.managerole', compact(
      'roles',
      'showCreateForm',
      'showEditForm',
      'editingRole'
      // 'permissions', // Uncomment if managing permissions
      // 'rolePermissions' // Uncomment if managing permissions
    ));
  }

  /**
   * Redirects to the index page with a flag to show the create form.
   * This will be called by the "Add New Role" button.
   *
   * @return \Illuminate\Http\Response
   */
  public function create()
  {
    return redirect()->route('settings.roles.index', ['action' => 'create']);
  }

  /**
   * Store a newly created role in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(Request $request)
  {
    $request->validate([
      'name' => 'required|string|max:255|unique:roles,name',
      // 'permissions' => 'nullable|array', // Uncomment if managing permissions
      // 'permissions.*' => 'exists:permissions,name',
    ]);

    $role = Role::create(['name' => $request->name]);

    // If you are managing permissions here:
    // $role->syncPermissions($request->permissions);

    return redirect()->route('settings.roles.index')->with('success', 'Role successfully added.');
  }

  /**
   * Redirects to the index page with a flag to show the edit form.
   * This will be called by the "Edit" button in the table.
   *
   * @param  \Spatie\Permission\Models\Role  $role
   * @return \Illuminate\Http\Response
   */
  public function edit(Role $role)
  {
    return redirect()->route('settings.roles.index', ['action' => 'edit', 'role_id' => $role->id]);
  }

  /**
   * Update the specified role in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Spatie\Permission\Models\Role  $role
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request, Role $role)
  {
    $request->validate([
      'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
      // 'permissions' => 'nullable|array', // Uncomment if managing permissions
      // 'permissions.*' => 'exists:permissions,name',
    ]);

    $role->update(['name' => $request->name]);

    // If you are managing permissions here:
    // $role->syncPermissions($request->permissions);

    return redirect()->route('settings.roles.index')->with('success', 'Role successfully updated.');
  }

  /**
   * Remove the specified role from storage.
   *
   * @param  \Spatie\Permission\Models\Role  $role
   * @return \Illuminate\Http\Response
   */
  public function destroy(Role $role)
  {
    // Prevent deletion of default roles like 'Admin', 'Perawat', 'Dokter' if necessary
    if (in_array($role->name, ['admin', 'Perawat', 'Dokter'])) {
      return redirect()->route('settings.roles.index')->with('error', 'Default roles cannot be deleted.');
    }

    $role->delete();
    return redirect()->route('settings.roles.index')->with('success', 'Role successfully deleted.');
  }
}
