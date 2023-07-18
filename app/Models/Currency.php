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

namespace App\Models;


use App\Observers\CurrencyObserver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Http\Controllers\Admin\Panel\Library\Traits\Models\Crud;

class Currency extends BaseModel
{
	use Crud, HasFactory;
	
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'currencies';
	
	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'code';
	
	/**
	 * The "type" of the primary key ID.
	 *
	 * @var string
	 */
	protected $keyType = 'string';
	
	public $incrementing = false;
	
	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var boolean
	 */
	//public $timestamps = false;
	
	/**
	 * The attributes that aren't mass assignable.
	 *
	 * @var array
	 */
	protected $guarded = ['id'];
	
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'code',
		'name',
		'symbol',
		'html_entities',
		'in_left',
		'decimal_places',
		'decimal_separator',
		'thousand_separator',
	];
	
	/**
	 * The attributes that should be hidden for arrays
	 *
	 * @var array
	 */
	// protected $hidden = [];
	
	/**
	 * The attributes that should be mutated to dates.
	 *
	 * @var array
	 */
	protected $dates = ['created_at', 'created_at'];
	
	/**
	 * User constructor.
	 *
	 * @param array $attributes
	 */
	public function __construct(array $attributes = [])
	{
		if (config('plugins.currencyexchange.installed')) {
			$this->fillable[] = 'rate';
		}
		
		parent::__construct($attributes);
	}
	
	/*
	|--------------------------------------------------------------------------
	| FUNCTIONS
	|--------------------------------------------------------------------------
	*/
	protected static function boot()
	{
		parent::boot();
		
		Currency::observe(CurrencyObserver::class);
	}
	
	public function getNameHtml(): string
	{
		$currentUrl = preg_replace('#/(search)$#', '', url()->current());
		$url = $currentUrl . '/' . $this->getKey() . '/edit';
		
		return '<a href="' . $url . '">' . $this->name . '</a>';
	}
	
	public function getSymbolHtml(): string
	{
		return html_entity_decode($this->symbol);
	}
	
	public function getPositionHtml(): string
	{
		if ($this->in_left == 1) {
			return '<i class="admin-single-icon fa fa-toggle-on" aria-hidden="true"></i>';
		} else {
			return '<i class="admin-single-icon fa fa-toggle-off" aria-hidden="true"></i>';
		}
	}
	
	public function getRateHtml(): string
	{
		$out = '-';
		
		if (isset($this->rate) && !empty($this->rate)) {
			$driver = config('currencyexchange.default');
			$currencyBase = config('currencyexchange.drivers.' . $driver . '.currencyBase', 'XXX');
			$info = '1 ' . $currencyBase . ' =';
			
			$tooltip = ' data-bs-toggle="tooltip" title="' . $info . '"';
			
			$out = '<span' . $tooltip . '>' . $this->rate . '</span>';
		}
		
		return $out;
	}
	
	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	public function countries()
	{
		return $this->hasMany(Country::class, 'currency_code', 'code');
	}
	
	/*
	|--------------------------------------------------------------------------
	| SCOPES
	|--------------------------------------------------------------------------
	*/
	
	/*
	|--------------------------------------------------------------------------
	| ACCESSORS | MUTATORS
	|--------------------------------------------------------------------------
	*/
	protected function id(): Attribute
	{
		return Attribute::make(
			get: fn ($value) => $this->attributes['code'] ?? $value,
		);
	}
	
	protected function symbol(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (trim($value) == '') {
					if (isset($this->attributes['symbol'])) {
						$value = $this->attributes['symbol'];
					}
				}
				if (trim($value) == '') {
					if (isset($this->attributes['html_entities'])) {
						$value = $this->attributes['html_entities'];
					}
				}
				if (trim($value) == '') {
					if (isset($this->attributes['code'])) {
						$value = $this->attributes['code'];
					}
				}
				
				return $value;
			},
		);
	}
	
	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
}
