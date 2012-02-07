<?php

class XHRUpload_Validator extends Upload_Validator{
	
	
	/**
	 * Run through the rules for this validator checking against
	 * the temporary file set by {@link setTmpFile()} to see if
	 * the file is deemed valid or not.
	 * 
	 * @return boolean
	 */
	public function validate() {
		// we don't validate for empty upload fields yet
		if(!isset($this->tmpFile['name']) || empty($this->tmpFile['name'])) return true;

		$isRunningTests = (class_exists('SapphireTest', false) && SapphireTest::is_running_test());
		//needed to allow XHR uploads
		/*if(isset($this->tmpFile['tmp_name']) && !is_uploaded_file($this->tmpFile['tmp_name']) && !$isRunningTests) {
			$this->errors[] = _t('File.NOVALIDUPLOAD', 'File is not a valid upload');
			return false;
		}*/

		$pathInfo = pathinfo($this->tmpFile['name']);
		// filesize validation
		if(!$this->isValidSize()) {
			$ext = (isset($pathInfo['extension'])) ? $pathInfo['extension'] : '';
			$arg = File::format_size($this->getAllowedMaxFileSize($ext));
			$this->errors[] = sprintf(
				_t(
					'File.TOOLARGE', 
					'Filesize is too large, maximum %s allowed.',
					PR_MEDIUM,
					'Argument 1: Filesize (e.g. 1MB)'
				),
				$arg
			);
			return false;
		}

		// extension validation
		if(!$this->isValidExtension()) {
			$this->errors[] = sprintf(
				_t(
					'File.INVALIDEXTENSION', 
					'Extension is not allowed (valid: %s)',
					PR_MEDIUM,
					'Argument 1: Comma-separated list of valid extensions'
				),
				wordwrap(implode(', ', $this->allowedExtensions))
			);
			return false;
		}
		
		return true;
	}
	
}