<?php
/**
 *
 *
 * @package CTE_Engine_Expression_Literal
 * @since 2006-08-12
 * @version 2007-02-08
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
class CTE_Engine_Expression_Literal_ParseableString
  extends CTE_Engine_Expression_Literal_Abstract
    implements CTE_Engine_Process_Creator_Interface
{
  /**
   * @var CTE_Engine_ExpressionMapper
   */
  private static $exprMapper = null;

  /**
   * @var bool
   */
  private $isStatic = true;
  
  /**
   * @var string
   */
  private $identifier;
  
  /**
   * @var bool
   */
  private $processActive = false;
  
  /**
   * @see CTE_Engine_Expression_Abstract::requestInstance()
   */
  public static function requestInstance(CTE_Engine_Parser_Tag $tagParser)
  {
    if ($tagParser->tokenMatches('/^".*?(?<!\\\)"$/')) {
      return new self($tagParser);
    } else {
      return null;
    }
  }

  /**
   * @param CTE_Engine_Parser_Tag $tagParser
   */
  private function __construct(CTE_Engine_Parser_Tag $tagParser)
  {
    $this->setLineNum($tagParser->getTemplateLineNum());
    
    // Fetch our identifier:
    $this->identifier = $tagParser->currentToken();
    $tagParser->nextToken();
    
    // Remove quotes from string:
    $this->identifier = stripslashes(mb_substr($this->identifier, 1, -1));
    
    $config = CTE_Engine_Environment::getInstance()->getConfig();
    
    // Check if we have to parse any dynamic content:
    $delimRegex = preg_quote($config->getTagStartDelim(), '/');
    if (preg_match('/(?:^|[^\\\])' . $delimRegex . '/', $this->identifier)) {
      
      // We need to setup expression mapper (done only once):
      self::setup();
      $this->isStatic = false; 
    }
  }

  /**
   * Does static setup only once.
   * 
   * @return void
   */
  private static function setup()
  {
    // Setup printable expressions mapper if not already set:
    if (is_null(self::$exprMapper)) {
      self::$exprMapper = new CTE_Engine_ExpressionMapper();
      self::$exprMapper->registerExpression('Tag_Print');
    }
  }
  
  /**
   * @see CTE_Engine_Expression_Abstract::getName()
   */
  public function getName()
  {
    return 'parseable string literal';
  }

  /**
   * Returns true if this string has dynamic content, for example a variable.
   * 
   * @return bool
   * 
   * @todo implement
   */
  public function hasDynamicContent()
  {
    return !$this->isStatic;
  }
  
  /**
   * Runs external compiler with mode $mode on our data and returns the result.
   * 
   * @param int $mode Compile mode {@see CTE_Engine_Compiler::MODE_*}
   * @return string
   */
  private function runExtCompiler($mode)
  {
    // Just return the string if we're static:
    if ($this->isStatic) {
      if ($evaluate) {
        return $this->identifier;
      } else {
        return CTE_Util_String::squote($this->identifier);
      }
    }
    
    $env = CTE_Engine_Environment::getInstance();
    
    // Create a parser for our contents:
    $parser = new CTE_Engine_Parser($this->identifier);
    
    // Create a new compiler for us:
    $compiler = $env->createCompiler(self::$exprMapper, $mode);
    
    // Enter a transparent inline template process:
    $process = $env->
      getProcessManager()->
      enterProcess($this, 'Template_Inline');
    $this->processActive = true;
    
    // Ignore non-existing variables if current parseable string is associated
    // with a external template variable:
    if ($env->getVariableRegistry()->isAssociated($this)) {
      $process->setFlag(
        CTE_Engine_Process_Template_Abstract::FLAG_VARS_CHECKED
      );
    }
    
    $result = $compiler->compile($parser);
    
    // Leave our process:
    $this->processActive = false;
    $env->getProcessManager()->leaveProcess($process);
    
    // Restore old compiler:
    $env->dropCompiler($compiler);
    
    return $result;
  }
  
  /**
   * @see CTE_Engine_Process_Creator_Interface::notifyProcessUpdated()
   * @throws CTE_Engine_Expression_Exception if our process is left without us
   *         being finished.
   */
  public function notifyProcessUpdated(CTE_Engine_Process_Abstract $process)
  {
    // Throw exception if our process was forced to close:
    if ($process->isClosed() && $this->processActive) {
      throw new CTE_Engine_Expression_Exception(
        'illegal close of inline template process'
      );
    }
  }
  
  /**
   * @see CTE_Engine_Expression_Evaluable_Interface::evaluate()
   */
  public function evaluate($inline)
  {
    // Return unquoted identifier (string) if we're static:
    if ($this->isStatic) {
      $result = $this->identifier;
    } else {
      $result = $this->runExtCompiler(CTE_Engine_Compiler::MODE_EVAL);
    }
    
    return ($inline) ? CTE_Util_String::squote($result) : $result;
  }

  /**
   * @see CTE_Engine_Expression_Evaluable_Interface::isStatic()
   */
  public function isStatic()
  {
    return $this->isStatic;
  }
  
  /**
   * @see CTE_Engine_Expression_Compilable_Interface::compile()
   */
  public function compile($concatContext = false)
  {
    if ($this->isStatic) {
      return CTE_Util_String::squote($this->identifier);
    }
    
    return $this->runExtCompiler(CTE_Engine_Compiler::MODE_COMPILE_CONCAT);
  }
}
?>