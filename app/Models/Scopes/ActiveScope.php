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

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

class ActiveScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
	 * @param \Illuminate\Database\Eloquent\Builder $builder
	 * @param \Illuminate\Database\Eloquent\Model $model
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
    public function apply(Builder $builder, Model $model)
    {
    	// Load only activated entries on Settings selection
		if (str_contains(Route::currentRouteAction(), 'App\Http\Controllers\Admin\SettingController')) {
			return $builder->where('active', 1);
		}
		
		// Load all entries for the Admin panel
        if (request()->segment(1) == admin_uri()) {
            return $builder;
        }
        
        // Load only activated entries for the front
        return $builder->where('active', 1);
    }
}
