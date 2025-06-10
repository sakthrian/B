# Faculty Handler

## Overview
The Faculty Handler manages all faculty-related queries in the ObeAI™ system. It processes requests for faculty information by subject (who teaches a subject) and faculty teaching assignments (what subjects a faculty member teaches).

## File Location
`actions/handlers/faculty_handler.py`

## Dependencies
```python
from typing import List, Dict, Any, Optional, Text
import logging
import re
import requests
import json
from actions.utils.database import get_db_connection, normalize_faculty_name
from actions.utils.database import extract_subject_name, extract_faculty_name

from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher
from rasa_sdk.events import SlotSet
```

## Main Class
```python
class ActionFacultyQuery(Action):
    """Handle all faculty-related queries"""
```

## Constants
```python
PDF_THRESHOLD = 5  # Number of results before generating PDF instead of direct message
```

## Helper Functions

### 1. get_faculty_for_subject() - Database Lookup
```python
def get_faculty_for_subject(subject_name: str) -> List[Dict[str, Any]]:
    """
    Get faculty members teaching a specific subject
    
    Args:
        subject_name: The name of the subject (partial match)
        
    Returns:
        List of dictionaries with faculty details
    """
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        # Format subject pattern for search
        subject_pattern = f"%{subject_name}%"
        
        # Special case handling for common subjects
        if re.search(r'computer\s+networks?', subject_name, re.IGNORECASE):
            subject_pattern = "%Computer Network%"
        
        query = """
        SELECT f.name, c.code as subject_code, c.name as subject_name
        FROM faculty f
        JOIN faculty_course fc ON f.id = fc.faculty_id
        JOIN course c ON fc.course_id = c.code
        WHERE c.name LIKE %s
        """
        
        cursor.execute(query, (subject_pattern,))
        results = cursor.fetchall()
        
        cursor.close()
        conn.close()
        
        return results
    except Exception as e:
        logger.error(f"Error fetching faculty for subject: {e}")
        return []
```

### 2. get_subjects_for_faculty() - Database Lookup
```python
def get_subjects_for_faculty(faculty_name: str) -> List[Dict[str, Any]]:
    """
    Get subjects taught by a specific faculty member
    
    Args:
        faculty_name: The name of the faculty member (partial match)
        
    Returns:
        List of dictionaries with subject details
    """
    try:
        # Normalize faculty name for consistent matching
        normalized_name = normalize_faculty_name(faculty_name)
        
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        query = """
        SELECT f.name, c.code as subject_code, c.name as subject_name
        FROM faculty f
        JOIN faculty_course fc ON f.id = fc.faculty_id
        JOIN course c ON fc.course_id = c.code
        WHERE f.name LIKE %s
        """
        
        cursor.execute(query, (f"%{normalized_name}%",))
        results = cursor.fetchall()
        
        cursor.close()
        conn.close()
        
        return results
    except Exception as e:
        logger.error(f"Error fetching subjects for faculty: {e}")
        return []
```

### 3. handle_faculty_query() - Main Logic Handler
```python
def handle_faculty_query(text: str) -> Dict[str, Any]:
    """
    Main function to handle faculty-related queries
    
    Args:
        text: The user's query text
        
    Returns:
        Dictionary with response data and success status
    """
    logger.info(f"Handling faculty query: {text}")
    
    # Try to extract subject name first
    subject_name = extract_subject_name(text)
    if subject_name:
        faculty_list = get_faculty_for_subject(subject_name)
        if faculty_list:
            response = f"Faculty teaching {subject_name}:\n\n"
            for i, faculty in enumerate(faculty_list, 1):
                response += f"{i}. {faculty['name']} - {faculty['subject_code']}: {faculty['subject_name']}\n"
            
            return {
                "success": True,
                "query_type": "faculty_for_subject",
                "data": faculty_list,
                "message": response
            }
        else:
            return {
                "success": False,
                "query_type": "faculty_for_subject",
                "message": f"No faculty found teaching '{subject_name}'. Please check the subject name."
            }
    
    # If no subject, try faculty name
    faculty_name = extract_faculty_name(text)
    if faculty_name:
        subjects = get_subjects_for_faculty(faculty_name)
        if subjects:
            # Group by faculty name
            faculty_subjects = {}
            for subject in subjects:
                if subject['name'] not in faculty_subjects:
                    faculty_subjects[subject['name']] = []
                faculty_subjects[subject['name']].append(f"{subject['subject_code']}: {subject['subject_name']}")
            
            response = ""
            for faculty, subject_list in faculty_subjects.items():
                response += f"Subjects taught by {faculty}:\n"
                for subject in subject_list:
                    response += f"{subject}\n"
                response += "\n"
            
            return {
                "success": True,
                "query_type": "subjects_for_faculty",
                "data": subjects,
                "message": response.strip()
            }
        else:
            return {
                "success": False,
                "query_type": "subjects_for_faculty",
                "message": f"No subjects found for faculty '{faculty_name}'. Please check the faculty name."
            }
    
    # Fallback response
    return {
        "success": False,
        "query_type": "unknown",
        "message": "I couldn't understand your faculty query. Please try rephrasing it."
    }
```

## Core Methods

### 1. name()
```python
def name(self) -> Text:
    return "action_faculty_query"
```
Returns the action name for Rasa identification.

### 2. run() - Main Entry Point
```python
def run(self, dispatcher: CollectingDispatcher, tracker: Tracker, domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
    """Process faculty-related queries based on intent and entities"""
    
    # Get intent and message
    intent = tracker.latest_message.get('intent', {}).get('name')
    message = tracker.latest_message.get('text', '')
    
    logger.info(f"Processing faculty query with intent: {intent}, message: {message}")
    
    # Extract entities from message
    entities = tracker.latest_message.get('entities', [])
    entity_dict = {e['entity']: e['value'] for e in entities if 'entity' in e and 'value' in e}
    
    # Route to appropriate handler based on intent
    if intent == "faculty_by_subject":
        return self._handle_faculty_by_subject(dispatcher, entity_dict, message)
    elif intent == "subjects_by_faculty":
        return self._handle_subjects_by_faculty(dispatcher, entity_dict, message)
    else:
        # For unrecognized intents, try generic handling
        return self._handle_generic_faculty_query(dispatcher, message)
```

### 3. PDF Generation Method
```python
def _generate_pdf_for_results(self, data: List[Dict[str, Any]], query_type: str, query_value: str) -> Optional[str]:
    """Generate a PDF for large result sets and return the download URL"""
    try:
        # Import PDF generation utility
        from actions.utils.pdf_generator import PDFGenerator
        pdf_generator = PDFGenerator()
        pdf_url = pdf_generator.generate_faculty_report(data, query_type, query_value)
        return pdf_url
    except Exception as e:
        logger.error(f"Error generating PDF: {str(e)}")
        return None
```

## Handler Methods

### 1. Faculty By Subject Handler
```python
def _handle_faculty_by_subject(self, dispatcher: CollectingDispatcher, entity_dict: Dict[str, Any], message: str) -> List[Dict[Text, Any]]:
    """Handle queries for faculty by subject"""
    subject = entity_dict.get('subject')
    
    if not subject:
        # Try to extract from message using utility function
        subject_name = extract_subject_name(message)
        if subject_name:
            subject = subject_name
    
    if subject:
        faculty_list = get_faculty_for_subject(subject)
        if faculty_list:
            # Handle large result sets with PDF generation
            if len(faculty_list) > PDF_THRESHOLD:
                dispatcher.utter_message(text=f"Found {len(faculty_list)} faculty members teaching {subject}.")
                
                # Generate PDF report
                pdf_url = self._generate_pdf_for_results(faculty_list, "subject", subject)
                if pdf_url:
                    dispatcher.utter_message(text=f"I've prepared a PDF report with all the results. [Download PDF]({pdf_url})")
                else:
                    # Fallback to showing limited results
                    dispatcher.utter_message(text="Here are the first few results:")
                    for faculty in faculty_list[:PDF_THRESHOLD]:
                        dispatcher.utter_message(text=f"- {faculty.get('name')} teaches {faculty.get('subject_name')} ({faculty.get('subject_code')})")
                    dispatcher.utter_message(text=f"... and {len(faculty_list) - PDF_THRESHOLD} more faculty members.")
            else:
                # Display all results directly
                if len(faculty_list) == 1:
                    dispatcher.utter_message(text=f"{subject} is taught by:")
                else:
                    dispatcher.utter_message(text=f"{subject} is taught by multiple faculty members:")
                
                for faculty in faculty_list:
                    dispatcher.utter_message(text=f"- {faculty.get('name')} teaches {faculty.get('subject_name')} ({faculty.get('subject_code')})")
            
            return [SlotSet("subject_name", subject)]
        else:
            dispatcher.utter_message(text=f"No faculty found teaching '{subject}'. Please check the subject name.")
    else:
        dispatcher.utter_message(text="I couldn't identify a subject in your query. Please provide a subject name.")
    
    return []
```

### 2. Subjects By Faculty Handler
```python
def _handle_subjects_by_faculty(self, dispatcher: CollectingDispatcher, entity_dict: Dict[str, Any], message: str) -> List[Dict[Text, Any]]:
    """Handle queries for subjects by faculty"""
    faculty_name = entity_dict.get('faculty_name')
    
    if not faculty_name:
        # Try to extract from message using utility function
        extracted_name = extract_faculty_name(message)
        if extracted_name:
            faculty_name = extracted_name
    
    if faculty_name:
        # Normalize faculty name for better matching
        faculty_name = normalize_faculty_name(faculty_name)
        
        subjects = get_subjects_for_faculty(faculty_name)
        if subjects:
            # Handle large result sets with PDF generation
            if len(subjects) > PDF_THRESHOLD:
                dispatcher.utter_message(text=f"Found {len(subjects)} subjects taught by {faculty_name}.")
                
                # Generate PDF report
                pdf_url = self._generate_pdf_for_results(subjects, "faculty", faculty_name)
                if pdf_url:
                    dispatcher.utter_message(text=f"I've prepared a PDF report with all the results. [Download PDF]({pdf_url})")
                else:
                    # Fallback to displaying limited results
                    dispatcher.utter_message(text="Here are the first few results:")
                    for subject in subjects[:PDF_THRESHOLD]:
                        dispatcher.utter_message(text=f"- {subject.get('subject_name')} ({subject.get('subject_code')})")
                    dispatcher.utter_message(text=f"... and {len(subjects) - PDF_THRESHOLD} more subjects.")
            else:
                # Display all results directly
                if len(subjects) == 1:
                    dispatcher.utter_message(text=f"{faculty_name} teaches:")
                else:
                    dispatcher.utter_message(text=f"{faculty_name} teaches multiple subjects:")
                
                for subject in subjects:
                    dispatcher.utter_message(text=f"- {subject.get('subject_name')} ({subject.get('subject_code')})")
            
            return [SlotSet("faculty_name", faculty_name)]
        else:
            dispatcher.utter_message(text=f"No subjects found for faculty '{faculty_name}'. Please check the faculty name.")
    else:
        dispatcher.utter_message(text="I couldn't identify a faculty name in your query. Please provide a faculty name.")
    
    return []
```

### 3. Generic Query Handler
```python
def _handle_generic_faculty_query(self, dispatcher: CollectingDispatcher, message: str) -> List[Dict[Text, Any]]:
    """Handle generic faculty queries using regex patterns"""
    
    # First try to identify subject queries
    subject_name = extract_subject_name(message)
    if subject_name:
        return self._handle_faculty_by_subject(dispatcher, {"subject": subject_name}, message)
    
    # Then try faculty name queries
    faculty_name = extract_faculty_name(message)
    if faculty_name:
        return self._handle_subjects_by_faculty(dispatcher, {"faculty_name": faculty_name}, message)
    
    # Fallback response if no patterns matched
    dispatcher.utter_message(text="I couldn't understand your faculty query. Please try again with more specific information.")
    return []
```

## Error Handling

### 1. Missing Information
```python
if not subject:
    dispatcher.utter_message(text="I couldn't identify a subject in your query. Please provide a subject name.")
    return []
```

### 2. No Results Found
```python
if not faculty_list:
    dispatcher.utter_message(text=f"No faculty found teaching '{subject}'. Please check the subject name.")
```

### 3. Database Errors
```python
except Exception as e:
    logger.error(f"Error fetching faculty for subject: {e}")
    return []
```

## PDF Generation

When result sets are large (exceeding `PDF_THRESHOLD` of 5), the system generates PDF reports:

1. The handler identifies a large result set
2. It calls `_generate_pdf_for_results()` to create a PDF
3. It returns a download link to the user
4. If PDF generation fails, it falls back to showing a limited set of results

```python
if len(faculty_list) > PDF_THRESHOLD:
    dispatcher.utter_message(text=f"Found {len(faculty_list)} faculty members teaching {subject}.")
    
    # Generate PDF report
    pdf_url = self._generate_pdf_for_results(faculty_list, "subject", subject)
    if pdf_url:
        dispatcher.utter_message(text=f"I've prepared a PDF report with all the results. [Download PDF]({pdf_url})")
    else:
        # Fallback to showing limited results
        dispatcher.utter_message(text="Here are the first few results:")
        for faculty in faculty_list[:PDF_THRESHOLD]:
            dispatcher.utter_message(text=f"- {faculty.get('name')} teaches {faculty.get('subject_name')} ({faculty.get('subject_code')})")
        dispatcher.utter_message(text=f"... and {len(faculty_list) - PDF_THRESHOLD} more faculty members.")
```

## Example Usage

### 1. Faculty for Subject Query
```
User: "Who teaches Database Management Systems?"
Response: 
"Database Management Systems is taught by multiple faculty members:
- Dr. Akila V teaches Database Management Systems (CS305)
- 
```

### 2. Subjects for Faculty Query
```
User: "What subjects does Dr. Akila V teach?"
Response: 
"Dr.Akila V teaches multiple subjects:
- Database Management Systems (CS305)
- Advanced Database Concepts (CS505)
- Big Data Analytics (CS605)"
```

### 3. Query with Large Result Set
```
User: "Show all Computer Networks faculty"
Response: 
"Found 6 faculty members teaching Computer Networks.
I've prepared a PDF report with all the results. [Download PDF](http://localhost/B/AuraAI/pdf_reports/faculty_subject_Computer_Networks_20240607_123456.pdf)"
```

## Best Practices for Maintenance

1. **Pattern Handling**: When adding new patterns for entity extraction, ensure they handle common variations

2. **Database Query Building**: Maintain the careful approach to building SQL queries with appropriate parameters

3. **PDF Threshold**: Adjust the `PDF_THRESHOLD` value based on chat platform limitations and user experience needs

4. **Name Normalization**: Keep the faculty name normalization function updated as new naming patterns emerge

5. **Response Format**: Maintain consistent response formats for both single and multiple results 