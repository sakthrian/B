# PDF Generator Utility

## Overview
The PDF Generator utility provides tools for creating professional PDF reports for the ObeAI™ system. It generates visually appealing, structured documents for student lists, faculty information, and course data, with consistent styling and formatting for improved readability and presentation.

## File Location
`actions/utils/pdf_generator.py`

## Dependencies
```python
import os
import logging
import time
from fpdf import FPDF
from typing import List, Dict, Any, Optional
```

## Core Classes

### 1. ModernPDF Class
```python
class ModernPDF(FPDF):
    """Modern PDF class with improved styling"""
    
    def __init__(self, title="ObeAI Report"):
        super().__init__()
        self.title = title
```

This class extends the FPDF library with custom styling, including:
- Professional blue header with white text
- Custom footer with page numbers and generation date
- Consistent styling throughout the document

#### Header Method
```python
def header(self):
    # Set background color for header
    self.set_fill_color(40, 70, 140)  
    self.rect(0, 0, 210, 25, 'F')
    
    # Add system name
    self.set_font("Arial", "B", 18)
    self.set_text_color(255, 255, 255) 
    self.cell(0, 18, "Outcome Attainment Tool", 0, 0, "L", False)
    
    # Add report title on right side
    self.set_font("Arial", "B", 14)
    self.cell(0, 18, self.title, 0, 1, "R", False)
    
    # Space after header
    self.ln(10)
```

#### Footer Method
```python
def footer(self):
    # Position at 1.5 cm from bottom
    self.set_y(-15)
    
    self.set_font('Arial', 'I', 8)
    
    self.set_text_color(128, 128, 128)
    
    self.cell(0, 10, f'Page {self.page_no()}/{{nb}}', 0, 0, 'C')
    # Add date
    self.cell(0, 10, f'Generated on {time.strftime("%Y-%m-%d")}', 0, 0, 'R')
```

### 2. PDFGenerator Class
```python
class PDFGenerator:
    """
    Class for generating PDF reports from query results
    """
    
    def __init__(self, title: str = "ObeAI Report"):
        """Initialize the PDF generator with a title"""
        self.title = title
```

The main class responsible for generating various types of reports, including:
- Student reports (by batch, year, section, etc.)
- Faculty reports (by subject, department)
- Course reports (by code, semester, credits)

## PDF Storage and Access

```python
PDF_DIR = "pdf_reports"
PDF_WEB_PATH = "/B/AuraAI/pdf_reports"
os.makedirs(PDF_DIR, exist_ok=True)
```

PDF reports are:
- Saved in the "pdf_reports" directory (created automatically if not existing)
- Accessible via web path "/B/AuraAI/pdf_reports"

## Core Methods

### 1. _save_pdf Method
```python
def _save_pdf(self, filename: str) -> str:
    """Save PDF and return web URL"""
    filepath = os.path.join(PDF_DIR, filename)
    
    try:
        # Ensure directory exists
        os.makedirs(os.path.dirname(filepath), exist_ok=True)
        
        # Save PDF
        self.pdf.output(filepath)
        logger.info(f"Generated PDF report at {filepath}")
        
        # Verify file exists and has content
        if os.path.exists(filepath) and os.path.getsize(filepath) > 0:
            logger.info(f"Verified PDF file: {filepath}, size: {os.path.getsize(filepath)} bytes")
            return f"{PDF_WEB_PATH}/{filename}"
        else:
            logger.error(f"PDF file not created or empty: {filepath}")
            return ""
    except Exception as e:
        logger.error(f"Error generating PDF: {str(e)}")
        import traceback
        logger.error(traceback.format_exc())
        return ""
```
Handles saving the generated PDF and returning a web-accessible URL.

### 2. generate_student_report Method
```python
def generate_student_report(self, students: List[Dict[str, Any]], query_type: str, query_value: str) -> str:
    """
    Generate a PDF report for student data
    
    Args:
        students: List of student dictionaries
        query_type: Type of query (e.g., "batch", "year", "section")
        query_value: Value of the query
            
    Returns:
        Web URL to the generated PDF file
    """
```
Creates a comprehensive report for student data including:
- Title page with query information
- Information box with query details
- Table of all matching students with register number, name, year, etc.
- Alternating row colors for improved readability
- Automatic pagination with repeating headers
- Summary information box

### 3. generate_faculty_report Method
```python
def generate_faculty_report(self, faculty_data: List[Dict[str, Any]], query_type: str, query_value: str) -> str:
    """
    Generate a PDF report for faculty data
    
    Args:
        faculty_data: List of faculty dictionaries
        query_type: Type of query (e.g., "subject", "faculty")
        query_value: Value of the query
            
    Returns:
        Web URL to the generated PDF file
    """
```
Creates a formatted report containing faculty information, including:
- Faculty name and contact details
- Subject assignments
- Department information

### 4. generate_course_report Method
```python
def generate_course_report(self, courses: List[Dict[str, Any]], query_type: str, query_value: str) -> str:
    """
    Generate a PDF report for course data
    
    Args:
        courses: List of course dictionaries
        query_type: Type of query (e.g., "semester", "credits", "code")
        query_value: Value of the query
            
    Returns:
        Web URL to the generated PDF file
    """
```
Produces a detailed course report with:
- Course codes and names
- Credit information
- Semester details
- Number of Course Outcomes (COs)
- Query summary information

## Report Formatting Features

### Document Styling
- Blue header bar with white text (RGB: 40, 70, 140)
- Gray footer with page numbers and date
- Information boxes with light gray backgrounds
- Alternating table row colors for readability
- Proper alignment for different content types

### Table Features
- Column widths automatically adjusted for content
- Text truncation for long strings with ellipsis
- Centered alignment for numeric data
- Left alignment for text data
- Table headers with distinct background color
- Repeating headers on page breaks

### Document Organization
- Title section with query details
- Data section with tabular representation
- Summary section with query information
- Automatic page breaks with header/footer continuation

## Error Handling

### File Operation Errors
```python
try:
    # Ensure directory exists
    os.makedirs(os.path.dirname(filepath), exist_ok=True)
    
    # Save PDF
    self.pdf.output(filepath)
    logger.info(f"Generated PDF report at {filepath}")
    
    # Verify file exists and has content
    if os.path.exists(filepath) and os.path.getsize(filepath) > 0:
        logger.info(f"Verified PDF file: {filepath}, size: {os.path.getsize(filepath)} bytes")
        return f"{PDF_WEB_PATH}/{filename}"
    else:
        logger.error(f"PDF file not created or empty: {filepath}")
        return ""
except Exception as e:
    logger.error(f"Error generating PDF: {str(e)}")
    import traceback
    logger.error(traceback.format_exc())
    return ""
```
Comprehensive error handling for file operations including:
- Directory creation errors
- PDF generation failures
- Output validation
- Detailed logging with traceback

### Empty Results Handling
```python
if not students:  # or not faculty_data or not courses
    # Display a message in the PDF for no results
    self.pdf.ln(20)
    self.pdf.set_fill_color(40, 70, 140)
    self.pdf.set_text_color(255, 255, 255)
    self.pdf.set_font("Arial", "B", 12)
    self.pdf.cell(190, 10, "SEARCH RESULTS", 0, 1, "L", True)
    
    # No results message
    self.pdf.set_text_color(0, 0, 0)
    self.pdf.set_font("Arial", "", 10)
    self.pdf.ln(5)
    self.pdf.cell(0, 10, "No students found matching the criteria.", 0, 1, "C")
```
Graceful handling of empty result sets with informative messages in the PDF.

## Example Usage

### 1. Student Report Generation
```python
# Create PDF generator
pdf_generator = PDFGenerator()

# Get student data from database
students = get_students_by_year(3)

# Generate PDF report
report_url = pdf_generator.generate_student_report(
    students,
    query_type="year",
    query_value="3"
)

# Provide the URL to the user
return f"I've generated a PDF report for year 3 students. You can view it here: {report_url}"
```

### 2. Faculty Report Generation
```python
# Create PDF generator
pdf_generator = PDFGenerator()

# Get faculty data from database
faculty_data = get_faculty_by_subject("Database Management Systems")

# Generate PDF report
report_url = pdf_generator.generate_faculty_report(
    faculty_data,
    query_type="subject",
    query_value="Database Management Systems"
)

# Provide the URL to the user
return f"Here's the faculty report for Database Management Systems: {report_url}"
```

### 3. Course Report Generation
```python
# Create PDF generator
pdf_generator = PDFGenerator()

# Get course data from database
courses = get_courses_by_semester(5)

# Generate PDF report
report_url = pdf_generator.generate_course_report(
    courses,
    query_type="semester",
    query_value="5"
)

# Provide the URL to the user
return f"I've created a comprehensive report of all semester 5 courses: {report_url}"
```

## Integration with Other Components

### 1. Database Integration
```python
from actions.utils.database import get_students_by_type

def generate_student_type_report(self, type_str):
    # Get student data from database
    students = get_students_by_type(type_str)
    
    # Generate PDF report
    pdf_generator = PDFGenerator()
    return pdf_generator.generate_student_report(
        students,
        query_type="type",
        query_value=type_str
    )
```

### 2. Handler Integration
```python
# In student_handler.py
from actions.utils.pdf_generator import PDFGenerator

def handle_student_report_request(self, year):
    students = self.get_students_by_year(year)
    pdf_generator = PDFGenerator()
    return pdf_generator.generate_student_report(
        students,
        query_type="year",
        query_value=year
    )
```

## Best Practices for Maintenance

1. **File Management**: Keep the PDF_DIR path synchronized with web server configurations

2. **Styling Consistency**: Maintain the same color scheme and font settings across reports

3. **Error Handling**: Continue robust logging for all PDF generation operations

4. **Content Formatting**: For long text fields, use consistent truncation patterns

5. **Performance**: For large datasets, consider pagination or split reports

