# Question Generation System

## Overview
The Question Generation System is a sophisticated component that leverages Large Language Models (LLM) to automatically generate questions based on course content, Course Outcomes (COs), and Bloom's Taxonomy levels.

## Files Structure

### 1. question_gen.php
Main file responsible for generating individual questions.

#### 1.1 Configuration Variables
```php
$db_config = [
    'host' => 'localhost',
    'dbname' => 'oat',
    'username' => 'root',
    'password' => ''
];
$model_url = "http://localhost:11434/api/generate";
```

#### 1.2 Key Functions and Operations

##### Database Connection
- Establishes secure MySQL connection
- Uses PDO for prepared statements
- Implements error handling and logging

##### API Endpoints
1. GET Endpoints:
   - `get_courses`: Retrieves available courses
   - `get_cos`: Gets Course Outcomes for a specific course
   - `get_units`: Fetches unit contents for a course

2. POST Endpoint:
   - Handles question generation requests
   - Processes course, CO, and unit information
   - Generates questions using LLM

##### LLM Integration
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

##### Error Handling
- Connection error management
- Model availability checks
- Response validation
- Error logging

### 2. qp_gen.php
Handles the generation of complete question papers.

#### 2.1 Configuration
```php
$db_config = [
    'host' => 'localhost',
    'dbname' => 'oat',
    'username' => 'root',
    'password' => ''
];
$model_url = "http://localhost:11434/api/generate";
```

#### 2.2 Key Components

##### Custom Mode Generation
- Processes unit text directly
- Handles mark distribution
- Manages knowledge level requirements

##### Question Paper Structure
```php
$questions = [
    'part_a' => [],
    'part_b' => [],
    'part_c' => []
];
```

##### Mark Distribution Logic
- Part A: Short questions (2 marks)
- Part B: Medium questions (5-8 marks)
- Part C: Long questions (10-15 marks)


## Question Generation Process

### 1. Prompt Engineering
```plaintext
Generate 1 unique and creative question for:
Course: {course_code} - {course_name}
Unit: {unit_number} - {unit_description}
CO: {co_number}
Level: {knowledge_level}
Marks: {marks}

Requirements:
- Worth {marks} marks
- Tests {knowledge_level} level
- Start with appropriate action verb
- Be specific to the unit content
- Be different from previous questions
```

### 2. Knowledge Level Mapping
```php
$verb_mapping = [
    'Remember' => ['List', 'Define', 'Identify'],
    'Understand' => ['Explain', 'Summarize', 'Describe'],
    'Apply' => ['Demonstrate', 'Illustrate'],
    'Analyze' => ['Compare', 'Differentiate', 'Analyze'],
    'Evaluate' => ['Assess', 'Critique', 'Justify'],
    'Create' => ['Design', 'Formulate', 'Build']
];
```

### 3. Response Processing
- Removes metadata and instructions
- Formats question text
- Validates question structure
- Ensures mark allocation alignment

## API Endpoints

### 1. GET /question_gen.php
#### Parameters
- `action`: String (get_courses | get_cos | get_units)
- `course_code`: String (required for get_cos and get_units)

#### Response
```json
{
    "success": true,
    "data": [
        {
            "id": "integer",
            "name": "string",
            "description": "string"
        }
    ]
}
```

### 2. POST /question_gen.php
#### Request Body
```json
{
    "co": "integer",
    "unit": "integer",
    "knowledge_level": "string",
    "marks": "integer"
}
```

#### Response
```json
{
    "success": true,
    "question": "string",
    "metadata": {
        "course": "string",
        "unit": "integer",
        "co": "integer",
        "level": "string",
        "marks": "integer"
    }
}
```

## Error Handling

### 1. Database Errors
```php
try {
    // Database operations
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $response['error'] = "Database operation failed";
}
```

### 2. LLM Service Errors
```php
if (!$connection_test) {
    throw new Exception('Ollama server not running');
}
```

### 3. Input Validation
- Course code format validation
- Mark range validation
- Knowledge level validation
- Unit number validation

## Performance Optimization

### 1. Caching
- Question history caching
- Course data caching
- Unit content caching

### 2. Database Optimization
- Indexed queries
- Prepared statements
- Connection pooling

### 3. Response Time Improvement
- Asynchronous processing
- Batch question generation
- Response streaming

## Security Measures

### 1. Input Sanitization
- SQL injection prevention
- XSS protection
- Special character handling

### 2. Access Control
- Session validation
- Role-based access
- Rate limiting

### 3. Error Handling
- Secure error messages
- Logging mechanisms
- Fallback procedures 