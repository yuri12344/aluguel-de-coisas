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

namespace App\Http\Requests;

class ResetPasswordRequest extends AuthRequest
{
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = parent::rules();
		
		$rules['token'] = ['required'];
		$rules['password'] = ['required', 'confirmed'];
		
		$rules = $this->validEmailRules('email', $rules);
		$rules = $this->validPhoneNumberRules('phone', $rules);
		
		$rules['phone_country'] = ['required_with:phone'];
		
		return $this->validPasswordRules('password', $rules);
	}
}
