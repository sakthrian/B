# Database Utility

## Overview
The Database utility provides a centralized interface for database operations in the ObeAI™ system. It handles connections, queries, data extraction from user messages, and offers a comprehensive set of functions for interacting with student, faculty, and course data.

## File Location
`actions/utils/database.py`

## Dependencies
```python
import mysql.connector
import re
import logging
from typing import List, Dict, Any, Tuple, Optional
```

## Database Connection

### get_db_connection()
```python
def get_db_connection():
    """Get a connection to the MySQL database"""
    try:
        conn = mysql.connector.connect(
            host="localhost",
            user="root", 
            password="", 
            database="oat"
        )
        return conn
    except Exception as e:
        logging.error(f"Database connection error: {str(e)}")
        raise
```
Core function to establish database connection used throughout the system.

### DatabaseConnection Class
```python
class DatabaseConnection:
    """Class to handle database connections and operations"""
    
    def __init__(self, host="127.0.0.1", user="root", password="", database="oat"):
        """Initialize database connection parameters"""
        self.host = host
        self.user = user
        self.password = password
        self.database = database
        self.connection = None
        self.cursor = None
```
Object-oriented approach to database connection management with methods:
- `connect()` - Establishes connection
- `disconnect()` - Closes connection
- `execute_query()` - Executes SQL with proper error handling

## Text Extraction Functions

### 1. Name Normalization
```python
def normalize_faculty_name(name: str) -> str:
    """Normalize faculty name by handling variations with or without Dr. prefix"""
    
    name = re.sub(r'[.,;:!?]$', '', name).strip()
    
    # Handle Dr. prefix variations
    if name.lower().startswith('dr '):
        name = 'Dr. ' + name[3:]
    elif name.lower().startswith('dr. '):
        pass  # Already formatted correctly
    elif name.lower().startswith('dr.'):
        name = 'Dr. ' + name[3:]
    
    return name
```

### 2. Register Number Extraction
```python
def extract_register_number(text: str) -> Optional[str]:
    """Extract register number from text"""
    
    cleaned_text = re.sub(r'^(?:hi|hello|hey|greetings|good\s+(?:morning|afternoon|evening))\s*', '', text.lower())
    
    patterns = [
        # MCA format (e.g., 2401507109, 2301507311)
        r'\b(2[0-9]{9})\b',
        # B.Tech format (e.g., 21CS1001)
        r'\b([0-9]{2}[A-Za-z]{2}[0-9]{4})\b',
        
        r'\b([0-9]{10})\b'
    ]
    
    for pattern in patterns:
        match = re.search(pattern, cleaned_text, re.IGNORECASE)
        if match:
            return match.group(1)
    
    return None
```

### 3. Student Attribute Extraction
Functions that extract specific student attributes from text:
- `extract_student_name()` - Identifies student names
- `extract_year()` - Finds year references (1-4)
- `extract_semester()` - Extracts semester numbers (1-8)
- `extract_section()` - Detects section mentions (A-E)
- `extract_batch()` - Identifies batch years (e.g., 2020-2024)
- `extract_type()` - Determines degree type (B.Tech, M.Tech, MCA, etc.)

### 4. Faculty and Course Extraction
```python
def extract_subject_name(text: str) -> Optional[str]:
    """Extract subject name from text"""
    patterns = [
        r'who\s+(?:is\s+)?(?:teaching|teaches|taking|takes|handling|handles)\s+([A-Za-z\s]+)',
        r'faculty\s+(?:for|of)\s+([A-Za-z\s]+)',
        r'which\s+faculty\s+(?:teaches|handles|taking|takes)\s+([A-Za-z\s]+)',
        r'who\s+(?:is|are)\s+(?:the)?\s*(?:faculty|teacher|professor)\s+(?:for|of)\s+([A-Za-z\s]+)'
    ]
    
    for pattern in patterns:
        match = re.search(pattern, text, re.IGNORECASE)
        if match:
            subject_name = match.group(1).strip()
            
            subject_name = re.sub(r'[.,;:!?]$', '', subject_name)
            return subject_name
    
    # For simple queries (3 words or fewer)
    if len(text.split()) <= 3:
        return text.strip()
    
    return None

def extract_faculty_name(text: str) -> Optional[str]:
    """Extract faculty name from text"""
    patterns = [
        r'what\s+(?:subject|subjects|courses)?\s+(?:does|do|is|are)?\s+([A-Za-z\s\.]+?)(?:\s+(?:teach|handle|take|handles|teaches|taking|taught|handled)s?)?(?:\s*\??)?$',
        r'(?:subject|subjects)\s+(?:taught|handled)\s+by\s+([^?]+?)(?:\s*\??)?$',
        r'what\s+does\s+([A-Za-z\s\.]+?)(?:\s+(?:teach|handle|take|handles|teaches|taking|taught|handled)s?)?(?:\s*\??)?$',
        r'what\s+(?:subject|subjects|courses)?\s+(?:does|do)?\s*([A-Za-z\s\.]+?)(?:\s+(?:teach|handle|take|handles|teaches|taking|taught|handled)s?)?(?:\s*\??)?$',
        r'(?:subject|subjects|courses)\s+(?:of|for|by)\s+([A-Za-z\s\.]+?)(?:\s*\??)?$',
    ]
    
    for pattern in patterns:
        match = re.search(pattern, text, re.IGNORECASE)
        if match:
            faculty_name = match.group(1).strip()
            # Remove any trailing punctuation
            faculty_name = re.sub(r'[.,;:!?]$', '', faculty_name)
            logger.info(f"Extracted faculty name: '{faculty_name}' from query: '{text}'")
            return faculty_name
    
    # Handle short queries (2-4 words)
    words = text.split()
    if 2 <= len(words) <= 4:
        # Look for capitalized words that aren't common terms
        for word in words:
            if word[0].isupper() and len(word) > 2 and word.lower() not in ['what', 'who', 'does', 'teach', 'subject', 'subjects', 'take', 'takes', 'handle', 'handles']:
                logger.info(f"Extracted potential faculty name: '{word}' from short query: '{text}'")
                return word
    
    logger.info(f"Could not extract faculty name from: '{text}'")
    return None
```

## Query Classification

### 1. Intent Classification
```python
def classify_query(text: str) -> Tuple[str, float]:
    """Classify the query type with confidence score"""
    # Check for register number (strongest indicator)
    if extract_register_number(text):
        return "student", 1.0  # High confidence
    
    # Remove greetings from text
    cleaned_text = re.sub(r'^(?:hi|hello|hey|greetings|good\s+(?:morning|afternoon|evening))\s*', '', text.lower())
    
    # Check for student-related patterns
    student_score = 0.0
    if is_student_query(cleaned_text):
        student_score = 0.8
        
        # Increase confidence based on specific identifiers
        if extract_year(cleaned_text) or extract_semester(cleaned_text) or extract_section(cleaned_text) or extract_batch(cleaned_text):
            student_score = 0.9
        elif extract_type(cleaned_text):
            student_score = 0.9
    
    # Check for faculty-related patterns
    faculty_score = 0.0
    if is_faculty_query(cleaned_text):
        faculty_score = 0.8
        
        # Increase confidence based on specific patterns
        if extract_subject_name(cleaned_text) and ("who" in cleaned_text.lower() or "faculty" in cleaned_text.lower()):
            faculty_score = 0.9
        elif extract_faculty_name(cleaned_text) and "subject" in cleaned_text.lower():
            faculty_score = 0.9
    
    # Return the highest confidence classification
    if student_score > faculty_score and student_score > 0.6:
        return "student", student_score
    elif faculty_score > 0.6:
        return "faculty", faculty_score
    else:
        return "unknown", 0.0
```

### 2. Query Type Detection
```python
def is_student_query(text: str) -> bool:
    """Determine if a query is related to students"""
    
    reg_no = extract_register_number(text)
    if reg_no:
        return True
        
    # Remove greeting phrases
    cleaned_text = re.sub(r'^(?:hi|hello|hey|greetings|good\s+(?:morning|afternoon|evening))\s*', '', text.lower())
    
    student_keywords = ["student", "students", "register", "reg no", "year", "semester", "section", "batch", "b.tech", "m.tech", "btech", "mtech", "phd", "mca"]
    
    # Check for student keyword
    if any(keyword in cleaned_text.lower() for keyword in student_keywords):
        return True
    
    # Check for student name 
    if extract_student_name(cleaned_text):
        return True
    
    # Check for student attributes
    if (extract_year(cleaned_text) or extract_semester(cleaned_text) or 
        extract_section(cleaned_text) or extract_batch(cleaned_text) or
        extract_type(cleaned_text)):
        return True
    
    return False

def is_faculty_query(text: str) -> bool:
    """Determine if a query is related to faculty"""
    faculty_keywords = ["faculty", "teacher", "professor", "lecturer", "instructor", "teaches", "teaching", "subject", "course"]
    
    # Check for faculty keywords
    if any(keyword in text.lower() for keyword in faculty_keywords):
        return True
    
    # Check for faculty name patterns
    if extract_faculty_name(text):
        return True
    
    # Check for subject name patterns
    if extract_subject_name(text):
        return True
    
    return False
```

## Database Query Functions

### 1. Student Database Operations
```python
def get_student_by_register_number(reg_no: str) -> List[Dict[str, Any]]:
    """Get student details by register number"""
    db = DatabaseConnection()
    if db.connect():
        query = "SELECT * FROM student WHERE register_no = %s"
        params = (reg_no,)
        results = db.execute_query(query, params)
        
        # Handle leading zeros if no results found
        if not results and reg_no.startswith('0'):
            query = "SELECT * FROM student WHERE register_no = %s"
            params = (reg_no.lstrip('0'),)
            results = db.execute_query(query, params)
        
        db.disconnect()
        return results
    return []
```
Additional student operations:
- `get_student_by_name(name)` - Lookup by name
- `get_students_by_year(year)` - Filter by year
- `get_students_by_semester(semester)` - Filter by semester
- `get_students_by_section(section)` - Filter by section
- `get_students_by_batch(batch)` - Filter by batch
- `get_students_by_type(type_str)` - Filter by degree type

### 2. Faculty Database Operations
```python
def get_faculty_by_subject(subject: str) -> List[Dict[str, Any]]:
    """Get faculty details by subject"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        # Create search pattern
        subject_pattern = f"%{subject}%"
        
        # Special case handling
        if re.search(r'computer\s+networks?', subject, re.IGNORECASE):
            subject_pattern = "%Computer Network%"
            logger.info(f"Special handling for Computer Networks, using pattern: {subject_pattern}")
        
        logger.info(f"Searching for faculty teaching subject matching: {subject_pattern}")
        
        query = """
        SELECT f.id, f.name, f.email, f.role, c.code as subject_code, c.name as subject_name
        FROM faculty f
        JOIN faculty_course fc ON f.id = fc.faculty_id
        JOIN course c ON fc.course_id = c.code
        WHERE c.name LIKE %s
        """
        
        cursor.execute(query, (subject_pattern,))
        results = cursor.fetchall()
        
        logger.info(f"Found {len(results)} faculty members teaching {subject}")
        
        # Try more flexible search if no results
        if not results:
            # Use just first two words of subject
            flexible_pattern = f"%{' '.join(subject.split()[:2])}%"
            logger.info(f"No results found, trying with more flexible pattern: {flexible_pattern}")
            
            cursor.execute(query, (flexible_pattern,))
            results = cursor.fetchall()
            logger.info(f"Found {len(results)} faculty members with flexible pattern")
        
        cursor.close()
        conn.close()
        
        return results
    except Exception as e:
        logger.error(f"Error fetching faculty for subject: {e}")
        return []
```

Additional faculty operation:
- `get_subjects_by_faculty(faculty_name)` - Get courses taught by a faculty member

### 3. Course Database Operations
```python
def get_course_by_code(course_code):
    """Get course details by course code"""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    
    # Normalize course code (remove spaces, uppercase)
    normalized_code = course_code.replace(" ", "").upper()
    
    cursor.execute("SELECT * FROM course WHERE code = %s", (normalized_code,))
    courses = cursor.fetchall()
    
    conn.close()
    return courses
```
Additional course operations:
- `get_course_by_name(course_name)` - Lookup by name
- `get_courses_by_semester(semester)` - Filter by semester
- `get_courses_by_credits(credits)` - Filter by credit value
- `get_courses_by_type(course_type)` - Filter by course type

## Formatting Functions

### 1. Student Formatting
```python
def format_student_details(student: Dict[str, Any]) -> str:
    """Format student details for display"""
    details = (
        f"Register No: {student.get('register_no')} "
        f"Name: {student.get('name')} "
        f"Year: {student.get('year')} "
        f"Semester: {student.get('semester')} "
        f"Section: {student.get('section')} "
    )
    
    # Add optional fields if present
    if student.get('batch'):
        details += f"Batch: {student.get('batch')} "
    if student.get('type'):
        details += f"Type: {student.get('type')}"
    
    return details.strip()
```

### 2. Faculty Formatting
```python
def format_faculty_details(faculty: Dict[str, Any]) -> str:
    """Format faculty details for display"""
    return (
        f"Name: {faculty.get('name')} "
        f"Email: {faculty.get('email')} "
        f"Subject: {faculty.get('subject_name')} ({faculty.get('subject_code')})"
    )
```

### 3. Course Formatting
```python
def format_course_details(course):
    """Format course details for display"""
    return f"""
Course: {course['code']} - {course['name']}
Credits: {course['credits']}
Type: {course['type']}
Semester: {course['semester']}
Course Outcomes: {course['no_of_co']}
"""
```

## Error Handling

### Database Connection Errors
```python
try:
    conn = mysql.connector.connect(
        host="localhost",
        user="root", 
        password="", 
        database="oat"
    )
    return conn
except Exception as e:
    logging.error(f"Database connection error: {str(e)}")
    raise
```

### Query Execution Errors
```python
try:
    if not self.connection or not self.connection.is_connected():
        self.connect()
    
    self.cursor.execute(query, params)
    results = self.cursor.fetchall()
    
except mysql.connector.Error as err:
    logger.error(f"Query execution error: {err}")
    logger.error(f"Query: {query}")
    logger.error(f"Params: {params}")
```

### Data Extraction Errors
```python
# With thorough logging
logger.info(f"Extracted faculty name: '{faculty_name}' from query: '{text}'")
logger.info(f"Could not extract faculty name from: '{text}'")
```

## Example Usage

### 1. Student Data Retrieval
```python
# Get student details by register number
register_no = extract_register_number("Show me information about 21CS1001")
if register_no:
    student_data = get_student_by_register_number(register_no)
    if student_data:
        student_details = format_student_details(student_data[0])
        print(student_details)
    else:
        print(f"No student found with register number {register_no}")
```

### 2. Faculty Query
```python
# Find faculty teaching a subject
subject_name = extract_subject_name("Who teaches Database Management Systems?")
if subject_name:
    faculty_list = get_faculty_by_subject(subject_name)
    if faculty_list:
        for faculty in faculty_list:
            print(format_faculty_details(faculty))
    else:
        print(f"No faculty found teaching {subject_name}")
```

### 3. Course Information
```python
# Get course information by code
course_code = extract_course_code("Tell me about CS301")
if course_code:
    course_data = get_course_by_code(course_code)
    if course_data:
        print(format_course_details(course_data[0]))
    else:
        print(f"Course {course_code} not found")
```

### 4. Query Classification
```python
# Determine query type and route appropriately
query_type, confidence = classify_query("What subjects does Dr. Smith teach?")
if query_type == "faculty" and confidence > 0.7:
    faculty_name = extract_faculty_name("What subjects does Dr. Smith teach?")
    if faculty_name:
        subjects = get_subjects_by_faculty(faculty_name)
        # Process and display results...
```

## Best Practices for Maintenance

1. **Pattern Updates**: When adding new extraction patterns, ensure they don't conflict with existing ones and test with a variety of input formats

2. **Database Schema Alignment**: When the database schema changes, update relevant functions to maintain compatibility

3. **Error Handling**: Maintain robust error handling for all database operations

4. **Connection Management**: Use the DatabaseConnection class for more complex operations requiring multiple queries

5. **Query Optimization**: For performance-critical operations, analyze and optimize SQL queries 