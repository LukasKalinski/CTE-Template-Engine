<?php
/**
 *
 *
 * @package CTE_Engine_Expression_Context_Integrator
 * @since 2007-01-19
 * @version 2007-01-19
 * @copyright Cylab 2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
abstract class CTE_Engine_Expression_Context_Integrator_Abstract
  extends CTE_Engine_Expression_Abstract
    implements CTE_Engine_Expression_Context_Integrator_Interface
{
  /**
   * @var bool
   */
  private $integrated = false;
  
  /**
   * @var CTE_Engine_Expression_Abstract
   */
  protected $leftOperand = null;

  /**
   * @var CTE_Engine_Expression_Abstract
   */
  protected $rightOperand = null;

  /**
   * Finalizes integration. Should be called when finished with integrate().
   *
   * @throws CTE_Engine_Template_Contextual_Exception
   * @return void
   */
  final protected function finalizeIntegration()
  {
    $this->integrated = true;
  }

  /**
   * Returns true if the integration has been done.
   * 
   * @return bool
   */
  final public function isIntegrated()
  {
    return $this->integrated;
  }
  
  /**
   * Removes left contextual expression and stores it locally.
   * 
   * @param bool $transferComps Whether to transfer components from neighbor
   *        or not.
   * @return void
   */
  final public function vacuumLeft($transferComps = false)
  {
    $left = $this->getLeftNeighbor();
    
    // Make sure left expression isn't null:
    if (is_null($left)) {
      throw new CTE_Engine_Expression_Exception('left expression was null');
    }
    
    $this->leftOperand = $this->context->remove($left);
    
    if ($transferComps) {
      $this->transferComponents($left);
    }
  }
  
  /**
   * Removes right contextual expression and stores it locally.
   * 
   * @param bool $transferComps Whether to transfer components from neighbor
   *        or not.
   * @return void
   */
  final public function vacuumRight($transferComps = false)
  {
    $right = $this->getRightNeighbor();
    
    // Make sure right expression isn't null:
    if (is_null($right)) {
      throw new CTE_Engine_Expression_Exception('right expression was null');
    }
    
    $this->rightOperand = $this->context->remove($right);
    
    if ($transferComps) {
      $this->transferComponents($right);
    }
  }
  
  /**
   * Does the class specific integration.
   * 
   * @return void
   */
  public function integrate()
  {
    if ($this->integrated) {
      throw new CTE_Engine_Expression_Exception(
        'cannot do integration more than once'
      );
    }
  }
}
?>