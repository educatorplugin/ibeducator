<?php

/**
 * Test email class.
 */
class IB_Educator_Test_Email extends IB_Educator_Tests {
	/**
	 * Test filter method.
	 */
	public function testFilter() {
		require_once IBEDUCATOR_PLUGIN_DIR . '/includes/ib-educator-email.php';
		$ibe_email = new IB_Educator_Email();
		$str1 = "\na\rb%0Ac\r\n%0D<CR><LF>";
		$str2 = <<<EOT
de\n
f\r\n
EOT;
		$this->assertEquals( 'abc', $ibe_email->filter( $str1 ) );
		$this->assertEquals( 'def', $ibe_email->filter( $str2 ) );
	}
}