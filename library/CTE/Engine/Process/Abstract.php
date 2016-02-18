<?php
/**
 *
 *
 * @package CTE_Engine_Process
 * @since 2006-07-08
 * @version 2007-02-08
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * Process Abstract Class
 * 
 * Every template, subtemplate and block are associated with a process. That
 * process will make it possible to supervise the above mentioned components.
 * The process will not directly interfere with the compilation and building
 * though; it is only meant to hold the data and let its associated components
 * know when the data or the process itself has changed.
 * 
 * @access private
 */
abstract class CTE_Engine_Process_Abstract
{
  const PID_ROOT = 1;
  
  /**
   * ID to use while creating next process instance.
   * 
   * @var int
   */
  private static $nextId = self::PID_ROOT;

  /**
   * The process ID, starting at {@see self::PID_ROOT}.
   * 
   * @var int
   */
  private $id;

  /**
   * Parent process object (if any).
   * 
   * @var CTE_Engine_Process_Abstract
   */
  private $parent = null;

  /**
   * True if current process is suspended (has children process).
   * 
   * @var bool
   */
  private $suspended = false;

  /**
   * Flags associated with current process.
   *
   * @var int
   */
  private $flags = 0;

  /**
   * @var bool
   */
  private $closed = false;

  /**
   * The process creator.
   * 
   * @var CTE_Engine_Process_Creator_Interface
   */
  private $creator;

  /**
   * 
   * 
   * @throws CTE_Engine_Process_Exception 
   * @return bool
   */
  final protected static function processIsInstanceof(
      CTE_Engine_Process_Abstract $process,
      array $validInstances
    )
  {
    for ($i=0, $ii=count($validInstances); $i<$ii; $i++) {
      // If $process is instance of any of the valid instances, we
      // return true:
      if ($process instanceof $validInstances[$i]) {
        return true;
      }
    }
    
    // Process failed to be instance of one of the valid instances:
    return false;
  }
  
  /**
   * Process Constructor
   * 
   * Calls child class validation method and then does the general setup.
   * 
   * @param CTE_Engine_Process_Creator_Interface $creator The creator of this
   *        process.
   * @param CTE_Engine_Process_Abstract $parentProcess
   */
  public function __construct(CTE_Engine_Process_Creator_Interface $creator,
                              CTE_Engine_Process_Abstract $parentProcess = null)
  {
    $this->id = $this->generateId();
    
    // Call the overrideable validation method:
    $this->validate($creator, $parentProcess);
    
    $this->creator = $creator;
    $this->parent = $parentProcess;
  }

  /**
   * Generates an ID for current process instance.
   * 
   * @return void
   */
  protected function generateId()
  {
    return self::$nextId++;
  }
  
  /**
   * Resets the process ID counter. Should only be used by the root process.
   * 
   * @return void
   */
  protected function resetId()
  {
    self::$nextId = self::PID_ROOT;
  }
  
  /**
   * Validates constructor arguments. This method is meant (but not required) to
   * be overridden.
   * 
   * @param CTE_Engine_Process_Creator_Interface $creator The creator of this
   *        process.
   * @param CTE_Engine_Process_Abstract $parentProcess
   * @return void
   */
  protected function validate(CTE_Engine_Process_Creator_Interface $creator,
                              CTE_Engine_Process_Abstract $parentProcess = null)
  {
  }
  
  /**
   * Returns the ID of current process.
   * 
   * @return int
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Returns the parent of this process.
   *
   * @return CTE_Engine_Process_Abstract
   * @return null
   */
  final public function getParent()
  {
    return $this->parent;
  }

  /**
   * Returns true if process is closed.
   *
   * @return bool
   */
  final public function isClosed()
  {
    return $this->closed;
  }

  /**
   * Tells wheter or not this process is suspended.
   *
   * @return bool
   */
  final public function isSuspended()
  {
    return $this->suspended;
  }

  /**
   * Notifies process creator about a relevant process update.
   * 
   * @return void
   */
  final protected function notifyCreator()
  {
    $this->creator->notifyProcessUpdated($this);
  }

  /**
   * Adds a flag to this process and returns true if the flag wasn't set before.
   *
   * @param int $flag
   * @return bool
   */
  final public function setFlag($flag)
  {
    $result = !$this->hasFlag($flag);
    $this->flags = ($this->flags | $flag);
    return $result;
  }

  /**
   * Returns current flags integer.
   * 
   * @return int
   */
  final public function getFlags()
  {
    return $this->flags;
  }
  
  /**
   * Tells whether or not a flag is set for this process.
   *
   * @param int $flag
   * @return bool
   */
  final public function hasFlag($flag)
  {
    return ($this->flags & $flag) > 0;
  }

  /**
   * Removes a flag from this process and returns true if the flag wasn't unset
   * before.
   *
   * @param int $flag
   * @return bool
   */
  final public function unsetFlag($flag)
  {
    $result = $this->hasFlag($flag);
    $this->flags = ($this->flags & ~$flag);
    return $result;
  }

  /**
   * This method will be called before suspending current process.
   *
   * @return void
   */
  protected function beforeSuspend()
  {
  }

  /**
   * This method will be called after reinstating current process.
   *
   * @return void
   */
  protected function afterReinstate()
  {
  }

  /**
   * This method will be called before the closing of current process.
   * 
   * @return void
   */
  protected function beforeClose()
  {
  }
  
  /**
   * Suspends current process to a child process.
   *
   * @throws CTE_Engine_Process_Exception
   * @return void
   */
  final public function suspend()
  {
    $this->beforeSuspend();
    $this->suspended = true;
  }

  /**
   * Wakes up current process and closes the left child process.
   *
   * @throws CTE_Engine_Process_Exception
   * @return void
   */
  final public function reinstate()
  {
    $this->suspended = false;
    $this->afterReinstate();
  }

  /**
   * Notifies the creator about the method call and closes current process.
   *
   * @throws CTE_Engine_Process_Exception if process is already closed.
   * @return void
   */
  final public function close()
  {
    if ($this->closed) {
      throw new CTE_Engine_Process_Exception('cannot close process twice');
    }
    
    $this->beforeClose();
    $this->closed = true;
    
    // Notify the creator AFTER the changes:
    $this->notifyCreator();
  }
}
?>