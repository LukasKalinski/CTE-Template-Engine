<?php
header('Content-type: text/plain');

if (PATH_SEPARATOR == ';') {
  ini_set('include_path', 'C:\\home\\cylab\\Projekt\\cte\\wdir\\svncheckout\\trunk\\library'
                        . PATH_SEPARATOR
                        . 'C:\\home\\cylab\\Projekt\\phpframework\\wdir\\svncheckout\\trunk');
}

define('PROJECT_ROOT', '/sites/sfish.cte.veloci.se/');

require 'CTE.php';
require 'CTE/Config.php';

class CTE_Default_Config extends CTE_Config
{
  public function __construct()
  {
    parent::__construct('sample_project');
    if (PATH_SEPARATOR == ';') {
      $this->projectRoot = 'C:\\home\\cylab\\Projekt\\cte\\wdir\\svncheckout\\trunk\\projects\\sample_project\\';
    }
  }
}

function get_microtime()
{
  list($usec, $sec) = explode(' ', microtime());
	return ((float)$usec + (float)$sec);
}
$time_start = get_microtime();

class TestClass {
  public $foo = 'hej';
  public function korv2($arg) { return $arg.', wie!'; }
}
$a = new TestClass;
$cte = new CTE(new CTE_Default_Config());
$cte->setCache(5);
$cte->registerVar('obj', $a);
$cte->createVar('title', 'Page Title');
$cte->createVar('name', 'Pekka');
$cte->createVar('month', 2);
$cte->createVar('months', array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'));
$cte->display('test.tpl');

$time = round(get_microtime() - $time_start, 3);

exit("\n\n***\n# index loaded in $time s");
?>