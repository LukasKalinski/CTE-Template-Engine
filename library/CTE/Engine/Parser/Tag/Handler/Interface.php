<?php
/**
 *
 *
 * @package CTE_Engine.Parser.Tag.Handler
 * @since 2006-08-21
 * @version 2006-09-01
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
interface CTE_Engine_Parser_Tag_Handler_Interface
{
  /**
   * Either parses the tag until it's finished or leaves it untouched.
   *
   * @param CTE_Engine_Parser_Tag $tagParser
   * @return void
   */
  public function handleTagParser(CTE_Engine_Parser_Tag $tagParser);
}
?>
