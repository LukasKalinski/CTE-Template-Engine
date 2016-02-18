<?php
/**
 *
 *
 * @package CTE_Engine_Expression_Tag
 * @since 2006-08-12
 * @version 2007-02-05
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
class CTE_Engine_Expression_Tag_Section
  extends CTE_Engine_Expression_Tag_Abstract
    implements CTE_Engine_Expression_Compilable_Interface,
               CTE_Engine_Process_Creator_Interface,
               CTE_Engine_Parser_Tag_Handler_Interface
{
  /**
   * Attribute Expected
   * 
   * An attribute is expected but not set yet.
   */  
  const ATTR_EXPECTED = 1;
  
  /**
   * Attribute OK
   * 
   * The attribute has either been set or isn't required anymore.
   */
  const ATTR_OK = 2;
  
  /**
   * Regex for user-defined ID's. Ignore case is assumed.
   * 
   * @var string
   */
  private static $idRegex = '(?:[a-z_][a-z0-9_]*)';
  
  /**
   * Array with used foreach ID's. The ID's will be categorized by template
   * process ID.
   * 
   * @var array
   */
  private static $activeIds = array();
  
  /**
   * True if section block had a sectionelse statement.
   * 
   * @var bool
   */
  private $hadElse = false;
  
  /**
   * The ID of template process in which this section block occurs.
   * 
   * @var int
   */
  private $templateId;
  
  /**
   * Valid attribute expression instances. It is assumed that all of them are
   * implementing the Evaluable Interface.
   * 
   * @var aArray
   */
  private static $validValues = array(
    'id' => array(
      'CTE_Engine_Expression_Literal_String' => true,
      'CTE_Engine_Expression_Literal_ParseableString' => true
    ),
    'source' => array(
      'CTE_Engine_Expression_Variable' => true,
      'CTE_Engine_Expression_Resource' => true
    ),
    'start' => array(
      'CTE_Engine_Expression_Literal_Numeric' => true
    ),
    'step' => array(
      'CTE_Engine_Expression_Literal_Numeric' => true
    ),
    'max' => array(
      'CTE_Engine_Expression_Literal_Numeric' => true
    ),
    'enable' => array(
      'CTE_Engine_Expression_Literal_String' => true,
      'CTE_Engine_Expression_Literal_ParseableString' => true
    )
  );
  
  /**
   * Process for current foreach block.
   * 
   * @var CTE_Engine_Process_Abstract
   */
  private $process;
  
  /**
   * Whether we have registered a close tag or not.
   * 
   * @var bool
   */
  private $isClosed = false;
  
  /**
   * Variable Expression object representing the current section system
   * variable, which might look like: $this->section[id].
   * 
   * @var CTE_Engine_Expression_Variable
   */
  private $sectionSysVar = null;
  
  /**
   * ATTRIBUTE: id
   * 
   * @var CTE_Engine_Expression_Abstract
   */
  private $attr_id = null;
  
  /**
   * ATTRIBUTE: source, src
   * 
   * The data source (variable or resource).
   * 
   * @var CTE_Engine_Expression_Abstract
   */
  private $attr_source = null;
  
  /**
   * ATTRIBUTE: start
   * 
   * Index to start iteration from.
   * 
   * @var int
   */
  private $attr_start = 0;
  
  /**
   * ATTRIBUTE: step
   * 
   * The number to increment index by after one iteration.
   * 
   * @var int
   */
  private $attr_step = 1;
  
  /**
   * ATTRIBUTE: max
   * 
   * Maximum number of iterations. Value -1 means there is no limit.
   * 
   * @var int
   */
  private $attr_max = -1;
  
  /**
   * ATTRIBUTE: enable
   * 
   * System variable properties to make available.
   * 
   * @var array
   */
  private $attr_enable = array();
  
  /**
   * Required attributes tracker.
   * 
   * @var aArray
   */
  private $reqAttrsTracker = array('id'     => self::ATTR_EXPECTED,
                                   'source' => self::ATTR_EXPECTED);
  
  /**
   * @see CTE_Engine_Expression_Abstract::requestInstance()
   */
  public static function requestInstance(CTE_Engine_Parser_Tag $tagParser)
  {
    if ($tagParser->beginsWith('section')) {
      self::tokenizeTag($tagParser);
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
    
    // Jump past tag name (section):
    $this->nextIdentifier($tagParser);
    
    // Get environment instance:
    $env = CTE_Engine_Environment::getInstance();
    
    // Enter a new block process and save it (so we know what to exit later):
    $this->process = $env->getProcessManager()->enterProcess($this, 'Block');
    
    // Register ourselves as a tag parser handler:
    $env->getCompiler()->registerTagParserHandler($this);
    
    // Store ID of current template process:
    $this->templateId = $env->getProcessManager()->
                              getCurrentTemplateProcess()->
                              getId();
    
    $attrs = $this->parseAttrs($tagParser);
    
    // Validate and store attribute list:
    foreach ($attrs as $name => $value) {
      
      // Validate current attribute value type:
      if (isset(self::$validValues[$name]) &&
          !isset(self::$validValues[$name][get_class($value)])) {
        throw new CTE_Engine_Template_Contextual_Exception(
          $this,
          "invalid value for $name attribute"
        );
      }
      
      switch ($name) {
        case 'id':
        
          // Mark this attribute as found:
          $this->reqAttrsTracker['id'] = self::ATTR_OK;
          
          // Set raw value:
          $this->attr_id = $value->evaluate(false);
          
          // Make sure the id matches regex for user defined id's:
          if (!preg_match('/^' . self::$idRegex . '$/i', $this->attr_id)) {
            throw new CTE_Engine_Template_Parsing_Exception(
              $this,
              "invalid id: {$this->attr_id}"
            );
          }
          
          // Register ID use:
          $this->registerId($this->attr_id);
          break;
        
        case 'source':
        
          // Mark this attribute as found:
          $this->reqAttrsTracker['source'] = self::ATTR_OK;
        
          // Store attribute:
          $this->attr_source = $value->compile();
          break;
        
        case 'start':
          $this->attr_start = $value->evaluate(false);
          break;
        
        case 'step':
          $this->attr_step = $value->evaluate(false);
          break;
        
        case 'max':
          $this->attr_max = $value->evaluate(false);
          break;
        
        case 'enable':
          $this->attr_enable = explode(',', $value->evaluate(false));
          break;
        
        default:
          throw new CTE_Engine_Template_Contextual_Exception(
            $this,
            "unknown attribute: $name"
          );
      }
    }
    
    // Make sure all required attributes are set:
    foreach ($this->reqAttrsTracker as $attrName => $attrStatus) {
      if ($attrStatus == self::ATTR_EXPECTED) {
        throw new CTE_Engine_Template_Contextual_Exception(
          $this,
          "missing attribute: $attrName"
        );
      }
    }
    
    // Set variable aliases in variable registry (it is important to do this
    // after the parsing above, so we don't make it legal to use the aliases
    // in the foreach tag).
    
    $varReg = CTE_Engine_Environment::getInstance()->getVariableRegistry();
    
    // Create section system variable:
    $this->sectionSysVar = CTE_Engine_Expression_Variable::createInstance(
      $this->attr_id,
      CTE_Engine_Expression_Variable::SOURCE_RUNTIME_SECTION
    );
  }
  
  /**
   * Returns true if section id $sId is available in template with id
   * $tId.
   * 
   * @param int $tId
   * @param string $sId
   * @return bool
   */
  public static function idIsAvail($tId, $sId)
  {
    return isset(self::$activeIds[$tId][$sId]);
  }
  
  /**
   * Tries to register a section ID.
   * 
   * @param string $id
   * @throws CTE_Engine_Template_Exception if id is already in use.
   * @return void
   */
  private function registerId($id)
  {
    if (self::idIsAvail($this->templateId, $id)) {
      throw new CTE_Engine_Template_Exception(
        $this,
        "id already in use: $id"
      );
    }
    
    self::$activeIds[$this->templateId][$id] = true;
  }
  
  /**
   * Unregisters current section ID.
   * 
   * @return void
   */
  private function unregisterId()
  {
    unset(self::$activeIds[$this->templateId][$this->attr_id]);
  }
  
  /**
   * @see CTE_Engine_Expression_Abstract::getName()
   */
  public function getName()
  {
    return 'section block';
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
   * Handles 'else' and end-tags.
   *
   * @see CTE_Engine_Parser_Tag_Handler_Interface::CTE_Engine_Parser_Tag()
   */
  public function handleTagParser(CTE_Engine_Parser_Tag $tagParser)
  {
    if ($tagParser->__toString() == '/section') {
      
      // Skip further parsing of the section end-tag:
      $tagParser->skip();
    
      $this->isClosed = true;
      
      // Free our ID:
      $this->unregisterId();
      
      $env = CTE_Engine_Environment::getInstance();
      
      $compiler = $env->getCompiler();

      // Unregister ourselves from handling tags in compiler:
      $compiler->unregisterTagParserHandler($this);

      // Close our process:
      $env->getProcessManager()->leaveProcess($this->process);
      
      return $this->wrapPhpCode(($this->hadElse ? '}' : '}}'));
    } else if ($tagParser->__toString() == 'sectionelse') {
      
      // Skip further parsing of the foreachelse tag:
      $tagParser->skip();
      
      $this->hadElse = true;
      
      return $this->wrapPhpCode('}}else{');
    } else {
      return $tagParser;
    }
  }

  /**
   * Returns true if system variable property with name $prop is enabled.
   * 
   * @param string $prop
   * @return bool
   */
  private function propEnabled($prop)
  {
    return in_array($prop, $this->attr_enable);
  }
  
  /**
   * @see CTE_Engine_Expression_Compilable_Interface::compile()
   */
  public function compile($concatContext = false)
  {
    parent::compile($concatContext);
    
    // Compile reference string to section system variable:
    $sysVarRef = $this->sectionSysVar->compile();
    
    // Create anonymous variable reference string:
    $anonVarRef = "\$s{$this->process->getId()}";
    
    // Prepare variable strings (null means we're not using it):
    $first = ($this->propEnabled('first') ?
               "{$sysVarRef}['first']" :
               null
             );
    $last = ($this->propEnabled('last') ?
              "{$sysVarRef}['last']" :
              null
            );
    $max = "{$anonVarRef}max";
    $size = ($this->propEnabled('size') ?
              "{$sysVarRef}['size']" :
              "{$anonVarRef}size"
            );
    $index = ($this->propEnabled('index') ?
               "{$sysVarRef}['index']" :
               "{$anonVarRef}i"
             );
    $iteration = ($this->propEnabled('iteration') ?
                   "{$sysVarRef}['iteration']" :
                   "{$anonVarRef}it"
                 );
    
    // Enclose generated for-statement in an if-statement, so we can use
    // sectionelse:
    $result = "if(isset({$this->attr_source})&&"
            . "is_array({$this->attr_source})&&"
            . "($size=count({$this->attr_source}))>0){"
            
            // Define 'first' and 'last' system var properties:
            .   (!is_null($first) ? "$first=true;" : '')
            .   (!is_null($last) ? "$last=false;" : '')
            
            // Make sure 'max' doesn't go out of bounds:
            .   ($this->attr_max == -1 ?
                  '' :
                  "$max=min($size,{$this->attr_max});"
                  )
            
            // Compile for-statement head:
            .   "for($index={$this->attr_start},$iteration=1;"
            .   "$index<" . ($this->attr_max == -1 ? $size : $max) . ';'
            .   "$index"
            .   ($this->attr_step == 1 ? '++' : "+={$this->attr_step}")
            .   ",$iteration++){"
            
            // Compile in-loop property assignment:
            .     (!is_null($first) ?
                    "if($iteration==2){"
            .         "$first=false;"
            .       '}' :
                    ''
                  )
            .     (!is_null($last) ?
                    "if($iteration==$max){"
            .         "$last=true;"
            .       '}' :
                    ''
                  );
  
    return $this->wrapPhpCode($result);
  }
}
?>