<?php
/**
 *
 *
 * @package CTE_Engine
 * @since 2006-07-07
 * @version 2007-02-06
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * Process Manager Class
 * 
 * Thinks to think about:
 * - No other class than this should close processes.
 * 
 * @access private
 */
class CTE_Engine_ProcessManager
{
  const PROCESS_CLASS_PREFIX = 'CTE_Engine_Process_';

  /**
   * @var CTE_Engine_Process_Template_Abstract[]
   */
  private $templateProcessStack = array();

  /**
   * @var CTE_Engine_Process_Template_Abstract
   */
  private $currentTemplateProcess = null;

  /**
   * @var CTE_Engine_Process_Abstract
   */
  private $currentProcess = null;

  /**
   * Constructor
   */
  public function __construct()
  {
  }

  /**
   * Creates and enters a new process. If there is a current process, it will be
   * notified about the entering of the new process.
   *
   * @param CTE_Engine_Process_Creator_Interface $creator The creator of the
   *        process.
   * @param string $processName Process name without class prefix.
   * @throws CTE_Engine_Exception if $processName is invalid.
   * @throws CTE_Engine_Exception if entering non-root process without a root
   *         process present.
   * @return CTE_Engine_Process_Abstract The created and entered process.
   */
  public function enterProcess(CTE_Engine_Process_Creator_Interface $creator,
                               $processName)
  {
    $class = self::PROCESS_CLASS_PREFIX . $processName;
    
    // Try to resolve class, throw CTE exception on failure:
    try {
      Cylab::loadClass($class);
    } catch (Cylab_Exception $e) {
      throw new CTE_Engine_Exception(
        "failed to resolve class for process: $processName"
      );
    }
    
    // Create new process class instance, entering from current process, 
    // which may be null:
    $process = new $class($creator, $this->currentProcess);
    
    // Suspend current process if not null:
    if (!is_null($this->currentProcess)) {
      $this->currentProcess->suspend();
    }
    
    // Update current process holder to hold our new process:
    $this->currentProcess = $process;
    
    // Check if we've entered any type of template process:
    if ($process instanceof CTE_Engine_Process_Template_Abstract) {
      
      // Check if we have a current template process to save (put on stack):
      if (!is_null($this->currentTemplateProcess)) {
        $this->templateProcessStack[] = $this->currentTemplateProcess;
      }
      
      // Update current template process holder:
      $this->currentTemplateProcess = $process;
    }
    
    return $this->currentProcess;
  }

  /**
   * Tries to leave process $process.
   *
   * @param string $processName
   * @throws CTE_Engine_Exception if no process is available in the
   *         process tree.
   * @throws CTE_Engine_Template_Contextual_Exception if forcing closure on a
   *         process still required by its creator
   * @return void
   */
  public function leaveProcess(CTE_Engine_Process_Abstract $process)
  {
    if (is_null($this->currentProcess)) {
      throw new CTE_Engine_Exception('no process to leave');
    }

    // Force leave for all over-laying processes:
    $p = $this->currentProcess;
    while ($p !== $process && !is_null($p)) {
      // Get parent of current process:
      $parent = $p->getParent();
      
      // Force close of overlaying process:
      $p->close();
      
      // Reinstate the parent process if not null:
      if (!is_null($parent)) {
        $parent->reinstate();
      }
      
      // Set current process to be the parent process:
      $p = $parent;
    }
    
    // We've reached the process to leave:
    $this->currentProcess = $p;

    // Throw exception if $process wasn't found in process list:
    if (is_null($this->currentProcess)) {
      throw new CTE_Engine_Exception(
        "process #{$process->getId()} not found in process list"
      );
    }

    // Re-point $this->currentTemplateProcess to previous
    // template process, if we're quiting a template process:
    if ($this->currentProcess === $this->currentTemplateProcess) {
      // Note that array_pop will return null if no more elements are found,
      // which is the wanted behaviour.
      $this->currentTemplateProcess = array_pop($this->templateProcessStack);
    }

    // Update current process to point at its parent:
    $parent = $this->currentProcess->getParent();
    $this->currentProcess->close();
    $this->currentProcess = $parent;
  }

  /**
   * Returns true if we're not having any processes in queue.
   *
   * @return bool
   */
  public function isEmpty()
  {
    return ($this->currentProcess === null);
  }

  /**
   * Returns the most recently opened template process.
   * 
   * @return CTE_Engine_Process_Template_Abstract
   * @return null if not template process has been started.
   */
  public function getCurrentTemplateProcess()
  {
    return $this->currentTemplateProcess;
  }
}
?>