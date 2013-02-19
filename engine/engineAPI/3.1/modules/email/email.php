<?php
/**
 * Helper class for email
 */
class email {
	/**
	 * Validate an email address
	 *
	 * @see validate::emailAddr()
	 * @param $email
	 * @param bool $internal Passed through to validate::emailAddr()
	 * @return bool
	 */
	public static function validate($email,$internal=FALSE) {
		return(validate::emailAddr($email,$internal));
	}

	/**
	 * Validate internal email
	 *
	 * @see validate::internalEmailAddr()
	 * @param $email
	 * @return bool
	 */
	public static function internalEmailAddr($email) {
		return(validate::internalEmailAddr($email));
	}
}

?>