<?php
/**
 *
 *
 * @package CTE.Util
 * @since 2006-07-08
 * @version 2006-07-12
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

class CTE_Util_String
{
  /**
   * Returns $str wrapped inside single quotes (') with all single quotes and backspaces escaped.
   *
   * @param string $str
   * @return string
   */
  public static function squote($str)
  {
    $str = str_replace('\\', '\\\\',  $str); // Replace \ with \\ // REMOVED @ 2006-03-05 Added 2006-06-05 ... removed with motivation?
    $str = str_replace('\'', '\\\'',  $str); // Replace ' with \'
    return '\''.$str.'\'';
    return $str;
  }
}
?>
