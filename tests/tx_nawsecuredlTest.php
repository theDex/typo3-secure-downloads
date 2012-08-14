<?php

require_once(__DIR__ . '/../class.tx_nawsecuredl.php');

class tx_nawsecuredlTest extends tx_phpunit_testcase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Exclude TYPO3_DB from backup/ restore of $GLOBALS
	 * because resource types cannot be handled during serializing
	 *
	 * @var array
	 */
	protected $backupGlobalsBlacklist = array('TYPO3_DB');

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject|tx_nawsecuredl
	 */
	protected $fixture;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject|tslib_fe
	 */
	protected $fakeFrontend;

	public function setUp() {
		//$this->fixture = $this->getMock('tx_nawsecuredl', array('dummy'));
		$this->fakeFrontend = $this->getMock('tslib_fe');
		$this->fakeFrontend->page['cache_timeout'] = '0';
		$this->fakeFrontend->fe_user->user['uid'] = '###FEUSER###';
		$this->fakeFrontend->fe_user->user['usergroup'] = '###FEGROUPS###';
		$GLOBALS['TSFE'] = $this->fakeFrontend;
		$GLOBALS['EXEC_TIME'] = 0;
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function parserIsNotInvokedWhenExtensionIsDisabledByTypoScript() {
		$this->fakeFrontend->config['config']['tx_nawsecuredl_enable'] = '0';
		$dummy = array();

		$this->fixture = $this->getMock('tx_nawsecuredl', array('parseContent'));
		$this->fixture->expects($this->never())->method('parseContent');
		$this->fixture->parseFE($dummy, $this->fakeFrontend);
	}

	/**
	 * @test
	 */
	public function parserIsInvokedOnceWhenExtensionIsEnabledByTypoScript() {
		$this->fakeFrontend->config['config']['tx_nawsecuredl_enable'] = '1';
		$dummy = NULL;

		$this->fixture = $this->getMock('tx_nawsecuredl', array('parseContent'));
		$this->fixture->expects($this->once())->method('parseContent');
		$this->fixture->parseFE($dummy, $this->fakeFrontend);
	}

	/**
	 * @test
	 */
	public function parserIsInvokedOnceWhenTypoScriptConfigurationIsNotSet() {
		$this->fakeFrontend->config['config'] = array();
		$dummy = NULL;

		$this->fixture = $this->getMock('tx_nawsecuredl', array('parseContent'));
		$this->fixture->expects($this->once())->method('parseContent');
		$this->fixture->parseFE($dummy, $this->fakeFrontend);
	}

	public function parseContentTestDataProvider() {
		return array(
			'Image urls are properly replaced' => array('<img src="fileadmin/secure/image.jpg">', '<img src="securedl/###FEUSER###/###FEGROUPS###/86400/###HASH###/fileadmin/secure/image.jpg">'),
			'Image urls with strange cases are properly replaced' => array('<img src="fileadmin/secure/image.jPg">', '<img src="securedl/###FEUSER###/###FEGROUPS###/86400/###HASH###/fileadmin/secure/image.jPg">'),
			'XHTML Image urls are properly replaced' => array('<img src="fileadmin/secure/image.jpg" />', '<img src="securedl/###FEUSER###/###FEGROUPS###/86400/###HASH###/fileadmin/secure/image.jpg" />'),
			'Link urls are properly replaced' => array('<a href="fileadmin/secure/image.jpg">', '<a href="securedl/###FEUSER###/###FEGROUPS###/86400/###HASH###/fileadmin/secure/image.jpg">'),
			'Source urls are properly replaced' => array('<source src="fileadmin/secure/image.jpg">', '<source src="securedl/###FEUSER###/###FEGROUPS###/86400/###HASH###/fileadmin/secure/image.jpg">'),
			'Image urls in not secured directories are ignored' => array('<img src="fileadmin/not-secure/image.jpg">', '<img src="fileadmin/not-secure/image.jpg">'),
			'Image urls with not configured types are ignored' => array('<img src="fileadmin/secure/image.gif">', '<img src="fileadmin/secure/image.gif">'),
			'Link urls with not configured types are ignored' => array('<a href="fileadmin/secure/file.doc" target="_blank" title="Click to view the document (PDF, DOC, &hellip;)">', '<a href="fileadmin/secure/file.doc" target="_blank" title="Click to view the document (PDF, DOC, &hellip;)">'),
			'Link urls with configured types are not ignored' => array('<a href="fileadmin/secure/file.pdf" target="_blank" title="Click to view the document (PDF, DOC, &hellip;)">', '<a href="securedl/###FEUSER###/###FEGROUPS###/86400/###HASH###/fileadmin/secure/file.pdf" target="_blank" title="Click to view the document (PDF, DOC, &hellip;)">'),
		);
	}


	public function makeSecureMock($arg) {
		return 'secured/' . $arg;
	}

	/**
	 * @param $originalHtml
	 * @param $expectedHtml
	 * @test
	 * @dataProvider parseContentTestDataProvider
	 */
	public function allConfiguredAssetsAreReplacedInHtml($originalHtml, $expectedHtml) {
		$this->fixture = $this->getMock('tx_nawsecuredl', array('getHash', 'getExtensionConfiguration'));

		$this->fixture->expects($this->any())->method('getHash')->will($this->returnValue('###HASH###'));
		$this->fixture->expects($this->any())->method('getExtensionConfiguration')->will($this->returnValue(array(
			'securedDirs' => 'fileadmin/secure/',
			'domain' => '/',
			'filetype' => 'jpe?g|pdf',
			'cachetimeadd' => 0,
			'linkFormat' => 'securedl/###FEUSER###/###FEGROUPS###/###TIMEOUT###/###HASH###/###FILE###',
		)));

		$actualHtml = $this->fixture->parseContent($originalHtml);

		$this->assertSame($expectedHtml, $actualHtml);
	}
}

?>