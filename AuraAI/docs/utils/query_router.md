# Query Router Utility

## Overview
The Query Router utility provides classification and routing functionality for directing user queries to appropriate handlers in the ObeAI™ system. It analyzes query text, determines the query type, and routes requests to specialized handlers for students, faculty, or fallback processing.

## File Location
`actions/utils/query_router.py`

## Dependencies
```python
import re
import logging
from typing import Dict, Any, Tuple
```

## Core Functions

### 1. classify_query
```python
def classify_query(text: str) -> Tuple[str, float]:
    """
    Classify a query as student-related, faculty-related, or unknown
    
    Args:
        text: The user's query text
        
    Returns:
        Tuple of (query_type, confidence)
    """
```
This function analyzes user queries and classifies them as:
- Student-related queries (e.g., questions about students, registration numbers)
- Faculty-related queries (e.g., questions about teachers, subjects)
- Unknown queries (when classification is uncertain)

The function works by:
1. Checking for keyword matches against predefined student and faculty keyword lists
2. Calculating a confidence score based on the keyword match distribution
3. Returning a tuple with query type and confidence score

### 2. is_student_query
```python
def is_student_query(text: str) -> bool:
    """Check if a query is student-related using regex patterns"""
    student_patterns = [
        r"(?:who|tell me about|about)\s+(?:is|are)?\s*([A-Za-z\s]+)(?:\?)?$",  # Who is Aamir Khan?
        r"(?:about|tell me about|find|get)\s+(?:student|register number|reg no)?\s*([0-9]{2}[A-Za-z]{2}[0-9]{4})(?:\?)?",  # Tell me about 21CS1001
        r"(?:students|student|show|list|get|find).*(?:in|of|from)?\s+(?:year)?\s*([1-4])(?:\?)?",  # Show students in year 3
        r"(?:students|student|show|list|get|find).*(?:in|of|from)?\s+(?:semester|sem)?\s*([1-8])(?:\?)?",  # List students in semester 6
        r"(?:students|student|show|list|get|find).*(?:in|of|from)?\s+(?:section)?\s*([A-C])(?:\?)?",  # Show students in section A
        r"(?:students|student|show|list|get|find).*(?:in|of|from)?\s+(?:batch)?\s*(20\d{2}-20\d{2})(?:\?)?",  # List students in batch 2021-2025
    ]
    
    for pattern in student_patterns:
        if re.search(pattern, text, re.IGNORECASE):
            return True
    
    return False
```
Determines if a query is student-related by checking against specific regex patterns that match common formats of student queries.

### 3. is_faculty_query
```python
def is_faculty_query(text: str) -> bool:
    """Check if a query is faculty-related using regex patterns"""
    faculty_patterns = [
        r"(?:what|which)\s+(?:subject|subjects)(?:\s+does)?\s+([A-Za-z\s\.]+)(?:\s+(?:teach|teaches|take|takes|handle|handles))?",  # What subjects does Dr. Sreenath teach?
        r"(?:who|which faculty)(?:\s+(?:is|are))?\s+(?:teaching|teaches|taking|takes|handling|handles)\s+([A-Za-z\s]+)",  # Who teaches Computer Networks?
        r"(?:faculty|teacher|professor)(?:\s+(?:for|of))\s+([A-Za-z\s]+)",  # Faculty for Computer Networks
    ]
    
    for pattern in faculty_patterns:
        if re.search(pattern, text, re.IGNORECASE):
            return True
    
    return False
```
Determines if a query is faculty-related by checking against specific regex patterns for faculty-related questions.

### 4. route_query
```python
def route_query(text: str) -> Dict[str, Any]:
    """
    Route a query to the appropriate handler based on classification
    
    Args:
        text: The user's query text
        
    Returns:
        Dictionary with response data and success status
    """
```
The main routing function that directs queries to the appropriate handler:
1. Checks for greeting keywords and routes to greeting handler
2. Checks for goodbye keywords and routes to goodbye handler
3. Checks for help keywords and routes to help handler
4. For other queries, uses the classify_query function to determine query type
5. Routes to student_handler or faculty_handler based on classification
6. Implements a fallback strategy for uncertain classifications

## Pattern Keywords

### 1. Student Keywords
```python
student_keywords = [
    "student", "students", "year", "semester", "section", "batch",
    "register", "registration", "reg no", "who is", "tell me about"
]
```
Keywords used to identify student-related queries in the classification process.

### 2. Faculty Keywords
```python
faculty_keywords = [
    "faculty", "teacher", "professor", "lecturer", "instructor",
    "subject", "course", "teach", "teaches", "teaching", "taught",
    "handle", "handles", "handling", "handled", "take", "takes", "taking"
]
```
Keywords used to identify faculty-related queries in the classification process.

### 3. Special Intent Keywords
```python
# Greeting keywords
greeting_keywords = ["hi", "hello", "hey", "greetings", "good morning", "good afternoon", "good evening"]

# Goodbye keywords
goodbye_keywords = ["bye", "goodbye", "see you", "farewell", "thanks", "thank you"]

# Help keywords
help_keywords = ["help", "assist", "support", "guide", "what can you do", "how to use"]
```
Keywords used to identify special intent queries like greetings, goodbyes, and help requests.

## Student Query Patterns

The system recognizes various student query patterns including:

1. **Student Name Queries**: "Who is Aamir Khan?", "Tell me about John Smith"

2. **Register Number Queries**: "Tell me about 21CS1001", "Find student 21CS1001"

3. **Year Queries**: "Show students in year 3", "List year 2 students"

4. **Semester Queries**: "List students in semester 6", "Find semester 4 students"

5. **Section Queries**: "Show students in section A", "Get students from section B"

6. **Batch Queries**: "List students in batch 2021-2025", "Show batch 2020-2024 students"

## Faculty Query Patterns

The system recognizes various faculty query patterns including:

1. **Faculty Subject Queries**: "What subjects does Dr. Sreenath teach?", "Which subjects does Ms. Priya handle?"

2. **Subject Faculty Queries**: "Who teaches Computer Networks?", "Which faculty is handling Database Management Systems?"

3. **Subject Faculty Reference**: "Faculty for Computer Networks", "Teacher for Data Structures"

## Routing Logic

The query routing process follows this logic:

1. **Special Intent Check**: First check if the query matches special intents like greetings, goodbyes, or help requests
   ```python
   if any(keyword in text.lower() for keyword in greeting_keywords):
       return handle_greeting()
   ```

2. **Classification**: If not a special intent, classify the query
   ```python
   query_type, confidence = classify_query(text)
   ```

3. **Direct Routing**: Route to specific handlers based on classification
   ```python
   if query_type == "student":
       return handle_student_query(text)
   elif query_type == "faculty":
       return handle_faculty_query(text)
   ```

4. **Fallback Strategy**: For uncertain classifications, try both handlers
   ```python
   student_result = handle_student_query(text)
   if student_result["success"]:
       return student_result
       
   faculty_result = handle_faculty_query(text)
   if faculty_result["success"]:
       return faculty_result
       
   return handle_fallback(text)
   ```

## Error Handling

The system implements logging for tracing query classification and routing:

```python
logger.info(f"Routing query: {text}")
logger.info(f"Query classified as {query_type} with confidence {confidence}")
```

## Example Usage

### 1. Basic Query Classification
```python
# Classify a student query
query = "Tell me about student 21CS1001"
query_type, confidence = classify_query(query)
# Returns: ("student", 1.0)

# Classify a faculty query
query = "Who teaches Database Management Systems?"
query_type, confidence = classify_query(query)
# Returns: ("faculty", 1.0)
```

### 2. Query Routing
```python
# Route a greeting query
response = route_query("Hello, how are you?")
# Returns greeting handler response

# Route a student query
response = route_query("Show me all students in semester 5")
# Returns response from student handler

# Route a faculty query
response = route_query("Who teaches Computer Networks?")
# Returns response from faculty handler
```

### 3. Pattern Matching
```python
# Check if query is student-related
is_student = is_student_query("List all students in section A")
# Returns: True

# Check if query is faculty-related
is_faculty = is_faculty_query("Who is the faculty for Data Structures?")
# Returns: True
```

## Integration with Handlers

The query router integrates with:

1. **Student Handler**: Routes student-related queries for processing
   ```python
   if query_type == "student":
       return handle_student_query(text)
   ```

2. **Faculty Handler**: Routes faculty-related queries for processing
   ```python
   elif query_type == "faculty":
       return handle_faculty_query(text)
   ```

3. **Greeting Handler**: Routes greeting queries
   ```python
   if any(keyword in text.lower() for keyword in greeting_keywords):
       return handle_greeting()
   ```

4. **Goodbye Handler**: Routes goodbye queries
   ```python
   if any(keyword in text.lower() for keyword in goodbye_keywords):
       return handle_goodbye()
   ```

5. **Help Handler**: Routes help queries
   ```python
   if any(keyword in text.lower() for keyword in help_keywords):
       return handle_help()
   ```

6. **Fallback Handler**: Handles queries that couldn't be processed by other handlers
   ```python
   return handle_fallback(text)
   ```

## Best Practices for Maintenance

1. **Pattern Updates**: When adding new patterns, ensure they are tested against a variety of query formats

2. **Keyword Management**: Keep keyword lists updated as new terminology is introduced

3. **Confidence Thresholds**: Adjust confidence thresholds based on classification performance

4. **Fallback Handling**: Maintain robust fallback mechanisms for unclassified queries

5. **Logging**: Use the logging system to track and analyze routing decisions 