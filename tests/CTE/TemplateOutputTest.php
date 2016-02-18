<?php
/**
 *
 *
 * @package CTE.Test
 * @since 2007-01-03
 * @version 2007-01-11
 * @copyright Cylab 2007
 * @author Lukas Kalinski
 */

require_once('PHPUnit2/Framework.php');

require_once('CTE.php');
require_once('CTE/Config.php');

/**
 * Custom CTE_config sub-class, setting up our paths.
 * 
 * @access private
 */
class CTE_Config_Test extends CTE_Config
{
  public function __construct()
  {
    parent::__construct('Test Project');
    $dir = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
    
    $this->debugMode = self::DEBUG_CLI;
    
    // We're ignoring default location for CTE projects:
    $projectRoot       = $dir
                       . 'test_project'
                       . DIRECTORY_SEPARATOR;
    $this->tplRoot     = $projectRoot
                       . 'input'
                       . DIRECTORY_SEPARATOR;
    $this->stplRoot    = $projectRoot
                       . 'input'
                       . DIRECTORY_SEPARATOR;
    $this->tplcRoot    = $projectRoot
                       . 'actual_output'
                       . DIRECTORY_SEPARATOR;
    
    $this->forceCompile = true;
    $this->compilerStamp = false;
  }
}

/**
 * Sample class for testing purposes.
 * 
 * @access private
 */
class Fruit
{
  public $type;
  public $color = 'green';
  public $friends = array();
  
  public function __construct($type)
  {
    $this->type = $type;
  }
  
  public function addFriend($name, Fruit $friend)
  {
    $this->friends[$name] = $friend;
  }
  
  public function slice($times)
  {
    return "Sliced the {$this->type} $times times.";
  }
}

/**
 * @access private
 *
 * @todo make working with DataSource_Interface
 * @todo add modifier tests in if tags (070111).
 */
class CTE_TemplateOutputTest extends PHPUnit2_Framework_TestCase
{
  /**
   * All files to check.
   * 
   * @var array
   */
  private $files = array(
    'print',
    'foreach',
    'if',
    'section'
  );
  
  /**
   * @var CTE_Config
   */
  private $config;
  
  /**
   * @var CTE
   */
  private $cte;
  
  /**
   * Root to all "expected output" files.
   * 
   * @var string
   */
  private $expectedRoot;
  
  public function setUp()
  {
    $this->config = new CTE_Config_Test();
    $this->expectedRoot = $this->config->getCteRoot()
                        . 'tests'
                        . DIRECTORY_SEPARATOR
                        . 'test_project'
                        . DIRECTORY_SEPARATOR
                        . 'expected_output'
                        . DIRECTORY_SEPARATOR;
    $this->cte = new CTE($this->config);
    
    $fruit = new Fruit('apple');
    $otherFruit = new Fruit('orange');
    $fruit->addFriend('peter', $otherFruit);
    $persons = array(
      array('name' => 'Pekka', 'age' => 19),
      array('name' => 'Peter', 'age' => 28),
      array('name' => 'Kevin', 'age' => 42)
    );
    
    $this->cte->registerVar('fruit', $fruit);
    $this->cte->registerVar('otherFruit', $otherFruit);
    $this->cte->createVar(
      'foo',
      array('bar' =>
        array('message' => 'foobar!')
      )
    );
    $this->cte->createVar('magicKey', 'bar');
    $this->cte->registerVar('persons', $persons);
    $this->cte->createVar('isNewYear', false);
    $this->cte->createVar('user', 'Arnold');
    $this->cte->createVar('page', 5);
    $this->cte->createVar('maxPage', 10);
  }

  public function tearDown()
  {
    $this->cte = null;
  }

  /**
   * Compares actual output of compiled template ($file) with the expected
   * output. Returns true if comparation succeeds.
   * 
   * @param string $file Filename without extension.
   * @return array (0:expected, 1:actual)
   */
  private function compare($file)
  {
    // Create the template and suppress the output:
    ob_start();
    $this->cte->display("$file.tpl");
    ob_end_clean();
    
    $filepath = implode(
      DIRECTORY_SEPARATOR,
      $this->config->createCompiledFilePath("$file.tpl")
    );
    
    $actualOutput = file_get_contents($this->config->getTplcRoot() . $filepath);
    $expectedOutput = file_get_contents($this->expectedRoot . $file . '.txt');
    
    // Remove line breaks:
    $actualOutput   = preg_replace('/\n|\r/', '', $actualOutput);
    $expectedOutput = preg_replace('/\n|\r/', '', $expectedOutput);
    
    return array($expectedOutput, $actualOutput);
  }
  
  /**
   * Goes through all template files and tests them.
   */
  public function testAll()
  {
    for ($i=0, $ii=count($this->files); $i<$ii; $i++) {
      $result = $this->compare($this->files[$i]);
      $this->assertEquals(
        $result[0],
        $result[1],
        "Failed at file: {$this->files[$i]}"
      );
    }
  }
}
?>