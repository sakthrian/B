import os
import logging
import time
from fpdf import FPDF
from typing import List, Dict, Any, Optional


logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


PDF_DIR = "pdf_reports"

PDF_WEB_PATH = "/B/AuraAI/pdf_reports"


os.makedirs(PDF_DIR, exist_ok=True)

class ModernPDF(FPDF):
    """Modern PDF class with improved styling"""
    
    def __init__(self, title="ObeAI Report"):
        super().__init__()
        self.title = title
        
    def header(self):
        # Set background color for header
        self.set_fill_color(40, 70, 140)  
        self.rect(0, 0, 210, 25, 'F')
        
        
        self.set_font("Arial", "B", 18)
        self.set_text_color(255, 255, 255) 
        self.cell(0, 18, "Outcome Attainment Tool", 0, 0, "L", False)
        
        # Add report title on right side
        self.set_font("Arial", "B", 14)
        self.cell(0, 18, self.title, 0, 1, "R", False)
        
        
        self.ln(10)
        
    def footer(self):
       
        self.set_y(-15)
        
        self.set_font('Arial', 'I', 8)
        
        self.set_text_color(128, 128, 128)
        
        self.cell(0, 10, f'Page {self.page_no()}/{{nb}}', 0, 0, 'C')
        # Add date
        self.cell(0, 10, f'Generated on {time.strftime("%Y-%m-%d")}', 0, 0, 'R')

class PDFGenerator:
    """
    Class for generating PDF reports from query results
    """
    
    def __init__(self, title: str = "AuraAI Report"):
        """Initialize the PDF generator with a title"""
        self.title = title
        
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
        
        # Create PDF 
        self.pdf = ModernPDF(f"Student Report - {query_value}")
        self.pdf.alias_nb_pages()
        self.pdf.set_auto_page_break(auto=True, margin=15)
        self.pdf.add_page()
        
        # Addinformation box
        self.pdf.set_fill_color(240, 240, 240)  
        self.pdf.set_draw_color(40, 70, 140)    
        self.pdf.set_line_width(0.5)
        self.pdf.rect(10, 30, 190, 30, 'DF')
        
        
        self.pdf.set_font("Arial", "B", 14)
        self.pdf.set_text_color(40, 70, 140) 
        self.pdf.set_xy(15, 35)
        self.pdf.cell(180, 10, f"Student Information - {query_type.title()}: {query_value}", 0, 1, "L")
        
        
        self.pdf.set_font("Arial", "B", 10)
        self.pdf.set_text_color(80, 80, 80) 
        self.pdf.set_xy(15, 48)
        self.pdf.cell(55, 8, f"Total Students: {len(students)}", 0, 0)
        
        if not students:
            # Add styled section heading
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
            
            
            timestamp = int(time.time())
            filename = f"student_report_{query_type}_{query_value}_{timestamp}.pdf"
            
            return self._save_pdf(filename)
        
        
        self.pdf.ln(20)
        self.pdf.set_fill_color(40, 70, 140)  
        self.pdf.set_text_color(255, 255, 255)  
        self.pdf.set_font("Arial", "B", 12)
        self.pdf.cell(190, 10, "STUDENT LIST", 0, 1, "L", True)
        
        
        self.pdf.ln(5)
        col_widths = [30, 50, 15, 20, 20, 30, 25] 
        
        # Table header with modern styling
        self.pdf.set_font("Arial", "B", 10)
        self.pdf.set_fill_color(40, 70, 140)  
        self.pdf.set_text_color(255, 255, 255)  
        
        headers = ["Reg No", "Name", "Year", "Semester", "Section", "Batch", "Type"]
        
        for i, header in enumerate(headers):
            self.pdf.cell(col_widths[i], 8, header, 1, 0, "C", True)
        self.pdf.ln()
        
        
        alternate = False
        self.pdf.set_text_color(0, 0, 0)  
        
        for i, student in enumerate(students):
            
            if self.pdf.get_y() > 250:
                self.pdf.add_page()
                
                
                self.pdf.set_font("Arial", "B", 10)
                self.pdf.set_fill_color(40, 70, 140)
                self.pdf.set_text_color(255, 255, 255)
                for j, header in enumerate(headers):
                    self.pdf.cell(col_widths[j], 8, header, 1, 0, "C", True)
                self.pdf.ln()
                self.pdf.set_text_color(0, 0, 0)
            
            
            if alternate:
                self.pdf.set_fill_color(240, 240, 240)  
            else:
                self.pdf.set_fill_color(255, 255, 255)  
            
            
            self.pdf.set_font("Arial", "", 9)
            
            
            self.pdf.cell(col_widths[0], 8, str(student.get("register_no", "")), 1, 0, "L", True)
            
            
            name = str(student.get("name", ""))
            self.pdf.cell(col_widths[1], 8, name[:25] + "..." if len(name) > 25 else name, 1, 0, "L", True)
            
           
            self.pdf.cell(col_widths[2], 8, str(student.get("year", "")), 1, 0, "C", True)
            self.pdf.cell(col_widths[3], 8, str(student.get("semester", "")), 1, 0, "C", True)
            self.pdf.cell(col_widths[4], 8, str(student.get("section", "")), 1, 0, "C", True)
            self.pdf.cell(col_widths[5], 8, str(student.get("batch", "")), 1, 0, "C", True)
            self.pdf.cell(col_widths[6], 8, str(student.get("type", "")), 1, 1, "C", True)
            
            alternate = not alternate
        
       
        if self.pdf.get_y() > 230:  
            self.pdf.add_page()
        
        self.pdf.ln(10)
        self.pdf.set_fill_color(245, 245, 245)  
        self.pdf.set_draw_color(40, 70, 140)    
        
        
        box_height = 40  
        self.pdf.rect(10, self.pdf.get_y(), 190, box_height, 'DF')
        
        self.pdf.set_text_color(40, 70, 140) 
        self.pdf.set_font("Arial", "B", 11)
        self.pdf.set_xy(15, self.pdf.get_y() + 5)
        self.pdf.cell(0, 8, "QUERY INFORMATION:", 0, 1)
        
        self.pdf.set_text_color(0, 0, 0)  
        self.pdf.set_font("Arial", "", 10)
        self.pdf.set_x(15)
        self.pdf.cell(175, 6, f"Query Type: {query_type.title()}", 0, 1)
        self.pdf.set_x(15)
        self.pdf.cell(175, 6, f"Query Value: {query_value}", 0, 1)
        self.pdf.set_x(15)
        self.pdf.cell(175, 6, f"Total Results: {len(students)}", 0, 1)
        
        # Generate timestamp and filename
        timestamp = int(time.time())
        filename = f"student_report_{query_type}_{query_value}_{timestamp}.pdf"
        
        return self._save_pdf(filename)
    
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
        
        self.title = f"Faculty Report - {query_value}"
        
      
        self._add_header()
        
        
        self.pdf.set_font("Arial", "B", 12)
        self.pdf.cell(0, 10, f"{query_type}: {query_value}", 0, 1)
        self.pdf.cell(0, 10, f"Total Results: {len(faculty_data)}", 0, 1)
        self.pdf.ln(5)
        
        
        self.pdf.set_font("Arial", "B", 10)
        self.pdf.set_fill_color(200, 220, 255)
        
        
        col_widths = [70, 90, 30]  
        
       
        headers = ["Faculty Name", "Subject", "Department"]
        
        
        for i, header in enumerate(headers):
            self.pdf.cell(col_widths[i], 10, header, 1, 0, "C", True)
        self.pdf.ln()
        
        
        self.pdf.set_font("Arial", "", 10)
        
        
        alternate = False
        
        for faculty in faculty_data:
            if alternate:
                self.pdf.set_fill_color(240, 240, 240)
            else:
                self.pdf.set_fill_color(255, 255, 255)
            
            
            self.pdf.cell(col_widths[0], 10, str(faculty.get("name", "")), 1, 0, "L", alternate)
            self.pdf.cell(col_widths[1], 10, str(faculty.get("subject_name", "")), 1, 0, "L", alternate)
            self.pdf.cell(col_widths[2], 10, str(faculty.get("department", "N/A")), 1, 0, "L", alternate)
            self.pdf.ln()
            
         
            alternate = not alternate
        
       
        timestamp = int(time.time())
        filename = f"faculty_report_{query_type}_{query_value}_{timestamp}.pdf"
        filepath = os.path.join(PDF_DIR, filename)
        
      
        self.pdf.output(filepath)
        
        logger.info(f"Generated PDF report at {filepath}")
        
        
        return f"{PDF_WEB_PATH}/{filename}"
    
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
        
        # Create PDF 
        self.pdf = ModernPDF(f"Course Report - {query_value}")
        self.pdf.alias_nb_pages()
        self.pdf.set_auto_page_break(auto=True, margin=15)
        self.pdf.add_page()
        
        # Add information box
        self.pdf.set_fill_color(240, 240, 240)  
        self.pdf.set_draw_color(40, 70, 140)    
        self.pdf.set_line_width(0.5)
        self.pdf.rect(10, 30, 190, 30, 'DF')
        
        
        self.pdf.set_font("Arial", "B", 14)
        self.pdf.set_text_color(40, 70, 140)  
        self.pdf.set_xy(15, 35)
        self.pdf.cell(180, 10, f"Course Information - {query_type.title()}: {query_value}", 0, 1, "L")
        
        
        self.pdf.set_font("Arial", "B", 10)
        self.pdf.set_text_color(80, 80, 80)  
        self.pdf.set_xy(15, 48)
        self.pdf.cell(55, 8, f"Total Courses: {len(courses)}", 0, 0)
        
        if not courses:
          
            self.pdf.ln(20)
            self.pdf.set_fill_color(40, 70, 140)  
            self.pdf.set_text_color(255, 255, 255)  
            self.pdf.set_font("Arial", "B", 12)
            self.pdf.cell(190, 10, "SEARCH RESULTS", 0, 1, "L", True)
            
            # No results message
            self.pdf.set_text_color(0, 0, 0)  
            self.pdf.set_font("Arial", "", 10)
            self.pdf.ln(5)
            self.pdf.cell(0, 10, "No courses found matching the criteria.", 0, 1, "C")
            
            # Generate timestamp and filename
            timestamp = int(time.time())
            filename = f"course_report_{query_type}_{query_value}_{timestamp}.pdf"
            
            return self._save_pdf(filename)
       

        self.pdf.ln(20)
        self.pdf.set_fill_color(40, 70, 140)  
        self.pdf.set_text_color(255, 255, 255)  
        self.pdf.set_font("Arial", "B", 12)
        self.pdf.cell(190, 10, "COURSE LIST", 0, 1, "L", True)
        
        
        self.pdf.ln(5)
        col_widths = [30, 90, 20, 25, 25]
        
        
        self.pdf.set_font("Arial", "B", 10)
        self.pdf.set_fill_color(40, 70, 140)  
        self.pdf.set_text_color(255, 255, 255)  
        
        headers = ["Code", "Course Name", "Credits", "Semester", "No. of COs"]
        
        for i, header in enumerate(headers):
            self.pdf.cell(col_widths[i], 8, header, 1, 0, "C", True)
        self.pdf.ln()
        
        
        alternate = False
        self.pdf.set_text_color(0, 0, 0)  
        
        for i, course in enumerate(courses):
            
            if self.pdf.get_y() > 250:
                self.pdf.add_page()
                
                
                self.pdf.set_font("Arial", "B", 10)
                self.pdf.set_fill_color(40, 70, 140)
                self.pdf.set_text_color(255, 255, 255)
                for j, header in enumerate(headers):
                    self.pdf.cell(col_widths[j], 8, header, 1, 0, "C", True)
                self.pdf.ln()
                self.pdf.set_text_color(0, 0, 0)
            
            
            if alternate:
                self.pdf.set_fill_color(240, 240, 240)  
            else:
                self.pdf.set_fill_color(255, 255, 255)  

            
            
            self.pdf.set_font("Arial", "", 9)
            
            
            course_name = str(course.get("name", ""))
           
            credits_val = course.get("credits", "")
            if isinstance(credits_val, int):
                credits_str = f"{credits_val}.0"
            else:
                credits_str = str(credits_val)
                
            # Get number of COs
            co_count = course.get("co_count", "N/A")
            
            # Add data
            self.pdf.cell(col_widths[0], 8, str(course.get("code", "")), 1, 0, "L", True)
            self.pdf.cell(col_widths[1], 8, course_name[:50] + "..." if len(course_name) > 50 else course_name, 1, 0, "L", True)
            self.pdf.cell(col_widths[2], 8, credits_str, 1, 0, "C", True)
            self.pdf.cell(col_widths[3], 8, str(course.get("semester", "")), 1, 0, "C", True)
            self.pdf.cell(col_widths[4], 8, str(co_count), 1, 1, "C", True)
            
            alternate = not alternate
        
        
        if self.pdf.get_y() > 230:  
            self.pdf.add_page()
        
        self.pdf.ln(10)
        self.pdf.set_fill_color(245, 245, 245)  
        self.pdf.set_draw_color(40, 70, 140)    
        
        # Calculate required box height based on content
        box_height = 40  
        self.pdf.rect(10, self.pdf.get_y(), 190, box_height, 'DF')
        
        self.pdf.set_text_color(40, 70, 140)  
        self.pdf.set_font("Arial", "B", 11)
        self.pdf.set_xy(15, self.pdf.get_y() + 5)
        self.pdf.cell(0, 8, "QUERY INFORMATION:", 0, 1)
        
        self.pdf.set_text_color(0, 0, 0)  
        self.pdf.set_font("Arial", "", 10)
        self.pdf.set_x(15)
        self.pdf.cell(175, 6, f"Query Type: {query_type.title()}", 0, 1)
        self.pdf.set_x(15)
        self.pdf.cell(175, 6, f"Query Value: {query_value}", 0, 1)
        self.pdf.set_x(15)
        self.pdf.cell(175, 6, f"Total Results: {len(courses)}", 0, 1)
        
        # Generate timestamp and filename
        timestamp = int(time.time())
        filename = f"course_report_{query_type}_{query_value}_{timestamp}.pdf"
        
        return self._save_pdf(filename) 