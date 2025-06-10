# Question Paper Generator

## Overview
The Question Paper Generator (qp_gen.php) is a specialized tool in the ObeAI™ system that automates the generation of question papers using AI. It integrates with the Ollama API to generate contextually relevant questions based on course content, knowledge levels, and mark allocations.

## File Location
`qp_gen.php`

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

### 1. POST Endpoint - Question Generation

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['custom_mode']) && $input['custom_mode']) {
        $text = trim($input['unit_text'] ?? '');
        $marks = $input['marks'] ?? [];
        $levels = $input['knowledge_levels'] ?? [];
        
        // Process and generate questions
    }
}
```

The POST endpoint accepts:
- `custom_mode`: Boolean flag to enable custom question generation
- `unit_text`: Content from which to generate questions
- `marks`: Array of mark allocations for each question
- `knowledge_levels`: Array of Bloom's taxonomy levels for each question

### 2. GET Endpoints - Course Data Retrieval

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
- `get_cos`: Lists course outcomes for a specific course
- `get_units`: Lists units with descriptions for a specific course

## Question Generation Process

### 1. Input Validation
```php
if (empty($text) || count($marks) === 0 || count($levels) === 0 || count($marks) !== count($levels)) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid question settings'
    ]);
    exit;
}
```

This validation ensures:
- Unit text is provided
- Mark allocations are specified
- Knowledge levels are specified
- Number of mark allocations matches number of knowledge levels

### 2. Ollama Service Validation
```php
$connection_test = @fsockopen("localhost", 11434, $errno, $errstr, 10);
if (!$connection_test) {
    throw new Exception('Ollama server is not running. Please start the Ollama service. Error: ' . $errstr);
}
fclose($connection_test);
```

Verifies that the Ollama service is running before attempting to generate questions.

### 3. Model Validation
```php
$model_to_use = 'mistral';
$model_check = curl_init("http://localhost:11434/api/tags");
curl_setopt($model_check, CURLOPT_RETURNTRANSFER, true);
$models_json = curl_exec($model_check);
curl_close($model_check);

if ($models_json) {
    $models = json_decode($models_json, true);
    if ($models && isset($models['models'])) {
        $found = false;
        foreach ($models['models'] as $model) {
            if ($model['name'] === 'mistral') {
                $found = true;
                break;
            }
        }
    }
}
```

Checks if the required AI model ('mistral') is available in the Ollama service.

### 4. Question Generation Loop
```php
$questions = [];

for ($i = 0; $i < count($marks); $i++) {
    $mark = intval($marks[$i]);
    $level = trim($levels[$i]);
    $previous_questions = implode("\n", $questions);

    $prompt = "You are an expert university-level question setter for Computer science...";
    
    // Generate question using Ollama API
    // Process response
    // Add formatted question to questions array
}
```

For each mark and knowledge level pair, the system:
1. Creates a specialized prompt for the AI
2. Sends the prompt to the Ollama API
3. Processes and formats the response
4. Adds the formatted question to the results

## Question Format

### 1. AI Prompt Structure
```php
$prompt = "You are an expert university-level question setter for Computer science.

Given the following content:
\"\"\"{$text}\"\"\"

Previously generated questions:
\"\"\"{$previous_questions}\"\"\"

Now, generate ONE new question based on a *different topic not already covered* above.

Generate ONE clear, short (max 2 lines) and specific question.

Instructions:
- Focus on a *different topic* from previous questions
- Target Bloom's Level: {$level}
- Marks: {$mark}
- Return only a single concise question — no extra text
- Use appropriate verbs: 
- if {$level} = Remember: then use (List, Define, Identify)
- if {$level} = Understand: then use (Explain, Summarize, Describe)
- if {$level} = Apply: then use (Demonstrate, Illustrate)
- if {$level} = Analyze: then use (Compare, Differentiate, Analyze)
- if {$level} = Evaluate: then use (Assess, Critique, Justify)
- if {$level} = Create: then use (Design, Formulate, Build)

Constraints:
- Do NOT return answers or multi-sentence explanations
- No repetition of given words
- Limit the question to *a maximum of 20 words*
- Be specific, technical, and exam-ready

Output only the question text.";
```

The prompt includes:
- Course content
- Previously generated questions to avoid duplication
- Target knowledge level
- Mark allocation
- Specific verb guidance based on Bloom's taxonomy
- Constraints for question length and format

### 2. AI Model Parameters
```php
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => $model_to_use,
    'prompt' => $prompt,
    'stream' => false,
    'options' => [
        'temperature' => 0.7,
        'top_p' => 0.95,
        'num_predict' => 150,
        'seed' => rand(1, 9999)
    ]
]));
```

Model configuration includes:
- `temperature`: Controls randomness (0.7 = balanced creativity)
- `top_p`: Nucleus sampling parameter
- `num_predict`: Maximum tokens to generate
- `seed`: Random seed for reproducibility

### 3. Response Processing
```php
$full_response = '';
$lines = explode("\n", $result);
foreach ($lines as $line) {
    if (empty(trim($line))) continue;
    $json_response = json_decode($line, true);
    if ($json_response && isset($json_response['response'])) {
        $full_response .= $json_response['response'];
    }
}

// Apply word limit enforcement
$cleaned = trim($full_response);
$word_limit = 20;
$words = explode(' ', $cleaned);
if (count($words) > $word_limit) {
    $cleaned = implode(' ', array_slice($words, 0, $word_limit)) .'?';
}

// Append to final output
$questions[] = "[{$mark}M | {$level}] " . $cleaned;
```

The response is processed by:
1. Collecting text from the JSON response
2. Cleaning whitespace
3. Enforcing a 20-word limit
4. Formatting with mark allocation and knowledge level
5. Adding to the questions array

## Error Handling

### 1. API Response Structure
```php
$response = ['success' => false, 'data' => null, 'error' => null];

// On success
$response['data'] = $stmt->fetchAll();
$response['success'] = true;

// On failure
$response['error'] = 'Course code not provided';
```

All API responses follow a consistent structure with:
- `success`: Boolean indicating if the operation was successful
- `data`: Response data (if successful)
- `error`: Error message (if unsuccessful)

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
$stmt = $pdo->prepare('SELECT id, co_number FROM course_outcome WHERE course_id = ? ORDER BY co_number');
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
    WHERE course_id = ? ORDER BY unit_number
');
$stmt->execute([$_GET['course_code']]);
$response['data'] = $stmt->fetchAll();
```

Returns units for a specific course with:
- Unit number
- Full description
- Formatted title with truncated description

## Bloom's Taxonomy Integration

The system generates questions across the six levels of Bloom's Taxonomy:

1. **Remember**
   - Verbs: List, Define, Identify
   - Question type: Recall of facts and basic concepts

2. **Understand**
   - Verbs: Explain, Summarize, Describe
   - Question type: Understanding of ideas and concepts

3. **Apply**
   - Verbs: Demonstrate, Illustrate
   - Question type: Application of knowledge to new situations

4. **Analyze**
   - Verbs: Compare, Differentiate, Analyze
   - Question type: Breaking information into parts to explore relationships

5. **Evaluate**
   - Verbs: Assess, Critique, Justify
   - Question type: Justifying a stand or decision

6. **Create**
   - Verbs: Design, Formulate, Build
   - Question type: Producing new or original work

## Example Usage

### 1. Generate Questions
```php
// POST request
$request_body = json_encode([
    'custom_mode' => true,
    'unit_text' => 'The Bloom taxonomy is a hierarchical framework used to classify educational learning objectives...',
    'marks' => [2, 5],
    'knowledge_levels' => ['Remember', 'Analyze']
]);

// Response
{
    "success": true,
    "questions": [
        "[2M | Remember] Define the Bloom's taxonomy and its primary purpose.",
        "[5M | Analyze] Compare the cognitive processes in Bloom's lower and higher order thinking levels."
    ]
}
```

### 2. Retrieve Course Data
```php
// GET request
/qp_gen.php?action=get_courses

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

## Best Practices for Maintenance

1. **Model Updates**: When the Ollama service is updated, test new models and update the model name if necessary

2. **Prompt Engineering**: Carefully modify the question prompt to improve quality or adjust question style

3. **Word Limit**: Adjust the word limit (currently 20) based on question complexity requirements

4. **Error Handling**: Monitor error logs to identify and address issues with the service

5. **Question Formatting**: Maintain consistent formatting for generated questions ([Mark]M | [Level]) for parsing by the UI 