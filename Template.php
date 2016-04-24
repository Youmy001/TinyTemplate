<?php
/**
 * @author François Allard <binarmorker@gmail.com>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace TinyTemplate;

/**
 * A Template is a view that can be processed through the Engine.
 */
class Template {
    
    /**
     * @var string The Template's contents.
     */
    private $template = "";
    
    /**
     * @var array[object|array] The loop stack.
     */
    private $stack = array();
    
    /**
     * @var array[mixed] The data to be passed to the Template.
     */
    private $data = array();
    
    /**
     * @var array[Rule] The rules to be applied to the Template.
     */
    private $rules = array();
    
    /**
     * Creates the Template from a filename.
     * @param $file string The filename and path of the Template.
     * @throws InvalidArgumentException If the file cannot be found.
     */
    public function __construct($file) {
        $this->template = @file_get_contents($file, true);
        
        if (!$this->template) {
            throw new \InvalidArgumentException('File not found');
        }
    }
    
    /**
     * Imports a file in the form of a new Template.
     * @var $file string The filename of the imported Template.
     * @returns string
     */
    private function importFile($file) {
        $template = new Template($file);
        return $template->process($this->rules, $this->data);
    }
    
    /**
     * Shows the content of a variable stored in the data.
     * @var $name string The variable name in the data array.
     * @var $sanitize boolean If the variable should be escaped before being returned.
     * @returns mixed
     */
    private function showVariable($name, $sanitize = false) {
        if (isset($this->data[$name])) {
            if ($sanitize) {
                echo htmlentities($this->data[$name]);
            } else {
                echo $this->data[$name]; 
            }
        } else {
            echo '{' . $name . '}';
        }
    }
    
    /**
     * Wraps the content of the loop into the data array so it can be used
     * @var $element object|array The element that will be looped into.
     */
    private function wrap($element) {
        $this->stack[] = $this->data;
        foreach ($element as $k => $v) {
            $this->data[$k] = $v;
        }
    }
    
    /**
     * Removes the loop variables from inside the data so we cannot use it afterwards.
     */
    private function unwrap() {
        $this->data = array_pop($this->stack);
    }
    
    /**
     * Process the Template and convert its variables into values.
     * @var $rules array[Rule] The rules to be applied to the Template.
     * @var $data array The data to be passed to the Template.
     * @returns string
     */
    public function process($rules, $data) {
        $this->rules = $rules;
        
        // Variables
        $this->rules[] = new Rule(
            'escape_var', 
            '~\{escape:(\w+)\}~', 
            '<?php $this->showVariable(\'$1\', true); ?>'
        );
        $this->rules[] = new Rule(
            'variable', 
            '~\{(\w+)\}~', 
            '<?php $this->showVariable(\'$1\'); ?>'
        );
        
        $this->data = $data;
        $this->stack = array();
        
        foreach ($this->rules as $rule) {
            $this->template = preg_replace($rule->rule(), $rule->replacement(), $this->template);
        }
        
        $this->template = '?>' . $this->template;
        ob_start();
        eval($this->template);
        return ob_get_clean();
    }
}