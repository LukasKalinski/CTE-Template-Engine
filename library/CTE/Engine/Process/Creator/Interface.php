<?php
/**
 *
 *
 * @package CTE_Engine_Process_Creator
 * @since 2006-09-10
 * @version 2007-02-04
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
interface CTE_Engine_Process_Creator_Interface
{
  /**
   * Forces the process creator to accept a process closure. If the process
   * creator refuses it will throw an exception.
   *
   * @param CTE_Engine_Process_Abstract $process
   * @return void
   */
  public function notifyProcessUpdated(CTE_Engine_Process_Abstract $process);
}
?>