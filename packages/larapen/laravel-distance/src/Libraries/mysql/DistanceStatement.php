<?php

namespace Larapen\LaravelDistance\Libraries\mysql;

use Larapen\LaravelDistance\Helper;

class DistanceStatement
{
	/**
	 * Get 'SELECT' statement column
	 *
	 * @param string $aLon
	 * @param string $aLat
	 * @param float $bLon
	 * @param float $bLat
	 * @return false|string
	 */
	public static function select(string $aLon, string $aLat, float $bLon, float $bLat)
	{
		// Get the Distance Calculation Formula
		$distanceCalculationFormula = config('distance.functions.default');
		
		// If the selected MySQL function doesn't exist...
		// If the 'haversine' or 'orthodromy' is selected, use the function formula as inline SQL
		// Else use the cities standard searches
		if (!DistanceHelper::checkIfDistanceCalculationFunctionExists($distanceCalculationFormula)) {
			if (in_array($distanceCalculationFormula, ['haversine', 'orthodromy'])) {
				$point1 = 'POINT(' . $aLon . ', ' . $aLat . ')';
				$point2 = 'POINT(' . $bLon . ', ' . $bLat . ')';
				
				$sql = DistanceHelper::$distanceCalculationFormula($point1, $point2);
				
				return $sql;
			}
			
			return false;
		} else {
			// Call the MySQL function (The result is in Meters)
			$formula = $distanceCalculationFormula . '(POINT(' . $aLon . ', ' . $aLat . '), POINT(' . $bLon . ', ' . $bLat . '))';
			
			// Meters To Km
			$formula = $formula . ' / 1000';
			
			// If the selected Country uses Miles unit, then convert Km To Miles
			if (Helper::isMilesUsingCountry(config('distance.countryCode'))) {
				$formula = '(' . $formula . ') * 0.621371192';
			}
			
			// Get the Distance calculation SQL query
			$sql = '(' . $formula . ') AS ' . config('distance.rename');
			
			return $sql;
		}
	}
	
	/**
	 * Get 'HAVING' statement condition
	 *
	 * @param int|null $distance
	 * @return string
	 */
	public static function having(int $distance = null): string
	{
		if ($distance === null) {
			$distance = config('distance.defaultDistance');
		}
		
		$distance = (int)$distance;
		
		// Fix the distance value that have to be greater than 0 (at least).
		if ($distance <= 0) {
			$distance = 1;
		}
		
		$sql = config('distance.rename') . ' <= ' . $distance;
		
		return $sql;
	}
	
	/**
	 * Get 'ORDER BY' rule
	 *
	 * @param string|null $order
	 * @return string
	 */
	public static function orderBy(string $order = null): string
	{
		if ($order === null) {
			$order = config('distance.orderBy');
		}
		
		if (!in_array(strtoupper($order), ['ASC', 'DESC'])) {
			$order = 'ASC';
		}
		
		$sql = config('distance.rename') . ' ' . strtoupper($order);
		
		return $sql;
	}
}
