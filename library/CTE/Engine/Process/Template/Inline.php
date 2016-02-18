<?php
/**
 *
 *
 * @package CTE_Engine_Process_Template
 * @since 2007-01-20
 * @version 2007-02-08
 * @copyright Cylab 2007
 * @author Lukas Kalinski
 */

/**
 * Inline Template Process
 * 
 * This process will be transparent to the environment, having the same ID as
 * the template process before this.
 * 
 * @access private
 */
class CTE_Engine_Process_Template_Inline
  extends CTE_Engine_Process_Template_Abstract
{
  /**
   * Valid parents to this process.
   * 
   * @var array
   */
  private static $validParents = array(
    'CTE_Engine_Process_Template_Abstract',
    'CTE_Engine_Process_Block'
  );
  
  /**
   * @see CTE_Engine_Process_Abstract::generateId()
   */
  protected function generateId()
  {
    // Since we're transparent we'll have the ID of our parent template process:
    return CTE_Engine_Environment::
      getInstance()->
      getCurrentTemplateProcess()->
      getId();
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
                              CTE_Engine_Process_Abstract $parentProcess)
  {
    // Make sure parent process isn't null:
    if (is_null($parentProcess)) {
      throw new CTE_Engine_Process_Exception('parent process required');
    // Make sure parent process is valid:
    } else if (!parent::processIsInstanceof($parentProcess,
                                            self::$validParents)) {
      throw new CTE_Engine_Process_Exception('invalid parent process');
    }
  }
}
?>