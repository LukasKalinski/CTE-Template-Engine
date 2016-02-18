<?php
/**
 *
 *
 * @package CTE_Engine_Process
 * @since 2006-07-31
 * @version 2007-02-08
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * Template_Root process defines behaviour for the main compiler.
 *
 * @access private
 */
class CTE_Engine_Process_Template_Root
  extends CTE_Engine_Process_Template_Abstract
{
  /**
   * The variable registry scope for current template process
   * 
   * @var int
   */
  private $varRegScope;
  
  /**
   * @see CTE_Engine_Process_Template_Abstract::__construct()
   */
  public function __construct(CTE_Engine_Process_Creator_Interface $creator,
                              CTE_Engine_Process_Abstract $parentProcess = null)
  {
    parent::__construct($creator, $parentProcess);
    
    // Create a new variable registry scope:
    $this->varRegScope = CTE_Engine_Environment::
      getInstance()->
      getVariableRegistry()->
      createScope();
  }
  
  /**
   * Validates constructor input.
   * 
   * @see CTE_Engine_Process_Abstract::setup()
   * @param CTE_Engine_Process_Creator_Interface $creator
   * @param CTE_Engine_Process_Abstract $parentProcess
   * @return void
   */
  protected function validate(CTE_Engine_Process_Creator_Interface $creator,
                              CTE_Engine_Process_Abstract $parentProcess = null)
  {
    // Make sure there is no parent process:
    if (!is_null($parentProcess)) {
      throw new CTE_Engine_Process_Exception(
        'root template process cannot have any parents'
      );
    }
  }
  
  /**
   * Resets the process ID counter when quiting this template root process.
   * 
   * @see CTE_Engine_Process_Abstract::beforeClose()
   */
  protected function beforeClose()
  {
    // Drop current variable registry scope:
    CTE_Engine_Environment::getInstance()->
                            getVariableRegistry()->
                            dropScope($this->varRegScope);
    
    $this->resetId();
  }
}
?>