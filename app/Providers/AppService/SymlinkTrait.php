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

namespace App\Providers\AppService;

use Illuminate\Support\Facades\Artisan;

trait SymlinkTrait
{
	/**
	 * Setup Storage Symlink
	 * Check the local storage symbolic link and Create it, if it does not exist.
	 */
	private function setupStorageSymlink()
	{
		$symlink = public_path('storage');
		
		try {
			if (!is_link($symlink)) {
				// Symbolic links on Windows are created by symlink() which accept only absolute paths.
				// Relative paths on Windows are not supported for symlinks: http://php.net/manual/en/function.symlink.php
				if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
					Artisan::call('storage:link');
				} else {
					symlink('../storage/app/public', './storage');
				}
			}
		} catch (\Throwable $e) {
			$message = ($e->getMessage() != '') ? $e->getMessage() : 'Error with the PHP symlink() function';
			
			$docSymlink = 'https://support.laraclassifier.com/help-center/articles/71/images-dont-appear-in-my-website';
			$docDirExists = 'https://support.laraclassifier.com/help-center/articles/1/10/80/symlink-file-exists-or-no-such-file-or-directory';
			if (
				str_contains($message, 'File exists')
				|| str_contains($message, 'No such file or directory')
			) {
				$docSymlink = $docDirExists;
			}
			
			$message = $message . ' - Please <a href="' . $docSymlink . '" target="_blank">see this article</a> for more information.';
			
			flash($message)->error();
		}
	}
}
