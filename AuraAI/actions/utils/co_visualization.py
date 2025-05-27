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


logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


PDF_DIR = "pdf_reports"
PDF_WEB_PATH = "/B/AuraAI/pdf_reports"

# Ensure PDF directory exists
os.makedirs(PDF_DIR, exist_ok=True)
os.makedirs(os.path.join(PDF_DIR, "temp"), exist_ok=True)

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

def fetch_co_attainment_data(course_name: str) -> Dict[str, Any]:
    """Fetch CO attainment data for a given course name"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        # Query to get course details
        query = """
        SELECT c.code, c.name, c.semester, c.credits
        FROM course c
        WHERE c.name LIKE %s
        LIMIT 1
        """
        cursor.execute(query, (f"%{course_name}%",))
        course = cursor.fetchone()
        
        if not course:
            return {
                'success': False,
                'error': f"Course '{course_name}' not found"
            }
        
        # Query to get the faculty course records
        query = """
        SELECT fc.id as fc_id, fc.section, fc.type, fc.batch
        FROM faculty_course fc
        JOIN course c ON fc.course_id = c.code
        WHERE c.code = %s
        """
        cursor.execute(query, (course['code'],))
        fc_records = cursor.fetchall()
        
        if not fc_records:
            return {
                'success': False,
                'error': f"No faculty assignments found for course '{course_name}'"
            }
        
        # Get CO numbers for this course
        query = """
        SELECT id, co_number
        FROM course_outcome
        WHERE course_id = %s
        ORDER BY co_number
        """
        cursor.execute(query, (course['code'],))
        cos = cursor.fetchall()
        
        if not cos:
            return {
                'success': False,
                'error': f"No Course Outcomes (COs) defined for course '{course_name}'"
            }
        
        # Collect CO attainment data
        co_data = {}
        
        for fc_record in fc_records:
            fc_id = fc_record['fc_id']
            
            # Query CO attainment data
            query = """
            SELECT co.co_number, coa.cia, coa.se, coa.da, coa.ia, coa.ca
            FROM co_overall coa
            JOIN course_outcome co ON coa.co_id = co.id
            WHERE coa.fc_id = %s
            ORDER BY co.co_number
            """
            cursor.execute(query, (fc_id,))
            attainment_records = cursor.fetchall()
            
            if attainment_records:
                section_key = f"{fc_record['section']}-{fc_record['batch']}"
                co_data[section_key] = attainment_records
        
        if not co_data:
            return {
                'success': False,
                'error': f"No CO attainment data found for course '{course_name}'"
            }
        
        # All data collected successfully
        return {
            'success': True,
            'course': course,
            'cos': cos,
            'co_data': co_data
        }
        
    except Exception as e:
        logger.error(f"Error fetching CO attainment data: {str(e)}")
        return {
            'success': False,
            'error': f"Error retrieving CO attainment data: {str(e)}"
        }
    finally:
        if 'conn' in locals() and conn:
            conn.close()

def create_co_attainment_charts(data: Dict[str, Any]) -> Tuple[List[str], str]:
    """
    Create charts for CO attainment visualization
    
    Returns:
        Tuple containing a list of image file paths and a summary text
    """
    if not data or not data.get('success'):
        return [], "No data available for visualization"
    
    course = data['course']
    co_data = data['co_data']
    chart_files = []
    
    # Prepare summary text
    summary_text = f"CO Attainment Summary for {course['name']} ({course['code']}):\n"
    
    # Bar chart for CA (Course Attainment) values across all sections
    try:
       
        plt.figure(figsize=(12, 6), dpi=200) 
        
        # Process data for plotting
        sections = list(co_data.keys())
        co_numbers = []
        ca_values = {}
        
        # Collect all unique CO numbers
        for section, attainments in co_data.items():
            for attainment in attainments:
                co_number = f"CO{attainment['co_number']}"
                if co_number not in co_numbers:
                    co_numbers.append(co_number)
                
                if co_number not in ca_values:
                    ca_values[co_number] = {}
                
                ca_values[co_number][section] = attainment['ca']
        
        # Sort CO numbers
        co_numbers.sort(key=lambda x: int(x[2:]))
        
        
        avg_attainment = {}
        for co in co_numbers:
            values = [v for v in ca_values[co].values() if v is not None]
            if values:
                avg_attainment[co] = sum(values) / len(values)
            else:
                avg_attainment[co] = 0
        
        # Add to summary
        for co in co_numbers:
            summary_text += f"{co}: {avg_attainment[co]:.2f} attainment\n"
        
        # Identify highest and lowest attained COs
        if co_numbers:
            highest_co = max(avg_attainment.items(), key=lambda x: x[1])
            lowest_co = min(avg_attainment.items(), key=lambda x: x[1])
            summary_text += f"\nHighest attainment: {highest_co[0]} ({highest_co[1]:.2f})\n"
            summary_text += f"Lowest attainment: {lowest_co[0]} ({lowest_co[1]:.2f})"
        
        # Plot bar chart of average attainment with enhanced styling
        x = np.arange(len(co_numbers))
        bars = plt.bar(x, [avg_attainment[co] for co in co_numbers], width=0.6, 
                 color='skyblue', edgecolor='navy', linewidth=1.5)
        plt.xlabel('Course Outcomes', fontsize=14, fontweight='bold')
        plt.ylabel('Attainment Value (CA)', fontsize=14, fontweight='bold')
        plt.title(f'Average CO Attainment for {course["name"]} ({course["code"]})', 
                 fontsize=16, fontweight='bold')
        plt.xticks(x, co_numbers, fontsize=12, fontweight='bold')
        plt.ylim(0, 5)  # Assuming attainment is on a scale of 0-5
        plt.grid(axis='y', linestyle='--', alpha=0.7)
        
        
        for bar in bars:
            height = bar.get_height()
            plt.text(bar.get_x() + bar.get_width()/2., height + 0.1,
                    f'{height:.2f}', ha='center', va='bottom', 
                    fontweight='bold', fontsize=12)
        
        
        timestamp = int(time.time())
        abs_temp_dir = os.path.abspath(os.path.join(PDF_DIR, "temp"))
        os.makedirs(abs_temp_dir, exist_ok=True)
        chart_file = os.path.join(abs_temp_dir, f"co_attainment_avg_{timestamp}.png")
        
        
        plt.tight_layout()
        try:
            plt.savefig(chart_file, dpi=300, bbox_inches='tight', pad_inches=0.2)
            logger.info(f"Saved chart to {chart_file} (size: {os.path.getsize(chart_file)} bytes)")
            
            
            if os.path.exists(chart_file) and os.path.getsize(chart_file) > 0:
                chart_files.append(chart_file)
            else:
                logger.error(f"Failed to create valid chart file at {chart_file}")
        except Exception as e:
            logger.error(f"Error saving chart: {str(e)}")
        
        plt.close()
        
        # Create comparative chart for different components (CIA, SE, IA, CA)
        plt.figure(figsize=(14, 7), dpi=200)  
        
        # Calculate averages for each component
        components = {'CIA': 'cia', 'SE': 'se', 'DA': 'da', 'IA': 'ia', 'CA': 'ca'}
        component_avgs = {comp: {} for comp in components.keys()}
        
        for co in co_numbers:
            co_num = int(co[2:])
            for comp_name, comp_key in components.items():
                values = []
                for section, attainments in co_data.items():
                    for attainment in attainments:
                        if attainment['co_number'] == co_num and attainment[comp_key] is not None:
                            values.append(attainment[comp_key])
                
                if values:
                    component_avgs[comp_name][co] = sum(values) / len(values)
                else:
                    component_avgs[comp_name][co] = 0
        
        
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
            
            # Add value labels on top of bars
            for bar in bars:
                height = bar.get_height()
                if height > 0.2:  # Only add text if bar is tall enough
                    plt.text(bar.get_x() + bar.get_width()/2., height + 0.05,
                            f'{height:.1f}', ha='center', va='bottom', 
                            fontsize=9, fontweight='bold', rotation=0)
        
        plt.xlabel('Course Outcomes', fontsize=14, fontweight='bold')
        plt.ylabel('Attainment Value', fontsize=14, fontweight='bold')
        plt.title(f'CO Attainment by Component - {course["name"]} ({course["code"]})', 
                 fontsize=16, fontweight='bold')
        plt.xticks(index + bar_width*2 - 0.3, co_numbers, fontsize=12, fontweight='bold')
        plt.ylim(0, 5)
        plt.legend(fontsize=12, loc='upper right')
        plt.grid(axis='y', linestyle='--', alpha=0.3)
        
        # Save to file with absolute path
        comp_chart_file = os.path.join(abs_temp_dir, f"co_attainment_comp_{timestamp}.png")
        plt.tight_layout()
        try:
            plt.savefig(comp_chart_file, dpi=300, bbox_inches='tight', pad_inches=0.2)
            logger.info(f"Saved component chart to {comp_chart_file} (size: {os.path.getsize(comp_chart_file)} bytes)")
            
           
            if os.path.exists(comp_chart_file) and os.path.getsize(comp_chart_file) > 0:
                chart_files.append(comp_chart_file)
            else:
                logger.error(f"Failed to create valid component chart file at {comp_chart_file}")
        except Exception as e:
            logger.error(f"Error saving component chart: {str(e)}")
            
        plt.close()
        
    except Exception as e:
        logger.error(f"Error creating charts: {str(e)}")
        import traceback
        logger.error(traceback.format_exc())
        
    return chart_files, summary_text

def generate_co_attainment_pdf(data: Dict[str, Any], chart_files: List[str], summary: str) -> str:
    """Generate PDF report for CO attainment with visualizations"""
    if not data or not data.get('success'):
        return ""
    
    course = data['course']
    co_data = data['co_data']
    
    # Extract highest and lowest CO information from summary
    highest_co = ["", 0]
    lowest_co = ["", 5]  
    
    # Parse summary to extract highest/lowest CO info
    summary_lines = summary.split('\n')
    for line in summary_lines:
        if "Highest attainment" in line:
            # Extract CO number and value
            parts = line.split(":")
            if len(parts) > 1:
                co_info = parts[1].strip()
                co_parts = co_info.split(" ")
                if len(co_parts) > 0:
                    highest_co[0] = co_parts[0].strip()
                    
                    if len(co_parts) > 1:
                        val_str = co_parts[1].strip("()")
                        try:
                            highest_co[1] = float(val_str)
                        except:
                            pass
        
        if "Lowest attainment" in line:
            # Extract CO number and value
            parts = line.split(":")
            if len(parts) > 1:
                co_info = parts[1].strip()
                co_parts = co_info.split(" ")
                if len(co_parts) > 0:
                    lowest_co[0] = co_parts[0].strip()
                   
                    if len(co_parts) > 1:
                        val_str = co_parts[1].strip("()")
                        try:
                            lowest_co[1] = float(val_str)
                        except:
                            pass
    
   
    class ModernPDF(FPDF):
        def header(self):
            
            self.set_fill_color(40, 70, 140) 
            self.rect(0, 0, 210, 25, 'F')
            
            
            self.set_font("Arial", "B", 18)
            self.set_text_color(255, 255, 255)  # White text
            self.cell(0, 18, "Outcome Attainment Tool", 0, 0, "L", False)
            
            
            self.set_font("Arial", "B", 14)
            self.cell(0, 18, "CO Attainment Report", 0, 1, "R", False)
            
           
            self.ln(10)
            
        def footer(self):
            
            self.set_y(-15)
            
            self.set_font('Arial', 'I', 8)
            
            self.set_text_color(128, 128, 128)
            
            self.cell(0, 10, f'Page {self.page_no()}/{{nb}}', 0, 0, 'C')
            
            self.cell(0, 10, f'Generated on {time.strftime("%Y-%m-%d")}', 0, 0, 'R')
    
    
    pdf = ModernPDF()
    pdf.alias_nb_pages()
    pdf.set_auto_page_break(auto=True, margin=15)
    pdf.add_page()
    
    
    pdf.set_fill_color(240, 240, 240)  
    pdf.set_draw_color(40, 70, 140)    
    pdf.set_line_width(0.5)
    pdf.rect(10, 30, 190, 40, 'DF')
    
    
    pdf.set_font("Arial", "B", 14)
    pdf.set_text_color(40, 70, 140)  
    pdf.set_xy(15, 35)
    pdf.cell(180, 10, course['name'], 0, 1, "L")
    
    
    pdf.set_font("Arial", "B", 10)
    pdf.set_text_color(80, 80, 80)  
    pdf.set_xy(15, 48)
    pdf.cell(30, 8, "Course Code:", 0, 0)
    pdf.set_font("Arial", "", 10)
    pdf.cell(55, 8, course['code'], 0, 0)
    
    pdf.set_font("Arial", "B", 10)
    pdf.set_xy(100, 48)
    pdf.cell(30, 8, "Semester:", 0, 0)
    pdf.set_font("Arial", "", 10)
    pdf.cell(0, 8, str(course['semester']), 0, 1)
    
    pdf.set_font("Arial", "B", 10)
    pdf.set_xy(15, 58)
    pdf.cell(30, 8, "Credits:", 0, 0)
    pdf.set_font("Arial", "", 10)
    pdf.cell(0, 8, str(course['credits']), 0, 1)
    
    
    pdf.ln(5)
    pdf.set_fill_color(40, 70, 140)  
    pdf.set_text_color(255, 255, 255)  
    pdf.set_font("Arial", "B", 12)
    pdf.cell(0, 10, "SUMMARY OF ATTAINMENT", 0, 1, "L", True)
    
   
    pdf.set_text_color(0, 0, 0)  
    pdf.set_font("Arial", "", 10)
    
    
    pdf.ln(2)
    summary_lines = summary.split('\n')
    for line in summary_lines:
        if "Highest attainment" in line or "Lowest attainment" in line:
            # Highlight highest/lowest attainment
            pdf.set_text_color(0, 100, 0) if "Highest" in line else pdf.set_text_color(180, 0, 0)
            pdf.set_font("Arial", "B", 10)
            pdf.cell(0, 8, line, 0, 1)
            pdf.set_text_color(0, 0, 0)  
            pdf.set_font("Arial", "", 10)
        elif ":" in line and not line.startswith("CO Attainment Summary"):
            
            parts = line.split(": ")
            if len(parts) == 2:
                pdf.set_font("Arial", "B", 10)
                pdf.cell(30, 8, parts[0], 0, 0)
                pdf.set_font("Arial", "", 10)
                pdf.cell(0, 8, parts[1], 0, 1)
        else:
            
            pdf.cell(0, 8, line, 0, 1)
    
    # Add styled section heading for visualizations
    pdf.ln(5)
    pdf.set_fill_color(40, 70, 140)  
    pdf.set_text_color(255, 255, 255)  
    pdf.set_font("Arial", "B", 12)
    pdf.cell(0, 10, "VISUALIZATION OF ATTAINMENT", 0, 1, "L", True)
    
    # Add the chart images 
    pdf.ln(5)
    
    # First chart -  on first page
    if len(chart_files) > 0 and os.path.exists(chart_files[0]):
        try:
            
            pdf.set_text_color(40, 70, 140)  
            pdf.set_font("Arial", "B", 11)
            pdf.cell(0, 10, "CO Attainment Overview", 0, 1, "L")
            
           
            if os.path.getsize(chart_files[0]) > 0:
                
                chart_y = pdf.get_y()
                
                try:
                    
                    pdf.image(chart_files[0], x=15, y=chart_y, w=170)
                    logger.info(f"Added chart 1: {chart_files[0]} (size: {os.path.getsize(chart_files[0])} bytes) to PDF")
                except Exception as img_error:
                    
                    pdf.ln(10)
                    pdf.set_text_color(180, 0, 0)  
                    pdf.set_font("Arial", "", 10)
                    pdf.cell(0, 10, f"Error displaying chart: {str(img_error)}", 0, 1)
                    pdf.ln(10)
                    logger.error(f"Error inserting image {chart_files[0]}: {str(img_error)}")
            else:
                pdf.set_text_color(180, 0, 0)  
                pdf.set_font("Arial", "", 10)
                pdf.cell(0, 10, f"Chart file is empty: {os.path.basename(chart_files[0])}", 0, 1)
                pdf.ln(10)
                logger.error(f"Chart file is empty: {chart_files[0]}")
                
            
            pdf.set_text_color(0, 0, 0)
            
        except Exception as e:
            
            pdf.set_text_color(180, 0, 0)  
            pdf.set_font("Arial", "", 10)
            pdf.cell(0, 10, f"Error processing chart 1: {str(e)}", 0, 1)
            pdf.ln(10)
            logger.error(f"Error processing chart section: {str(e)}")
    
    # Start a new page for the second chart
    pdf.add_page()
    
    # Second chart - on second page
    if len(chart_files) > 1 and os.path.exists(chart_files[1]):
        try:
            # Add a descriptive heading for component chart
            pdf.set_text_color(40, 70, 140)  
            pdf.set_font("Arial", "B", 11)
            pdf.cell(0, 10, "Component-wise CO Attainment", 0, 1, "L")
            
            # Verify image file is valid
            if os.path.getsize(chart_files[1]) > 0:
                # Position for second chart at top of new page
                chart_y = pdf.get_y()
                
                try:
                    
                    pdf.image(chart_files[1], x=15, y=chart_y, w=170)
                    logger.info(f"Added chart 2: {chart_files[1]} (size: {os.path.getsize(chart_files[1])} bytes) to PDF")
                except Exception as img_error:
                    
                    pdf.ln(10)
                    pdf.set_text_color(180, 0, 0)  
                    pdf.set_font("Arial", "", 10)
                    pdf.cell(0, 10, f"Error displaying chart: {str(img_error)}", 0, 1)
                    pdf.ln(10)
                    logger.error(f"Error inserting image {chart_files[1]}: {str(img_error)}")
            else:
                pdf.set_text_color(180, 0, 0)  #
                pdf.set_font("Arial", "", 10)
                pdf.cell(0, 10, f"Chart file is empty: {os.path.basename(chart_files[1])}", 0, 1)
                pdf.ln(10)
                logger.error(f"Chart file is empty: {chart_files[1]}")
                
           
            pdf.set_text_color(0, 0, 0)
            
        except Exception as e:
           
            pdf.set_text_color(180, 0, 0)  
            pdf.set_font("Arial", "", 10)
            pdf.cell(0, 10, f"Error processing chart 2: {str(e)}", 0, 1)
            pdf.ln(10)
            logger.error(f"Error processing chart section: {str(e)}")
    
    
    pdf.add_page()
    
    
    pdf.set_fill_color(40, 70, 140)  
    pdf.set_text_color(255, 255, 255) 
    pdf.set_font("Arial", "B", 12)
    pdf.cell(190, 10, "DETAILED ATTAINMENT DATA", 0, 1, "L", True)  
    
    # Get component data from all sections
    co_data = data['co_data']
    
    for section, attainments in co_data.items():
        pdf.ln(5)
        
        pdf.set_fill_color(230, 230, 230) 
        pdf.set_text_color(40, 70, 140)    
        pdf.set_font("Arial", "B", 11)
        pdf.cell(0, 8, f"Section: {section}", 0, 1, "L", True)
        
        
        pdf.set_font("Arial", "B", 10)
        col_widths = [20, 25, 25, 25, 25, 25]
        pdf.set_fill_color(40, 70, 140)    
        pdf.set_text_color(255, 255, 255)  
        
        # Table header
        pdf.cell(col_widths[0], 8, "CO", 1, 0, "C", True)
        pdf.cell(col_widths[1], 8, "CIA", 1, 0, "C", True)
        pdf.cell(col_widths[2], 8, "SE", 1, 0, "C", True)
        pdf.cell(col_widths[3], 8, "DA", 1, 0, "C", True)
        pdf.cell(col_widths[4], 8, "IA", 1, 0, "C", True)
        pdf.cell(col_widths[5], 8, "CA", 1, 1, "C", True)
        
        
        pdf.set_font("Arial", "", 10)
        for i, attainment in enumerate(attainments):
            
            if i % 2 == 0:
                pdf.set_fill_color(240, 240, 240)  
                fill = True
            else:
                pdf.set_fill_color(255, 255, 255)  
                fill = True
                
            pdf.set_text_color(0, 0, 0)  
            co_num = f"CO{attainment['co_number']}"
            pdf.cell(col_widths[0], 8, co_num, 1, 0, "C", fill)
            pdf.cell(col_widths[1], 8, f"{attainment['cia']:.2f}" if attainment['cia'] is not None else "N/A", 1, 0, "C", fill)
            pdf.cell(col_widths[2], 8, f"{attainment['se']:.1f}" if attainment['se'] is not None else "N/A", 1, 0, "C", fill)
            pdf.cell(col_widths[3], 8, f"{attainment['da']:.2f}" if attainment['da'] is not None else "N/A", 1, 0, "C", fill)
            pdf.cell(col_widths[4], 8, f"{attainment['ia']:.1f}" if attainment['ia'] is not None else "N/A", 1, 0, "C", fill)
            
            # Highlight CA values based on threshold
            ca_val = attainment['ca']
            if ca_val is not None:
                # Get CO number for this row to identify if it's highest or lowest
                current_co = f"CO{attainment['co_number']}"
                
                # Identify if this CO has highest or lowest attainment
                if current_co == highest_co[0]:  # Highest attainment
                    pdf.set_text_color(0, 128, 0)  
                elif current_co == lowest_co[0]:  # Lowest attainment
                    pdf.set_text_color(255, 0, 0)  
                else:  # All other values
                    pdf.set_text_color(0, 0, 0)  # Black
                
                pdf.cell(col_widths[5], 8, f"{ca_val:.2f}", 1, 1, "C", fill)
            else:
                pdf.cell(col_widths[5], 8, "N/A", 1, 1, "C", fill)
    
    
    pdf.ln(10)
    pdf.set_fill_color(245, 245, 245)  
    pdf.set_draw_color(40, 70, 140)    
    
    
    box_height = 50  
    pdf.rect(10, pdf.get_y(), 190, box_height, 'DF')
    
    pdf.set_text_color(40, 70, 140)  
    pdf.set_font("Arial", "B", 11)
    pdf.set_xy(15, pdf.get_y() + 5)
    pdf.cell(0, 8, "COMPONENT EXPLANATIONS:", 0, 1)
    
    pdf.set_text_color(0, 0, 0)  
    pdf.set_font("Arial", "", 10)
    explanations = [
        "CIA: Continuous Internal Assessment",
        "SE: Semester Exam",
        "DA: Direct Assessment",
        "IA: Indirect Assessment",
        "CA: Course Attainment (Overall)"
    ]
    
    for explanation in explanations:
        pdf.set_x(15)
        pdf.cell(175, 6, explanation, 0, 1)  
    
    
    timestamp = int(time.time())
    course_code = course['code'].replace(' ', '_')
    filename = f"co_attainment_{course_code}_{timestamp}.pdf"
    filepath = os.path.join(PDF_DIR, filename)
    
    try:
        
        os.makedirs(os.path.dirname(filepath), exist_ok=True)
        
        # Save PDF
        pdf.output(filepath)
        logger.info(f"Generated CO attainment PDF report at {filepath}")
        
       
        if os.path.exists(filepath) and os.path.getsize(filepath) > 0:
            logger.info(f"Verified PDF file: {filepath}, size: {os.path.getsize(filepath)} bytes")
        else:
            logger.error(f"PDF file not created or empty: {filepath}")
            return ""
    except Exception as e:
        logger.error(f"Error generating PDF: {str(e)}")
        import traceback
        logger.error(traceback.format_exc())
        return ""
    
    
    for chart_file in chart_files:
        if os.path.exists(chart_file):
            try:
                os.remove(chart_file)
                logger.info(f"Removed temporary chart file: {chart_file}")
            except Exception as e:
                logger.error(f"Error removing temporary file {chart_file}: {str(e)}")
    
    
    web_path = f"{PDF_WEB_PATH}/{filename}"
    logger.info(f"Returning web path: {web_path}")
    
    
    if not os.path.exists(filepath):
        logger.error(f"PDF file does not exist at {filepath}")
        return ""
    
    if not filename.endswith('.pdf'):
        logger.error(f"Filename does not end with .pdf: {filename}")
        return ""
    
    return web_path

def generate_co_attainment_visualization(course_name: str) -> Dict[str, Any]:
    """Main function to generate CO attainment visualization"""
    try:
        # Fetch data
        data = fetch_co_attainment_data(course_name)
        
        if not data or not data.get('success'):
            return data  # Return error information
        
        # Create charts
        chart_files, summary = create_co_attainment_charts(data)
        
        # Generate PDF report
        pdf_url = generate_co_attainment_pdf(data, chart_files, summary)
        
        if pdf_url:
            return {
                'success': True,
                'course_name': data['course']['name'],
                'pdf_url': pdf_url,
                'summary': summary
            }
        else:
            return {
                'success': False,
                'error': "Failed to generate PDF report"
            }
    
    except Exception as e:
        logger.error(f"Error in CO attainment visualization: {str(e)}")
        return {
            'success': False,
            'error': f"Error generating visualization: {str(e)}"
        } 