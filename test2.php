<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
use UKMNorge\Design\UKMDesign;
use UKMNorge\Design\Sitemap\Section;
use UKMNorge\TemplateEngine\Proxy\Twig;
use UKMNorge\TemplateEngine\Vanilla;

require_once('UKMconfig.inc.php');
require_once('vendor/autoload.php');

require_once('UKM/Autoloader.php');
require_once('UKM/vendor/autoload.php');

/**
 * Extend Twig
 */
$environment = [
	'needs_environment' => true,
	'is_safe' => ['html']
];
Twig::addFilter('count', ['data', 'count'], $environment);
Twig::addFilter('countByCol', ['data', 'countByCol'], $environment);
Twig::addFilter('header', ['data','header']);

/**
 * Init Vanilla
 */
Vanilla::init(__DIR__);

// Set where we are
UKMDesign::setCurrentSection(
    new Section(
        'current',
        'https://scripts.ukm.no/',
        'UKM'
    )
);

// Do the magic
require_once('excel.class.php');
$excelDoc = IOFactory::load('datagrunnlag.xlsx');
Vanilla::addViewData('data', new data($excelDoc->getSheet(0)));
echo Vanilla::render('Excel/index');