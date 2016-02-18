<?php
/**
 *
 *
 * @package CTE
 * @since 2006-07-22
 * @version 2006-07-22
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @internal
 */
class __cte_debugger
{
  const DEBUG = false;

  public static function println($object, $str)
  {
    if (self::DEBUG) {
      $className = get_class($object);
      echo  str_pad("\nDEBUGGER: $className:", 40, ' ') . "$str";
    }
  }
}
?>
