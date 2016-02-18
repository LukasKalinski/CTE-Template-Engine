<?php
/**
 *
 *
 * @package CTE_Config_Language
 * @since 2007-02-07
 * @version 2007-02-07
 * @copyright Cylab 2007
 * @author Lukas Kalinski
 */

/**
 * Language interface for CTE_Config subclass.
 * 
 * Important:
 * - Call CTE_Config::setResourceClassNameAssoc() setting an association for
 *   language related data access.
 * 
 * @access public
 */
interface CTE_Config_Language_Interface
{
  /**
   * @param string $lang
   * @return void
   */
  public function setLanguage($lang);

  /**
   * @return string
   */
  public function getLanguage();
}
?>