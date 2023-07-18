<?php
/**
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - http://codecanyon.net/licenses/standard
 */

namespace App\Http\Middleware;

use App\Models\Permission;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Prologue\Alerts\Facades\Alert;

class Clearance
{
	/**
	 * Handle an incoming request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		try {
			$aclTableNames = config('permission.table_names');
			if (isset($aclTableNames['permissions'])) {
				// Check if the 'permissions' table exists
				$cacheId = 'permissionsTableExists';
				$cacheExpiration = (int)config('settings.optimization.cache_expiration', 86400) * 5;
				$permissionsTableExists = cache()->remember($cacheId, $cacheExpiration, function () use ($aclTableNames) {
					return Schema::hasTable($aclTableNames['permissions']);
				});
				
				if (!$permissionsTableExists) {
					return $next($request);
				}
			}
		} catch (\Throwable $e) {
			return $next($request);
		}
		
		// If user has this //permission
		if (userHasSuperAdminPermissions()) {
			return $next($request);
		}
		
		// Get all routes that have permissions
		$routesPermissions = Permission::getRoutesPermissions();
		if (!empty($routesPermissions)) {
			foreach ($routesPermissions as $key => $route) {
				if (!isset($route['uri']) || !isset($route['permission']) || !isset($route['methods'])) {
					continue;
				}
				
				// If the current route found, ...
				if ($request->is($route['uri']) && in_array($request->method(), $route['methods'])) {
					
					// Check if user has permission to perform this action
					if (!auth()->user()->can($route['permission'])) {
						return $this->unauthorized($request);
					}
					
				}
			}
		}
		
		// If the logged admin user has permissions to manage users and is has not 'super-admin' role,
		// don't allow him to manage 'super-admin' role's users.
		if (isAdminPanel() && auth()->check()) {
			$userController = 'Controllers\Admin\UserController';
			if (
				str_contains(Route::currentRouteAction(), $userController . '@edit')
				|| str_contains(Route::currentRouteAction(), $userController . '@update')
				|| str_contains(Route::currentRouteAction(), $userController . '@show')
				|| str_contains(Route::currentRouteAction(), $userController . '@destroy')
			) {
				// Get the current possible 'super-admin' User ID
				$userId = request()->segment(3);
				if (!empty($userId) && is_numeric($userId)) {
					// If the logged admin user has not 'super-admin' role...
					if (!auth()->user()->can(Permission::getSuperAdminPermissions())) {
						try {
							$user = User::withoutGlobalScopes([VerifiedScope::class])
								->where('id', $userId)
								->role('super-admin')
								->first(['id', 'created_at']);
							
							// If the current User ID is for a 'super-admin' role's user,
							// don't allow the logged user (admin) to manage him (since he doesn't have 'super-admin' role).
							if (!empty($user)) {
								return $this->unauthorized($request);
							}
						} catch (\Throwable $e) {}
					}
				}
			}
		}
		
		return $next($request);
	}
	
	/**
	 * Unauthorized access
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
	 */
	private function unauthorized(Request $request)
	{
		if ($request->ajax() || $request->wantsJson()) {
			return response(trans('admin.unauthorized'), 401);
		} else {
			Alert::error(trans('admin.unauthorized'))->flash();
			
			return redirect()->back();
		}
	}
}
