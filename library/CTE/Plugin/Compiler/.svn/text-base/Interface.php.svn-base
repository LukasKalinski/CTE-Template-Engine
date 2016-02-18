<?php
/**
 *
 *
 * @package CTE_DataAccess
 * @since 2006-07-24
 * @version 2007-02-05
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access public
 */
interface CTE_DataAccess_Interface
{
  /**
   * Plain Rendering Mode
   * 
   * @var int
   */
  const RENDER_PLAIN = 1;
  
  /**
   * Template Rendering Mode
   * 
   * @var int
   */
  const RENDER_TPL = 2;
  
  /**
   * Does simple macro rendering, such as inserting resources into a resource.
   * 
   * Not yet decided whether to keep this render mode or not.
   * 
   * @var int
   */
  const RENDER_SIMPLE = 3;

  /**
   * Constructor
   */
  public function __construct(CTE_Config $config);
  
  /**
   * Returns the data source name, which will associate a template resource tag
   * with its data accessor.
   *
   * Example:
   * In {@lang[title.foo]} 'lang' is the DSN.
   *
   * @return string
   */
  public function getDsn();

  /**
   * Returns the delimiters to use for contents fetched from this data access
   * handler.
   * 
   * @return array(start, end)
   * @return null If default delimiters should be used.
   */
  public function getTplDelims();
  
  /**
   * Returns the render mode for the supplied data path.
   *
   * @param string $path
   * @return bool
   */
  public function getRenderMode($path);

  /**
   * Returns content based on the $sourceName parameter.
   *
   * @param string $path The data path, i.e. "foo/bar.tpl" or "lang.foo.bar",
   *        for example.
   * @return string
   */
  public function fetch($path);
}
?>