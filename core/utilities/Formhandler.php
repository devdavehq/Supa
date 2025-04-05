<?php

class Validator {
    private $data = [];
    private $errors = [];
    private static $scenarios = [];
    private static $dbConnection = null;
    
    public function __construct($formData) {
        $this->data = $formData;
        self::initScenarios();
    }
    
    public static function setDbConnection($connection) {
        self::$dbConnection = $connection;
    }
    
    private static function initScenarios() {
        if (empty(self::$scenarios)) {
            self::$scenarios = [
                // User Management
                'registration' => [
                    'username' => [
                        'required' => 'Please choose a username',
                        'min:3' => 'Username must be at least 3 characters',
                        'max:20' => 'Username cannot exceed 20 characters',
                        'alphanumeric' => 'Username can only contain letters and numbers',
                        'unique:users,username' => 'Username already taken'
                    ],
                    'email' => [
                        'required' => 'Email is required',
                        'email' => 'Please enter a valid email address',
                        'max:100' => 'Email cannot exceed 100 characters',
                        'unique:users,email' => 'Email already registered'
                    ],
                    'password' => [
                        'required' => 'Password is required',
                        'min:8' => 'Password must be at least 8 characters',
                        'regex:/[A-Z]/' => 'Password must contain at least one uppercase letter',
                        'regex:/[0-9]/' => 'Password must contain at least one number',
                        'regex:/[^a-zA-Z0-9]/' => 'Password must contain at least one special character',
                        'strong_password' => 'Password is not strong enough'
                    ],
                    'password_confirmation' => [
                        'required' => 'Please confirm your password',
                        'same:password' => 'Passwords do not match'
                    ],
                    'agree_terms' => [
                        'required' => 'You must agree to the terms and conditions',
                        'accepted' => 'You must accept the terms'
                    ]
                ],
                
                'login' => [
                    'login' => [
                        'required' => 'Username or email is required',
                        'custom_login' => 'Please enter a valid username or email'
                    ],
                    'password' => [
                        'required' => 'Password is required'
                    ],
                    'remember_me' => [
                        'boolean' => 'Invalid remember me selection'
                    ]
                ],
                
                'password_reset' => [
                    'email' => [
                        'required' => 'Email is required',
                        'email' => 'Please enter a valid email address',
                        'exists:users,email' => 'No account found with this email'
                    ],
                    'token' => [
                        'required' => 'Token is required',
                        'size:64' => 'Invalid token format'
                    ],
                    'new_password' => [
                        'required' => 'New password is required',
                        'min:8' => 'Password must be at least 8 characters',
                        'confirmed' => 'Password confirmation does not match'
                    ]
                ],
                
                // E-Commerce
                'checkout' => [
                    'shipping_address' => [
                        'required' => 'Shipping address is required',
                        'array' => 'Invalid address format'
                    ],
                    'billing_address' => [
                        'required' => 'Billing address is required',
                        'array' => 'Invalid address format'
                    ],
                    'payment_method' => [
                        'required' => 'Payment method is required',
                        'in:credit_card,paypal,apple_pay' => 'Invalid payment method'
                    ],
                    'coupon_code' => [
                        'nullable' => '',
                        'exists:coupons,code' => 'Invalid coupon code'
                    ]
                ],
                
                'address' => [
                    'street' => [
                        'required' => 'Street address is required',
                        'max:100' => 'Street address too long'
                    ],
                    'city' => [
                        'required' => 'City is required',
                        'alpha_spaces' => 'City can only contain letters and spaces',
                        'max:50' => 'City name too long'
                    ],
                    'state' => [
                        'required' => 'State is required',
                        'alpha_spaces' => 'Invalid state format'
                    ],
                    'zip_code' => [
                        'required' => 'ZIP code is required',
                        'regex:/^\d{5}(-\d{4})?$/' => 'Invalid ZIP code format'
                    ],
                    'country' => [
                        'required' => 'Country is required',
                        'in:US,CA,UK,AU,DE,FR,JP' => 'We currently only ship to these countries'
                    ],
                    'phone' => [
                        'required' => 'Phone number is required',
                        'phone' => 'Invalid phone number format'
                    ]
                ],
                
                'payment' => [
                    'card_number' => [
                        'required' => 'Card number is required',
                        'credit_card' => 'Invalid card number'
                    ],
                    'card_holder' => [
                        'required' => 'Card holder name is required',
                        'alpha_spaces' => 'Invalid name format',
                        'max:100' => 'Name too long'
                    ],
                    'expiry' => [
                        'required' => 'Expiry date is required',
                        'date_format:m/Y' => 'Use MM/YYYY format',
                        'after:+1 month' => 'Card must not be expired'
                    ],
                    'cvv' => [
                        'required' => 'CVV is required',
                        'digits_between:3,4' => 'CVV must be 3 or 4 digits'
                    ]
                ],
                
                // Content Management
                'article' => [
                    'title' => [
                        'required' => 'Title is required',
                        'min:5' => 'Title must be at least 5 characters',
                        'max:100' => 'Title cannot exceed 100 characters',
                        'unique:articles,title' => 'Title already exists'
                    ],
                    'content' => [
                        'required' => 'Content is required',
                        'min:50' => 'Content must be at least 50 characters',
                        'max:10000' => 'Content too long',
                        'profanity_filter' => 'Content contains inappropriate language'
                    ],
                    'category_id' => [
                        'required' => 'Category is required',
                        'exists:categories,id' => 'Invalid category'
                    ],
                    'tags' => [
                        'array' => 'Invalid tags format',
                        'max:5' => 'Maximum 5 tags allowed'
                    ],
                    'tags.*' => [
                        'exists:tags,id' => 'Invalid tag selected'
                    ],
                    'published_at' => [
                        'nullable' => '',
                        'date' => 'Invalid date format',
                        'after_or_equal:today' => 'Publish date cannot be in the past'
                    ]
                ],
                
                // File Uploads
                'file_upload' => [
                    'file' => [
                        'required' => 'File is required',
                        'file' => 'Invalid file upload',
                        'mimes:jpg,png,pdf,docx' => 'Allowed file types: JPG, PNG, PDF, DOCX',
                        'max:10240' => 'File size cannot exceed 10MB'
                    ],
                    'description' => [
                        'nullable' => '',
                        'max:255' => 'Description too long'
                    ]
                ],
                
                // API Specific
                'api_user_create' => [
                    'name' => [
                        'required' => 'Name is required',
                        'max:100' => 'Name too long'
                    ],
                    'email' => [
                        'required' => 'Email is required',
                        'email' => 'Invalid email format',
                        'unique:users,email' => 'Email already registered'
                    ],
                    'password' => [
                        'required' => 'Password is required',
                        'min:8' => 'Password must be at least 8 characters'
                    ],
                    'roles' => [
                        'required' => 'Roles are required',
                        'array' => 'Invalid roles format',
                        'in:user,editor,admin' => 'Invalid role specified'
                    ]
                ],
                
                // System Settings
                'system_settings' => [
                    'site_name' => [
                        'required' => 'Site name is required',
                        'max:50' => 'Site name too long'
                    ],
                    'timezone' => [
                        'required' => 'Timezone is required',
                        'timezone' => 'Invalid timezone'
                    ],
                    'maintenance_mode' => [
                        'boolean' => 'Invalid maintenance mode setting'
                    ],
                    'allowed_file_types' => [
                        'required' => 'Allowed file types are required',
                        'array' => 'Invalid file types format'
                    ]
                ],
                
                // Social Media
                'social_post' => [
                    'content' => [
                        'required' => 'Post content is required',
                        'min:1' => 'Post cannot be empty',
                        'max:1000' => 'Post cannot exceed 1000 characters',
                        'profanity_filter' => 'Content contains inappropriate language'
                    ],
                    'media' => [
                        'array' => 'Invalid media format',
                        'max:10' => 'Maximum 10 media items allowed'
                    ],
                    'media.*' => [
                        'file' => 'Invalid file upload',
                        'mimes:jpg,png,gif,mp4' => 'Allowed types: JPG, PNG, GIF, MP4',
                        'max:5120' => 'File size cannot exceed 5MB'
                    ],
                    'scheduled_at' => [
                        'nullable' => '',
                        'date' => 'Invalid date format',
                        'after:now' => 'Scheduled time must be in the future'
                    ]
                ]
            ];
        }

    }

    public function validate($field, $rules) {
        $value = $this->getValue($field);
        
        foreach ($this->parseRules($rules) as $rule => $errorMsg) {
            if ($this->shouldSkipValidation($rule, $value)) {
                continue;
            }

            $params = $this->parseRuleParameters($rule);
            $ruleName = $this->getRuleName($rule);
            
            if (!$this->validateRule($value, $ruleName, $params, $field)) {
                $this->addError($field, $errorMsg, $ruleName, $params);
            }
        }
        
        
        return $this;
    }

    protected function validateRule($value, $rule, $params, $field) {
        switch ($rule) {
            // Basic Validations
            case 'required': return !empty(trim($value));
            case 'email': return filter_var($value, FILTER_VALIDATE_EMAIL);
            case 'min': return strlen($value) >= (int)$params[0];
            case 'max': return strlen($value) <= (int)$params[0];
            case 'numeric': return is_numeric($value);
            case 'integer': return filter_var($value, FILTER_VALIDATE_INT);
            case 'boolean': return in_array($value, [true, false, 1, 0, '1', '0'], true);
            
            // String Validations
            case 'alpha': return ctype_alpha($value);
            case 'alpha_spaces': return preg_match('/^[a-zA-Z\s]+$/', $value);
            case 'alphanumeric': return ctype_alnum($value);
            case 'regex': return preg_match($params[0], $value);
            
            // Date Validations
            case 'date': return strtotime($value) !== false;
            case 'date_format':
                $date = \DateTime::createFromFormat($params[0], $value);
                return $date && $date->format($params[0]) === $value;
            case 'before': return strtotime($value) < strtotime($params[0]);
            case 'after': return strtotime($value) > strtotime($params[0]);
            case 'after_or_equal': return strtotime($value) >= strtotime($params[0]);
            
            // Comparison Validations
            case 'same': return $value === $this->getValue($params[0]);
            case 'different': return $value !== $this->getValue($params[0]);
            case 'in': return in_array($value, $params);
            case 'not_in': return !in_array($value, $params);
            case 'confirmed': 
                return $value === $this->getValue($field.'_confirmation');
            
            // File Validations
            case 'file': return is_uploaded_file($value['tmp_name'] ?? '');
            case 'mimes':
                $ext = strtolower(pathinfo($value['name'], PATHINFO_EXTENSION));
                return in_array($ext, $params);
            case 'max_size':
                return ($value['size'] ?? 0) <= ($params[0] * 1024);
            
            // Database Validations
            case 'unique':
                return !$this->existsInDatabase($params[0], $params[1] ?? $field, $value);
            case 'exists':
                return $this->existsInDatabase($params[0], $params[1] ?? 'id', $value);
            
            // Custom Validations
            case 'phone':
                return preg_match('/^\+?[\d\s\-\(\)]{10,}$/', $value);
            case 'credit_card':
                return $this->validateCreditCard($value);
            case 'profanity_filter':
                return !$this->containsProfanity($value);
            case 'strong_password':
                return $this->isStrongPassword($value);
            case 'digits_between':
                return ctype_digit($value) && 
                       strlen($value) >= $params[0] && 
                       strlen($value) <= $params[1];
            case 'size':
                return strlen($value) == $params[0];
            case 'custom_login':
                return filter_var($value, FILTER_VALIDATE_EMAIL) || 
                      (strlen($value) >= 3 && ctype_alnum($value));
                
            default: return true;
        }
    }
    
    protected function getValue($field) {
        $value = $this->data[$field] ?? null;
        
        // Handle array notation (e.g., 'user[name]')
        if (strpos($field, '[') !== false && preg_match('/^([^\[]+)\[(.+)\]$/', $field, $matches)) {
            return $this->data[$matches[1]][$matches[2]] ?? null;
        }
        
        // Handle nested arrays (e.g., 'tags.0')
        if (strpos($field, '.') !== false) {
            $keys = explode('.', $field);
            $result = $this->data;
            
            foreach ($keys as $key) {
                if (!isset($result[$key])) return null;
                $result = $result[$key];
            }
            
            return $result;
        }
        
        return $value;
    }
    
    protected function parseRules($rules) {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }
        
        $parsed = [];
        foreach ($rules as $key => $rule) {
            if (is_numeric($key)) {
                $parsed[$rule] = null;
            } else {
                $parsed[$key] = $rule;
            }
        }
        return $parsed;
    }
    
    protected function parseRuleParameters($rule) {
        if (strpos($rule, ':') === false) {
            return [];
        }
        
        list($rule, $params) = explode(':', $rule, 2);
        return explode(',', $params);
    }
    
    protected function getRuleName($rule) {
        return strpos($rule, ':') === false ? $rule : explode(':', $rule)[0];
    }
    
    protected function shouldSkipValidation($rule, $value) {
        $ruleName = $this->getRuleName($rule);
        return $ruleName === 'nullable' && empty(trim($value));
    }
    
    protected function addError($field, $message, $rule, $params) {
        if ($message === null) {
            $message = $this->getDefaultErrorMessage($field, $rule, $params);
        }
        
        $this->errors[$field][] = $message;
    }
    
    protected function getDefaultErrorMessage($field, $rule, $params) {
        $messages = [
            'required' => "The $field field is required",
            'email' => "The $field must be a valid email address",
            'min' => "The $field must be at least {$params[0]} characters",
            'max' => "The $field may not be greater than {$params[0]} characters",
            'numeric' => "The $field must be a number",
            'integer' => "The $field must be an integer",
            'boolean' => "The $field field must be true or false",
            'alpha' => "The $field may only contain letters",
            'alpha_spaces' => "The $field may only contain letters and spaces",
            'alphanumeric' => "The $field may only contain letters and numbers",
            'regex' => "The $field format is invalid",
            'date' => "The $field is not a valid date",
            'date_format' => "The $field does not match the format {$params[0]}",
            'before' => "The $field must be a date before {$params[0]}",
            'after' => "The $field must be a date after {$params[0]}",
            'same' => "The $field and {$params[0]} must match",
            'different' => "The $field and {$params[0]} must be different",
            'in' => "The selected $field is invalid",
            'not_in' => "The selected $field is invalid",
            'confirmed' => "The $field confirmation does not match",
            'file' => "The $field must be a file",
            'mimes' => "The $field must be a file of type: ".implode(',', $params),
            'max_size' => "The $field may not be greater than {$params[0]} kilobytes",
            'unique' => "The $field has already been taken",
            'exists' => "The selected $field is invalid",
            'phone' => "The $field must be a valid phone number",
            'credit_card' => "The $field must be a valid credit card number",
            'profanity_filter' => "The $field contains inappropriate content",
            'strong_password' => "The $field must be stronger",
            'digits_between' => "The $field must be between {$params[0]} and {$params[1]} digits",
            'size' => "The $field must be {$params[0]} characters",
            'custom_login' => "The $field must be a valid username or email"
        ];
        
        return $messages[$rule] ?? "The $field is invalid";
    }
    
    protected function existsInDatabase($table, $column, $value) {
        if (!self::$dbConnection) {
            throw new \RuntimeException('Database connection not set');
        }
        
        $stmt = self::$dbConnection->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?"
        );
        $stmt->execute([$value]);
        return $stmt->fetchColumn() > 0;
    }
    
    protected function validateCreditCard($number) {
        $number = preg_replace('/\D/', '', $number);
        $length = strlen($number);
        
        if ($length < 13 || $length > 19) return false;
        
        $sum = 0;
        $alt = false;
        
        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = $number[$i];
            if ($alt) {
                $digit *= 2;
                if ($digit > 9) $digit -= 9;
            }
            $sum += $digit;
            $alt = !$alt;
        }
        
        return ($sum % 10) == 0;
    }
    
    protected function containsProfanity($text) {
        $profanities = ['badword1', 'badword2']; // Load from config/db
        foreach ($profanities as $word) {
            if (stripos($text, $word) !== false) return true;
        }
        return false;
    }
    
    protected function isStrongPassword($password) {
        return strlen($password) >= 8 &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[0-9]/', $password) &&
               preg_match('/[^a-zA-Z0-9]/', $password);
    }
    
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
    
    public function validateForm($scenario, $onlyFields = null) {
        if (!isset(self::$scenarios[$scenario])) {
            throw new \InvalidArgumentException("Scenario '{$scenario}' not found");
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
    
    public function fails() {
        return !empty($this->errors);
    }
    
    public function failed() {
        return $this->fails();
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
    
    public function addRule($field, $callback, $errorMsg) {
        $value = $this->getValue($field);
        if (!$callback($value)) {
            $this->addError($field, $errorMsg, 'custom', []);
        }
        return $this;
    }
    
    public static function check($formData) {
        return new self($formData);
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