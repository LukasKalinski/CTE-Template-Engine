<?php
/**
 * CTE Test Suite
 *
 * @package CTE Test
 * @since 2006-07-17
 * @version 2006-07-17
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

require_once('PHPUnitTestAll.php');

define('PROJECT_ROOT', dirname(dirname(__FILE__)).'/library/');
set_include_path(get_include_path() . ':' . PROJECT_ROOT);

/**
 * @access private
 */
class AllTests extends PHPUnitTestAll { }

AllTests::setFromFile(__FILE__);
AllTests::run();
?>