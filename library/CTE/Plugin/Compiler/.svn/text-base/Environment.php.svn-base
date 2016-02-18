<?php
/**
 *
 *
 * @package CTE_Engine
 * @since 2006-08-07
 * @version 2007-02-10
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
class CTE_Engine_Environment
{
  /**
   * @var CTE_Engine_Environment
   */
  private static $instance;

  /**
   * Compiler Stack
   * 
   * @var CTE_Engine_Compiler[]
   */
  private $compilerStack = array();
  
  /**
   * Current Compiler
   * 
   * @var CTE_Engine_Compiler
   */
  private $compiler = null;
  
  /**
   * @var CTE_Engine
   */
  private $engine = null;

  /**
   * Prevent public creation.
   */
  private function __construct()
  {
  }

  /**
   *
   * @return CTE_Engine_Environment
   */
  public static function getInstance()
  {
    if (!isset(self::$instance)) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  /**
   *
   * @param CTE_Engine $engine
   * @return void
   */
  public function register(CTE_Engine $engine)
  {
    $this->engine = $engine;
  }

  /**
   * Creates a new compiler and saves the old one on stack (if any).
   * 
   * @param CTE_Engine_ExpressionMapper $exprMapper
   * @param int $mode Compiler mode {@link CTE_Engine_Compiler}
   * @return CTE_Engine_Compiler The created compiler instance.
   */
  public function createCompiler(CTE_Engine_ExpressionMapper $exprMapper, $mode)
  {
    // Push current compiler on stack if we have one:
    if (!is_null($this->compiler)) {
      $this->compilerStack[] = $this->compiler;
    }
    
    $this->compiler = new CTE_Engine_Compiler($exprMapper, $mode);
    
    return $this->compiler;
  }
  
  /**
   * Deletes current compiler and tries to restore the previous one from
   * compiler stack.
   * 
   * @param CTE_Engine_Compiler $compiler
   * @throws CTE_Engine_Exception if the drop-requested compiler isn't current.
   * @return void
   */
  public function dropCompiler(CTE_Engine_Compiler $compiler)
  {
    if ($this->compiler !== $compiler) {
      throw new CTE_Engine_Exception('cannot drop compiler if not current');
    }
    
    $this->compiler = array_pop($this->compilerStack);
  }
  
  /**
   * @throws CTE_Engine_Exception if no compiler is created.
   * @return CTE_Engine_Compiler
   */
  public function getCompiler()
  {
    if (is_null($this->compiler)) {
      throw new CTE_Engine_Exception('no compiler created');
    }
    
    return $this->compiler;
  }

  /**
   * @throws CTE_Engine_Exception
   * @return CTE_Config
   */
  public function getConfig()
  {
    $this->requireEngine();
    return $this->engine->getConfig();
  }

  /**
   * @throws CTE_Engine_Exception
   * @return CTE_Engine_PluginManager
   */
  public function getPluginManager()
  {
    $this->requireEngine();
    return $this->engine->getPluginManager();
  }

  /**
   * @throws CTE_Engine_Exception
   * @return CTE_Engine_ProcessManager
   */
  public function getProcessManager()
  {
    $this->requireEngine();
    return $this->engine->getProcessManager();
  }

  /**
   * @throws CTE_Engine_Exception
   * @return CTE_Engine_VariableRegistry
   */
  public function getVariableRegistry()
  {
    $this->requireEngine();
    return $this->engine->getVariableRegistry();
  }

  /**
   * Returns the currently active template process.
   * 
   * @return CTE_Engine_Process_Template_Abstract
   */
  public function getCurrentTemplateProcess()
  {
    return $this->getProcessManager()->getCurrentTemplateProcess();
  }

  /**
   * @throws CTE_Engine_Exception
   * @return void
   */
  private function requireEngine()
  {
    if (!isset($this->engine)) {
      throw new CTE_Engine_Exception('No engine registered.');
    }
  }
}
?>