<?php
/**
 *
 *
 * @package CTE_Engine.Compiler.OutputFilter
 * @since 2006-08-17
 * @version 2006-08-21
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
interface CTE_Engine_Compiler_OutputFilter_Interface
{
  /**
   * Filters compiler output string.
   *
   * @param string $output
   * @return string
   */
  public function filterCompilerOutput($output);
}
?>