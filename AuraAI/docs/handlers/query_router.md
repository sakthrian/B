# Query Router Handler

## Overview
The Query Router is the central routing system that analyzes incoming user queries and directs them to the appropriate specialized handlers based on content analysis. It serves as the first point of contact for all user interactions in the ObeAI™ system.

## File Location
`actions/handlers/query_router.py`

## Dependencies
```python
import re
import logging
from typing import Any, Dict, List, Text

from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher
```

## Main Class
```python
class ActionQueryRouter(Action):
    """Route queries to the appropriate handler based on content analysis"""
```

## Methods

### 1. name()
```python
def name(self) -> Text:
    return "action_query_router"
```
Returns the action name for Rasa identification.

### 2. run()
```python
def run(self, dispatcher: CollectingDispatcher, tracker: Tracker, domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
    """Analyze the message and route to the appropriate handler"""
```
Main method for analyzing and routing incoming queries to specialized handlers.

## Routing Logic

### 1. Message and Intent Analysis
```python
# Get the message
message = tracker.latest_message.get('text', '')
intent = tracker.latest_message.get('intent', {}).get('name')
confidence = tracker.latest_message.get('intent', {}).get('confidence', 0)
```

### 2. Debug Information
```python
print("\n")
print("*" * 100)
print("DEBUGGING INFORMATION")
print("*" * 100)
print(f"RECEIVED MESSAGE: '{message}'")
print(f"DETECTED INTENT: {intent}")
print(f"CONFIDENCE SCORE: {confidence:.2%}")
print("*" * 100)
print("\n")

logger.info("\n" + "="*50)
logger.info("QUERY ANALYSIS START")
logger.info("="*50)
logger.info(f"User Query: '{message}'")
logger.info(f"NLU Intent: {intent}")
logger.info(f"Confidence Score: {confidence:.2%}")

debug_info = f"\n[DEBUG INFO]\nQuery: '{message}'\nDetected Intent: {intent}\nConfidence: {confidence:.2%}"
```

### 3. Course-Related Routing
```python
# routing to course handler
if "course" in message.lower() or "courses" in message.lower():
    logger.info("ROUTING: Detected course-related query, routing to course handler")
    from actions.handlers.course_handler import ActionCourseQuery
    return ActionCourseQuery().run(dispatcher, tracker, domain)
```

### 4. Degree-Type Routing
```python
# route based on degree type -> student
if (("btech" in message.lower() or "b.tech" in message.lower() or "b tech" in message.lower() or 
     "mtech" in message.lower() or "m.tech" in message.lower() or "m tech" in message.lower() or
     "mca" in message.lower() or "phd" in message.lower())):
    
    # If contains student keywords, route to student handler
    if "student" in message.lower() or "students" in message.lower():
        
        if re.search(r'm\.?tech\s+is|m\s*tech\s+is', message, re.IGNORECASE) or re.search(r'm\.?tech\s+ds|m\s*tech\s+ds', message, re.IGNORECASE):
            logger.info("ROUTING: Detected M.Tech specialization query with student context, routing to student handler")
            logger.info(f"Original message: '{message}'")
            from actions.handlers.student_handler import ActionStudentQuery
            return ActionStudentQuery().run(dispatcher, tracker, domain)
        
        logger.info("ROUTING: Detected degree-type query with student context, routing to student handler")
        logger.info(f"Original message: '{message}'")
        from actions.handlers.student_handler import ActionStudentQuery
        return ActionStudentQuery().run(dispatcher, tracker, domain)
    #route to course handler
    else:
        logger.info("ROUTING: Detected degree-type query without student context, routing to course handler")
        from actions.handlers.course_handler import ActionCourseQuery
        return ActionCourseQuery().run(dispatcher, tracker, domain)
```

### 5. Intent-based Routing
```python
# If we have a high-confidence intent then go according to the NLU
if intent != 'nlu_fallback' and confidence > 0.7:
    method_info = "USING: NLU (High Confidence)"
    print(f"PROCESSING METHOD: {method_info}")
    debug_info += f"\n{method_info}"
    logger.info(method_info)
    dispatcher.utter_message(text=debug_info)
    logger.info("QUERY ANALYSIS END")
    logger.info("="*50)
    return []
```

### 6. Pattern-Based Routing
```python
# Otherwise, use regex 
method_info = "USING: Regex Patterns (NLU confidence too low)"
debug_info += f"\n{method_info}"
logger.info(method_info)
```

## Pattern Matching System

### 1. Course Patterns
```python
course_patterns = [
    r"\b([A-Z]{2,3}\s*\d{3})\b", 
    r"(?:course|subject)\s+(?:code)?\s*([A-Z]{2,3}\s*\d{3})",  
    r"(?:what is|tell me about|details of|information on|show|give me details of)\s+([A-Z]{2,3}\s*\d{3})",  
    r"(?:courses|subjects)(?:\s+in)?\s+(?:semester|sem)\s*([1-8])", 
    r"(?:courses|subjects)(?:\s+with)?\s+([0-9]\.[0-9]|[0-9])\s+credits",  
    r"(?:what|which)\s+(?:courses|subjects)(?:\s+are)?\s+(?:offered|available|taught)(?:\s+in)?\s+(?:semester|sem)?\s*([1-8])", 
]

for pattern in course_patterns:
    if re.search(pattern, message, re.IGNORECASE):
        pattern_info = f"Matched Course Pattern: {pattern}"
        debug_info += f"\n{pattern_info}"
        logger.info(pattern_info)
        dispatcher.utter_message(text=debug_info)
        logger.info("QUERY ANALYSIS END")
        logger.info("="*50)
        from actions.handlers.course_handler import ActionCourseQuery
        return ActionCourseQuery().run(dispatcher, tracker, domain)
```

### 2. Student Patterns
```python
student_patterns = [
    r"(?:who|tell me about|about)\s+(?:is|are)?\s*([A-Za-z\s]+)(?:\?)?$",  # Who is Aamir Khan?
    r"(?:about|tell me about|find|get)\s+(?:student|register number|reg no)?\s*([0-9]{2}[A-Za-z]{2}[0-9]{4})(?:\?)?",  # Tell me about 21CS1001
    r"(?:students|student|show|list|get|find).*(?:in|of|from)?\s+(?:year)?\s*([1-4])(?:\?)?",  # Show students in year 3
    r"(?:students|student|show|list|get|find).*(?:in|of|from)?\s+(?:semester|sem)?\s*([1-8])(?:\?)?",  # List students in semester 6
    r"(?:students|student|show|list|get|find).*(?:in|of|from)?\s+(?:section)?\s*([A-C])(?:\?)?",  # Show students in section A
    r"(?:students|student|show|list|get|find).*(?:in|of|from)?\s+(?:batch)?\s*(20\d{2}-20\d{2})(?:\?)?",  # List students in batch 2021-2025
]

for pattern in student_patterns:
    if re.search(pattern, message, re.IGNORECASE):
        pattern_info = f"Matched Student Pattern: {pattern}"
        debug_info += f"\n{pattern_info}"
        logger.info(pattern_info)
        dispatcher.utter_message(text=debug_info)
        logger.info("QUERY ANALYSIS END")
        logger.info("="*50)
        from actions.handlers.student_handler import ActionStudentQuery
        return ActionStudentQuery().run(dispatcher, tracker, domain)
```

### 3. Faculty Patterns
```python
faculty_patterns = [
    r"(?:what|which)\s+(?:subject|subjects)(?:\s+does)?\s+([A-Za-z\s\.]+)(?:\s+(?:teach|teaches|take|takes|handle|handles))?",  # What subjects does Dr. Sreenath teach?
    r"(?:who|which faculty)(?:\s+(?:is|are))?\s+(?:teaching|teaches|taking|takes|handling|handles)\s+([A-Za-z\s]+)",  # Who teaches Computer Networks?
    r"(?:faculty|teacher|professor)(?:\s+(?:for|of))\s+([A-Za-z\s]+)",  # Faculty for Computer Networks
]

for pattern in faculty_patterns:
    if re.search(pattern, message, re.IGNORECASE):
        debug_info += f"\nMatched Pattern: Faculty Query ({pattern})"
        logger.info(f"Routing to faculty handler based on pattern match: {pattern}")
        dispatcher.utter_message(text=debug_info)
        from actions.handlers.faculty_handler import ActionFacultyQuery
        return ActionFacultyQuery().run(dispatcher, tracker, domain)
```

### 4. Greeting Patterns
```python
greeting_patterns = [
    r"^(?:hi|hello|hey|greetings|good morning|good afternoon|good evening)(?:\s|$)",
]

for pattern in greeting_patterns:
    if re.search(pattern, message, re.IGNORECASE):
        debug_info += f"\nMatched Pattern: Greeting ({pattern})"
        logger.info(f"Routing to greeting handler based on pattern match: {pattern}")
        dispatcher.utter_message(text=debug_info)
        from actions.handlers.greeting_handler import ActionGreeting
        return ActionGreeting().run(dispatcher, tracker, domain)
```

### 5. Goodbye Patterns
```python
goodbye_patterns = [
    r"^(?:bye|goodbye|see you|farewell|thanks|thank you)(?:\s|$)",
]

for pattern in goodbye_patterns:
    if re.search(pattern, message, re.IGNORECASE):
        debug_info += f"\nMatched Pattern: Goodbye ({pattern})"
        logger.info(f"Routing to goodbye handler based on pattern match: {pattern}")
        dispatcher.utter_message(text=debug_info)
        from actions.handlers.greeting_handler import ActionGoodbye
        return ActionGoodbye().run(dispatcher, tracker, domain)
```

## Simple Query Handling

### 1. Short Query Processing
```python
if len(message.split()) <= 3:
    
    # Try to match as a faculty query first
    from actions.utils.database import get_faculty_by_subject
    faculty = get_faculty_by_subject(message.strip())
    if faculty:
        debug_info += "\nMatched Pattern: Simple Subject Query (Faculty)"
        logger.info(f"Routing to faculty handler for subject name: {message}")
        dispatcher.utter_message(text=debug_info)
        from actions.handlers.faculty_handler import ActionFacultyQuery
        return ActionFacultyQuery().run(dispatcher, tracker, domain)
    
    # Then try to match as a course query
    from actions.utils.database import get_course_by_name
    courses = get_course_by_name(message.strip())
    if courses:
        debug_info += "\nMatched Pattern: Simple Subject Query (Course)"
        logger.info(f"Routing to course handler for course name: {message}")
        dispatcher.utter_message(text=debug_info)
        from actions.handlers.course_handler import ActionCourseQuery
        return ActionCourseQuery().run(dispatcher, tracker, domain)
```

## Fallback Handling

```python
# If no patterns matched, use fallback handler
fallback_info = "NO MATCHES: Using fallback handler"
debug_info += f"\n{fallback_info}"
logger.info(fallback_info)
dispatcher.utter_message(text=debug_info)
logger.info("QUERY ANALYSIS END")
logger.info("="*50)
from actions.handlers.fallback_handler import ActionFallback
return ActionFallback().run(dispatcher, tracker, domain)
```

## Logging System

### 1. Configuration
```python
logger = logging.getLogger(__name__)
```

### 2. Log Levels and Categories
- Debug information for query processing
- Detailed pattern matching results
- Handler routing decisions
- Query analysis start/end markers

## Example Usage

### 1. Course Query Routing
```
User: "What are the courses in semester 3?"
- Matched course pattern
- Routes to course_handler.ActionCourseQuery
```

### 2. Student Query Routing
```
User: "Tell me about student 21CS1001"
- Matched student pattern
- Routes to student_handler.ActionStudentQuery
```

### 3. Faculty Query Routing
```
User: "Who teaches Computer Networks?"
- Matched faculty pattern
- Routes to faculty_handler.ActionFacultyQuery
```

### 4. Simple Subject Query
```
User: "Python"
- Short query processing
- Database lookup for faculty or course match
- Routes to appropriate handler based on result
```

### 5. M.Tech Specialization Query
```
User: "Is M.Tech DS offered at this college?"
- Matched degree type pattern with specialization
- Routes to student_handler.ActionStudentQuery
```

## Best Practices for Maintenance

1. **Pattern Refinement**: When adding new patterns, ensure they don't conflict with existing ones and are specific enough to correctly route queries

2. **Debug Information**: Maintain extensive logging to track how queries are being processed and routed

3. **Handler Integration**: When adding new handlers, ensure they're properly imported and accessible by the query router

4. **Fallback Behavior**: Always maintain a valid fallback option for unmatched queries

5. **Intent Confidence**: Adjust the confidence threshold (currently 0.7) based on the accuracy of the NLU model 