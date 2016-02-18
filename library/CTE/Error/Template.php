<?php
/**
 *
 *
 * @package CTE_Error
 * @since 2007-01-04
 * @version 2007-02-07
 * @copyright Cylab 2007
 * @author Lukas Kalinski
 */

/**
 * Template specific error class.
 * 
 * @access private
 */
class CTE_Error_Template extends CTE_Error
{
  /**
   * @param CTE_Config $config
   * @param int $type
   * @param CTE_Engine_Template_Exception $e
   * @param string $tplResource
   * @param int $tplLine
   */
  public function __construct(CTE_Config $config,
                              CTE_Engine_Template_Exception $e,
                              $tplResource)
  {
    parent::__construct($config, $e);
    $this->data['template_resource'] = $tplResource;
    $this->data['template_line'] = $e->getTemplateLineNumber();
  }
}
?>