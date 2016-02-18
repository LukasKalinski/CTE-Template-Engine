<?php
/**
 *
 *
 * @package CTE_Engine_Process_Template
 * @since 2006-07-31
 * @version 2007-02-08
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
abstract class CTE_Engine_Process_Template_Abstract
  extends CTE_Engine_Process_Abstract
{
  const NO_TEMPLATE = 0;
  
  /**
   * States that all variables are existence checked as long as this flag
   * is on.
   */
  const FLAG_VARS_CHECKED = 1;
  
  /**
   * The highest template level.
   * 
   * @var int
   */
  private static $topLevel = self::NO_TEMPLATE;
  
  /**
   * Current template level.
   * 
   * @var int
   */
  private $level;
  
  /**
   * @see CTE_Engine_Process_Abstract::__construct()
   */
  public function __construct(CTE_Engine_Process_Creator_Interface $creator,
                              CTE_Engine_Process_Abstract $parentProcess = null)
  {
    parent::__construct($creator, $parentProcess);
    
    // Increment template level:
    $this->level = ++self::$topLevel;
  }
  
  /**
   * Returns true if the current template is on top level.
   * 
   * @return bool
   */
  public function isOnTop()
  {
    return ($this->level == self::$topLevel);
  }
  
  /**
   * Returns the current template level.
   *  
   * @return int
   */
  public static function getLevel()
  {
    return $this->level;
  }
  
  /**
   * @see CTE_Engine_Process_Abstract::beforeClose()
   */
  protected function beforeClose()
  {
    // This is very unlikely, but make sure that we're on top before closing:
    if (!$this->isOnTop()) {
      throw new CTE_Engine_Process_Exception('template was not top level');
    }
    
    self::$topLevel--;
  }
}
?>