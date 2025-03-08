<?php

class Validator {
    private $data = [];
    private $errors = [];
    
    // Constructor
    public function __construct($formData) {
        $this->data = $formData;
    }
    
    // Create a new validator instance
    public static function check($formData) {
        return new self($formData);
    }
    
    // Validate a field against rules
    public function validate($field, array $rules) {
        $value = $this->data[$field] ?? '';
        
        foreach ($rules as $rule => $errorMsg) {
            if (is_numeric($rule)) {
                $rule = $errorMsg;
                $errorMsg = null;
            }

            $params = [];
            if (strpos($rule, ':') !== false) {
                [$rule, $paramStr] = explode(':', $rule);
                $params = explode(',', $paramStr);
            }

            $valid = false; // Initialize $valid

            switch ($rule) {
                case 'required':
                    $valid = !empty(trim($value));
                    break;
                case 'email':
                    $valid = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                    break;
                case 'min':
                    $valid = strlen($value) >= (int)($params[0] ?? 0);
                    break;
                case 'max':
                    $valid = strlen($value) <= (int)($params[0] ?? 0);
                    break;
                case 'numeric':
                    $valid = is_numeric($value);
                    break;
                case 'alpha':
                    $valid = ctype_alpha($value);
                    break;
                case 'alphanumeric':
                    $valid = ctype_alnum($value);
                    break;
                default:
                    $valid = true; // Default case
                    break;
            }

            if (!$valid) {
                $defaultMsg = "$field is invalid"; // Default error message
                switch ($rule) {
                    case 'required':
                        $defaultMsg = "$field is required";
                        break;
                    case 'email':
                        $defaultMsg = "$field must be a valid email";
                        break;
                    case 'min':
                        $defaultMsg = "$field must be at least {$params[0]} characters";
                        break;
                    case 'max':
                        $defaultMsg = "$field must not exceed {$params[0]} characters";
                        break;
                    case 'numeric':
                        $defaultMsg = "$field must be numeric";
                        break;
                    case 'alpha':
                        $defaultMsg = "$field must contain only letters";
                        break;
                    case 'alphanumeric':
                        $defaultMsg = "$field must contain only letters and numbers";
                        break;
                }

                $this->errors[$field][] = $errorMsg ?? $defaultMsg;
            }
        }
        
        return $this;
    }

    // Add a custom validation rule
    public function addRule($field, $callback, $errorMsg) {
        $value = $this->data[$field] ?? '';
        
        if (!$callback($value)) {
            $this->errors[$field][] = $errorMsg;
        }
        
        return $this;
    }

    // Check if validation failed
    public function failed() {
        return !empty($this->errors);
    }
    
    // Get all errors
    public function getErrors() {
        return [
            'errors' => $this->errors,        // All errors
            'first' => $this->getFirstErrors(), // First error of each field
            'has' => !empty($this->errors)      // Boolean if has errors
        ];
    }

    // Get errors for a specific field
    public function getFieldError($field) {
        return $this->errors[$field] ?? [];
    }

    // Get the first error for each field
    private function getFirstErrors(): array {
        $firstErrors = [];
        foreach ($this->errors as $field => $errors) {
            $firstErrors[$field] = $errors[0] ?? '';
        }
        return $firstErrors;
    }

    // Clear all errors
    public function clear(): self {
        $this->errors = [];
        return $this;
    }
}


// usage 
// $validator = Validator::check($_POST)
//     ->validate('username', ['required', 'min:3'])
//     ->validate('email', ['required', 'email']);

// if ($validator->failed()) {
//     $errors = $validator->getErrors();
//     // Handle errors
// } else {
//     // Process valid form
// }
// $validator = Validator::check($_POST)
//     ->validate('username', ['required', 'min:3'])
//     ->validate('email', ['required', 'email']);

// if ($validator->failed()) {
//     $errors = $validator->getErrors();
//     // Handle errors
// } else {
//     // Process valid form
// }
// $validator = Validator::check($_POST)
//     ->validate('password', [
//         'required' => 'Password required',
//         'min:8' => 'Too short'
//     ])
//     ->addRule('password', 
//         fn($value) => preg_match('/[A-Z]/', $value),
//         'Need uppercase letter'
//     )
//     ->addRule('password',
//         fn($value) => preg_match('/[0-9]/', $value),
//         'Need a number'
//     );

// if ($validator->failed()) {
//     $errors = $validator->getErrors();
//     // Handle errors
// }