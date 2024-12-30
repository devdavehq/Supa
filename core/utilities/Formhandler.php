<?php

class Validator {
    private static $instance = null;
    private $data = [];
    private $errors = [];
    
    // Private constructor for singleton
    private function __construct($formData) {
        $this->data = $formData;
    }
    
    // Get validator instance
    public static function check($formData) {
        if (self::$instance === null) {
            self::$instance = new self($formData);
        } else {
            // Automatically clear errors and set new data
            self::$instance->errors = [];
            self::$instance->data = $formData;
        }
        return self::$instance;
    }
    
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

            $valid = match($rule) {
                'required' => !empty(trim($value)),
                'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
                'min' => strlen($value) >= (int)($params[0] ?? 0),
                'max' => strlen($value) <= (int)($params[0] ?? 0),
                'numeric' => is_numeric($value),
                'alpha' => ctype_alpha($value),
                'alphanumeric' => ctype_alnum($value),
                default => true
            };

            if (!$valid) {
                $defaultMsg = match($rule) {
                    'required' => "$field is required",
                    'email' => "$field must be a valid email",
                    'min' => "$field must be at least {$params[0]} characters",
                    'max' => "$field must not exceed {$params[0]} characters",
                    'numeric' => "$field must be numeric",
                    'alpha' => "$field must contain only letters",
                    'alphanumeric' => "$field must contain only letters and numbers",
                    default => "$field is invalid"
                };

                $this->errors[$field][] = $errorMsg ?? $defaultMsg;
            }
        }
        
        return $this;
    }

    public function addRule($field, $callback, $errorMsg) {
        $value = $this->data[$field] ?? '';
        
        if (!$callback($value)) {
            $this->errors[$field][] = $errorMsg;
        }
        
        return $this;
    }

    public function failed() {
        return !empty($this->errors);
    }
    
    public function getErrors() {
        return [
            'errors' => $this->errors,        // All errors
            'first' => $this->getFirstErrors(), // First error of each field
            'has' => !empty($this->errors)      // Boolean if has errors
        ];
    }

    public function getFieldError($field) {
        return $this->errors[$field] ?? [];
    }

    private function getFirstErrors(): array {
        $firstErrors = [];
        foreach ($this->errors as $field => $errors) {
            $firstErrors[$field] = $errors[0] ?? '';
        }
        return $firstErrors;
    }

    public function clear(): self {
        $this->errors = [];
        return $this;
    }
}

//  normal usage





// Apis and ajax

//    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     Validator::check($_POST)
//         ->validate('username', ['required', 'min:3'])
//         ->validate('email', ['required', 'email']);

//     if (Validator::check($_POST)->failed()) {
//         header('Content-Type: application/json');  set tis before
//         echo json_encode([
//             'status' => 'error',
//             'errors' => Validator::check($_POST)->getErrors()
//         ]);
//         exit;
//     }
    
//     // Process valid form...
//     echo json_encode(['status' => 'success']);
// } 



// Correct usage:
// Validator::check($_POST)
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

// // Wrong usage (don't do this):
// Validator::check($_POST)
//     ->addRule('password', fn($value) => preg_match('/[A-Z]/', $value), 'Need uppercase')
//     ->validate('password', ['required']);  // Should validate first