# Course Handler

## Overview
The Course Handler manages all course-related queries in the ObeAI™ system. It processes requests for course information by code, name, semester, credits, and type (including degree-specific courses for B.Tech, M.Tech IS, M.Tech DS, MCA, PhD). The handler also provides course prerequisites and CO attainment visualization.

## File Location
`actions/handlers/course_handler.py`

## Dependencies
```python
from typing import List, Dict, Any, Optional, Text
import logging
import re
from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher
from rasa_sdk.events import SlotSet
from actions.utils.database import (
    get_course_by_code,
    get_course_by_name,
    get_courses_by_semester,
    get_courses_by_credits,
    format_course_details,
    extract_course_code,
    extract_course_name,
    extract_semester
)
```

## Main Class
```python
class ActionCourseQuery(Action):
    """Handle all course-related queries"""
```

## Constants
```python
PDF_THRESHOLD = 5  # Number of results before generating PDF instead of direct message
```

## Core Methods

### 1. name()
```python
def name(self) -> Text:
    return "action_course_query"
```
Returns the action name for Rasa identification.

### 2. run() - Main Entry Point
```python
def run(self, dispatcher: CollectingDispatcher, tracker: Tracker, domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
    """Process course-related queries based on intent and entities"""
    
    # Get the intent and message
    intent = tracker.latest_message.get('intent', {}).get('name')
    message = tracker.latest_message.get('text', '')
    confidence = tracker.latest_message.get('intent', {}).get('confidence', 0)
    
    # Debug information
    print("\n")
    print("*" * 100)
    print("DEBUGGING INFORMATION")
    print("*" * 100)
    print(f"RECEIVED MESSAGE: '{message}'")
    print(f"DETECTED INTENT: {intent}")
    print(f"CONFIDENCE SCORE: {confidence:.2%}")
    print("*" * 100)
    print("\n")
    
    logger.info(f"Processing course query with intent: {intent}, message: {message}")
    
    # Early detection and handling of degree-specific course queries
    if "course" in message.lower() or "courses" in message.lower():
        if "mtech ds" in message.lower() or "m.tech ds" in message.lower() or "m tech ds" in message.lower():
            course_type = "mtech"
            logger.info(f"Detected M.Tech DS course query: {message}")
            return self._handle_courses_by_type(dispatcher, {"course_type": course_type}, message)
        elif "mtech is" in message.lower() or "m.tech is" in message.lower() or "m tech is" in message.lower():
            course_type = "mtech"
            logger.info(f"Detected M.Tech IS course query: {message}")
            return self._handle_courses_by_type(dispatcher, {"course_type": course_type}, message)
        elif "btech" in message.lower() or "b.tech" in message.lower() or "b tech" in message.lower():
            course_type = "btech"
            logger.info(f"Detected btech course query: {message}")
            return self._handle_courses_by_type(dispatcher, {"course_type": course_type}, message)
        elif "mtech" in message.lower() or "m.tech" in message.lower() or "m tech" in message.lower():
            course_type = "mtech"
            logger.info(f"Detected mtech course query: {message}")
            return self._handle_courses_by_type(dispatcher, {"course_type": course_type}, message)
        elif "mca" in message.lower():
            course_type = "mca"
            logger.info(f"Detected mca course query: {message}")
            return self._handle_courses_by_type(dispatcher, {"course_type": course_type}, message)
        elif "phd" in message.lower():
            course_type = "phd"
            logger.info(f"Detected phd course query: {message}")
            return self._handle_courses_by_type(dispatcher, {"course_type": course_type}, message)
    
    # Extract entities from the tracker
    entities = tracker.latest_message.get('entities', [])
    entity_dict = {e['entity']: e['value'] for e in entities if 'entity' in e and 'value' in e}
    
    # Route to the appropriate handler based on intent
    if intent == "course_by_code":
        return self._handle_course_by_code(dispatcher, entity_dict, message)
    elif intent == "course_by_name":
        return self._handle_course_by_name(dispatcher, entity_dict, message)
    elif intent == "courses_by_semester":
        return self._handle_courses_by_semester(dispatcher, entity_dict, message)
    elif intent == "courses_by_credits":
        return self._handle_courses_by_credits(dispatcher, entity_dict, message)
    elif intent == "courses_by_type":
        return self._handle_courses_by_type(dispatcher, entity_dict, message)
    elif intent == "course_count":
        return self._handle_course_count(dispatcher, entity_dict, message)
    elif intent == "course_prerequisites":
        return self._handle_course_prerequisites(dispatcher, entity_dict, message)
    elif intent == "course_co_attainment":
        return self._handle_course_co_attainment(dispatcher, entity_dict, message)
    else:
        # For unrecognized intents, try generic handling
        return self._handle_generic_course_query(dispatcher, message)
```

### 3. PDF Generation Method
```python
def _generate_pdf_for_results(self, courses: List[Dict[str, Any]], query_type: str, query_value: str) -> Optional[str]:
    """Generate a PDF for large result sets and return the download URL"""
    try:
        from actions.utils.pdf_generator import PDFGenerator
        pdf_generator = PDFGenerator()
        pdf_url = pdf_generator.generate_course_report(courses, query_type, query_value)
        return pdf_url
    except Exception as e:
        logger.error(f"Error generating PDF: {str(e)}")
        return None
```

## Handler Methods

### 1. Course By Code Handler
```python
def _handle_course_by_code(self, dispatcher: CollectingDispatcher, entity_dict: Dict[str, Any], message: str) -> List[Dict[Text, Any]]:
    """Handle queries for course by code"""
    course_code = entity_dict.get('course_code')
    
    if not course_code:
        extracted_code = extract_course_code(message)
        if extracted_code:
            course_code = extracted_code
        else:
            code_match = re.search(r"\b([A-Z]{2,3}\s*\d{3})\b", message, re.IGNORECASE)
            if code_match:
                course_code = code_match.group(1).strip().replace(" ", "").upper()
    
    if course_code:
        logger.info(f"Looking up course with code: {course_code}")
        courses = get_course_by_code(course_code)
        if courses:
            for course in courses:
                dispatcher.utter_message(text=format_course_details(course))
            return [SlotSet("course_code", course_code)]
        else:
            dispatcher.utter_message(text=f"No course found with code '{course_code}'. Please check the course code.")
    else:
        dispatcher.utter_message(text="I couldn't identify a course code in your query. Please provide a valid course code (e.g., CS203).")
    
    return []
```

### 2. Course By Name Handler
```python
def _handle_course_by_name(self, dispatcher: CollectingDispatcher, entity_dict: Dict[str, Any], message: str) -> List[Dict[Text, Any]]:
    """Handle queries for course by name"""
    course_name = entity_dict.get('course_name')
    
    if not course_name:
        extracted_name = extract_course_name(message)
        if extracted_name:
            course_name = extracted_name
    
    if course_name:
        courses = get_course_by_name(course_name)
        if courses:
            for course in courses:
                dispatcher.utter_message(text=format_course_details(course))
            return [SlotSet("course_name", course_name)]
        else:
            dispatcher.utter_message(text=f"No course found with name containing '{course_name}'. Please check the course name.")
    else:
        dispatcher.utter_message(text="I couldn't identify a course name in your query. Please provide a valid course name.")
    
    return []
```

### 3. Courses By Semester Handler
```python
def _handle_courses_by_semester(self, dispatcher: CollectingDispatcher, entity_dict: Dict[str, Any], message: str) -> List[Dict[Text, Any]]:
    """Handle queries for courses by semester"""
    semester = entity_dict.get('semester')
    
    if not semester:
        extracted_semester = extract_semester(message)
        if extracted_semester:
            semester = extracted_semester
    
    if semester:
        try:
            semester_int = int(semester)
            courses = get_courses_by_semester(semester_int)
            if courses:
                if len(courses) > PDF_THRESHOLD:
                    dispatcher.utter_message(text=f"Found {len(courses)} courses in semester {semester}.")
                    
                    pdf_url = self._generate_pdf_for_results(courses, "semester", str(semester_int))
                    if pdf_url:
                        dispatcher.utter_message(text=f"I've prepared a PDF report with all the results. [Download PDF]({pdf_url})")
                    else:
                        dispatcher.utter_message(text="Here are the first few results:")
                        for course in courses[:PDF_THRESHOLD]:
                            dispatcher.utter_message(text=format_course_details(course))
                        dispatcher.utter_message(text=f"... and {len(courses) - PDF_THRESHOLD} more courses.")
                else:
                    dispatcher.utter_message(text=f"Found {len(courses)} courses in semester {semester}:")
                    for course in courses:
                        dispatcher.utter_message(text=format_course_details(course))
                
                return [SlotSet("semester", semester_int)]
            else:
                dispatcher.utter_message(text=f"No courses found in semester {semester}.")
        except ValueError:
            dispatcher.utter_message(text=f"Invalid semester value: {semester}. Please provide a number between 1 and 8.")
    else:
        dispatcher.utter_message(text="I couldn't identify a semester in your query. Please specify which semester you're interested in.")
    
    return []
```

### 4. Courses By Type Handler
```python
def _handle_courses_by_type(self, dispatcher: CollectingDispatcher, entity_dict: Dict[str, Any], message: str) -> List[Dict[Text, Any]]:
    """Handle queries for courses by type (core, elective, etc.)"""
    course_type = entity_dict.get('course_type')
    
    if not course_type:
        type_match = re.search(r"(?:courses|course|show|list|get|find).*(?:of type|that are|which are)?\s+(core|elective|lab|theory|btech|b\.tech|mtech|m\.tech|mca|phd)", message, re.IGNORECASE)
        if type_match:
            course_type = type_match.group(1).strip().lower()
            if course_type in ["b.tech", "b tech"]:
                course_type = "btech"
            elif course_type in ["m.tech", "m tech"]:
                course_type = "mtech"
    
    # Check for M.Tech specializations
    is_mtech_ds = "ds" in message.lower() and ("mtech" in message.lower() or "m.tech" in message.lower() or "m tech" in message.lower())
    is_mtech_is = "is" in message.lower() and ("mtech" in message.lower() or "m.tech" in message.lower() or "m tech" in message.lower())
    
    if course_type:
        try:
            # Map course types to database values
            type_mapping = {
                "btech": "B.Tech",
                "mtech": "M.Tech",
                "mca": "MCA",
                "phd": "PhD"
            }
            
            db_course_type = type_mapping.get(course_type.lower(), course_type)
            
            # Get database connection
            from actions.utils.database import get_db_connection
            conn = get_db_connection()
            cursor = conn.cursor(dictionary=True)
            
            # Handle different query types based on specialization
            if is_mtech_ds:
                cursor.execute("SELECT * FROM course WHERE type = 'M.Tech DS'")
                db_course_type = "M.Tech DS"
            elif is_mtech_is:
                cursor.execute("SELECT * FROM course WHERE type = 'M.Tech IS'")
                db_course_type = "M.Tech IS"
            elif db_course_type == "M.Tech":
                cursor.execute("SELECT * FROM course WHERE type LIKE 'M.Tech%'")
            else:
                cursor.execute("SELECT * FROM course WHERE type = %s", (db_course_type,))
            
            courses = cursor.fetchall()
            
            conn.close()
            
            if courses:
                if len(courses) > PDF_THRESHOLD:
                    dispatcher.utter_message(text=f"Found {len(courses)} {db_course_type} courses.")
                    pdf_url = self._generate_pdf_for_results(courses, "type", db_course_type)
                    if pdf_url:
                        dispatcher.utter_message(text=f"I've prepared a PDF report with all the results. [Download PDF]({pdf_url})")
                    else:
                        dispatcher.utter_message(text="Here are the first few results:")
                        for course in courses[:PDF_THRESHOLD]:
                            dispatcher.utter_message(text=format_course_details(course))
                        dispatcher.utter_message(text=f"... and {len(courses) - PDF_THRESHOLD} more courses.")
                else:
                    dispatcher.utter_message(text=f"Found {len(courses)} {db_course_type} courses:")
                    for course in courses:
                        dispatcher.utter_message(text=format_course_details(course))
                
                return [SlotSet("course_type", course_type)]
            else:
                dispatcher.utter_message(text=f"No courses found with type '{db_course_type}'.")
        except Exception as e:
            logger.error(f"Error querying courses by type: {str(e)}")
            dispatcher.utter_message(text="Sorry, I encountered an error while searching for courses by type.")
    else:
        dispatcher.utter_message(text="I couldn't identify a course type in your query. Please specify which type (core, elective, lab, theory, btech, mtech, etc.) you're interested in.")
    
    return []
```

### 5. Course Count Handler
```python
def _handle_course_count(self, dispatcher: CollectingDispatcher, entity_dict: Dict[str, Any], message: str) -> List[Dict[Text, Any]]:
    """Handle queries for course counts (total, by semester, by type, etc.)"""

    semester_match = re.search(r"(?:in|for)\s+semester\s+([1-8])", message, re.IGNORECASE)
    type_match = re.search(r"(?:of type|that are|which are)?\s+(core|elective|lab|theory)", message, re.IGNORECASE)
    
    try:
        from actions.utils.database import get_db_connection
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        query = "SELECT COUNT(*) as count FROM course"
        params = []
        where_clauses = []
        
        if semester_match:
            semester = int(semester_match.group(1))
            where_clauses.append("semester = %s")
            params.append(semester)
        
        if type_match:
            course_type = type_match.group(1).lower()
            where_clauses.append("type = %s")
            params.append(course_type)
        
        if where_clauses:
            query += " WHERE " + " AND ".join(where_clauses)
        
        cursor.execute(query, tuple(params))
        result = cursor.fetchone()
        count = result['count'] if result else 0
        
        conn.close()
        
        response = f"There are {count} courses"
        if semester_match:
            response += f" in semester {semester_match.group(1)}"
        if type_match:
            response += f" of type {type_match.group(1)}"
        response += "."
        
        dispatcher.utter_message(text=response)
        
    except Exception as e:
        logger.error(f"Error querying course count: {str(e)}")
        dispatcher.utter_message(text="Sorry, I encountered an error while counting courses.")
    
    return []
```

### 6. Course Prerequisites Handler
```python
def _handle_course_prerequisites(self, dispatcher: CollectingDispatcher, entity_dict: Dict[str, Any], message: str) -> List[Dict[Text, Any]]:
    """Handle queries for course prerequisites"""
    course_code = entity_dict.get('course_code')
    course_name = entity_dict.get('course_name')
    
    if not course_code and not course_name:
        extracted_code = extract_course_code(message)
        if extracted_code:
            course_code = extracted_code
        else:
            extracted_name = extract_course_name(message)
            if extracted_name:
                course_name = extracted_name
    
    try:
        from actions.utils.database import get_db_connection
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        if course_code:
            cursor.execute("SELECT * FROM course WHERE code = %s", (course_code,))
        elif course_name:
            cursor.execute("SELECT * FROM course WHERE name LIKE %s", (f"%{course_name}%",))
        else:
            dispatcher.utter_message(text="I couldn't identify a course in your query. Please specify which course you're interested in.")
            return []
        
        course = cursor.fetchone()
        
        if course:
            cursor.execute("""
                SELECT p.* FROM course c
                JOIN course_prerequisites cp ON c.id = cp.course_id
                JOIN course p ON cp.prerequisite_id = p.id
                WHERE c.code = %s
            """, (course['code'],))
            
            prerequisites = cursor.fetchall()
            
            conn.close()
            
            if prerequisites:
                dispatcher.utter_message(text=f"Prerequisites for {course['code']} - {course['name']}:")
                for prereq in prerequisites:
                    dispatcher.utter_message(text=f"- {prereq['code']}: {prereq['name']}")
            else:
                dispatcher.utter_message(text=f"The course {course['code']} - {course['name']} has no prerequisites.")
            
            return [SlotSet("course_code", course['code'])]
        else:
            dispatcher.utter_message(text=f"No course found matching your query.")
    except Exception as e:
        logger.error(f"Error querying course prerequisites: {str(e)}")
        dispatcher.utter_message(text="Sorry, I encountered an error while searching for course prerequisites.")
    
    return []
```

### 7. Course CO Attainment Handler
```python
def _handle_course_co_attainment(self, dispatcher: CollectingDispatcher, entity_dict: Dict[str, Any], message: str) -> List[Dict[Text, Any]]:
    """Handle queries for CO attainment visualization"""
    course_name = entity_dict.get('course_name')
    
    if not course_name:
        # Try to extract course name from message
        course_match = re.search(r"(?:co attainment|attainment|co)(?:\s+of)?\s+(.+?)(?:\?|$)", message, re.IGNORECASE)
        if course_match:
            course_name = course_match.group(1).strip()
    
    if course_name:
        # Import visualization and CO attainment utilities
        from actions.utils.co_visualization import generate_co_attainment_visualization
        
        # Get CO attainment data and visualization
        result = generate_co_attainment_visualization(course_name)
        
        if result and 'success' in result and result['success']:
            # Send response with visualization link
            if 'pdf_url' in result:
                dispatcher.utter_message(text=f"I've generated a CO attainment report for {result['course_name']}.")
                dispatcher.utter_message(text=f"You can view the detailed visualization here: [Download CO Attainment Report]({result['pdf_url']})")
                
                # Send a summary of the CO attainment if available
                if 'summary' in result:
                    dispatcher.utter_message(text=result['summary'])
                    
                return [SlotSet("course_name", course_name)]
            else:
                dispatcher.utter_message(text=f"I found CO attainment data for {result['course_name']}, but couldn't generate a visualization.")
        else:
            error_msg = result.get('error', f"No CO attainment data found for '{course_name}'. Please check the course name.")
            dispatcher.utter_message(text=error_msg)
    else:
        dispatcher.utter_message(text="I couldn't identify a course name in your query. Please specify which course you want to see CO attainment for.")
    
    return []
```

### 8. Generic Query Handler
```python
def _handle_generic_course_query(self, dispatcher: CollectingDispatcher, message: str) -> List[Dict[Text, Any]]:
    """Handle generic course-related queries not matched by specific intents"""
    
    # Check for semester course patterns that might not be caught by extract_semester
    sem_patterns = [
        r"(?:give|show|get|list|display)\s+(?:me)?\s+(?:the)?\s*(\d)(?:st|nd|rd|th)?\s+(?:sem|semester)(?:\s+(?:course|courses|subject|subjects))?",
        r"(\d)(?:st|nd|rd|th)?\s+(?:sem|semester)\s+(?:course|courses|subject|subjects)",
        r"(?:course|courses|subject|subjects)(?:\s+(?:in|of|from))\s+(\d)(?:st|nd|rd|th)?\s+(?:sem|semester)"
    ]
    
    for pattern in sem_patterns:
        sem_match = re.search(pattern, message, re.IGNORECASE)
        if sem_match:
            logger.info(f"Generic handler found semester pattern in: {message}")
            return self._handle_courses_by_semester(dispatcher, {"semester": sem_match.group(1)}, message)

    # Try different extractors in sequence
    # First try course code
    course_code = extract_course_code(message)
    if course_code:
        return self._handle_course_by_code(dispatcher, {"course_code": course_code}, message)
    
    # Then try semester
    semester = extract_semester(message)
    if semester:
        return self._handle_courses_by_semester(dispatcher, {"semester": semester}, message)
    
    # Then try credits
    credits_match = re.search(r"(?:courses|course|show|list|get|find).*(?:with|having|of|worth)?\s+([0-9]\.[0-9]|[0-9])\s*(?:credits?)", message, re.IGNORECASE)
    if credits_match:
        credits = credits_match.group(1).strip()
        return self._handle_courses_by_credits(dispatcher, {"credits": credits}, message)
    
    # Then try type
    type_match = re.search(r"(?:courses|course|show|list|get|find).*(?:of type|that are|which are)?\s+(core|elective|lab|theory)", message, re.IGNORECASE)
    if type_match:
        course_type = type_match.group(1).strip().lower()
        return self._handle_courses_by_type(dispatcher, {"course_type": course_type}, message)
    
    # Finally try course name
    course_name = extract_course_name(message)
    if course_name:
        return self._handle_course_by_name(dispatcher, {"course_name": course_name}, message)
    
    # Check for CO attainment queries
    if re.search(r"\b(?:co attainment|attainment)\b", message, re.IGNORECASE):
        return self._handle_course_co_attainment(dispatcher, {}, message)
    
    # If nothing matched
    dispatcher.utter_message(text="I couldn't understand your course query. Please try again with more specific information about the course code, name, semester, or credits.")
    return []
```

## Pattern Matching System

### 1. Course Code Pattern
```python
r"\b([A-Z]{2,3}\s*\d{3})\b"
```
Extracts course codes like "CS301" from various query formats

### 2. Course Type Pattern
```python
r"(?:courses|course|show|list|get|find).*(?:of type|that are|which are)?\s+(core|elective|lab|theory|btech|b\.tech|mtech|m\.tech|mca|phd)"
```
Identifies course type queries (core, elective, or degree-specific)

### 3. Semester Patterns
```python
[
    r"(?:give|show|get|list|display)\s+(?:me)?\s+(?:the)?\s*(\d)(?:st|nd|rd|th)?\s+(?:sem|semester)(?:\s+(?:course|courses|subject|subjects))?",
    r"(\d)(?:st|nd|rd|th)?\s+(?:sem|semester)\s+(?:course|courses|subject|subjects)",
    r"(?:course|courses|subject|subjects)(?:\s+(?:in|of|from))\s+(\d)(?:st|nd|rd|th)?\s+(?:sem|semester)"
]
```
Handles various ways users might ask about courses in a specific semester

### 4. M.Tech Specialization Detection
```python
# For M.Tech IS
"is" in message.lower() and ("mtech" in message.lower() or "m.tech" in message.lower() or "m tech" in message.lower())

# For M.Tech DS
"ds" in message.lower() and ("mtech" in message.lower() or "m.tech" in message.lower() or "m tech" in message.lower())
```
Detects specific M.Tech specializations in queries

## Error Handling

### 1. Missing Information
```python
if not course_code:
    dispatcher.utter_message(text="I couldn't identify a course code in your query. Please provide a valid course code (e.g., CS203).")
    return []
```

### 2. No Results Found
```python
if not courses:
    dispatcher.utter_message(text=f"No course found with code '{course_code}'. Please check the course code.")
```

### 3. Invalid Input
```python
except ValueError:
    dispatcher.utter_message(text=f"Invalid semester value: {semester}. Please provide a number between 1 and 8.")
```

### 4. Database Errors
```python
except Exception as e:
    logger.error(f"Error querying course prerequisites: {str(e)}")
    dispatcher.utter_message(text="Sorry, I encountered an error while searching for course prerequisites.")
```

## PDF Generation

When result sets are large (exceeding `PDF_THRESHOLD` of 5), the system generates PDF reports:

1. The handler identifies a large result set
2. It calls `_generate_pdf_for_results()` to create a PDF
3. It returns a download link to the user
4. If PDF generation fails, it falls back to showing a limited set of results

```python
if len(courses) > PDF_THRESHOLD:
    dispatcher.utter_message(text=f"Found {len(courses)} courses in semester {semester}.")
    
    pdf_url = self._generate_pdf_for_results(courses, "semester", str(semester_int))
    if pdf_url:
        dispatcher.utter_message(text=f"I've prepared a PDF report with all the results. [Download PDF]({pdf_url})")
    else:
        # Fallback to showing limited results
        dispatcher.utter_message(text="Here are the first few results:")
        for course in courses[:PDF_THRESHOLD]:
            dispatcher.utter_message(text=format_course_details(course))
        dispatcher.utter_message(text=f"... and {len(courses) - PDF_THRESHOLD} more courses.")
```

## Type Mapping System

The handler includes sophisticated mapping of course types for database queries:

```python
# Map course types to database values
type_mapping = {
    "btech": "B.Tech",
    "mtech": "M.Tech",
    "mca": "MCA",
    "phd": "PhD"
}

db_course_type = type_mapping.get(course_type.lower(), course_type)

# Special handling for M.Tech specializations
if is_mtech_ds:
    cursor.execute("SELECT * FROM course WHERE type = 'M.Tech DS'")
    db_course_type = "M.Tech DS"
elif is_mtech_is:
    cursor.execute("SELECT * FROM course WHERE type = 'M.Tech IS'")
    db_course_type = "M.Tech IS"
elif db_course_type == "M.Tech":
    cursor.execute("SELECT * FROM course WHERE type LIKE 'M.Tech%'")
```

## Example Usage

### 1. Course Code Query
```
User: "Tell me about CS301"
Response: 
"Code: CS301
Name: Data Structures
Credits: 4.0
Type: Theory
Semester: 3
Department: Computer Science
Description: Fundamental data structures and algorithms."
```

### 2. Semester Query With Large Results
```
User: "What are the courses in 4th semester?"
Response: 
"Found 12 courses in semester 4.
I've prepared a PDF report with all the results. [Download PDF](http://localhost/B/AuraAI/pdf_reports/courses_semester_4_20240607_123456.pdf)"
```

### 3. M.Tech Specialization Query
```
User: "Show M.Tech DS courses"
Response: 
"Found 8 M.Tech DS courses:
Code: DS501
Name: Machine Learning
Credits: 4.0
Type: M.Tech DS
Semester: 1
Department: Computer Science
Description: Fundamentals of machine learning algorithms."
(+ remaining courses)
```

### 4. Prerequisites Query
```
User: "What are the prerequisites for Database Systems?"
Response:
"Prerequisites for CS305 - Database Systems:
- CS301: Data Structures
- CS302: Design and Analysis of Algorithms"
```

### 5. CO Attainment Query
```
User: "Show CO attainment for Software Engineering"
Response: 
"I've generated a CO attainment report for Software Engineering.
You can view the detailed visualization here: [Download CO Attainment Report](http://localhost/B/AuraAI/pdf_reports/co_attainment_SE_20240607_123456.pdf)
The course has achieved an average CO attainment of 72.5% across all assessment components."
```

## Best Practices for Maintenance

1. **Pattern Handling**: When adding new patterns, ensure they handle various ways users might phrase their queries

2. **Database Query Building**: Maintain the careful approach to building dynamic SQL queries with appropriate parameters

3. **PDF Threshold**: Adjust the `PDF_THRESHOLD` value based on chat platform limitations and user experience needs

4. **Specialized Course Handling**: When adding new course types or specializations, remember to update:
   - Type detection patterns
   - Type mapping logic
   - Database query format for specialization

5. **CO Attainment Visualization**: Ensure the CO attainment visualization utility is properly maintained as it's linked to this handler

