<?php
defined('COREPATH') or exit('No direct script access allowed');

/**
 * Base Form Validation Class
 * 
 * Extends CI_Form_validation to provide:
 * - Fluent validation rule syntax
 * - File upload validation support
 * - Conditional validation (when/unless)
 * - Custom rule objects
 * - Better error handling
 * 
 * @package     Engine
 * @subpackage  Libraries
 * @category    Validation
 */
class Base_Form_validation extends \CI_Form_validation
{
	/**
	 * Fluent API rules storage
	 * @var array
	 */
	protected $fluentRules = [];

	/**
	 * Custom error messages for fluent API
	 * @var array
	 */
	protected $fluentMessages = [];

	/**
	 * Field labels for fluent API
	 * @var array
	 */
	protected $fluentLabels = [];

	/**
	 * Conditional checks for fields
	 * @var array
	 */
	protected $conditionalChecks = [];

	/**
	 * Constructor
	 * 
	 * @param array $rules
	 */
	public function __construct($rules = [])
	{
		parent::__construct($rules);

		$this->CI->load->helper('form');

		log_message('info', 'Base Form Validation Class Initialized');
	}

	// ============================================================
	// FLUENT API IMPLEMENTATION
	// ============================================================

	/**
	 * Entry point for the Fluent API
	 * 
	 * Returns a Field object to start chaining validation rules
	 *
	 * @param string $field The name of the form field
	 * @param string $label The human-readable label for the field
	 * @return Field
	 */
	public function field(string $field, string $label = ''): Field
	{
		if (empty($label)) {
			$label = ucwords(str_replace('_', ' ', $field));
		}

		// Clear any previous state for this field
		unset(
			$this->fluentRules[$field],
			$this->fluentMessages[$field],
			$this->conditionalChecks[$field]
		);

		return new Field($field, $label, $this);
	}

	/**
	 * Store rules from Field object (internal use)
	 *
	 * @param string $field
	 * @param string $label
	 * @param array $rules
	 * @param array $messages
	 * @param callable|null $condition
	 * @return void
	 */
	public function storeFieldRules(string $field, string $label, array $rules, array $messages = [], $condition = null): void
	{
		$this->fluentRules[$field] = $rules;
		$this->fluentLabels[$field] = $label;

		if (!empty($messages)) {
			$this->fluentMessages[$field] = $messages;
		}

		if ($condition !== null) {
			$this->conditionalChecks[$field] = $condition;
		}
	}

	// ============================================================
	// OVERRIDE SET_RULES TO SUPPORT FLUENT API + FILES
	// ============================================================

	/**
	 * Set Rules - Enhanced to support file validation
	 *
	 * @param mixed $field
	 * @param string $label
	 * @param mixed $rules
	 * @param array $errors
	 * @return Base_Form_validation
	 */
	public function set_rules($field, $label = '', $rules = [], $errors = [])
	{
		// Handle file-only forms (no POST data, only FILES)
		$needsPlaceholder = (count($_POST) === 0 && count($_FILES) > 0);

		if ($needsPlaceholder) {
			$_POST['__FV_PLACEHOLDER__'] = '';
		}

		// Call parent set_rules
		parent::set_rules($field, $label, $rules, $errors);

		if ($needsPlaceholder) {
			unset($_POST['__FV_PLACEHOLDER__']);
		}

		return $this;
	}

	// ============================================================
	// OVERRIDE RUN TO INTEGRATE FLUENT API
	// ============================================================

	/**
	 * Run validation - Enhanced to support fluent API and file validation
	 *
	 * @param string $group
	 * @return bool
	 */
	public function run($group = '')
	{
		// Process fluent rules first
		$this->_process_fluent_rules();

		// Handle file-only forms
		$needsPlaceholder = (count($_POST) === 0 && count($_FILES) > 0);

		if ($needsPlaceholder) {
			$_POST['__FV_PLACEHOLDER__'] = '';
		}

		// Run parent validation
		$result = parent::run($group);

		if ($needsPlaceholder) {
			unset($_POST['__FV_PLACEHOLDER__']);
		}

		return $result;
	}

	/**
	 * Process fluent API rules and convert them to standard format
	 *
	 * @return void
	 */
	protected function _process_fluent_rules(): void
	{
		foreach ($this->fluentRules as $field => $rules) {
			// Check conditional validation
			if (isset($this->conditionalChecks[$field])) {
				$condition = $this->conditionalChecks[$field];
				$data = empty($this->validation_data) ? $_POST : $this->validation_data;

				$shouldValidate = call_user_func($condition, $data);

				if (!$shouldValidate) {
					// Skip this field validation
					continue;
				}
			}

			$label = $this->fluentLabels[$field] ?? $field;
			$errors = $this->fluentMessages[$field] ?? [];

			// Convert fluent rules array to format parent expects
			$processedRules = [];

			foreach ($rules as $ruleString => $ruleValue) {
				if (is_object($ruleValue)) {
					// Custom rule object - store it and create reference
					$processedRules[] = $ruleValue;
				} else {
					// Standard string rule
					$processedRules[] = $ruleString;
				}
			}

			// Set rules using parent method
			$this->set_rules($field, $label, $processedRules, $errors);
		}
	}

	// ============================================================
	// OVERRIDE _EXECUTE FOR FILE VALIDATION
	// ============================================================

	/**
	 * Execute validation - Enhanced for file validation
	 *
	 * @param array $row
	 * @param array $rules
	 * @param mixed $postdata
	 * @param int $cycles
	 * @return mixed
	 */
	protected function _execute($row, $rules, $postdata = null, $cycles = 0)
	{
		// Check if this is a file field
		$isFileField = isset($_FILES[$row['field']]) && is_array($_FILES[$row['field']]);

		if ($isFileField) {
			return $this->_execute_file_validation($row, $rules, $postdata, $cycles);
		}

		// Not a file field, use parent execution
		return parent::_execute($row, $rules, $postdata, $cycles);
	}

	/**
	 * Execute file validation
	 *
	 * @param array $row
	 * @param array $rules
	 * @param mixed $postdata
	 * @param int $cycles
	 * @return mixed
	 */
	protected function _execute_file_validation($row, $rules, $postdata = null, $cycles = 0)
	{
		log_message('debug', 'Base_Form_validation::_execute_file_validation - ' . $row['field']);

		$postdata = $_FILES[$row['field']];

		// Modify rules: replace 'required' with 'file_required'
		$modifiedRules = [];
		foreach ($rules as $rule) {
			if ($rule === 'required') {
				$modifiedRules[] = 'file_required';
			} else {
				$modifiedRules[] = $rule;
			}
		}
		$rules = $modifiedRules;

		// Check for upload errors first
		if (isset($postdata['error']) && $postdata['error'] !== UPLOAD_ERR_OK) {
			// If error is "no file" and file_required is not in rules, skip
			if ($postdata['error'] == UPLOAD_ERR_NO_FILE && !in_array('file_required', $rules)) {
				return; // No error, just no file uploaded
			}

			// Get error message
			$message = $this->_get_file_upload_error_message($row['label'], $postdata['error']);

			$this->_field_data[$row['field']]['error'] = $message;
			$this->_error_array[$row['field']] = $message;

			return false;
		}

		$rules = $this->_prepare_rules($rules);
		$_in_array = false;

		// Execute each rule
		foreach ($rules as $rule) {
			// Handle callbacks and custom objects
			$callback = $callable = false;
			$custom_rule_object = null;

			if (is_string($rule)) {
				if (strpos($rule, 'callback_') === 0) {
					$rule = substr($rule, 9);
					$callback = true;
				}
			} elseif (is_callable($rule)) {
				$callable = true;
			} elseif (is_object($rule)) {
				$custom_rule_object = $rule;
				$callable = true;
			}

			// Extract parameters
			$param = false;
			if (!$callable && !$custom_rule_object && preg_match('/(.*?)\[(.*)\]/', $rule, $match)) {
				$rule = $match[1];
				$param = $match[2];
			}

			// Skip empty non-required files
			if (
				(!isset($postdata['size']) || $postdata['size'] == 0)
				&& $callback === false
				&& $callable === false
				&& $custom_rule_object === null
				&& !in_array($rule, ['file_required', 'required'], true)
			) {
				continue;
			}

			// Execute the rule
			if ($callback or $callable !== false or $custom_rule_object !== null) {
				if ($callback) {
					if (!method_exists($this->CI, $rule)) {
						log_message('debug', 'Unable to find callback validation rule: ' . $rule);
						$result = false;
					} else {
						$result = $this->CI->$rule($postdata, $param);
					}
				} elseif ($custom_rule_object !== null) {
					$result = $this->_execute_custom_rule($custom_rule_object, $postdata, $row, $param);

					if ($result === false) {
						return;
					}

					$this->_field_data[$row['field']]['postdata'] = is_bool($result) ? $postdata : $result;
					continue;
				} else {
					$result = is_array($rule) ? $rule[0]->{$rule[1]}($postdata) : $rule($postdata);
				}

				$this->_field_data[$row['field']]['postdata'] = is_bool($result) ? $postdata : $result;

				if (!in_array('file_required', $rules, true) && $result !== false) {
					return;
				}
			} elseif (!method_exists($this, $rule)) {
				if (function_exists($rule)) {
					$result = ($param !== false) ? $rule($postdata, $param) : $rule($postdata);
					$this->_field_data[$row['field']]['postdata'] = is_bool($result) ? $postdata : $result;
				} else {
					log_message('debug', 'Unable to find validation rule: ' . $rule);
					$result = false;
				}
			} else {
				$result = $this->$rule($postdata, $param);
				$this->_field_data[$row['field']]['postdata'] = is_bool($result) ? $postdata : $result;
			}

			// Handle failed validation
			if ($result === false) {
				$line = '';

				if (!isset($this->_error_messages[$rule])) {
					if (false === ($line = $this->CI->lang->line($rule))) {
						$line = 'Unable to access an error message corresponding to your field name.';
					}
				} else {
					$line = $this->_error_messages[$rule];
				}

				// Check for custom message
				if (isset($this->_field_data[$row['field']]['errors'][$rule])) {
					$line = $this->_field_data[$row['field']]['errors'][$rule];
				}

				// Check for field-specific param
				if (isset($this->_field_data[$param], $this->_field_data[$param]['label'])) {
					$param = $this->_translate_fieldname($this->_field_data[$param]['label']);
				}

				// Build error message
				$message = $this->_build_error_msg($line, $this->_translate_fieldname($row['label']), $param);

				$this->_field_data[$row['field']]['error'] = $message;

				if (!isset($this->_error_array[$row['field']])) {
					$this->_error_array[$row['field']] = $message;
				}

				return;
			}
		}
	}

	/**
	 * Get file upload error message
	 *
	 * @param string $field
	 * @param int $error_code
	 * @return string
	 */
	protected function _get_file_upload_error_message($field, $error_code)
	{
		$this->CI->lang->load('upload');

		$param = '';

		switch ($error_code) {
			case UPLOAD_ERR_INI_SIZE:
				$message = 'The {field} file exceeds the maximum allowed size in php.ini.';
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$message = 'The {field} file exceeds the maximum allowed size in the form.';
				break;
			case UPLOAD_ERR_PARTIAL:
				$message = 'The {field} file was only partially uploaded.';
				break;
			case UPLOAD_ERR_NO_FILE:
				$message = 'The {field} field is required.';
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$message = 'Missing a temporary folder for {field} file upload.';
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$message = 'Failed to write {field} file to disk.';
				break;
			case UPLOAD_ERR_EXTENSION:
				$message = 'A PHP extension stopped the {field} file upload.';
				break;
			default:
				$message = 'An unexpected error occurred while uploading {field} file. Error code: ' . $error_code;
		}

		return str_replace('{field}', $this->_translate_fieldname($field), $message);
	}

	/* ----------------------------------- Helper Methods For Error Handling -----------------------------------
 
	/**
	 * Set a custom error for a specific field
	 *
	 * @param string $field
	 * @param string $error
	 * @return void
	 */
	public function set_error(string $field, string $error): void
	{
		$this->_error_array[$field] = $error;

		if (isset($this->_field_data[$field])) {
			$this->_field_data[$field]['error'] = $error;
		}
	}

	/**
	 * Alias for set_error
	 *
	 * @param string $field
	 * @param string $error
	 * @return void
	 */
	public function setError(string $field, string $error): void
	{
		$this->set_error($field, $error);
	}

	/* ----------------------------------- Overide Reset To Clear Fluent API State -----------------------------------
 
	/**
	 * Reset validation state
	 *
	 * @return Base_Form_validation
	 */
	public function reset_validation()
	{
		parent::reset_validation();

		$this->fluentRules = [];
		$this->fluentMessages = [];
		$this->fluentLabels = [];
		$this->conditionalChecks = [];

		return $this;
	}
}

/* ----------------------------------- Field Class For Fluent API -----------------------------------
 
/**
 * Field Class
 * 
 * Provides a fluent interface 
 * for building validation rules
 */
class Field
{
	/**
	 * Field name
	 * @var string
	 */
	private string $field;

	/**
	 * Field label
	 * @var string
	 */
	private string $label;

	/**
	 * Validation rules
	 * @var array
	 */
	private array $rules = [];

	/**
	 * Custom error messages
	 * @var array
	 */
	private array $messages = [];

	/**
	 * Validator instance
	 * @var Base_Form_validation
	 */
	private Base_Form_validation $validator;

	/**
	 * Conditional check
	 * @var callable|null
	 */
	private $condition = null;

	/**
	 * Constructor
	 *
	 * @param string $field
	 * @param string $label
	 * @param Base_Form_validation $validator
	 */
	public function __construct(string $field, string $label, Base_Form_validation $validator)
	{
		$this->field = $field;
		$this->label = $label;
		$this->validator = $validator;
	}

	/**
	 * Magic method to handle dynamic rule calls
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return self
	 */
	public function __call(string $name, array $arguments): self
	{
		$rule = $name;

		// Handle custom rule objects/closures
		if (!empty($arguments) && (is_callable($arguments[0]) || is_object($arguments[0]))) {
			// Store the object/closure directly
			$this->rules[uniqid('rule_', true)] = $arguments[0];
		} else {
			// Build standard rule string
			if (!empty($arguments)) {
				$rule .= '[' . implode(',', $arguments) . ']';
			}
			$this->rules[$rule] = true;
		}

		return $this;
	}

	/**
	 * Add a database uniqueness check
	 *
	 * @param string $table
	 * @param string $column
	 * @param mixed $exceptId Optional ID to exclude (for updates)
	 * @return self
	 */
	public function unique(string $table, string $column, $exceptId = null): self
	{
		if ($exceptId !== null) {
			$this->rules["is_unique[{$table}.{$column},id,{$exceptId}]"] = true;
		} else {
			$this->rules["is_unique[{$table}.{$column}]"] = true;
		}

		return $this;
	}

	/**
	 * Set custom error messages for specific rules
	 *
	 * @param array $messages
	 * @return self
	 */
	public function messages(array $messages): self
	{
		$this->messages = array_merge($this->messages, $messages);
		return $this;
	}

	/**
	 * Add a custom validation rule using an object
	 *
	 * @param object $ruleObject Must have passes() and message() methods
	 * @return self
	 */
	public function customRule($ruleObject): self
	{
		if (!is_object($ruleObject)) {
			throw new InvalidArgumentException('customRule() expects an object');
		}

		$this->rules[uniqid('custom_', true)] = $ruleObject;
		return $this;
	}

	/**
	 * Conditional validation - only validate when condition is true
	 *
	 * @param callable $condition Function that receives all form data and returns bool
	 * @return self
	 */
	public function when(callable $condition): self
	{
		$this->condition = $condition;
		return $this;
	}

	/**
	 * Conditional validation - only validate when condition is false
	 *
	 * @param callable $condition Function that receives all form data and returns bool
	 * @return self
	 */
	public function unless(callable $condition): self
	{
		$this->condition = function ($data) use ($condition) {
			return !$condition($data);
		};
		return $this;
	}

	/**
	 * Finalize and store the field rules
	 *
	 * @return void
	 */
	public function get(): void
	{
		$this->validator->storeFieldRules(
			$this->field,
			$this->label,
			$this->rules,
			$this->messages,
			$this->condition
		);
	}

	/**
	 * Finalize rules and return validator instance
	 *
	 * @return Base_Form_validation
	 */
	public function build(): Base_Form_validation
	{
		$this->get();
		return $this->validator;
	}

	/**
	 * Alias for build()
	 *
	 * @return Base_Form_validation
	 */
	public function create(): Base_Form_validation
	{
		return $this->build();
	}
}

/**
 * ValidationRule Interface
 * 
 * Implement this interface to create custom validation rule classes
 */
interface ValidationRule
{
	/**
	 * Check if the value passes validation
	 *
	 * @param mixed $value The field value
	 * @param array $data All form data
	 * @return bool
	 */
	public function passes($value, array $data): bool;

	/**
	 * Get the validation error message
	 *
	 * @return string
	 */
	public function message(): string;
}

/* End of file Base_Form_validation.php */
/* Location: ./engine/Core/libraries/Base_Form_validation.php */
