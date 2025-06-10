# Student Handler

## Overview
The Student Handler manages all student-related queries in the ObeAI™ system. It processes requests for student information by name, register number, year, semester, section, batch, and degree type. The handler includes extensive pattern matching and entity extraction to handle various query formats.

## File Location
`actions/handlers/student_handler.py`

## Dependencies
```python
import re
import logging
import requests
import json
from typing import Any, Dict, List, Text, Optional

from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher
from rasa_sdk.events import SlotSet

from actions.utils.database import (
    get_student_by_name,
    get_student_by_register_number,
    get_students_by_year,
    get_students_by_semester,
    get_students_by_section,
    get_students_by_batch,
    get_students_by_type,
    extract_type,
    format_student_details
)
```

## Main Class
```python
class ActionStudentQuery(Action):
    """Handle all student-related queries"""
```

## Constants
```python
PDF_THRESHOLD = 5  # Number of results before generating PDF instead of direct message
```

## Core Methods

### 1. name()
```python
def name(self) -> Text:
    return "action_student_query"
```
Returns the action name for Rasa identification.

### 2. run() - Main Entry Point
```python
def run(self, dispatcher: CollectingDispatcher, tracker: Tracker, domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
    """Process student-related queries based on intent and entities"""
    
    intent = tracker.latest_message.get('intent', {}).get('name')
    message = tracker.latest_message.get('text', '')
    
    logger.info(f"Processing student query with intent: {intent}, message: {message}")
    
    # Return early if it's actually a course query
    if ("course" in message.lower() or "courses" in message.lower()):
        logger.info("Detected course query misclassified as student query, deferring to course handler")
        return []
    
    # Return early if it's a degree query without student context
    if (("btech" in message.lower() or "b.tech" in message.lower() or "b tech" in message.lower() or 
         "mtech" in message.lower() or "m.tech" in message.lower() or "m tech" in message.lower() or
         "mca" in message.lower() or "phd" in message.lower()) and 
        "student" not in message.lower() and "students" not in message.lower()):
        logger.info("Detected potential degree query without student context, deferring")
        return []
    
    # Extract entities from the message
    entities = tracker.latest_message.get('entities', [])
    entity_dict = {e['entity']: e['value'] for e in entities if 'entity' in e and 'value' in e}
    
    # Route to the appropriate handler based on intent
    if intent == "student_by_name":
        return self._handle_student_by_name(dispatcher, entity_dict, message)
    elif intent == "student_info":
        return self._handle_student_info(dispatcher, entity_dict, message)
    elif intent == "students_by_year":
        return self._handle_students_by_year(dispatcher, entity_dict, message)
    elif intent == "students_by_semester":
        return self._handle_students_by_semester(dispatcher, entity_dict, message)
    elif intent == "students_by_section":
        return self._handle_students_by_section(dispatcher, entity_dict, message)
    elif intent == "students_by_batch":
        return self._handle_students_by_batch(dispatcher, entity_dict, message)
    elif intent == "students_by_type":
        return self._handle_students_by_type(dispatcher, entity_dict, message)
    else:
        # For unrecognized intents, try generic handling
        return self._handle_generic_student_query(dispatcher, message)
```

### 3. PDF Generation Method
```python
def _generate_pdf_for_results(self, students: List[Dict[str, Any]], query_type: str, query_value: str) -> Optional[str]:
    """Generate a PDF for large result sets and return the download URL"""
    try:
        from actions.utils.pdf_generator import PDFGenerator
        pdf_generator = PDFGenerator()
        pdf_url = pdf_generator.generate_student_report(students, query_type, query_value)
        return pdf_url
    except Exception as e:
        logger.error(f"Error generating PDF: {str(e)}")
        return None
```

## Handler Methods

### 1. Student By Name Handler
```python
def _handle_student_by_name(self, dispatcher: CollectingDispatcher, entity_dict: Dict[str, Any], message: str) -> List[Dict[Text, Any]]:
    """Handle queries for student by name"""
    student_name = entity_dict.get('student_name')
    
    if not student_name:
        # Try to extract from message using regex
        name_match = re.search(r"(?:who|about|tell me about)\s+(?:is|are)?\s*([A-Za-z\s]+)(?:\?)?$", message, re.IGNORECASE)
        if name_match:
            student_name = name_match.group(1).strip()
    
    if student_name:
        students = get_student_by_name(student_name)
        if students:
            for student in students:
                dispatcher.utter_message(text=format_student_details(student))
            return [SlotSet("student_name", student_name)]
        else:
            dispatcher.utter_message(text=f"No student found with name containing '{student_name}'. Please check the name.")
    else:
        dispatcher.utter_message(text="I couldn't identify a student name in your query. Please provide a name.")
    
    return []
```

### 2. Student Info By Register Number Handler
```python
def _handle_student_info(self, dispatcher: CollectingDispatcher, entity_dict: Dict[str, Any], message: str) -> List[Dict[Text, Any]]:
    """Handle queries for student information by register number"""
    reg_no = entity_dict.get('register_number') or entity_dict.get('register_no')
    
    if not reg_no:
        reg_match = re.search(r"(?:about|tell me about|find|get)\s+(?:student|register number|reg no)?\s*([0-9]{2}[A-Za-z]{2}[0-9]{4})(?:\?)?", message, re.IGNORECASE)
        if reg_match:
            reg_no = reg_match.group(1).strip()
    
    course_code_match = re.search(r"\b([A-Z]{2,3}\s*\d{3})\b", message, re.IGNORECASE)
    if course_code_match and not reg_no:
        logger.info(f"Detected potential course code: {course_code_match.group(1)}, redirecting to course handler")
        dispatcher.utter_message(text=f"It looks like you're asking about a course code ({course_code_match.group(1)}). Please try asking 'What is {course_code_match.group(1)}?' or 'Tell me about {course_code_match.group(1)}'.")
        return []
        
    if reg_no:
        students = get_student_by_register_number(reg_no)
        if students:
            for student in students:
                dispatcher.utter_message(text=format_student_details(student))
            return [SlotSet("register_number", reg_no)]
        else:
            dispatcher.utter_message(text=f"No student found with register number '{reg_no}'. Please check the register number.")
            return []
    else:
        dispatcher.utter_message(text="I couldn't identify a register number in your query. Please provide a valid register number.")
        return []
```

### 3. Students By Year Handler
```python
def _handle_students_by_year(self, dispatcher: CollectingDispatcher, entity_dict: Dict[str, Any], message: str) -> List[Dict[Text, Any]]:
    """Handle queries for students by year"""
    year = entity_dict.get('year')
    
    if not year:
        # Complex pattern matching for year extraction
        # Check for numeric with suffix (1st, 2nd, etc.)
        year_match = re.search(r"(?:students?|show|list|get|find|display|tell|give|me).*?(?:in|of|from|about|me|for)?\s+(\d)(?:st|nd|rd|th)?\s*(?:year|yr)", message, re.IGNORECASE)
        if year_match:
            year = year_match.group(1).strip()
        else:
            # Check for word form (first, second, etc.)
            word_year_match = re.search(r"(?:students?|show|list|get|find|display|tell|give|me).*?(?:in|of|from|about|me|for)?\s+(first|second|third|fourth|1st|2nd|3rd|4th)\s*(?:year|yr)", message, re.IGNORECASE)
            if word_year_match:
                word_year = word_year_match.group(1).lower()
                year_mapping = {
                    "first": "1", "second": "2", "third": "3", "fourth": "4",
                    "1st": "1", "2nd": "2", "3rd": "3", "4th": "4"
                }
                year = year_mapping.get(word_year)
            # Additional patterns for direct word form and list requests
            # ...
    
    if year:
        try:
            year_int = int(year)
            students = get_students_by_year(year_int)
            if students:
                # Large result handling with PDF option
                if len(students) > PDF_THRESHOLD:
                    dispatcher.utter_message(text=f"Found {len(students)} students in year {year}.")
                    
                    pdf_url = self._generate_pdf_for_results(students, "year", str(year_int))
                    if pdf_url:
                        dispatcher.utter_message(text=f"I've prepared a PDF report with all the results. [Download PDF]({pdf_url})")
                    else:
                        # Fallback to showing limited results
                        dispatcher.utter_message(text="Here are the first few results:")
                        for student in students[:PDF_THRESHOLD]:
                            dispatcher.utter_message(text=format_student_details(student))
                        dispatcher.utter_message(text=f"... and {len(students) - PDF_THRESHOLD} more students.")
                else:
                    # Show all results directly
                    dispatcher.utter_message(text=f"Found {len(students)} students in year {year}:")
                    for student in students:
                        dispatcher.utter_message(text=format_student_details(student))
                
                return [SlotSet("year", year_int)]
            else:
                dispatcher.utter_message(text=f"No students found in year {year}.")
        except ValueError:
            dispatcher.utter_message(text=f"Invalid year value: {year}. Please provide a year between 1 and 4.")
    else:
        dispatcher.utter_message(text="I couldn't identify a year in your query. Please specify which year you're interested in (e.g., '1st year', 'second year').")
    
    return []
```

### 4. Students By Type (Degree) Handler
```python
def _handle_students_by_type(self, dispatcher: CollectingDispatcher, entity_dict: Dict[str, Any], message: str) -> List[Dict[Text, Any]]:
    """Handle queries for students by type (B.Tech, M.Tech, PhD)"""
    
    student_type = entity_dict.get('type')
    
    if not student_type:
        student_type = extract_type(message)
    
    if student_type:
        normalized_type = student_type.lower()
        
        # Special handling for M.Tech specializations
        is_mtech_is = bool(re.search(r'm\.?tech\s+is|m\s*tech\s+is', message, re.IGNORECASE))
        is_mtech_ds = bool(re.search(r'm\.?tech\s+ds|m\s*tech\s+ds', message, re.IGNORECASE))
        
        # Map to database types
        if normalized_type == "btech":
            db_type = "B.Tech"
        elif normalized_type == "mtech":
            if is_mtech_is:
                db_type = "M.Tech IS"  
                logger.info(f"Detected M.Tech IS specialization in message: {message}")
            elif is_mtech_ds:
                db_type = "M.Tech DS"  
                logger.info(f"Detected M.Tech DS specialization in message: {message}")
            else:
                db_type = "M.Tech%"
                logger.info(f"Using general M.Tech% pattern for query")
        elif normalized_type == "mca":
            db_type = "MCA"  
        else:
            db_type = student_type  
        
        logger.info(f"Looking up students with type: {db_type} (normalized from {student_type})")
        
        # Get display type for user messages
        display_type = student_type
        if is_mtech_is:
            display_type = "M.Tech IS"
        elif is_mtech_ds:
            display_type = "M.Tech DS"
        elif normalized_type == "btech":
            display_type = "B.Tech"
        elif normalized_type == "mtech":
            display_type = "M.Tech"
        elif normalized_type == "mca":
            display_type = "MCA"
        
        # Fetch and return students
        students = get_students_by_type(db_type)
        
        if students:
            # Handle large result sets with PDF option
            if len(students) > PDF_THRESHOLD:
                dispatcher.utter_message(text=f"Found {len(students)} {display_type} students.")
                
                pdf_url = self._generate_pdf_for_results(students, "type", display_type)
                if pdf_url:
                    dispatcher.utter_message(text=f"I've prepared a PDF report with all the results. [Download PDF]({pdf_url})")
                else:
                    # Fallback to showing limited results
                    dispatcher.utter_message(text="Here are the first few results:")
                    for student in students[:PDF_THRESHOLD]:
                        dispatcher.utter_message(text=format_student_details(student))
                    dispatcher.utter_message(text=f"... and {len(students) - PDF_THRESHOLD} more students.")
            else:
                # Show all results directly
                dispatcher.utter_message(text=f"Found {len(students)} {display_type} students:")
                for student in students:
                    dispatcher.utter_message(text=format_student_details(student))
            
            return [SlotSet("student_type", student_type)]
        else:
            dispatcher.utter_message(text=f"No students found of type {display_type}.")
    else:
        dispatcher.utter_message(text="I couldn't identify the student type in your query. Please specify B.Tech, M.Tech IS, M.Tech DS, or MCA.")
    
    return []
```

### 5. Generic Query Handler
```python
def _handle_generic_student_query(self, dispatcher: CollectingDispatcher, message: str) -> List[Dict[Text, Any]]:
    """Handle generic student queries by trying to extract information from the message"""
    
    # First try detecting degree types
    if "student" in message.lower() or "students" in message.lower():
        # Check for M.Tech specializations
        if re.search(r'm\.?tech\s+is|m\s*tech\s+is', message, re.IGNORECASE):
            logger.info(f"Generic handler detected M.Tech IS student query: {message}")
            return self._handle_students_by_type(dispatcher, {'type': 'mtech'}, message)
        elif re.search(r'm\.?tech\s+ds|m\s*tech\s+ds', message, re.IGNORECASE):
            logger.info(f"Generic handler detected M.Tech DS student query: {message}")
            return self._handle_students_by_type(dispatcher, {'type': 'mtech'}, message)
        
        # Check for degree types
        if "btech" in message.lower() or "b.tech" in message.lower() or "b tech" in message.lower():
            return self._handle_students_by_type(dispatcher, {'type': 'btech'}, message)
        elif "mtech" in message.lower() or "m.tech" in message.lower() or "m tech" in message.lower():
            return self._handle_students_by_type(dispatcher, {'type': 'mtech'}, message)
        elif "mca" in message.lower():
            return self._handle_students_by_type(dispatcher, {'type': 'mca'}, message)
        elif "phd" in message.lower():
            return self._handle_students_by_type(dispatcher, {'type': 'phd'}, message)
    
    # Try different types of queries in sequence
    reg_no = extract_register_number(message)
    if reg_no:
        return self._handle_student_info(dispatcher, {'register_no': reg_no}, message)
    
    student_type = extract_type(message)
    if student_type:
        return self._handle_students_by_type(dispatcher, {'type': student_type}, message)
    
    # Check for year, semester, section, batch or student name
    # (various regex patterns...)
    
    # If nothing matched
    dispatcher.utter_message(text="I couldn't understand what student information you're looking for. Please try being more specific.")
    return []
```

## Pattern Matching System

### 1. Student Name Pattern
```python
r"(?:who|about|tell me about)\s+(?:is|are)?\s*([A-Za-z\s]+)(?:\?)?$"
```
Extracts student names from queries like "Who is John Smith?" or "Tell me about Mary Jones"

### 2. Register Number Pattern
```python
r"(?:about|tell me about|find|get)\s+(?:student|register number|reg no)?\s*([0-9]{2}[A-Za-z]{2}[0-9]{4})(?:\?)?"
```
Extracts register numbers like "21CS1001" from various query formats

### 3. Year Patterns
```python
r"(?:students?|show|list|get|find|display|tell|give|me).*?(?:in|of|from|about|me|for)?\s+(\d)(?:st|nd|rd|th)?\s*(?:year|yr)"
r"(?:students?|show|list|get|find|display|tell|give|me).*?(?:in|of|from|about|me|for)?\s+(first|second|third|fourth|1st|2nd|3rd|4th)\s*(?:year|yr)"
```
Handles various ways users might ask about students in a specific year

### 4. M.Tech Specialization Detection
```python
r'm\.?tech\s+is|m\s*tech\s+is'  # For M.Tech IS
r'm\.?tech\s+ds|m\s*tech\s+ds'  # For M.Tech DS
```
Detects specific M.Tech specializations in queries

## Error Handling

### 1. Missing Information
```python
if not reg_no:
    dispatcher.utter_message(text="I couldn't identify a register number in your query. Please provide a valid register number.")
    return []
```

### 2. No Results Found
```python
if not students:
    dispatcher.utter_message(text=f"No students found with register number '{reg_no}'. Please check the register number.")
    return []
```

### 3. Invalid Input
```python
except ValueError:
    dispatcher.utter_message(text=f"Invalid year value: {year}. Please provide a year between 1 and 4.")
```

### 4. PDF Generation Failure
```python
except Exception as e:
    logger.error(f"Error generating PDF: {str(e)}")
    return None
```

## PDF Generation

When result sets are large (exceeding `PDF_THRESHOLD` of 5), the system generates PDF reports instead of flooding the chat with messages:

1. The handler identifies a large result set
2. It calls `_generate_pdf_for_results()` to create a PDF
3. It returns a download link to the user
4. If PDF generation fails, it falls back to showing a limited set of results

```python
if len(students) > PDF_THRESHOLD:
    dispatcher.utter_message(text=f"Found {len(students)} students in year {year}.")
    
    pdf_url = self._generate_pdf_for_results(students, "year", str(year_int))
    if pdf_url:
        dispatcher.utter_message(text=f"I've prepared a PDF report with all the results. [Download PDF]({pdf_url})")
    else:
        # Fallback to showing limited results
        dispatcher.utter_message(text="Here are the first few results:")
        for student in students[:PDF_THRESHOLD]:
            dispatcher.utter_message(text=format_student_details(student))
        dispatcher.utter_message(text=f"... and {len(students) - PDF_THRESHOLD} more students.")
```

## Normalization & Mapping

The handler includes sophisticated normalization and mapping for user inputs:

### 1. Year Mapping
```python
year_mapping = {
    "first": "1", "second": "2", "third": "3", "fourth": "4",
    "1st": "1", "2nd": "2", "3rd": "3", "4th": "4"
}
```

### 2. Degree Type Normalization
```python
if normalized_type == "btech":
    db_type = "B.Tech"
elif normalized_type == "mtech":
    if is_mtech_is:
        db_type = "M.Tech IS"
    elif is_mtech_ds:
        db_type = "M.Tech DS"
    else:
        db_type = "M.Tech%"
```

## Example Usage

### 1. Register Number Query
```
User: "Tell me about student 21CS1001"
Response: 
"Name: John Smith
Register No: 21CS1001
Year: 2
Semester: 4
Section: B
Batch: 2021-2025
Program: B.Tech
Department: Computer Science
Email: john.smith@example.com
Phone: 9876543210"
```

### 2. Type Query With Large Results
```
User: "Show all M.Tech students"
Response: 
"Found 28 M.Tech students.
I've prepared a PDF report with all the results. [Download PDF](http://localhost/B/AuraAI/pdf_reports/students_type_M.Tech_20240607_123456.pdf)"
```

### 3. Year Query
```
User: "Who are the third year students?"
Response: 
"Found 65 students in year 3.
I've prepared a PDF report with all the results. [Download PDF](http://localhost/B/AuraAI/pdf_reports/students_year_3_20240607_123456.pdf)"
```

### 4. Name Query
```
User: "Who is Aamir Khan?"
Response:
"Name: Aamir Khan
Register No: 21CS1056
Year: 2
Semester: 4
Section: A
Batch: 2021-2025
Program: B.Tech
Department: Computer Science
Email: aamir.k@example.com
Phone: 9876543210"
```

## Best Practices for Maintenance

1. **Pattern Handling**: When adding new patterns, ensure they handle common variations in user speech

2. **Error Messages**: Keep error messages informative but concise, guiding the user toward correct query formats

3. **PDF Threshold**: Adjust the `PDF_THRESHOLD` value based on chat platform limitations and user experience needs

4. **Specialized Degree Handling**: When adding new degree types or specializations, remember to add:
   - Detection patterns
   - Normalization logic
   - Display name mapping
   - Database query format

5. **Query Redirection**: Maintain the cross-handler redirection logic when queries are misclassified 