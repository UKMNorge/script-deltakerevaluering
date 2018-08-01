<?php

define('STANDALONE_URL', 'http://scripts.ukm.no/festivalfeedback-18/');
define('STANDALONE_AJAX', 'false');
define('STANDALONE_SECTION', 'Organisasjonen');
define('STANDALONE_TITLE', 'Tilbakemelding på UKM-festivalen 2018');
define('STANDALONE_DESCRIPTION', 'Deltakernes tilbakemeldinger oppsummert');
define('STANDALONE_TWIG_PATH', dirname(__FILE__) . '/twig/');

require_once('vendor/autoload.php');
require_once('vendor/ukmnorge/designbundle/UKMNorge/Standalone/Environment/loader.php');

require_once('excel.class.php');
require_once('PHPExcel/PHPExcel.php');
if (UKM_HOSTNAME != 'ukm.dev') {
	require_once('PHPExcel/IOFactory.php');
}
require_once('UKM/inc/excel.inc.php');


WP_TWIG::setCacheDir( dirname(__FILE__).'/cache/' );

$environment = [
	'needs_environment' => true,
	'is_safe' => ['html']
];

WP_TWIG::addFilter('count', ['data', 'count'], $environment);
WP_TWIG::addFilter('countByCol', ['data', 'countByCol'], $environment);
WP_TWIG::addFilter('header', ['data','header']);


$excelDoc = PHPExcel_IOFactory::load('datagrunnlag.xlsx');
$WP_TWIG_DATA['data'] = new data( $excelDoc->getSheet( 0 ) );


echo WP_TWIG::render( 'Excel/index', $WP_TWIG_DATA );
