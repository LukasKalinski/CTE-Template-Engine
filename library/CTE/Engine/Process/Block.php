<?php
/**
 *
 *
 * @package CTE.Compiler.Process
 * @since 2006-07-22
 * @version 2007-01-10
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * Block Process Class
 * 
 * 
 * 
 * @access private
 */
class CTE_Engine_Process_Block extends CTE_Engine_Process_Abstract
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