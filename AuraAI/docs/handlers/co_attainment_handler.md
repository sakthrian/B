# CO Attainment Handler

## Overview
The CO Attainment Handler manages Course Outcome (CO) attainment analysis and visualization in the ObeAI™ system. It processes attainment calculations, generates comparative analyses, and creates visual reports for course outcomes.

## File Location
`actions/handlers/co_attainment_handler.py`

## Dependencies
```python
from typing import Any, Text, Dict, List
from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher
import logging
import mysql.connector
from mysql.connector import Error
import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns
from io import BytesIO
import base64
import re
import os
from datetime import datetime
from fpdf import FPDF  
```

## Main Class
```python
class ActionCompareCOAttainment(Action):
    """Handler for comparing Course Outcome attainment between different batches"""
```

## Core Methods

### 1. name()
```python
def name(self) -> Text:
    return "action_compare_co_attainment"
```
Returns the action name for Rasa identification.

### 2. Database Connection
```python
def _get_db_connection(self):
    """Establish database connection"""
    try:
        connection = mysql.connector.connect(
            host='localhost',
            database='oat',
            user='root',
            password=''
        )
        return connection
    except Error as e:
        logger.error(f"Error connecting to MySQL database: {e}")
        return None
```

### 3. Batch Extraction
```python
def _extract_batches(self, message):
    """Extract batch years from message, allowing spaces around the hyphen"""
    # Pattern to match YYYY-YYYY with optional spaces
    pattern = r'(\d{4}\s*-\s*\d{4})'
    return [b.replace(' ', '') for b in re.findall(pattern, message)]
```

### 4. Data Retrieval
```python
def _fetch_co_attainment_data(self, connection, batches=None):
    """Fetch CO attainment data from database"""
    try:
        cursor = connection.cursor(dictionary=True)
        
        query = """
            SELECT 
                batch,
                faculty_name,
                course_id,
                course_name,
                type,
                semester,
                co_number,
                ca as attainment_percentage
            FROM 
                co_attainment
            WHERE 
                co_number IS NOT NULL 
                AND ca IS NOT NULL
        """
        
        if batches:
            placeholders = ', '.join(['%s'] * len(batches))
            query += f" AND batch IN ({placeholders})"
            cursor.execute(query, batches)
        else:
            cursor.execute(query)
        
        results = cursor.fetchall()
        cursor.close()
        return results
        
    except Error as e:
        logger.error(f"Error fetching CO attainment data: {e}")
        return None
```

## Visualization Methods

### 1. Subject Plot Generation
```python
def _generate_subject_plot(self, df, subject_name):
    """Generate plot for a specific subject and return as bytes"""
    try:
        plt.style.use('ggplot')
        
        # Create a figure 
        fig, ax = plt.subplots(figsize=(12, 6.5))
        
        plt.subplots_adjust(top=0.88, bottom=0.15, left=0.12, right=0.85)
        
        # Get data for this subject
        subject_data = df[df['course_name'] == subject_name]
        plot_data = subject_data.pivot(index='batch', columns='co_number', values='attainment_percentage')
        
        # Create the bar chart
        bars = plot_data.plot(kind='bar', width=0.75, ax=ax, edgecolor='white', linewidth=0.7)
        
        # title and labels 
        plt.title(f'CO Attainment Comparison for {subject_name}', fontsize=14, fontweight='bold', pad=20)
        plt.xlabel('Batch', fontsize=12, labelpad=10)
        plt.ylabel('Attainment Value', fontsize=12, labelpad=10)
        
        # Add legend
        plt.legend(title='Course Outcomes', bbox_to_anchor=(1.04, 1), loc='upper left', 
                  title_fontsize=11, fontsize=10, frameon=True, facecolor='white', edgecolor='lightgray')
        
        # Rotate x-axis labels 
        plt.xticks(rotation=45, ha='right', fontsize=10)
        plt.yticks(fontsize=10)
        
        # Add grid
        plt.grid(True, axis='y', linestyle='--', alpha=0.6, color='gray')
        
        # Add value labels on top of bars 
        for container in ax.containers:
            ax.bar_label(container, fmt='%.2f', padding=3, fontsize=9, fontweight='bold')
        
        # Set y-axis limit
        max_value = plot_data.values.max()
        plt.ylim(0, max_value * 1.15)
        
        # Set spine properties
        for spine in ax.spines.values():
            spine.set_edgecolor('lightgray')
            spine.set_linewidth(0.8)
        
        # Save the plot to a BytesIO object
        buf = BytesIO()
        plt.savefig(buf, format='png', bbox_inches='tight', dpi=300)
        buf.seek(0)
        plt.close(fig)
        
        return buf.getvalue()
        
    except Exception as e:
        logger.error(f"Error generating plot for {subject_name}: {e}")
        return None
```

### 2. Density Plot Generation
```python
def _generate_density_plot(self, df, subject_name=None, co_number=None):
    """Generate density plot comparing CO attainment distribution across batches"""
    try:
        plt.style.use('ggplot')
        fig, ax = plt.subplots(figsize=(12, 6.5))
        plt.subplots_adjust(top=0.88, bottom=0.15, left=0.12, right=0.85)
        
        # Filter data based on parameters
        filtered_data = df.copy()
        if subject_name:
            filtered_data = filtered_data[filtered_data['course_name'] == subject_name]
        if co_number:
            filtered_data = filtered_data[filtered_data['co_number'] == co_number]
        
        # Check if we have enough data
        if filtered_data.empty or len(filtered_data['batch'].unique()) < 2:
            logger.warning(f"Not enough data for density plot for {subject_name if subject_name else 'all subjects'}")
            return None
        
        # Get unique batches (up to 3)
        batches = filtered_data['batch'].unique()[:3]
        
        # Plot density for each batch with different colors
        colors = ['royalblue', 'firebrick', 'forestgreen']
        for i, batch in enumerate(batches):
            batch_data = filtered_data[filtered_data['batch'] == batch]
            sns.kdeplot(
                data=batch_data, 
                x='attainment_percentage',
                color=colors[i % len(colors)],
                fill=True,
                alpha=0.4,
                label=f"Batch {batch}",
                linewidth=2.5
            )
        
        title_text = "Distribution of CO Attainment Values"
        if subject_name:
            title_text += f" for {subject_name}"
        if co_number:
            title_text += f" - CO{co_number}"
            
        plt.title(title_text, fontsize=14, fontweight='bold', pad=20)
        plt.xlabel('CO Attainment Value', fontsize=12, labelpad=10)
        plt.ylabel('Density', fontsize=12, labelpad=10)
        
        plt.grid(True, linestyle='--', alpha=0.6)
        plt.legend(fontsize=10)
        
        # Set spine properties
        for spine in ax.spines.values():
            spine.set_edgecolor('lightgray')
            spine.set_linewidth(0.8)
        
        plt.tight_layout()
        
        # Save plot
        buf = BytesIO()
        plt.savefig(buf, format='png', bbox_inches='tight', dpi=300)
        buf.seek(0)
        plt.close(fig)
        
        return buf.getvalue()
        
    except Exception as e:
        logger.error(f"Error generating density plot: {e}")
        import traceback
        logger.error(traceback.format_exc())
        return None
```

## PDF Report Generation

### 1. PDF Report Creation
```python
def _generate_pdf_report(self, df, batches):
    """Generate PDF report with comparisons using the common PDF generator."""
    try:
        # Import appropriate FPDF version
        try:
            from fpdf import FPDF2 as PDF
            use_fpdf2 = True
            logger.info("Using FPDF2 for better Unicode support")
        except ImportError:
            from fpdf import FPDF as PDF
            use_fpdf2 = False
            logger.info("Using standard FPDF - some Unicode characters may not display correctly")
        
        # Define custom PDF class
        class ModernPDF(PDF):
            def __init__(self):
                # Initialize with UTF-8 support if using FPDF2
                if use_fpdf2:
                    super().__init__(orientation='P', unit='mm', format='A4')
                else:
                    super().__init__()
            
            def header(self):
                # Create header with blue background
                self.set_fill_color(41, 128, 185)  
                self.rect(0, 0, 210, 15, 'F')
                
                # Add title
                self.set_font('Arial', 'B', 10)
                self.set_text_color(255, 255, 255)
                self.cell(0, 15, "AuraAI Analytics - CO Attainment Report", 0, 0, 'R')
                
                # Reset text color
                self.set_text_color(0, 0, 0)
                self.ln(20)
            
            def footer(self):
                # Position at 1.5 cm from bottom
                self.set_y(-15)
                self.set_font('Arial', 'I', 8)
                self.set_text_color(128, 128, 128)
                self.cell(0, 10, f'Page {self.page_no()}', 0, 0, 'C')
        
        # Generate timestamp for filenames
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        
        # Initialize PDF with styling
        batch_text = ', '.join(batches)
        pdf = ModernPDF()
        pdf.set_auto_page_break(auto=True, margin=15)
        
        # Color scheme for consistent styling
        title_color = (41, 128, 185)  # Blue
        subtitle_color = (52, 73, 94)  # Dark blue-gray
        text_color = (44, 62, 80)      # Very dark blue-gray
        highlight_color = (231, 76, 60) # Red
        
        # Add first page and title
        pdf.add_page()
        pdf.set_font("Arial", "B", 18)
        pdf.set_text_color(*title_color)
        pdf.cell(0, 10, f"CO Attainment Comparison", 0, 1, "L")
        pdf.set_font("Arial", "", 12)
        pdf.set_text_color(*subtitle_color)
        pdf.cell(0, 10, f"Batches: {batch_text}", 0, 1, "L")
        pdf.ln(5)
        
        # Add divider
        pdf.set_draw_color(*title_color)
        pdf.set_line_width(0.5)
        pdf.line(10, pdf.get_y(), 200, pdf.get_y())
        pdf.ln(10)
        
        # Overall summary section 
        pdf.set_font("Arial", "B", 14)
        pdf.set_text_color(*title_color)
        pdf.cell(0, 10, "OVERALL SUMMARY", 0, 1)
        pdf.ln(5)
        
        # Calculate and display batch averages
        batch_avg = df.groupby('batch')['attainment_percentage'].mean()
        pdf.set_font("Arial", "B", 12)
        pdf.set_text_color(*subtitle_color)
        pdf.cell(0, 10, "Batch Performance Overview:", 0, 1)
        pdf.set_font("Arial", "", 11)
        pdf.set_text_color(*text_color)
        
        # Find and highlight the best performing batch
        best_batch = batch_avg.idxmax()
        pdf.set_fill_color(240, 240, 240)
        pdf.set_draw_color(200, 200, 200)
        
        for batch in batch_avg.index:
            # Highlight the best batch
            if batch == best_batch:
                pdf.set_text_color(*highlight_color)
                pdf.set_font("Arial", "B", 11)
                pdf.cell(0, 8, f"Batch {batch}: {batch_avg[batch]:.2f} (Best Performance)", 1, 1, 'L', 1)
                pdf.set_text_color(*text_color)
                pdf.set_font("Arial", "", 11)
            else:
                pdf.cell(0, 8, f"Batch {batch}: {batch_avg[batch]:.2f}", 1, 1, 'L', 1)
        
        # Generate and add visualizations
        # ....additional PDF generation logic and visualizations...
```

### 2. Nested Modern Visualization Methods

#### 2.1 Modern Density Plot
```python
def _generate_modern_density_plot(self, df, subject_name=None, co_number=None):
    """Generate modernized density plot"""
    try:
        plt.style.use('ggplot')  
        fig, ax = plt.subplots(figsize=(12, 6))
        
        # Filter data based on parameters
        filtered_data = df.copy()
        if subject_name:
            filtered_data = filtered_data[filtered_data['course_name'] == subject_name]
        if co_number:
            filtered_data = filtered_data[filtered_data['co_number'] == co_number]
        
        # Check if we have enough data
        if filtered_data.empty or len(filtered_data['batch'].unique()) < 2:
            return None
        
        # Get unique batches (up to 3)
        batches = filtered_data['batch'].unique()[:3]
        
        # Modern color palette
        colors = ['#3498db', '#e74c3c', '#2ecc71']
        
        # Plot density for each batch
        for i, batch in enumerate(batches):
            batch_data = filtered_data[filtered_data['batch'] == batch]
            sns.kdeplot(
                data=batch_data, 
                x='attainment_percentage',
                color=colors[i % len(colors)],
                fill=True,
                alpha=0.5,
                label=f"Batch {batch}",
                linewidth=2.5
            )
        
        # Add title and labels
        title_text = "Distribution of CO Attainment Values"
        if subject_name:
            title_text += f" for {subject_name}"
        if co_number:
            title_text += f" - CO{co_number}"
            
        plt.title(title_text, fontsize=16, fontweight='bold', pad=20)
        plt.xlabel('CO Attainment Value', fontsize=12, labelpad=10)
        plt.ylabel('Density', fontsize=12, labelpad=10)
        
        # Add grid and legend
        plt.grid(True, linestyle='--', alpha=0.3)
        legend = plt.legend(fontsize=10, frameon=True, fancybox=True, framealpha=0.8)
        legend.get_frame().set_edgecolor('lightgray')
        
        # Remove unnecessary spines
        ax.spines['top'].set_visible(False)
        ax.spines['right'].set_visible(False)
        ax.spines['left'].set_linewidth(0.5)
        ax.spines['bottom'].set_linewidth(0.5)
        
        plt.tight_layout()
        
        # Save plot
        buf = BytesIO()
        plt.savefig(buf, format='png', dpi=200, bbox_inches='tight')
        buf.seek(0)
        plt.close(fig)
        
        return buf.getvalue()
        
    except Exception as e:
        logger.error(f"Error generating modern density plot: {e}")
        return None
```

#### 2.2 Modern Subject Plot
```python
def _generate_modern_subject_plot(self, df, subject_name):
    """Generate modernized plot for a specific subject"""
    try:
        plt.style.use('ggplot')  
        
        # Create figure
        fig, ax = plt.subplots(figsize=(12, 6.5))
        
        # Extract and prepare data
        subject_data = df[df['course_name'] == subject_name]
        plot_data = subject_data.pivot(index='batch', columns='co_number', values='attainment_percentage')
        
        # Use colorful palette
        colors = plt.cm.tab10.colors
        
        # Create bar chart
        bars = plot_data.plot(
            kind='bar', 
            width=0.75, 
            ax=ax, 
            color=colors,
            edgecolor='white', 
            linewidth=1.5
        )
        
        # Add title and labels
        plt.title(f'CO Attainment Comparison for {subject_name}', 
                fontsize=16, fontweight='bold', pad=20)
        plt.xlabel('Batch', fontsize=12, labelpad=10)
        plt.ylabel('Attainment Value', fontsize=12, labelpad=10)
        
        # Add legend with styling
        legend = plt.legend(
            title='Course Outcomes', 
            bbox_to_anchor=(1.04, 1), 
            loc='upper left', 
            title_fontsize=12, 
            fontsize=10, 
            frameon=True, 
            facecolor='white', 
            edgecolor='lightgray',
            framealpha=0.8
        )
        
        # Style axes and labels
        plt.xticks(rotation=45, ha='right', fontsize=10)
        plt.yticks(fontsize=10)
        plt.grid(True, axis='y', linestyle='--', alpha=0.3, color='gray')
        
        # Add value labels on bars
        for container in ax.containers:
            ax.bar_label(container, fmt='%.2f', padding=3, fontsize=9, fontweight='bold')
        
        # Set appropriate y-axis limit
        max_value = plot_data.values.max()
        plt.ylim(0, max_value * 1.15)  
        
        # Modern clean look with minimal spines
        ax.spines['top'].set_visible(False)
        ax.spines['right'].set_visible(False)
        ax.spines['left'].set_linewidth(0.5)
        ax.spines['bottom'].set_linewidth(0.5)
        
        plt.tight_layout()
        
        # Save the plot
        buf = BytesIO()
        plt.savefig(buf, format='png', dpi=200, bbox_inches='tight')
        buf.seek(0)
        plt.close(fig)
        
        return buf.getvalue()
        
    except Exception as e:
        logger.error(f"Error generating plot for {subject_name}: {e}")
        return None
```

### 3. PDF Report Structure
The PDF report consists of several key sections:

1. **Overall Summary**
   - Batch performance overview
   - Best performing batch 
   - Best subject-batch combination

2. **CO Attainment Visualization**
   - Bar chart comparing CO attainment across batches
   - Density plot showing distribution of attainment values

3. **Batch Performance Summary**
   - Table with batch average attainment
   - Best subject for each batch

4. **Subject-Wise Analysis**
   - Individual pages for each subject
   - CO-wise performance with best batch for each CO
   - Faculty information per batch
   - Visualizations specific to the subject

### 4. Fallback Mechanism
The report generation includes a comprehensive fallback process:

```python
# If main PDF generation fails, create a basic fallback PDF
try:
    # Basic PDF with minimal info
    basic_pdf = FPDF()
    basic_pdf.add_page()
    basic_pdf.set_font("Arial", "B", 16)
    basic_pdf.cell(0, 10, f"CO Attainment Comparison - {', '.join(batches)}", 0, 1, "C")
    
    # Add batch averages
    batch_avg = df.groupby('batch')['attainment_percentage'].mean()
    for batch in batch_avg.index:
        basic_pdf.cell(0, 10, f"Batch {batch}: {batch_avg[batch]:.2f}", 0, 1)
    
    # Add best performing entities
    basic_pdf.cell(0, 10, f"Best Batch: {best_batch_overall} ({batch_avg[best_batch_overall]:.2f})", 0, 1)
    
    # Save basic PDF as fallback
    basic_filename = f"simple_co_report_{timestamp}.pdf"
    basic_path = os.path.join(pdf_dir, basic_filename)
    basic_pdf.output(basic_path)
    
    return basic_path, web_url
    
except Exception as basic_error:
    logger.error(f"Even basic PDF failed: {basic_error}")
    return None, None
```

## Run Method Implementation

```python
def run(self, dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
    
    user_message = tracker.latest_message.get('text', '')
    logger.info(f"Processing CO attainment comparison request: {user_message}")
    
    # Extract specific batches from the message
    requested_batches = self._extract_batches(user_message)
    
    # Get database connection
    connection = self._get_db_connection()
    if not connection:
        dispatcher.utter_message(text="I apologize, but I'm having trouble accessing the database right now. Please try again later.")
        return []
    
    try:
        # Fetch CO attainment data
        data = self._fetch_co_attainment_data(connection, requested_batches if requested_batches else None)
        if not data:
            dispatcher.utter_message(text="I couldn't find any CO attainment data to compare. Please make sure the data is available in the system.")
            return []
        
        # Convert to DataFrame
        df = pd.DataFrame(data)
        
        # Generate PDF report and get public URL
        pdf_path, public_url = self._generate_pdf_report(df, requested_batches if requested_batches else df['batch'].unique())
        
        # Let user know processing is happening
        dispatcher.utter_message(text="Generating CO attainment visualizations and PDF report. This may take a moment...")
        
        # Ensure report directory exists
        pdf_dir = os.path.dirname(pdf_path)
        if not os.path.exists(pdf_dir):
            os.makedirs(pdf_dir, exist_ok=True)
            logger.info(f"Created PDF directory: {pdf_dir}")
        
        # Return report link when complete
        if pdf_path and os.path.exists(pdf_path):
            dispatcher.utter_message(
                text=f"I've generated a detailed CO attainment comparison report. [Download Report]({public_url})"
            )
            
            # Provide quick summary in chat
            dispatcher.utter_message(text="\nQuick Summary:")
            
            batch_avg_local = df.groupby('batch')['attainment_percentage'].mean()
            for batch in batch_avg_local.index:
                dispatcher.utter_message(text=f"- Batch {batch}: Average attainment {batch_avg_local[batch]:.2f}")
        else:
            if pdf_path:
                logger.error(f"PDF file not found at path: {pdf_path}")
            dispatcher.utter_message(text="I encountered an error while generating the PDF report. Please try again.")
        
    except Exception as e:
        logger.error(f"Error processing CO attainment comparison: {e}")
        dispatcher.utter_message(text="I encountered an error while comparing CO attainment data. Please try again later.")
    
    finally:
        connection.close()
    
    return [] 
```

## Error Handling

### 1. Database Connection Issues
```python
connection = self._get_db_connection()
if not connection:
    dispatcher.utter_message(text="I apologize, but I'm having trouble accessing the database right now. Please try again later.")
    return []
```

### 2. Missing or Empty Data
```python
data = self._fetch_co_attainment_data(connection, requested_batches)
if not data:
    dispatcher.utter_message(text="I couldn't find any CO attainment data to compare. Please make sure the data is available in the system.")
    return []
```

### 3. Visualization Errors
```python
try:
    # Visualization generation
except Exception as e:
    logger.error(f"Error generating plot for {subject_name}: {e}")
    import traceback
    logger.error(traceback.format_exc())
    return None
```

### 4. PDF Generation Errors
```python
try:
    # Main PDF generation
except Exception as e:
    logger.error(f"Error generating PDF report: {e}")
    import traceback
    logger.error(f"Traceback: {traceback.format_exc()}")
    
    # Fallback to basic PDF generation
    try:
        # Simple PDF generation
    except Exception as basic_error:
        logger.error(f"Even basic PDF failed: {basic_error}")
        return None, None
```

## Best Practices

### 1. Data Processing
- Input validation and error handling 
- DataFrame operations for efficient data manipulation
- Aggregation and pivoting for visualization preparation
- Graceful handling of missing data

### 2. Visualization
- Consistent color schemes and styling
- Meaningful titles and labels
- Value annotations on data points
- Multiple visualization types for different insights
- Modern, clean aesthetic with intentional design choices

### 3. Report Generation
- Multi-page, structured PDF layout
- Visual hierarchy with styled headings
- Data tables for detailed information
- Highlight of key insights and best performers
- Backup generation with graceful degradation

### 4. User Experience
- Informative intermediate messages
- Quick summary in chat with the detailed report available
- Clickable download link for detailed analysis
- Error messages that provide guidance

## Database Integration

The handler retrieves pre-calculated data from:
```sql
SELECT 
    batch,
    faculty_name,
    course_id,
    course_name,
    type,
    semester,
    co_number,
    ca as attainment_percentage
FROM 
    co_attainment
WHERE 
    co_number IS NOT NULL 
    AND ca IS NOT NULL
```

## Example Usage

### 1. Batch Comparison
```
User: "Compare CO attainment for batches 2021-2022 and 2022-2023"
Response: 
"Generating CO attainment visualizations and PDF report. This may take a moment...
I've generated a detailed CO attainment comparison report. [Download Report](http://localhost/B/AuraAI/pdf_reports/co_attainment_report_20240607_123456.pdf)

Quick Summary:
- Batch 2021-2022: Average attainment 72.45
- Batch 2022-2023: Average attainment 78.32"
```

### 2. All Batches Comparison
```
User: "Compare CO attainment for all batches"
Response: [PDF report with comparative analysis of all available batches]
```

### 3. Subject Analysis
```
User: "Show CO attainment for Data Structures"
Response: [Analysis focuses on the specified subject across batches]
```

## Notes on CO Attainment Calculation

The CO attainment values (cia, se, da, ia, ca) are pre-calculated in the database rather than computed in Python code. The handler focuses on retrieving, visualizing, and comparing these values rather than performing the actual calculations. 