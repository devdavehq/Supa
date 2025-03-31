<?php


class Validator {
    private $data = [];
    private $errors = [];
    private static $scenarios = [];
    
    public function __construct($formData) {
        $this->data = $formData;
        self::initScenarios();
    }
    
    private static function initScenarios() {
        if (empty(self::$scenarios)) {
            self::$scenarios = [
                'registration' => [
                    'username' => [
                        'required' => 'Please choose a username',
                        'min:3' => 'Username must be at least 3 characters',
                        'max:20' => 'Username cannot exceed 20 characters',
                        'alphanumeric' => 'Username can only contain letters and numbers'
                    ],
                    'email' => [
                        'required' => 'Email is required',
                        'email' => 'Please enter a valid email address',
                        'max:100' => 'Email cannot exceed 100 characters'
                    ],
                    'password' => [
                        'required' => 'Password is required',
                        'min:8' => 'Password must be at least 8 characters',
                        'regex:/[A-Z]/' => 'Password must contain at least one uppercase letter',
                        'regex:/[0-9]/' => 'Password must contain at least one number',
                        'regex:/[^a-zA-Z0-9]/' => 'Password must contain at least one special character'
                    ]
                ],
                'login' => [
                    'login' => [
                        'required' => 'Username or email is required',
                        'custom_login' => 'Please enter a valid username or email'
                    ],
                    'password' => [
                        'required' => 'Password is required'
                    ]
                    ],
                    'profile_update' => [
                        'name' => [
                            'required' => 'Name is required',
                            'max:50' => 'Name cannot exceed 50 characters'
                        ],
                        'bio' => [
                            'max:500' => 'Bio cannot exceed 500 characters'
                        ],
                        'birth_date' => [
                            'date' => 'Please enter a valid date',
                            'before:-18 years' => 'You must be at least 18 years old'
                        ],
                        'website' => [
                            'nullable' => '',
                            'url' => 'Please enter a valid URL'
                        ]
                    ],
                    'product' => [
                        'name' => [
                            'required' => 'Product name is required',
                            'max:100' => 'Product name cannot exceed 100 characters'
                        ],
                        'price' => [
                            'required' => 'Price is required',
                            'numeric' => 'Price must be a number',
                            'min:0' => 'Price cannot be negative'
                        ],
                        'category' => [
                            'required' => 'Category is required',
                            'in:electronics,clothing,food,furniture' => 'Please select a valid category'
                        ],
                        'stock' => [
                            'required' => 'Stock quantity is required',
                            'numeric' => 'Stock must be a number',
                            'min:0' => 'Stock cannot be negative'
                        ]
            ];
        }
    }
    
       // Scenario Management
       public static function addScenario($name, array $rules, $overwrite = true) {
        if (!$overwrite && isset(self::$scenarios[$name])) {
            return;
        }
        self::$scenarios[$name] = $rules;
    }
    
    public static function extendScenario($name, array $additionalRules) {
        if (!isset(self::$scenarios[$name])) {
            self::$scenarios[$name] = [];
        }
        self::$scenarios[$name] = array_merge(self::$scenarios[$name], $additionalRules);
    }
    
    public static function removeScenario($name) {
        unset(self::$scenarios[$name]);
    }
    
    public static function getScenario($name) {
        return self::$scenarios[$name] ?? null;
    }
    
    public function validate($field, array $rules) {
        $value = $this->data[$field] ?? '';
        
        foreach ($rules as $rule => $errorMsg) {
            if (is_numeric($rule)) {
                $rule = $errorMsg;
                $errorMsg = null;
            }

            if ($rule === 'nullable' && empty(trim($value))) {
                continue;
            }

            $params = [];
            if (strpos($rule, ':') !== false) {
                [$rule, $paramStr] = explode(':', $rule);
                $params = explode(',', $paramStr);
            }

            $valid = false;

            if ($rule === 'custom_login') {
                $valid = filter_var($value, FILTER_VALIDATE_EMAIL) !== false || 
                        (strlen($value) >= 3 && ctype_alnum($value));
                $rule = 'login';
            } else {
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
                    case 'in':
                        $valid = in_array($value, $params);
                        break;
                    case 'not_in':
                        $valid = !in_array($value, $params);
                        break;
                    case 'regex':
                        $valid = preg_match($params[0], $value);
                        break;
                    case 'same':
                        $valid = $value === ($this->data[$params[0] ?? null);
                        break;
                    case 'different':
                        $valid = $value !== ($this->data[$params[0] ?? null);
                        break;
                    case 'date':
                        $valid = strtotime($value) !== false;
                        break;
                    case 'before':
                        $valid = strtotime($value) < strtotime($params[0]);
                        break;
                    case 'after':
                        $valid = strtotime($value) > strtotime($params[0]);
                        break;
                    case 'url':
                        $valid = filter_var($value, FILTER_VALIDATE_URL) !== false;
                        break;
                    case 'ip':
                        $valid = filter_var($value, FILTER_VALIDATE_IP) !== false;
                        break;
                    case 'json':
                        json_decode($value);
                        $valid = json_last_error() === JSON_ERROR_NONE;
                        break;
                    case 'boolean':
                        $valid = in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true);
                        break;
                    default:
                        $valid = true;
                        break;
                }
            }

            if (!$valid) {
                $defaultMsg = "$field is invalid";
                switch ($rule) {
                    case 'required':
                        $defaultMsg = "$field is required";
                        break;
                    case 'email':
                        $defaultMsg = "$field must be a valid email address";
                        break;
                    case 'min':
                        $defaultMsg = "$field must be at least {$params[0]} characters";
                        break;
                    case 'max':
                        $defaultMsg = "$field must be no more than {$params[0]} characters";
                        break;
                    case 'numeric':
                        $defaultMsg = "$field must be a number";
                        break;
                    case 'alpha':
                        $defaultMsg = "$field can only contain letters";
                        break;
                    case 'alphanumeric':
                        $defaultMsg = "$field can only contain letters and numbers";
                        break;
                    case 'in':
                        $defaultMsg = "$field must be one of: " . implode(', ', $params);
                        break;
                    case 'not_in':
                        $defaultMsg = "$field cannot be one of: " . implode(', ', $params);
                        break;
                    case 'regex':
                        $defaultMsg = "$field format is invalid";
                        break;
                    case 'same':
                        $defaultMsg = "$field must match {$params[0]}";
                        break;
                    case 'different':
                        $defaultMsg = "$field must be different from {$params[0]}";
                        break;
                    case 'date':
                        $defaultMsg = "$field must be a valid date";
                        break;
                    case 'before':
                        $defaultMsg = "$field must be before {$params[0]}";
                        break;
                    case 'after':
                        $defaultMsg = "$field must be after {$params[0]}";
                        break;
                    case 'url':
                        $defaultMsg = "$field must be a valid URL";
                        break;
                    case 'ip':
                        $defaultMsg = "$field must be a valid IP address";
                        break;
                    case 'json':
                        $defaultMsg = "$field must be valid JSON";
                        break;
                    case 'boolean':
                        $defaultMsg = "$field must be true or false";
                        break;
                    case 'login':
                        $defaultMsg = "$field must be a valid username or email";
                        break;
                }

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

    public function removeError($field, $index = null) {
        if ($index === null) {
            unset($this->errors[$field]);
        } else {
            unset($this->errors[$field][$index]);
            // Re-index array
            $this->errors[$field] = array_values($this->errors[$field]);
        }
        return $this;
    }

    public function validateForm($scenario, $onlyFields = null) {
        if (!isset(self::$scenarios[$scenario])) {
            throw new InvalidArgumentException("Scenario '$scenario' not found");
        }
        
        $rules = self::$scenarios[$scenario];
        
        if ($onlyFields !== null) {
            $onlyFields = is_array($onlyFields) ? $onlyFields : [$onlyFields];
            $rules = array_intersect_key($rules, array_flip($onlyFields));
        }
        
        foreach ($rules as $field => $fieldRules) {
            $this->validate($field, $fieldRules);
        }
        
        return $this;
    }

    public function failed() {
        return !empty($this->errors);
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function getFirstErrors() {
        $firstErrors = [];
        foreach ($this->errors as $field => $errors) {
            $firstErrors[$field] = $errors[0] ?? null;
        }
        return $firstErrors;
    }
    
    public function clear() {
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



// // Validate registration form using scenario
// $validator = Validator::check($_POST)
//     ->validateForm('registration');

// // Validate product form but only check name and price
// $validator = Validator::check($_POST)
//     ->validateForm('product', ['name', 'price']);

// Completely new scenario or overwrite existing
// Validator::addScenario('user', [
//     'name' => ['required', 'max:50'],
//     'email' => ['required', 'email']
// ]);

// // Later completely replace it
// Validator::addScenario('user', [
//     'username' => ['required', 'min:3'],
//     'password' => ['required', 'min:8']
// ], true); // Overwrite existing
// // Start with basic scenario
// Validator::addScenario('user', [
//     'name' => ['required']
// ]);

// // Later just add more rules without touching existing ones
// Validator::extendScenario('user', [
//     'email' => ['required', 'email'],
//     'age' => ['numeric', 'min:18']
// ]);

// Add a scenario
// Validator::addScenario('user_login', [
//     'email' => ['required', 'email'],
//     'password' => ['required', 'min:8']
// ]);

// // Later remove it
// Validator::removeScenario('user_login');