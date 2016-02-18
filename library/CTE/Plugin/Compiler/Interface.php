<?php
/**
 *
 *
 * @package CTE.Plugin.Compiler
 * @since 2006-07-19
 * @version 2006-07-19
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access public
 */
interface CTE_Plugin_Compiler_Interface
{
  /**
   * Returns either a unique ID or null.
   *
   * - Unique ID means we're going to have one instance for every ID.
   * - Null means we're not using ID's -> only one instance -> singleton.
   *
   * Examples:
   * Accessing unique instances:  $cte.plugin.PluginName.id->method()
   * Accessing singletons:        $cte.plugin.PluginName->method()
   *
   * @return string
   */
  public function getId();

  /**
   * @param CTE_Compiler $compiler
   * @param array $args
   * @return mixed
   */
  public function execute(CTE_Compiler $compiler, array $args);
}
?>
