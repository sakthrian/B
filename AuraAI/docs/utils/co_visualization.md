# CO Visualization Utility

## Overview
The CO Visualization utility is a component of the ObeAI™ system that generates comprehensive visual representations of Course Outcome (CO) attainment data. The module creates advanced charts, visualizations, and PDF reports that help faculty and administrators analyze and interpret CO attainment metrics across different course sections.

## File Location
`actions/utils/co_visualization.py`

## Dependencies
```python
import os
import logging
import time
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
plt.ioff()
import numpy as np
from fpdf import FPDF
from typing import Dict, Any, List, Optional, Tuple
import mysql.connector
```

## Constants
```python
PDF_DIR = "pdf_reports"
PDF_WEB_PATH = "/B/AuraAI/pdf_reports"

# Ensure PDF directory exists
os.makedirs(PDF_DIR, exist_ok=True)
os.makedirs(os.path.join(PDF_DIR, "temp"), exist_ok=True)
```

## Main Functions

### 1. get_db_connection() - Database Connection
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
Establishes a connection to the MySQL database.

### 2. fetch_co_attainment_data() - Data Retrieval
```python
def fetch_co_attainment_data(course_name: str) -> Dict[str, Any]:
    """Fetch CO attainment data for a given course name"""
```
Retrieves comprehensive CO attainment data for a specified course, including:
- Course details (code, name, semester, credits)
- Faculty course assignments
- Course outcome definitions
- Attainment data across multiple assessment components

#### Parameters:
- `course_name`: Name of the course (partial match supported)

#### Returns:
- Dictionary with course details and attainment data

### 3. create_co_attainment_charts() - Chart Generation
```python
def create_co_attainment_charts(data: Dict[str, Any]) -> Tuple[List[str], str]:
    """
    Create charts for CO attainment visualization
    
    Returns:
        Tuple containing a list of image file paths and a summary text
    """
```
Generates visual charts for CO attainment data, including:
- Bar chart of average CO attainment across sections
- Comparative chart showing different attainment components (CIA, SE, DA, IA, CA)

#### Parameters:
- `data`: Dictionary containing course and CO attainment data

#### Returns:
- List of temporary image file paths and summary text

### 4. generate_co_attainment_pdf() - PDF Report Creation
```python
def generate_co_attainment_pdf(data: Dict[str, Any], chart_files: List[str], summary: str) -> str:
    """Generate PDF report for CO attainment with visualizations"""
```
Creates a professionally formatted PDF report with:
- Course information
- Attainment summary and analysis
- Visualization charts
- Detailed attainment data tables by section
- Component explanations

#### Parameters:
- `data`: Dictionary containing course and CO attainment data
- `chart_files`: List of temporary chart image files to include
- `summary`: Text summary of attainment analysis

#### Returns:
- Web-accessible path to the generated PDF file

### 5. generate_co_attainment_visualization() - Main Orchestrator
```python
def generate_co_attainment_visualization(course_name: str) -> Dict[str, Any]:
    """Main function to generate CO attainment visualization"""
```
Main entry point that orchestrates the entire visualization process:
1. Fetches data from the database
2. Creates charts and visualizations
3. Generates the PDF report

#### Parameters:
- `course_name`: Name of the course to visualize

#### Returns:
- Dictionary with success status, course name, PDF URL, and summary

## PDF Report Structure

### 1. Header Section
The report includes a professionally designed header with:
- System title "Outcome Attainment Tool"
- Report title "CO Attainment Report"
- Page numbering and generation date

### 2. Course Information
```python
# Course information box
pdf.set_fill_color(240, 240, 240)  # Light gray background
pdf.set_draw_color(40, 70, 140)    # Blue border
pdf.set_line_width(0.5)
pdf.rect(10, 30, 190, 40, 'DF')
```
Displays course details including:
- Course name
- Course code
- Semester
- Credits

### 3. Summary Section
```python
# Summary section with highlighting
pdf.set_fill_color(40, 70, 140)  # Blue background
pdf.set_text_color(255, 255, 255)  # White text
pdf.set_font("Arial", "B", 12)
pdf.cell(0, 10, "SUMMARY OF ATTAINMENT", 0, 1, "L", True)
```
Provides an analysis of attainment results:
- CO-wise attainment values
- Highest attainment CO (highlighted in green)
- Lowest attainment CO (highlighted in red)

### 4. Visualization Section
```python
# Visualization section heading
pdf.set_fill_color(40, 70, 140)  # Blue background
pdf.set_text_color(255, 255, 255)  # White text
pdf.set_font("Arial", "B", 12)
pdf.cell(0, 10, "VISUALIZATION OF ATTAINMENT", 0, 1, "L", True)
```
Includes:
- CO Attainment Overview chart
- Component-wise CO Attainment comparison chart

### 5. Detailed Data Tables
```python
# Detailed data section
pdf.set_fill_color(40, 70, 140)  # Blue background
pdf.set_text_color(255, 255, 255)  # White text
pdf.set_font("Arial", "B", 12)
pdf.cell(190, 10, "DETAILED ATTAINMENT DATA", 0, 1, "L", True)
```
Section-wise tables showing:
- CO numbers
- CIA attainment
- SE attainment
- DA attainment
- IA attainment
- CA attainment (with highlighting for highest/lowest)

### 6. Component Explanations
```python
# Component explanations box
pdf.set_fill_color(245, 245, 245)  # Very light gray
pdf.set_draw_color(40, 70, 140)    # Blue border
pdf.rect(10, pdf.get_y(), 190, box_height, 'DF')
```
Legend explaining assessment components:
- CIA: Continuous Internal Assessment
- SE: Semester Exam
- DA: Direct Assessment
- IA: Indirect Assessment
- CA: Course Attainment (Overall)

## Chart Generation

### 1. Average CO Attainment Chart
```python
# Plot bar chart of average attainment with enhanced styling
x = np.arange(len(co_numbers))
bars = plt.bar(x, [avg_attainment[co] for co in co_numbers], width=0.6, 
         color='skyblue', edgecolor='navy', linewidth=1.5)
```
Bar chart showing:
- Average attainment level for each CO
- Value labels on top of each bar
- Professional styling with grid lines

### 2. Component-wise Comparison Chart
```python
# Multiple bar chart comparing different assessment components
bar_width = 0.15
index = np.arange(len(co_numbers))
colors = ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd']
for i, (comp_name, color) in enumerate(zip(components.keys(), colors)):
    bars = plt.bar(index + i*bar_width - 0.3, 
             [component_avgs[comp_name][co] for co in co_numbers], 
              bar_width, 
              label=comp_name, 
              color=color,
              edgecolor='black',
              linewidth=0.8)
```
Multi-bar chart comparing:
- CIA attainment for each CO
- SE attainment for each CO
- DA attainment for each CO
- IA attainment for each CO
- CA attainment for each CO

## Error Handling

### 1. Database Connection Errors
```python
except Exception as e:
    logging.error(f"Database connection error: {str(e)}")
    raise
```

### 2. Data Retrieval Errors
```python
if not course:
    return {
        'success': False,
        'error': f"Course '{course_name}' not found"
    }
```

### 3. Chart Creation Errors
```python
except Exception as e:
    logger.error(f"Error creating charts: {str(e)}")
    import traceback
    logger.error(traceback.format_exc())
```

### 4. PDF Generation Errors
```python
try:
    # Save PDF
    pdf.output(filepath)
    logger.info(f"Generated CO attainment PDF report at {filepath}")
    
    # Verify file was created
    if os.path.exists(filepath) and os.path.getsize(filepath) > 0:
        logger.info(f"Verified PDF file: {filepath}, size: {os.path.getsize(filepath)} bytes")
    else:
        logger.error(f"PDF file not created or empty: {filepath}")
        return ""
except Exception as e:
    logger.error(f"Error generating PDF: {str(e)}")
```

## Color Scheme

The visualization system uses a consistent color scheme:
- Primary color: Dark blue `(40, 70, 140)`
- Section headers: White text on dark blue background
- Highest attainment: Green `(0, 128, 0)`
- Lowest attainment: Red `(255, 0, 0)`
- Chart colors: 
  - Bar chart: Sky blue with navy border
  - Component chart: Varied color palette `['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd']`

## Example Usage

```python
from actions.utils.co_visualization import generate_co_attainment_visualization

result = generate_co_attainment_visualization("Database Management Systems")

if result['success']:
    pdf_url = result['pdf_url']
    print(f"Generated PDF report available at: {pdf_url}")
    print("Summary:")
    print(result['summary'])
else:
    print(f"Error: {result['error']}")
```

## Best Practices for Maintenance

1. **Database Schema Alignment**: Ensure SQL queries match the database schema, particularly the attainment tables

2. **Chart Styling**: When modifying chart styles, test the output visually to ensure readability and professional appearance

3. **PDF Layout**: When adding content to PDF, adjust positioning carefully and test with various data volumes

4. **File Management**: Maintain proper handling of temporary files and ensure they're cleaned up after use

5. **Error Handling**: Keep comprehensive error logging for debugging across the entire visualization pipeline 