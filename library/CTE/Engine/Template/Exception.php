<?php
/**
 *
 *
 * @package CTE_Engine_Template
 * @since 2006-09-03
 * @version 2007-01-22
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
class CTE_Engine_Template_Exception
  extends CTE_Engine_Exception
{
  /**
   * @var int
   */
  private $tplLineNo = -1;

  /**
   * Creates a template exception.
   *
   * @param CTE_Engine_Template_Monitor_Interface $monitor
   * @param string $message
   * @param int $code
   */
  public function __construct(CTE_Engine_Template_Monitor_Interface $monitor,
                              $message)
  {
    if ($monitor instanceof CTE_Engine_Expression_Abstract) {
      $message = $monitor->getName() . ': ' . $message;
    }

    parent::__construct($message);
    $this->tplLineNo = $monitor->getTemplateLineNum();
  }

  /**
   *
   * @return int
   * @todo implement
   */
  public function getTemplateLineNumber()
  {
    return $this->tplLineNo;
  }
}
?>