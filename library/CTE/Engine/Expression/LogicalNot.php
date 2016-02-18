<?php
/**
 *
 *
 * @package CTE_Engine_Expression
 * @since 2007-01-11
 * @version 2007-01-23
 * @copyright Cylab 2007
 * @author Lukas Kalinski
 */

/**
 * Logical Not Expression
 * 
 * This class only applies on variable negations, such as !$var or 'not $var'.
 * 
 * @access private
 */
class CTE_Engine_Expression_LogicalNot
  extends CTE_Engine_Expression_Context_Integrator_Abstract
    implements CTE_Engine_Expression_Compilable_Interface
{
  /**
   * @var string[]
   */
  private static $operators = array(
    '!'   => '!',
    'not'  => '!'
  );

  /**
   * @var bool
   */
  private $validated = false;

  /**
   * @see CTE_Engine_Expression_Abstract::requestInstance()
   */
  public static function requestInstance(CTE_Engine_Parser_Tag $tagParser)
  {
    switch ($tagParser->currentToken()) {
      case '!':
      case 'not':
        return new self($tagParser);
      default:
        return null;
    }
  }

  /**
   * @param CTE_Engine_Parser_Tag $tagParser
   */
  private function __construct(CTE_Engine_Parser_Tag $tagParser)
  {
    $this->setLineNum($tagParser->getTemplateLineNum());
    $this->nextIdentifier($tagParser);
  }

  /**
   * Validates neighbors.
   * 
   * @throws CTE_Engine_Template_Contextual_Exception on failure.
   * @return void
   */
  private function validate()
  {
    // Doesn't need to validate twice:
    if ($this->validated) {
      return;
    }

    $left = $this->getLeftNeighbor();
    
    // Check that our left neighbor has the right type:
    if (!$left instanceof CTE_Engine_Expression_Logical &&
        !is_null($left)) {
      throw new CTE_Engine_Template_Contextual_Exception(
        $this,
        "invalid left operand: {$left->getName()}"
      );
    }
    
    $right = $this->getRightNeighbor();
    
    // Check that our right neighbor has the right type:
    if (!$right instanceof CTE_Engine_Expression_Variable &&
        !$right instanceof CTE_Engine_Expression_Function) {
      throw new CTE_Engine_Template_Contextual_Exception(
        $this,
        "invalid right operand: {$right->getName()}"
      );
    }
    
    $this->validated = true;
  }

  /**
   * @see CTE_Engine_Expression_Context_Integrator_Interface::
   *      integrationPriority()
   */
  public function integrationPriority()
  {
    return CTE_Engine_Expression_Context_Integrator_Interface::
           PRIORITY_LOGIC_NOT;
  }

  /**
   * @see CTE_Engine_Expression_Context_Integrator_Interface::integrate()
   */
  public function integrate()
  {
    parent::integrate();
    $this->validate();
    $this->vacuumRight();
    $this->finalizeIntegration();
  }

  /**
   * @see CTE_Engine_Expression_Abstract::getName()
   */
  public function getName()
  {
    return 'logical not expression';
  }

  /**
   * @see CTE_Engine_Expression_Compilable_Interface::compile()
   */
  public function compile($concatContext = false)
  {
    return $this->assemble('!' . $this->rightOperand->compile());
  }
}
?>