<?php
/**
 *
 *
 * @package CTE_Engine_Template_Token
 * @since 2006-08-17
 * @version 2007-01-22
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
class CTE_Engine_Parser_Tag
  implements CTE_Engine_Template_Monitor_Interface
{
  /**
   * @var CTE_Engine_Parser
   */
  private $mainParser;

  /**
   * @var string
   */
  private $tagString = null;

  /**
   * @var string[]
   */
  private $identifiers;

  /**
   * @var int
   */
  private $tokensNum = -1;

  /**
   * @var int
   */
  private $tokenIndex = -1;

  /**
   * @var bool
   */
  private $finished = false;

  /**
   * @var bool
   */
  private $tokenized = false;

  /**
   * @var aArray(3)
   */
  private $savedPosition = null;

  /**
   * @param CTE_Engine_Parser $mainParser
   * @param string $tagString
   */
  public function __construct(CTE_Engine_Parser $mainParser, $tagString)
  {
    $this->tagString = $tagString;
    $this->mainParser = $mainParser;
  }

  /**
   * @see CTE_Engine_Template_Monitor_Interface::getTemplateLineNum()
   */
  public function getTemplateLineNum()
  {
    return $this->mainParser->getTemplateLineNum();
  }

  /**
   *
   * @return void
   */
  private function finish()
  {
    $this->finished = true;
  }

  /**
   * Skips parsing of this tag.
   *
   * @return void
   */
  public function skip()
  {
    $this->mainParser->updatePointer($this->tagString);
    $this->finish();
  }

  /**
   *
   * @throws CTE_Engine_Parser_Tag_Exception
   * @return void
   */
  public function tokenize($regex)
  {
    if (!$this->identifiers) {
      $this->identifiers = preg_split(
        "/(\s*|$regex)/",
        $this->tagString, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
    }

    $this->tokenIndex = 0;
    $this->tokensNum = count($this->identifiers);

    if ($this->tokensNum == 0) {
      throw new CTE_Engine_Parser_Tag_Exception('tag was empty');
    }

    $this->tokenized = true;
  }

  /**
   * Returns true if this expression has *not* been finished.
   *
   * @return bool
   */
  public function valid()
  {
    return !$this->finished;
  }

  /**
   * Sets a point of return, to which we can return after calling
   * restoreSavedPosition() or discard calling discardSavedPosition().
   * 
   * @return void
   */
  public function savePosition()
  {
    $this->savedPosition = array(
      'line_no'  => $this->mainParser->getTemplateLineNum(),
      'ident_no' => $this->tokenIndex
    );
  }

  /**
   *
   * @return void
   */
  public function discardSavedPosition()
  {
    $this->savedPosition = null;
  }

  /**
   *
   * @return void
   */
  public function restoreSavedPosition()
  {
    if (is_null($this->savedPosition)) {
      throw new CTE_Engine_Parser_Tag_Exception(
        $this->mainParser,
        'no position remembered'
      );
    }
    $this->mainParser->setLine($this->savedPosition['line_no']);
    $this->tokenIndex = $this->savedPosition['ident_no'];
    $this->savedPosition = -1;
  }

  /**
   *
   * @return void
   */
  private function assertNotFinished()
  {
    if ($this->finished) {
      throw new CTE_Engine_Parser_Exception(
        'cannot access finished expression parser'
      );
    }
  }

  /**
   * Returns true if the tag string begins with $string.
   *
   * @param string $string
   * @return bool
   */
  public function beginsWith($string)
  {
    return preg_match('/^' . preg_quote($string, '/') . '/', $this->tagString);
  }

  /**
   * Returns true if current identifier matches pattern $pattern.
   *
   * @param string $pattern A full regex pattern.
   * @return bool
   */
  public function tokenMatches($pattern)
  {
    return preg_match($pattern, $this->currentToken());
  }

  /**
   * Returns true if the tag string ends with $string.
   *
   * @param string $string
   * @return bool
   */
  public function endsWith($string)
  {
    return preg_match('/' . preg_quote($string, '/') . '$/', $this->tagString);
  }

  /**
   *
   * @return bool
   */
  public function tokenized()
  {
    return $this->tokenized;
  }

  /**
   * Returns the currently pointed token/identifier.
   * 
   * @return string
   */
  public function currentToken()
  {
    // Check that we have tokens available:
    if (!$this->tokenized) {
      throw new CTE_Engine_Parser_Tag_Exception('tag is not tokenized');
    }
    
    return $this->identifiers[$this->tokenIndex];
  }

  /**
   * Maps current identifier(s) against $assoc and returns the value of $assoc
   * found at the position specified by our identifiers. If mapping fails null
   * will be returned.
   * 
   * @param CTE_Engine_Parser_Tag $this
   * @param array $operators
   * @param bool $first
   * @return string or null if mapping failed.
   */
  private function _mapAssoc(array $operators, $first = false)
  {
    // If we're parsing the first identifier, remember the parser position:
    if ($first) {
      $this->savePosition();
    }

    $result = null;

    $token = $this->currentToken();

    if (isset($operators[$token]) && $this->valid()) {
      $result = $operators[$token];
      if (is_array($result)) {
        $this->nextToken();
        $result = $this->_mapAssoc($result);
      }
    } else if(isset($operators[0])) {
      $result = $operators[0];
    }

    // Now that we're through all recursive calls and back in the first call,
    // discard or restore saved parser position, depending on whether a result
    // was found or not:
    if ($first) {
      if (!is_null($result)) { // success
        $this->discardSavedPosition();
      } else { // failure
        $this->restoreSavedPosition();
      }
    }

    return $result;
  }
  
  /**
   * Maps current identifier(s) against $assoc and returns the value of $assoc
   * found at the position specified by our identifiers. If mapping fails null
   * will be returned.
   * 
   * Example usage:
   *   - Next two identifiers are foo and bar.
   *   - We set $assoc to be array('foo' => array('bar' => array('our value'))).
   *   - Calling mapAssoc will return the string 'my value'.
   * Note that a value is identified by its zero-key... other keys mean we have
   * to dig deeper or give up if that isn't possible.
   * 
   * @param aArray $assoc
   * @return string or null on failure.
   */
  public function mapAssoc(array $assoc)
  {
    return $this->_mapAssoc($assoc, true);
  }
  
  /**
   * Moves to the next token and returns true if a move has been made. If token
   * consists of blanks only it will be skipped and the next avaiable token will
   * be returned if it exists, otherwise false will be returned.
   *
   * @return bool
   */
  public function nextToken()
  {
    if ($this->tokenIndex + 1 < $this->tokensNum) {
      $this->tokenIndex++;
      $this->mainParser->updatePointer($this->currentToken());

      // Check if current token consists of blanks and skip to next token
      // if true:
      if ($this->tokenMatches('/^\s/s')) {
        $this->nextToken();
      }

      return true;
    }

    $this->finished = true;
    return false;
  }

  /**
   * Returns tag string as it was before parsing.
   *
   * @return string
   */
  public function __toString()
  {
    return $this->tagString;
  }
}
?>