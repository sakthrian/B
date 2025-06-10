# Chatbot Engine Documentation

## Overview
The chatbot engine is built using Rasa 3.6.2 and serves as the core natural language understanding and dialogue management system for ObeAI™. It handles user queries, intent classification, entity extraction, and response generation.

## Architecture Components

### 1. NLU Pipeline (config.yml)
```yaml
pipeline:
  - name: WhitespaceTokenizer
  - name: RegexFeaturizer
  - name: LexicalSyntacticFeaturizer
  - name: CountVectorsFeaturizer
  - name: CountVectorsFeaturizer
    analyzer: char_wb
    min_ngram: 1
    max_ngram: 4
  - name: DIETClassifier
    epochs: 100
    constrain_similarities: true
  - name: EntitySynonymMapper
  - name: ResponseSelector
    epochs: 100
  - name: FallbackClassifier
    threshold: 0.6
```

### 2. Core Policies (config.yml)
```yaml
policies:
  - name: MemoizationPolicy
    max_history: 5
  - name: RulePolicy
    core_fallback_threshold: 0.3
  - name: TEDPolicy
    max_history: 5
    epochs: 100
```

## Intent Configuration

### 1. Student-Related Intents
```yaml
intents:
  - student_info
  - student_by_name
  - students_by_year
  - students_by_semester
  - students_by_section
  - students_by_batch
  - students_by_type
```

### 2. Faculty-Related Intents
```yaml
intents:
  - faculty_subject
  - faculty_for_subject
```

### 3. Course-Related Intents
```yaml
intents:
  - course_by_code
  - course_by_name
  - courses_by_semester
  - courses_by_credits
  - course_count
  - course_prerequisites
```

### 4. CO-Related Intents
```yaml
intents:
  - course_co_attainment
  - compare_co_attainment
  - student_co_report
```

## Entity Configuration

### 1. Student Entities
```yaml
entities:
  - student_name
  - register_number
  - year
  - semester
  - section
  - batch
  - type
```

### 2. Faculty Entities
```yaml
entities:
  - faculty_name
  - subject_name
```

### 3. Course Entities
```yaml
entities:
  - course_code
  - course_name
  - credits
  - course_type
```

## Custom Actions

### 1. Query Router (actions/handlers/query_router.py)
```python
class ActionQueryRouter(Action):
    def name(self) -> Text:
        return "action_query_router"

    def run(self, dispatcher, tracker, domain):
        # Message analysis
        # Intent classification
        # Entity extraction
        # Handler routing
```

### 2. Student Handler (actions/handlers/student_handler.py)
```python
class ActionStudentQuery(Action):
    def name(self) -> Text:
        return "action_student_query"

    def run(self, dispatcher, tracker, domain):
        # Student information retrieval
        # Response formatting
        # Error handling
```

### 3. Faculty Handler (actions/handlers/faculty_handler.py)
```python
class ActionFacultyQuery(Action):
    def name(self) -> Text:
        return "action_faculty_query"

    def run(self, dispatcher, tracker, domain):
        # Faculty information retrieval
        # Subject mapping
        # Response generation
```

## Database Integration

### 1. Connection Management
```python
def get_db_connection():
    return mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="oat"
    )
```

### 2. Query Functions
```python
def get_student_by_name(name: str) -> List[Dict[str, Any]]:
    # Student lookup by name

def get_faculty_by_subject(subject: str) -> List[Dict[str, Any]]:
    # Faculty lookup by subject

def get_course_by_code(code: str) -> Dict[str, Any]:
    # Course lookup by code
```

## Response Templates

### 1. Student Information
```yaml
responses:
  utter_student_info:
    - text: "Here are the details for {name}:\nRegister Number: {reg_no}\nBatch: {batch}\nSection: {section}"
```

### 2. Faculty Information
```yaml
responses:
  utter_faculty_info:
    - text: "Dr. {name} handles the following subjects:\n{subjects}"
```

### 3. Course Information
```yaml
responses:
  utter_course_info:
    - text: "Course: {code}\nName: {name}\nCredits: {credits}\nSemester: {semester}"
```

## Error Handling

### 1. Intent Classification Errors
```python
if intent_confidence < 0.6:
    return [FollowupAction("action_default_fallback")]
```

### 2. Entity Extraction Errors
```python
if not all([slot_value for slot_value in required_slots]):
    return [FollowupAction("action_extract_missing_slots")]
```

### 3. Database Errors
```python
try:
    result = db_query()
except Exception as e:
    logger.error(f"Database error: {str(e)}")
    dispatcher.utter_message(text="I encountered an error while fetching the data.")
```

## Performance Optimization

### 1. Response Caching
```python
from functools import lru_cache

@lru_cache(maxsize=1000)
def get_cached_student_info(reg_no: str) -> Dict[str, Any]:
    return fetch_student_info(reg_no)
```

### 2. Database Query Optimization
```python
# Indexed queries
SELECT * FROM student USE INDEX (idx_reg_no) WHERE register_number = ?
```

### 3. Connection Pooling
```python
from mysql.connector import pooling

dbconfig = {
    "pool_name": "mypool",
    "pool_size": 5,
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "oat"
}
```

## Security Measures

### 1. Input Validation
```python
def sanitize_input(text: str) -> str:
    return re.sub(r'[^\w\s-]', '', text)
```

### 2. SQL Injection Prevention
```python
def safe_query(query: str, params: tuple) -> List[Dict[str, Any]]:
    cursor.execute(query, params)
```

### 3. Access Control
```python
def verify_user_access(user_id: str, resource: str) -> bool:
    # Access verification logic
```

## Deployment Configuration

### 1. Server Setup
```yaml
# endpoints.yml
action_endpoint:
  url: "http://localhost:5055/webhook"
```

### 2. CORS Configuration
```yaml
cors:
  "*"
```

### 3. Model Configuration
```yaml
# config.yml
assistant_id: 20240520-143642-dull-phrasing
language: en
pipeline:
  # NLU pipeline configuration
policies:
  # Core policies configuration
```

## Testing Framework

### 1. Test Stories
```yaml
stories:
- story: happy path 1
  steps:
  - intent: greet
  - action: utter_greet
  - intent: mood_great
  - action: utter_happy
```

### 2. NLU Testing
```yaml
nlu:
- intent: student_info
  examples: |
    - tell me about student [21CS1001]
    - show details of [21CS1003]
```

### 3. Core Testing
```yaml
rules:
- rule: Say goodbye anytime the user says goodbye
  steps:
  - intent: goodbye
  - action: utter_goodbye
``` 