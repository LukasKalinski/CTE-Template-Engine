<?php
/**
 *
 *
 * @package CTE_Engine_Expression_Tag
 * @since 2006-08-18
 * @version 2006-08-28
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
class CTE_Engine_Expression_Tag_Comment
  extends CTE_Engine_Expression_Tag_Abstract
{
  /**
   * @see CTE_Engine_Expression_Abstract::requestInstance()
   * @throws CTE_Engine_Template_Parsing_Exception
   */
  public static function requestInstance(CTE_Engine_Parser_Tag $tagParser)
  {
    if ($tagParser->beginsWith('*')) {
      return new self($tagParser);
    } else {
      return null;
    }
  }

  /**
   * @param CTE_Engine_Parser_Tag $tagParser
   * @throws CTE_Engine_Template_Parsing_Exception
   */
  private function __construct(CTE_Engine_Parser_Tag $tagParser)
  {
    if (!$tagParser->endsWith('*')) {
      throw new CTE_Engine_Template_Parsing_Exception(
        $this,
        'invalid comment syntax'
      );
    }

    $tagParser->skip();
  }

  /**
   * @see CTE_Engine_Expression_Abstract::getName()
   */
  public function getName()
  {
    return 'comment tag';
  }

  /**
   * @see CTE_Engine_Expression_Compilable_Interface::compile()
   */
  public function compile($concatContext = false)
  {
    return '';
  }
}
?>