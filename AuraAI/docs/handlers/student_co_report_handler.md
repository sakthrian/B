# Student CO Report Handler

## Overview
The Student CO Report Handler generates comprehensive Course Outcome (CO) attainment reports for students in the ObeAI™ system. It analyzes test performance, calculates CO attainment levels, and creates visually appealing PDF reports with detailed analytics and recommendations.

## File Location
`actions/handlers/student_co_report_handler.py`

## Dependencies
```python
import os
import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns
from fpdf import FPDF
import mysql.connector
from decimal import Decimal
from datetime import datetime
from actions.utils.database import get_db_connection
```

## Main Class
```python
class StudentCOReportHandler:
    """Handler for generating student Course Outcome attainment reports"""
```

## Main Methods

### 1. generate_student_co_report() - Main Report Generation
```python
def generate_student_co_report(self, register_no):
    """Generate a CO attainment report for a specific student"""
```
This is the primary method that orchestrates the report generation process by:
1. Retrieving student information from the database
2. Getting the student's test performance data
3. Calculating CO attainment across all assessments
4. Analyzing knowledge level performance using Bloom's taxonomy
5. Generating a formatted PDF report 

#### Parameters:
- `register_no`: Student's registration number

#### Returns:
- Dictionary with status, message, and PDF path

### 2. _generate_pdf() - PDF Creation Helper
```python
def _generate_pdf(self, student_info, course_info, test_performance, best_two_avg_obtained, 
                 best_two_avg_total, best_two_avg_percentage, co_attainment, 
                 overall_co_percentage, knowledge_level_performance, total_questions,
                 total_max_marks, total_obtained, overall_knowledge_percentage):
    """Generate a PDF report with student CO attainment data with modern design"""
```
Internal method that creates a professionally formatted PDF report containing:
1. Student information
2. Course details
3. Test performance summary
4. CO attainment levels
5. Bloom's taxonomy analysis
6. Personalized recommendations

#### Returns:
- Web-accessible path to the generated PDF file

### 3. debug_student_co_data() - Diagnostic Method
```python
def debug_student_co_data(self, register_no):
    """Debug method to diagnose CO attainment calculation issues for a student"""
```
Development and troubleshooting method that provides detailed diagnostic output about:
1. Tests taken by the student
2. Questions in each test
3. CO mappings for each question
4. Mark distribution
5. Course outcome definitions

#### Parameters:
- `register_no`: Student's registration number

#### Returns:
- Boolean indicating success/failure

## Data Collection Process

### 1. Student Information Retrieval
```python
# Get student information
student_query = """
    SELECT register_no, name, year, semester, section, batch
    FROM student
    WHERE register_no = %s
"""
cursor.execute(student_query, (register_no,))
student_info = cursor.fetchone()
```

### 2. Test Data Collection
```python
# Get tests the student has taken
test_query = """
    SELECT DISTINCT t.id, t.test_no, t.total_mark, t.test_date, t.fc_id
    FROM test t
    JOIN mark m ON t.id = m.test_id
    WHERE m.student_id = %s
    ORDER BY t.test_no
"""
cursor.execute(test_query, (register_no,))
test_data = cursor.fetchall()
```

### 3. Course Information Retrieval
```python
# Get course information from fc_id (faculty course id)
course_query = """
    SELECT c.code AS course_code, c.name AS course_name
    FROM faculty_course fc
    JOIN course c ON fc.course_id = c.code
    WHERE fc.id = %s
"""
cursor.execute(course_query, (test_data[0]['fc_id'],))
course_info = cursor.fetchone()
```

### 4. Mark Collection and Processing
```python
# Get student's marks for each test
test_performance = []
for test in test_data:
    marks_query = """
        SELECT SUM(m.obtained_mark) as obtained
        FROM mark m
        WHERE m.student_id = %s AND m.test_id = %s
    """
    cursor.execute(marks_query, (register_no, test['id']))
    marks_result = cursor.fetchone()
    
    # Check if student was absent (marks < 0)
    is_absent = False
    obtained = marks_result['obtained'] if marks_result['obtained'] is not None else 0
    if float(obtained) < 0:
        is_absent = True
        obtained = 0  # Treat absent as 0 for calculations
```

## CO Attainment Calculation

### 1. Course Outcome Retrieval
```python
# Get course outcomes from the database
co_list_query = """
    SELECT co.id, co.co_number, CONCAT('Course Outcome ', co.co_number) as description
    FROM course_outcome co
    WHERE co.course_id = %s
    ORDER BY co.co_number
"""
cursor.execute(co_list_query, (course_info['course_code'],))
course_outcomes = cursor.fetchall()

if not course_outcomes:
    # If no COs defined in database, create default ones
    course_outcomes = []
    for i in range(1, 5):
        course_outcomes.append({
            'id': i,
            'co_number': i,
            'description': f"Course Outcome {i}"
        })
```

### 2. Question-CO Mapping Retrieval
```python
# Find questions mapped to this CO 
co_questions_query = """
    SELECT q.id, q.max_mark
    FROM question q
    JOIN question_co qc ON q.id = qc.question_id
    WHERE qc.co_id = %s
"""

# Using the id field from course_outcome table to match with co_id in question_co
cursor.execute(co_questions_query, (co['id'],))
co_questions = cursor.fetchall()
```

### 3. CO Attainment Calculation
```python
# If questions are found, calculate attainment
if co_questions:
    question_ids = [q['id'] for q in co_questions]
    placeholders = ', '.join(['%s'] * len(question_ids))
    
    marks_query = f"""
        SELECT q.id, m.obtained_mark, q.max_mark
        FROM mark m
        JOIN question q ON m.question_id = q.id
        WHERE m.student_id = %s AND q.id IN ({placeholders})
    """
    
    cursor.execute(marks_query, [register_no] + question_ids)
    marks_data = cursor.fetchall()
    
    for mark in marks_data:
        if mark['obtained_mark'] is not None and mark['max_mark'] is not None:
            # Handle absent marks (stored as -1)
            if float(mark['obtained_mark']) < 0:
                obtained_marks += 0
            else:
                obtained_marks += float(mark['obtained_mark'])
            
            max_marks += float(mark['max_mark'])

# Calculate attainment percentage
if max_marks > 0:
    attainment_percentage = max(0, (obtained_marks / max_marks) * 100)
else:
    attainment_percentage = 0  # No data available

# Determine level based on percentage
level = "High" if attainment_percentage >= 70 else "Medium" if attainment_percentage >= 50 else "Low"
```

### 4. Knowledge Level Analysis
```python
# Calculate Knowledge Level (Bloom's Taxonomy) performance
knowledge_level_query = """
    SELECT 
        CASE
            WHEN q.knowledge_level IS NULL THEN 'Unknown'
            WHEN q.knowledge_level = '1' THEN 'Remember'
            WHEN q.knowledge_level = '2' THEN 'Understand'
            WHEN q.knowledge_level = '3' THEN 'Apply'
            WHEN q.knowledge_level = '4' THEN 'Analyze'
            WHEN q.knowledge_level = '5' THEN 'Evaluate'
            WHEN q.knowledge_level = '6' THEN 'Create'
            ELSE q.knowledge_level
        END as level_name,
        COUNT(q.id) as questions,
        SUM(q.max_mark) as max_marks, 
        SUM(CASE WHEN m.obtained_mark < 0 THEN 0 ELSE m.obtained_mark END) as obtained
    FROM question q
    JOIN mark m ON q.id = m.question_id
    WHERE m.student_id = %s
    GROUP BY level_name
    ORDER BY MIN(q.knowledge_level)
"""
cursor.execute(knowledge_level_query, (register_no,))
knowledge_levels = cursor.fetchall()
```

## PDF Report Generation

### 1. Report Directory Setup
```python
reports_dir = "../../reports/student_co"
os.makedirs(reports_dir, exist_ok=True)
```

### 2. Color Scheme Definition
```python
# Define color scheme for modern design
primary_color = (41, 65, 122)  # Dark blue
secondary_color = (65, 105, 225)  # Royal blue
accent_color = (70, 130, 180)  # Steel blue
text_color = (50, 50, 50)  # Dark gray
light_bg = (245, 245, 245)  # Light gray

# Status colors
high_color = (46, 139, 87)  # Sea green
medium_color = (255, 140, 0)  # Orange
low_color = (220, 20, 60)  # Crimson
```

### 3. PDF Structure Creation
```python
# Initialize PDF
pdf = FPDF()
pdf.add_page()
pdf.set_auto_page_break(auto=True, margin=15)

# Set default font
pdf.set_font("Arial", "", 10)
pdf.set_text_color(text_color[0], text_color[1], text_color[2])

# Create header
pdf.set_fill_color(primary_color[0], primary_color[1], primary_color[2])
pdf.rect(0, 0, 210, 30, 'F')
pdf.set_y(10)
pdf.set_text_color(255, 255, 255)
pdf.set_font("Arial", "B", 18)
pdf.cell(0, 10, "COURSE OUTCOME ATTAINMENT REPORT", 0, 1, "C")
```

### 4. Visual Components

#### Student Information Section
```python
pdf.set_y(40)
pdf.set_text_color(text_color[0], text_color[1], text_color[2])
pdf.set_font("Arial", "B", 14)
pdf.cell(0, 10, "Student Information", 0, 1, "L")

# Create styled box
pdf.set_fill_color(light_bg[0], light_bg[1], light_bg[2])
pdf.set_draw_color(secondary_color[0], secondary_color[1], secondary_color[2])
pdf.rect(10, 52, 190, 35, 'DF')
```

#### Test Performance Table
```python
# Test performance header
pdf.set_y(95)
pdf.set_font("Arial", "B", 14)
pdf.cell(0, 10, "Test Performance Summary", 0, 1, "L")

# Table column setup
col_width = 47.5
row_height = 10

# Create header row
pdf.set_fill_color(primary_color[0], primary_color[1], primary_color[2])
pdf.set_text_color(255, 255, 255)
pdf.set_font("Arial", "B", 10)
pdf.set_x(10)
pdf.cell(col_width, row_height, "Test No", 1, 0, "C", 1)
pdf.cell(col_width, row_height, "Total Marks", 1, 0, "C", 1)
pdf.cell(col_width, row_height, "Obtained", 1, 0, "C", 1)
pdf.cell(col_width, row_height, "Percentage", 1, 1, "C", 1)
```

#### CO Attainment Table
```python
# CO attainment table
co_col_width = [20, 100, 35, 35]  

# Create header row
pdf.set_fill_color(primary_color[0], primary_color[1], primary_color[2])
pdf.set_text_color(255, 255, 255)
pdf.set_font("Arial", "B", 10)
pdf.set_x(10)
pdf.cell(co_col_width[0], row_height, "CO No", 1, 0, "C", 1)
pdf.cell(co_col_width[1], row_height, "Description", 1, 0, "C", 1)
pdf.cell(co_col_width[2], row_height, "Attainment %", 1, 0, "C", 1)
pdf.cell(co_col_width[3], row_height, "Level", 1, 1, "C", 1)
```

### 5. Analysis & Recommendations Section
```python
# Analysis & Recommendations
pdf.ln(10)
pdf.set_text_color(text_color[0], text_color[1], text_color[2])
pdf.set_font("Arial", "B", 14)
pdf.cell(0, 10, "Analysis & Recommendations", 0, 1, "L")

# Create styled box
pdf.set_fill_color(light_bg[0], light_bg[1], light_bg[2])
pdf.set_draw_color(secondary_color[0], secondary_color[1], secondary_color[2])
pdf.rect(10, pdf.get_y(), 190, 90, 'DF')

# Recommendations based on performance
if strongest_co:
    pdf.set_fill_color(high_color[0], high_color[1], high_color[2])
    pdf.rect(15, pdf.get_y() + 2, 3, 3, 'F')
    
    pdf.set_text_color(high_color[0], high_color[1], high_color[2])
    pdf.cell(0, 6, f"Strongest in {strongest_co['co_no']} ({strongest_co['attainment']:.1f}%)", 0, 1, "L")
```

## Error Handling

### 1. Student Not Found
```python
if not student_info:
    return {"status": "error", "message": f"Student with register number {register_no} not found."}
```

### 2. No Test Data Available
```python
if not test_data:
    return {"status": "error", "message": f"No test data found for student {register_no}."}
```

### 3. Missing Course Information
```python
if not course_info:
    return {"status": "error", "message": f"Course information not found for the given tests."}
```

### 4. Missing CO Attainment Data
```python
if not co_attainment:
    return {"status": "error", "message": "Could not generate CO attainment data."}
```

### 5. General Exception Handling
```python
try:
    # Report generation code
except Exception as e:
    import traceback
    traceback.print_exc()
    return {"status": "error", "message": f"Error generating report: {str(e)}"}
```

## Special Features

### 1. Absent Handling
The system handles student absences (marked as negative values in the database) by treating them as zero for calculation purposes while marking them as "ABSENT" in the report.
```python
# Check if student was absent (marks < 0)
is_absent = False
obtained = marks_result['obtained'] if marks_result['obtained'] is not None else 0
if float(obtained) < 0:
    is_absent = True
    obtained = 0  # Treat absent as 0 for calculations
```

### 2. Bloom's Taxonomy Analysis
The system categorizes and analyzes performance across the six levels of Bloom's taxonomy (Remember, Understand, Apply, Analyze, Evaluate, Create).
```python
CASE
    WHEN q.knowledge_level IS NULL THEN 'Unknown'
    WHEN q.knowledge_level = '1' THEN 'Remember'
    WHEN q.knowledge_level = '2' THEN 'Understand'
    WHEN q.knowledge_level = '3' THEN 'Apply'
    WHEN q.knowledge_level = '4' THEN 'Analyze'
    WHEN q.knowledge_level = '5' THEN 'Evaluate'
    WHEN q.knowledge_level = '6' THEN 'Create'
    ELSE q.knowledge_level
END as level_name
```

### 3. Best 2 Average Calculation
The system calculates the average of the best two test performances for a fairer assessment.
```python
# Calculate best 2 average
valid_tests = sorted(test_performance, key=lambda x: x['percentage'], reverse=True)
best_two = valid_tests[:2]

if len(best_two) >= 2:
    best_two_avg_obtained = sum(t['obtained'] for t in best_two) / 2
    best_two_avg_total = sum(t['total_marks'] for t in best_two) / 2
    best_two_avg_percentage = (best_two_avg_obtained / best_two_avg_total) * 100 if best_two_avg_total > 0 else 0
```

### 4. Dynamic Recommendations
The system provides personalized recommendations based on performance analysis.
```python
if strongest_level:
    level_number = {"Remember": 1, "Understand": 2, "Apply": 3, "Analyze": 4, "Evaluate": 5, "Create": 6}.get(strongest_level[0], 1)
    level_desc = {"Remember": "Knowledge Recall", "Understand": "Comprehension", "Apply": "Application", 
                "Analyze": "Analysis", "Evaluate": "Evaluation", "Create": "Creation"}.get(strongest_level[0], "Knowledge Recall")
    
    pdf.set_text_color(high_color[0], high_color[1], high_color[2])
    pdf.cell(0, 6, f"Strongest in {strongest_level[0]} (Level {level_number} - {level_desc}) questions ({strongest_level[1]:.1f}%)", 0, 1, "L")
```

## Example Usage

### 1. Generate CO Report
```python
from actions.handlers.student_co_report_handler import StudentCOReportHandler

handler = StudentCOReportHandler()
result = handler.generate_student_co_report("21CS1001")

if result["status"] == "success":
    print(f"Report generated: {result['pdf_path']}")
else:
    print(f"Error: {result['message']}")
```

### 2. Debug CO Data Issues
```python
handler = StudentCOReportHandler()
handler.debug_student_co_data("21CS1001")  # Will print detailed diagnostic information
```

## Best Practices for Maintenance

1. **Database Schema Alignment**: Ensure the SQL queries match the current database schema, particularly for the CO mapping tables

2. **PDF Layout Management**: When modifying PDF layout, carefully adjust positioning coordinates (x, y) to avoid overlapping elements

3. **Color Scheme**: Maintain the defined color scheme for consistency across reports

4. **Error Messages**: Provide specific error messages that help identify the exact issue (missing student, missing tests, etc.)

5. **Performance Optimization**: For large datasets, consider pagination or limiting the number of records processed at once 