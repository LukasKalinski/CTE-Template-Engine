<?php
/**
 *
 *
 * @package CTE_Engine_Expression
 * @since 2006-08-10
 * @version 2007-01-23
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
class CTE_Engine_Expression_Logical
  extends CTE_Engine_Expression_Context_Integrator_Abstract
    implements CTE_Engine_Expression_Compilable_Interface
{
  /**
   * @var aArray
   */
  private static $operators = array(
    '&&'   => '&&',
    'and'  => '&&',
    '||'   => '||',
    'or'   => '||'
  );

  /**
   * Allowed operands for 
   * 
   * @var string[]
   */
  private static $allowedOperands = array(
    'CTE_Engine_Expression_Variable' => true,
    'CTE_Engine_Expression_Logical' => true,
    'CTE_Engine_Expression_LogicalNot' => true,
    'CTE_Engine_Expression_Function' => true,
    'CTE_Engine_Expression_Arithmetic' => true,
    'CTE_Engine_Expression_Comparison' => true
  );

  /**
   * @var bool
   */
  private $validated = false;

  /**
   * @var string
   */
  private $operator;
  
  /**
   * @see CTE_Engine_Expression_Abstract::requestInstance()
   */
  public static function requestInstance(CTE_Engine_Parser_Tag $tagParser)
  {
    if (isset(self::$operators[$tagParser->currentToken()])) {
      return new self($tagParser);
    }

    return null;
  }

  /**
   * @param CTE_Engine_Parser_Tag $tagParser
   */
  private function __construct(CTE_Engine_Parser_Tag $tagParser)
  {
    $this->setLineNum($tagParser->getTemplateLineNum());
    $this->operator = self::$operators[$tagParser->currentToken()];
    $this->nextIdentifier($tagParser);
  }

  /**
   * @see CTE_Engine_Expression_Context_Validator_Interface::validate()
   */
  protected function validate()
  {
    // Doesn't need to validate twice:
    if ($this->validated) {
      return;
    }

    $left = $this->getLeftNeighbor();
    
    // Check that our left neighbor has the right type:
    if (!isset(self::$allowedOperands[get_class($left)])) {
      throw new CTE_Engine_Template_Contextual_Exception(
        $this,
        "invalid left operand: {$left->getName()}"
      );
    }
    
    // Check that our left neighbor either is integrated or a non-integrator
    // (if it isn't integrated it means we have two raw operators next to 
    // eachother... and that isn't good):
    if ($left instanceof
        CTE_Engine_Expression_Context_Integrator_Interface &&
        !$left->isIntegrated()) {
      throw new CTE_Engine_Template_Contextual_Exception(
        $this,
        "unexpected left operand: {$left->getName()}"
      );
    }
  
    $right = $this->getRightNeighbor();
    
    // Check that our right neighbor has the right type:
    if (!isset(self::$allowedOperands[get_class($right)])) {
      throw new CTE_Engine_Template_Contextual_Exception(
        $this,
        "invalid right operand: {$right->getName()}"
      );
    }
    
    // Check that our right neighbor either is integrated or a non-integrator
    // (if it isn't integrated it means we have two raw operators next to 
    // eachother... and that isn't good):
    if ($right instanceof
        CTE_Engine_Expression_Context_Integrator_Interface &&
        !$right->isIntegrated()) {
      throw new CTE_Engine_Template_Contextual_Exception(
        $this,
        "unexpected right operand: {$right->getName()}"
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
    return CTE_Engine_Expression_Context_Integrator_Interface::PRIORITY_LOGIC;
  }

  /**
   * @see CTE_Engine_Expression_Context_Integrator_Interface::integrate()
   */
  public function integrate()
  {
    parent::integrate();

    $this->validate();
    
    // Remove left operand from context and store it locally:
    $this->vacuumLeft();
    
    // Remove right operand from context and store it locally:
    $this->vacuumRight();
    
    $this->finalizeIntegration();
  }

  /**
   * @see CTE_Engine_Expression_Abstract::getName()
   */
  public function getName()
  {
    return 'logical expression';
  }

  /**
   * @see CTE_Engine_Expression_Compilable_Interface::compile()
   */
  public function compile($concatContext = false)
  {
    return $this->leftOperand->compile()
         . $this->operator
         . $this->rightOperand->compile();
  }
}
?>