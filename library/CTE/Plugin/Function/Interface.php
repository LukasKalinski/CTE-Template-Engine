<?php
/**
 *
 *
 * @package CTE.Plugin.Function
 * @since 2006-07-19
 * @version 2006-07-19
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access public
 */
interface CTE_Plugin_Function_Interface
{
  /**
   * @param CTE $cte
   * @param array $args
   * @return mixed
   */
  public static function execute(CTE $cte, array $args);
}
?>
