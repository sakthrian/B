# Question Generator

## Overview
The Question Generator (question_gen.php) is a specialized component of the ObeAI™ system that generates individual questions based on course units, course outcomes (COs), and knowledge levels. It uses the Ollama API with mistral or tinyllama models to create contextually relevant and pedagogically sound questions for educational assessment.

## File Location
`question_gen.php`

## Dependencies
```php
<?php
// Core PHP dependencies
$db_config = [
    'host' => 'localhost',
    'dbname' => 'oat',
    'username' => 'root',
    'password' => ''
];

// External API endpoint
$model_url = "http://localhost:11434/api/generate";

// Response structure
$response = ['success' => false, 'data' => null, 'error' => null];
```

## Database Connection
```php
$dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset=utf8mb4";
$pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
```

The system establishes a secure connection to the MySQL database with specific error handling settings.

## API Endpoints

### 1. GET Endpoints - Course Data Retrieval

```php
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_courses':
            // Return course list
            break;

        case 'get_cos':
            // Return course outcomes
            break;

        case 'get_units':
            // Return unit list and details
            break;
    }
}
```

The GET endpoint supports:
- `get_courses`: Lists all courses with code and name
- `get_cos`: Lists course outcomes for a specific course (requires course_code parameter)
- `get_units`: Lists units with descriptions for a specific course (requires course_code parameter)

### 2. POST Endpoint - Question Generation

```php
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data) {
        // Fetch course, unit and CO details
        // Generate question using Ollama API
    }
}
```

The POST endpoint accepts:
- `co`: Course Outcome ID
- `unit`: Unit ID
- `knowledge_level`: Bloom's taxonomy level for the question
- `marks`: Mark allocation for the question

## Question Generation Process

### 1. Course and Unit Data Retrieval
```php
$stmt = $pdo->prepare('
    SELECT 
        c.name as course_name,
        c.code as course_code,
        u.unit_description,
        u.unit_number,
        co.co_number
    FROM unit_contents u
    JOIN course c ON c.code = u.course_id
    JOIN course_outcome co ON co.course_id = c.code AND co.id = ?
    WHERE u.id = ?
');
$stmt->execute([$data['co'], $data['unit']]);
$details = $stmt->fetch();
```

This query retrieves the necessary context for question generation including:
- Course name and code
- Unit description and number
- Course Outcome (CO) number

### 2. Ollama Service Validation
```php
$connection_test = @fsockopen("localhost", 11434, $errno, $errstr, 10);
if (!$connection_test) {
    throw new Exception('Ollama server is not running. Please start the Ollama service first. Error: ' . $errstr);
}
fclose($connection_test);
```

Verifies that the Ollama service is running before attempting to generate questions.

### 3. Model Selection and Validation
```php
$model_check = curl_init("http://localhost:11434/api/tags");
curl_setopt($model_check, CURLOPT_RETURNTRANSFER, true);
$models_json = curl_exec($model_check);
curl_close($model_check);

$model_to_use = 'mistral';
$model_found = false;

if ($models_json) {
    $models = json_decode($models_json, true);
    if ($models && isset($models['models'])) {
        foreach ($models['models'] as $model) {
            if (isset($model['name']) && $model['name'] === 'tinyllama') {
                $model_found = true;
                break;
            }
        }
    }
}

if (!$model_found) {
    error_log("Mistral model not found, falling back to tinyllama");
    $model_to_use = 'tinyllama';
}
```

The system tries to use the 'mistral' model first, but will fall back to 'tinyllama' if necessary.

## Question Format

### 1. AI Prompt Structure
```php
$prompt = "You are a university exam question creator. Current timestamp: {$timestamp}

Generate 1 unique and creative question for:
Course: {$details['course_code']} - {$details['course_name']}
Unit: {$details['unit_number']} - {$details['unit_description']}
CO: {$details['co_number']}
Level: {$data['knowledge_level']}
Marks: {$data['marks']}

Requirements:
- Worth {$data['marks']} marks
- Tests {$data['knowledge_level']} level
- Start with 'Explain', 'Analyze', 'Discuss', 'Compare', 'Evaluate', or 'Describe'
- Be specific to the unit content
- Be different from previous questions
- Focus on a different aspect of the unit than usual
- ONLY provide the question itself
- NO instructions to students
- NO word count requirements
- NO additional context

Your response:";
```

The prompt includes:
- Course details (code and name)
- Unit information (number and description)
- Course Outcome number
- Knowledge level
- Mark allocation
- Specific verb requirements for question formulation
- Constraints to ensure clean question output

### 2. AI Model Parameters

#### Mistral Model Configuration
```php
$request_data = [
    'model' => $model_to_use,
    'prompt' => $prompt,
    'stream' => false,
    'options' => [
        'temperature' => 1.0, 
        'top_p' => 0.95,
        'num_predict' => 300, 
        'seed' => rand(1, 10000) 
    ]
];
```

#### TinyLlama Fallback Configuration
```php
$request_data = [
    'model' => $model_to_use,
    'prompt' => $prompt,
    'stream' => false,
    'options' => [
        'temperature' => 0.9, 
        'top_p' => 0.95, 
        'num_predict' => 500,
        'seed' => rand(1, 10000) 
    ]
];
```

Model parameters are adjusted based on the selected model:
- `temperature`: Higher for mistral (1.0) for more creative responses
- `num_predict`: Higher for tinyllama (500) as it may require more tokens
- Random seed for reproducibility but with variation between requests

### 3. Response Processing
```php
$lines = explode("\n", $result);
$full_response = '';

foreach ($lines as $line) {
    if (empty(trim($line))) continue;
    
    $json_response = json_decode($line, true);
    if ($json_response && isset($json_response['response'])) {
        $full_response .= $json_response['response'];
    } elseif ($json_response && isset($json_response['error'])) {
        throw new Exception('Ollama API Error: ' . $json_response['error']);
    }
}

// Clean and format the response
$clean_response = trim($full_response);
$clean_response = preg_replace('/^(Questions?|Course|Unit|CO|Level|Marks).*?\n/im', '', $clean_response);
$clean_response = preg_replace('/\b(remember|note:|please note|hint:|tip:).*$/im', '', $clean_response);
$clean_response = preg_replace('/\b(word count|marks allocation|learning outcomes).*$/im', '', $clean_response);
$clean_response = preg_replace('/\b\d+-\d+ words\b.*$/i', '', $clean_response);
$clean_response = preg_replace('/\bin your answer,.*$/im', '', $clean_response);
$clean_response = preg_replace('/\byour (answer|response) should.*$/im', '', $clean_response);
$clean_response = preg_replace('/^Question:\s*/i', '', $clean_response);
```

The response is processed by:
1. Collecting text from the JSON response
2. Removing metadata headers (Questions, Course, Unit, etc.)
3. Removing instructional text (hints, notes, word counts)
4. Cleaning up formatting
5. Creating a clean question without extraneous content

## Response Formatting

### 1. Formatted Question Output
```php
$formatted_response = "Questions for {$details['course_code']} - {$details['course_name']}\n" .
                   "Unit {$details['unit_number']}\n" .
                   "CO{$details['co_number']}\n" .
                   "Level: {$data['knowledge_level']}\n" .
                   "({$data['marks']} marks)\n\n" . 
                   $clean_response;
```

The formatted response includes:
- Course information (code and name)
- Unit number
- CO number
- Knowledge level
- Mark allocation
- The clean question text

### 2. API Response Structure
```php
$response['success'] = true;
$response['data'] = [
    'question' => $formatted_response,
    'raw_question' => $clean_response,
    'course' => "{$details['course_code']} - {$details['course_name']}",
    'unit_number' => $details['unit_number'],
    'unit_description' => $details['unit_description'],
    'co' => $details['co_number'],
    'marks' => $data['marks'],
    'knowledge_level' => $data['knowledge_level']
];
```

The API response includes both:
- The formatted question with all metadata
- The raw question text without metadata
- All contextual information for reference

## Error Handling

### 1. Connection Error Handling
```php
if (curl_errno($ch)) {
    $curl_error = curl_error($ch);
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    
    error_log("Curl Error: " . $curl_error);
    error_log("Verbose Log: " . $verboseLog);
    error_log("HTTP Code: " . $http_code);
    
    $error_message = "Connection Error: ";
    if (strpos($curl_error, "Failed to connect") !== false) {
        $error_message .= "Ollama server is not running. Please ensure Ollama is started and running on port 11434.";
    } elseif (strpos($curl_error, "Operation timed out") !== false) {
        $error_message .= "Request timed out. TinyLlama should be faster than larger models. Please check if your GPU has enough memory or try restarting Ollama.";
    } else {
        $error_message .= $curl_error;
    }
    
    throw new Exception($error_message);
}
```

Sophisticated error handling includes:
- Detailed logging of curl errors
- User-friendly error messages based on error type
- Specific suggestions for resolution
- Verbose logging for debugging

### 2. Exception Handling
```php
try {
    // Database and API operations
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    $response['debug'] = [
        'trace' => $e->getTraceAsString(),
        'line' => $e->getLine(),
        'file' => $e->getFile(),
        'last_error' => error_get_last()
    ];
    error_log("Error in question_gen.php: " . $e->getMessage());
}
```

Comprehensive exception handling includes:
- Capturing error messages
- Logging errors to server log
- Including debug information in development environments
- Structured error response to clients

## Database Queries

### 1. Get Courses
```php
$stmt = $pdo->query('SELECT code, name FROM course ORDER BY code');
$response['data'] = $stmt->fetchAll();
```

Returns a sorted list of all courses.

### 2. Get Course Outcomes
```php
$stmt = $pdo->prepare('
    SELECT id, co_number 
    FROM course_outcome 
    WHERE course_id = ?
    ORDER BY co_number
');
$stmt->execute([$_GET['course_code']]);
$response['data'] = $stmt->fetchAll();
```

Returns course outcomes for a specific course, sorted by number.

### 3. Get Units
```php
$stmt = $pdo->prepare('
    SELECT id, unit_number, unit_description,
    CONCAT("Unit ", unit_number, ": ", LEFT(unit_description, 50), "...") as unit_title
    FROM unit_contents 
    WHERE course_id = ?
    ORDER BY unit_number
');
$stmt->execute([$_GET['course_code']]);
$units = $stmt->fetchAll();
```

Returns units for a specific course with:
- Unit number
- Full description
- Formatted title with truncated description

## Knowledge Level Integration

The system supports various knowledge levels from Bloom's Taxonomy, with verbs including:
- **Explain**: For understanding and clarification
- **Analyze**: For breaking down information and exploring relationships
- **Discuss**: For presenting and examining various aspects
- **Compare**: For examining similarities and differences
- **Evaluate**: For making judgments based on criteria
- **Describe**: For detailed explanation of features or characteristics

## Example Usage

### 1. Generate a Question
```php
// POST request
$request_body = json_encode([
    'co': 3,
    'unit': 2,
    'knowledge_level': 'Analyze',
    'marks': 10
]);

// Response
{
    "success": true,
    "data": {
        "question": "Questions for CS301 - Database Management Systems\nUnit 2\nCO3\nLevel: Analyze\n(10 marks)\n\nAnalyze the implications of denormalization on query performance and data integrity in large database systems.",
        "raw_question": "Analyze the implications of denormalization on query performance and data integrity in large database systems.",
        "course": "CS301 - Database Management Systems",
        "unit_number": 2,
        "unit_description": "Database Normalization and Denormalization",
        "co": 3,
        "marks": 10,
        "knowledge_level": "Analyze"
    }
}
```

### 2. Retrieve Course Data
```php
// GET request
/question_gen.php?action=get_courses

// Response
{
    "success": true,
    "data": [
        {"code": "CS301", "name": "Database Management Systems"},
        {"code": "CS302", "name": "Computer Networks"}
    ],
    "error": null
}
```

### 3. Retrieve Course Outcomes
```php
// GET request
/question_gen.php?action=get_cos&course_code=CS301

// Response
{
    "success": true,
    "data": [
        {"id": 1, "co_number": 1},
        {"id": 2, "co_number": 2},
        {"id": 3, "co_number": 3}
    ],
    "error": null
}
```

### 4. Retrieve Units
```php
// GET request
/question_gen.php?action=get_units&course_code=CS301

// Response
{
    "success": true,
    "data": [
        {
            "id": 1,
            "unit_number": 1,
            "unit_description": "Introduction to Database Concepts",
            "unit_title": "Unit 1: Introduction to Database Concepts..."
        },
        {
            "id": 2,
            "unit_number": 2,
            "unit_description": "Database Normalization and Denormalization",
            "unit_title": "Unit 2: Database Normalization and Denormaliza..."
        }
    ],
    "error": null
}
```

## Best Practices for Maintenance

1. **Model Updates**: When the Ollama service is updated, test both mistral and tinyllama models for performance

2. **Prompt Engineering**: Carefully modify the question prompt to adjust style, difficulty or question format

3. **Response Cleaning**: Update regex patterns if new types of unwanted content appear in responses

4. **Error Handling**: Monitor error logs to identify and address issues with Ollama connectivity

5. **Performance Monitoring**: For slow response times, consider adjusting model parameters or using a smaller model 