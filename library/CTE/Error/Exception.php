<?php
/**
 *
 *
 * @package CTE_Error
 * @since 2007-01-11
 * @version 2007-01-11
 * @copyright Cylab 2007
 * @author Lukas Kalinski
 */

/**
 * Error Exception
 * 
 * This exception will be thrown in case of an error, instead of displaying
 * the error, if CTE is configured that way.
 * 
 * @access private
 */
class CTE_Error_Exception extends CTE_Exception
{
  /**
   * List with relevant data about the error.
   * 
   * @var array
   */
  private $data;
  
  /**
   * @param array $data List with relevant data about the error.
   */
  public function __construct(array $data)
  {
    $this->data = $data;
  }
  
  /**
   *
   * @return array
   */
  public function getData()
  {
    return $this->data;
  }
}
?>