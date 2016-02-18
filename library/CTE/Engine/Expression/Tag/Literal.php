<?php
/**
 *
 *
 * @package CTE_Engine_Expression_Tag
 * @since 2006-08-12
 * @version 2007-01-22
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
class CTE_Engine_Expression_Tag_Literal
  extends CTE_Engine_Expression_Tag_Abstract
    implements CTE_Engine_Parser_Tag_Handler_Interface,
               CTE_Engine_Process_Creator_Interface
{
  /**
   * @var bool
   */
  private $isClosed = false;

  /**
   * @var string
   */
  private $tagStartDelim;

  /**
   * @var string
   */
  private $tagEndDelim;

  /**
   * @var CTE_Engine_Process_Block
   */
  private $process;

  /**
   * @see CTE_Engine_Expression_Abstract::requestInstance()
   */
  public static function requestInstance(CTE_Engine_Parser_Tag $tagParser)
  {
    if (strtolower($tagParser->__toString()) == 'literal') {
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
    
    $tagParser->skip();

    // Get environment instance:
    $env = CTE_Engine_Environment::getInstance();

    // Register ourselves as a tag parser handler:
    $env->getCompiler()->registerTagParserHandler($this);

    // Enter a new block process and save it (so we know what to exit later):
    $this->process = $env->getProcessManager()->enterProcess($this, 'Block');

    // Save tag delimiters:
    $this->tagStartDelim = $env->getConfig()->getTagStartDelim();
    $this->tagEndDelim = $env->getConfig()->getTagEndDelim();
  }

  /**
   * @see CTE_Engine_Expression_Compilable_Interface::compile()
   */
  public function compile($concatContext = false)
  {
    return '';
  }

  /**
   * @see CTE_Engine_Expression_Abstract::getName()
   */
  public function getName()
  {
    return 'literal block';
  }

  /**
   * @see CTE_Engine_Process_Creator_Interface::notifyProcessUpdated()
   */
  public function notifyProcessUpdated(CTE_Engine_Process_Abstract $process)
  {
    if (!$this->isClosed && $process->isClosed()) {
      // We can't close process without an end tag, throw exception:
      throw new CTE_Engine_Template_Contextual_Exception(
        $this,
        'missing end tag'
      );
    }
  }

  /**
   * Handles end tags.
   * 
   * @see CTE_Engine_Parser_Tag_Handler_Interface::CTE_Engine_Parser_Tag()
   */
  public function handleTagParser(CTE_Engine_Parser_Tag $tagParser)
  {
    $tagParser->skip();
    if ($tagParser->__toString() == '/literal') {
      $this->isClosed = true;
      $compiler = CTE_Engine_Environment::getInstance()->getCompiler();

      // Unregister ourselves from handling tags in compiler:
      $compiler->unregisterTagParserHandler($this);

      // Close our process:
      CTE_Engine_Environment::getInstance()->
                              getProcessManager()->
                              leaveProcess($this->process);
      return '';
    } else {
      return $this->tagStartDelim
           . $tagParser->__toString()
           . $this->tagEndDelim;
    }
  }
}
?>