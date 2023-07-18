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

namespace App\Http\Controllers\Admin;

// Increase the server resources
$iniConfigFile = __DIR__ . '/../../../Helpers/Functions/ini.php';
if (file_exists($iniConfigFile)) {
	include_once $iniConfigFile;
}

/*
------------------------------------------------------------------------------------
The "field" field value for "settings" table
------------------------------------------------------------------------------------
text            => {"name":"value","label":"Value","type":"text"}
textarea        => {"name":"value","label":"Value","type":"textarea"}
checkbox        => {"name":"value","label":"Activation","type":"checkbox"}
upload (image)  => {"name":"value","label":"Value","type":"image","upload":"true","disk":"uploads","default":"images/logo@2x.png"}
selectbox       => {"name":"value","label":"Value","type":"select_from_array","options":OPTIONS}
                => {"default":"Default","blue":"Blue","yellow":"Yellow","green":"Green","red":"Red"}
                => {"smtp":"SMTP","mailgun":"Mailgun","ses":"Amazon SES","mail":"PHP Mail","sendmail":"Sendmail"}
                => {"sandbox":"sandbox","live":"live"}
------------------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\Traits\SettingsTrait;
use App\Http\Requests\Admin\SettingRequest as StoreRequest;
use App\Http\Requests\Admin\SettingRequest as UpdateRequest;
use App\Http\Controllers\Admin\Panel\PanelController;
use App\Models\Setting;

class SettingController extends PanelController
{
	use SettingsTrait;
	
	public function __construct()
	{
		parent::__construct();
		
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel('App\Models\Setting');
		$this->xPanel->addClause('where', 'active', 1);
		$this->xPanel->setEntityNameStrings(trans('admin.general setting'), trans('admin.general settings'));
		$this->xPanel->setRoute(admin_uri('settings'));
		$this->xPanel->enableReorder('name', 1);
		$this->xPanel->allowAccess(['reorder']);
		$this->xPanel->denyAccess(['create', 'delete']);
		$this->xPanel->setDefaultPageLength(100);
		if (!request()->input('order')) {
			$this->xPanel->orderBy('lft', 'ASC');
			$this->xPanel->orderBy('id', 'ASC');
		}
		
		$this->xPanel->removeButton('update');
		$this->xPanel->addButtonFromModelFunction('line', 'configure', 'configureBtn', 'beginning');
		
		/*
		|--------------------------------------------------------------------------
		| COLUMNS AND FIELDS
		|--------------------------------------------------------------------------
		*/
		// COLUMNS
		$this->xPanel->addColumn([
			'name'          => 'name',
			'label'         => "Setting",
			'type'          => "model_function",
			'function_name' => 'getNameHtml',
		]);
		$this->xPanel->addColumn([
			'name'  => 'description',
			'label' => "",
		]);
		
		// FIELDS
		// ...
	}
	
	public function store(StoreRequest $request)
	{
		return parent::storeCrud($request);
	}
	
	public function update(UpdateRequest $request)
	{
		$setting = Setting::find(request()->segment(3));
		if (!empty($setting)) {
			// Get the right Setting
			$settingClassName = str($setting->key)->camel()->ucfirst() . 'Setting';
			$settingNamespace = '\\App\Models\Setting\\';
			$settingClass = $settingNamespace . $settingClassName;
			if (class_exists($settingClass)) {
				if (method_exists($settingClass, 'passedValidation')) {
					$request = $settingClass::passedValidation($request);
				}
			} else {
				$settingNamespace = plugin_namespace($setting->key) . '\app\Models\Setting\\';
				$settingClass = $settingNamespace . $settingClassName;
				// Get the plugin's setting
				if (class_exists($settingClass)) {
					if (method_exists($settingClass, 'passedValidation')) {
						$request = $settingClass::passedValidation($request);
					}
				}
			}
		}
		
		return $this->updateTrait($request);
	}
}
