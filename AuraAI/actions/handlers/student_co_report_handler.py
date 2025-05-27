import os
import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns
from fpdf import FPDF
import mysql.connector
from decimal import Decimal
from datetime import datetime
from actions.utils.database import get_db_connection

class StudentCOReportHandler:
    def __init__(self):
        pass
    
    def generate_student_co_report(self, register_no):
        """Generate a CO attainment report for a specific student"""
        try:
            # Connect to the database
            connection = get_db_connection()
            cursor = connection.cursor(dictionary=True)
            
            # Get student information
            student_query = """
                SELECT register_no, name, year, semester, section, batch
                FROM student
                WHERE register_no = %s
            """
            cursor.execute(student_query, (register_no,))
            student_info = cursor.fetchone()
            
            if not student_info:
                return {"status": "error", "message": f"Student with register number {register_no} not found."}
            
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
            
            if not test_data:
                return {"status": "error", "message": f"No test data found for student {register_no}."}
            
            # Get course information from fc_id (faculty course id)
            course_query = """
                SELECT c.code AS course_code, c.name AS course_name
                FROM faculty_course fc
                JOIN course c ON fc.course_id = c.code
                WHERE fc.id = %s
            """
            cursor.execute(course_query, (test_data[0]['fc_id'],))
            course_info = cursor.fetchone()
            
            if not course_info:
                return {"status": "error", "message": f"Course information not found for the given tests."}
            
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
                
                # Convert decimal to float for calculations
                total_mark = float(test['total_mark'])
                obtained_float = float(obtained)
                percentage = (obtained_float / total_mark * 100) if total_mark > 0 else 0
                
                test_performance.append({
                    'test_no': test['test_no'],
                    'total_marks': total_mark,
                    'obtained': obtained_float,
                    'percentage': percentage,
                    'is_absent': is_absent
                })
            
            # Calculate best 2 average
            valid_tests = sorted(test_performance, key=lambda x: x['percentage'], reverse=True)
            best_two = valid_tests[:2]
            
            if len(best_two) >= 2:
                best_two_avg_obtained = sum(t['obtained'] for t in best_two) / 2
                best_two_avg_total = sum(t['total_marks'] for t in best_two) / 2
                best_two_avg_percentage = (best_two_avg_obtained / best_two_avg_total) * 100 if best_two_avg_total > 0 else 0
            else:
                best_two_avg_obtained = sum(t['obtained'] for t in best_two) / len(best_two) if best_two else 0
                best_two_avg_total = sum(t['total_marks'] for t in best_two) / len(best_two) if best_two else 0
                best_two_avg_percentage = (best_two_avg_obtained / best_two_avg_total) * 100 if best_two_avg_total > 0 else 0
            
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
                
                course_outcomes = []
                for i in range(1, 5):
                    course_outcomes.append({
                        'id': i,
                        'co_number': i,
                        'description': f"Course Outcome {i}"
                    })
            
            # Get CO attainment for each course outcome
            co_attainment = []
            for co in course_outcomes:
                # Find questions mapped to this CO - using id instead of co_number
                co_questions_query = """
                    SELECT q.id, q.max_mark
                    FROM question q
                    JOIN question_co qc ON q.id = qc.question_id
                    WHERE qc.co_id = %s
                """
                
                print(f"Checking CO: {co['co_number']}, CO ID: {co['id']}")
                
                # Using the id field from course_outcome table to match with co_id in question_co
                cursor.execute(co_questions_query, (co['id'],))
                co_questions = cursor.fetchall()
                
                print(f"Found {len(co_questions)} questions mapped to CO{co['co_number']} (ID: {co['id']})")
                
                obtained_marks = 0
                max_marks = 0
                
                # If questions are found, calculate attainment
                if co_questions:
                    question_ids = [q['id'] for q in co_questions]
                    placeholders = ', '.join(['%s'] * len(question_ids))
                    
                    
                    print(f"Question IDs: {question_ids}")
                    
                    marks_query = f"""
                        SELECT q.id, m.obtained_mark, q.max_mark
                        FROM mark m
                        JOIN question q ON m.question_id = q.id
                        WHERE m.student_id = %s AND q.id IN ({placeholders})
                    """
                    
                    cursor.execute(marks_query, [register_no] + question_ids)
                    marks_data = cursor.fetchall()
                    print(f"Found {len(marks_data)} mark records for student {register_no}")
                    
                    
                    for mark in marks_data:
                        print(f"Question ID: {mark['id']}, Obtained: {mark['obtained_mark']}, Max: {mark['max_mark']}")
                        
                        if mark['obtained_mark'] is not None and mark['max_mark'] is not None:
                            # Handle absent marks (stored as -1)
                            if float(mark['obtained_mark']) < 0:
                                print(f"  -> Absent mark detected ({mark['obtained_mark']}), treating as 0")
                                obtained_marks += 0
                            else:
                                obtained_marks += float(mark['obtained_mark'])
                            
                            max_marks += float(mark['max_mark'])
                    
                    print(f"CO{co['co_number']} - Total Obtained: {obtained_marks}, Total Max: {max_marks}")
                
                # Calculate attainment percentage
                if max_marks > 0:
                    attainment_percentage = max(0, (obtained_marks / max_marks) * 100) 
                    print(f"CO{co['co_number']} Attainment: {attainment_percentage:.2f}%")
                else:
                    attainment_percentage = 0  # No data available
                    print(f"CO{co['co_number']} Attainment: No data available")
                
                # Determine level based on percentage
                level = "High" if attainment_percentage >= 70 else "Medium" if attainment_percentage >= 50 else "Low"
                
                co_attainment.append({
                    'co_no': f"CO{co['co_number']}",
                    'description': co['description'],
                    'attainment': attainment_percentage,
                    'level': level
                })
            
           
            if not co_attainment:
                return {"status": "error", "message": "Could not generate CO attainment data."}
            
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
            
            
            total_questions = 0
            total_max_marks = 0
            total_obtained = 0
            knowledge_level_performance = {}
            
            # Process knowledge level data
            if knowledge_levels:
                for level in knowledge_levels:
                    if level['max_marks'] and level['obtained'] is not None:
                        kl = level['level_name']
                        questions = int(level['questions'])
                        max_marks = float(level['max_marks'])
                        obtained = float(level['obtained'])
                        
                        # Add to totals
                        total_questions += questions
                        total_max_marks += max_marks
                        total_obtained += obtained
                        
                        # Calculate percentage for this level
                        if max_marks > 0:
                            performance = (obtained / max_marks) * 100
                        else:
                            performance = 0
                            
                        knowledge_level_performance[kl] = performance
                        print(f"Knowledge Level: {kl}, Questions: {questions}, Max: {max_marks}, Obtained: {obtained}, Performance: {performance:.2f}%")
            
            # Calculate overall knowledge level percentage (Bloom's Taxonomy)
            if total_max_marks > 0:
                overall_knowledge_percentage = (total_obtained / total_max_marks) * 100
            else:
                overall_knowledge_percentage = 0
            
            # Calculate overall CO attainment
            total_co_max_marks = 0
            total_co_obtained = 0
            valid_cos = [co for co in co_attainment if co['attainment'] > 0]
            
            for co in valid_cos:
                
                co_weight = 1.0  
                total_co_max_marks += co_weight * 100  # Max possible for each CO is 100%
                total_co_obtained += co_weight * co['attainment']
            
            if total_co_max_marks > 0:
                overall_co_percentage = total_co_obtained / total_co_max_marks * 100
            else:
                overall_co_percentage = 0
            
            print(f"Overall Knowledge Level Performance: {overall_knowledge_percentage:.2f}%")
            print(f"Overall CO Attainment: {overall_co_percentage:.2f}%")
            
            # Generate PDF report
            pdf_path = self._generate_pdf(
                student_info,
                course_info,
                test_performance,
                best_two_avg_obtained,
                best_two_avg_total,
                best_two_avg_percentage,
                co_attainment,
                overall_co_percentage,
                knowledge_level_performance,
                total_questions,
                total_max_marks,
                total_obtained,
                overall_knowledge_percentage
            )
            
            cursor.close()
            connection.close()
            
            return {
                "status": "success",
                "message": f"CO attainment report generated for {student_info['name']}",
                "pdf_path": pdf_path
            }
            
        except Exception as e:
            import traceback
            traceback.print_exc()
            return {"status": "error", "message": f"Error generating report: {str(e)}"}
    
    def _generate_pdf(self, student_info, course_info, test_performance, best_two_avg_obtained, 
                     best_two_avg_total, best_two_avg_percentage, co_attainment, 
                     overall_co_percentage, knowledge_level_performance, total_questions,
                     total_max_marks, total_obtained, overall_knowledge_percentage):
        """Generate a PDF report with student CO attainment data with modern design"""
        
       
        reports_dir = "../../reports/student_co"
        os.makedirs(reports_dir, exist_ok=True)
        
        
        primary_color = (41, 65, 122)  
        secondary_color = (65, 105, 225)  
        accent_color = (70, 130, 180)  
        text_color = (50, 50, 50)  
        light_bg = (245, 245, 245)  
        
        # Status colors
        high_color = (46, 139, 87)  
        medium_color = (255, 140, 0)  
        low_color = (220, 20, 60)  
        
        
        pdf = FPDF()
        pdf.add_page()
        pdf.set_auto_page_break(auto=True, margin=15)
        
        
        pdf.set_font("Arial", "", 10)
        pdf.set_text_color(text_color[0], text_color[1], text_color[2])
        
        
        pdf.set_fill_color(primary_color[0], primary_color[1], primary_color[2])
        pdf.rect(0, 0, 210, 30, 'F')
        pdf.set_y(10)
        pdf.set_text_color(255, 255, 255)
        pdf.set_font("Arial", "B", 18)
        pdf.cell(0, 10, "COURSE OUTCOME ATTAINMENT REPORT", 0, 1, "C")
        
        # Add subtitle
        pdf.set_font("Arial", "", 12)
        pdf.cell(0, 6, f"Generated on {datetime.now().strftime('%d %b %Y')}", 0, 1, "C")
        
      
        pdf.set_y(33)
        pdf.set_draw_color(200, 200, 200)
        pdf.line(10, 33, 200, 33)
        
        # Student information section
        pdf.set_y(40)
        pdf.set_text_color(text_color[0], text_color[1], text_color[2])
        pdf.set_font("Arial", "B", 14)
        pdf.cell(0, 10, "Student Information", 0, 1, "L")
        
        
        pdf.set_fill_color(light_bg[0], light_bg[1], light_bg[2])
        pdf.set_draw_color(secondary_color[0], secondary_color[1], secondary_color[2])
        pdf.rect(10, 52, 190, 35, 'DF')
        
       
        pdf.set_y(54)
        pdf.set_x(15)
        pdf.set_font("Arial", "B", 10)
        pdf.cell(40, 8, "Student Name:", 0, 0, "L")
        pdf.set_font("Arial", "", 10)
        pdf.cell(55, 8, student_info['name'], 0, 0, "L")
        
        pdf.set_font("Arial", "B", 10)
        pdf.cell(40, 8, "Register No:", 0, 0, "L")
        pdf.set_font("Arial", "", 10)
        pdf.cell(40, 8, student_info['register_no'], 0, 1, "L")
        
        pdf.set_x(15)
        pdf.set_font("Arial", "B", 10)
        pdf.cell(40, 8, "Course:", 0, 0, "L")
        pdf.set_font("Arial", "", 10)
        pdf.cell(55, 8, f"{course_info['course_name']}", 0, 0, "L")
        
        pdf.set_font("Arial", "B", 10)
        pdf.cell(40, 8, "Course Code:", 0, 0, "L")
        pdf.set_font("Arial", "", 10)
        pdf.cell(40, 8, f"{course_info['course_code']}", 0, 1, "L")
        
        pdf.set_x(15)
        pdf.set_font("Arial", "B", 10)
        pdf.cell(40, 8, "Semester:", 0, 0, "L")
        pdf.set_font("Arial", "", 10)
        pdf.cell(55, 8, str(student_info['semester']), 0, 0, "L")
        
        # Test performance summary
        pdf.set_y(95)
        pdf.set_font("Arial", "B", 14)
        pdf.cell(0, 10, "Test Performance Summary", 0, 1, "L")
        
       
        col_width = 47.5
        row_height = 10
        
        
        pdf.set_fill_color(primary_color[0], primary_color[1], primary_color[2])
        pdf.set_text_color(255, 255, 255)
        pdf.set_font("Arial", "B", 10)
        pdf.set_x(10)
        pdf.cell(col_width, row_height, "Test No", 1, 0, "C", 1)
        pdf.cell(col_width, row_height, "Total Marks", 1, 0, "C", 1)
        pdf.cell(col_width, row_height, "Obtained", 1, 0, "C", 1)
        pdf.cell(col_width, row_height, "Percentage", 1, 1, "C", 1)
        
        
        pdf.set_text_color(text_color[0], text_color[1], text_color[2])
        pdf.set_font("Arial", "", 10)
        
        for i, test in enumerate(test_performance):
            
            if i % 2 == 0:
                pdf.set_fill_color(245, 245, 245)
            else:
                pdf.set_fill_color(255, 255, 255)
                
            pdf.set_x(10)
            pdf.cell(col_width, row_height, str(test['test_no']), 1, 0, "C", 1)
            pdf.cell(col_width, row_height, f"{test['total_marks']:.1f}", 1, 0, "C", 1)
            
            
            if test['is_absent']:
                pdf.set_text_color(low_color[0], low_color[1], low_color[2])
                pdf.cell(col_width, row_height, "ABSENT", 1, 0, "C", 1)
                pdf.set_text_color(text_color[0], text_color[1], text_color[2])
            else:
                pdf.cell(col_width, row_height, f"{test['obtained']:.1f}", 1, 0, "C", 1)
            
            
            pdf.cell(col_width, row_height, f"{test['percentage']:.1f}%", 1, 1, "C", 1)
        
        # Best 2 Avg row with highlight
        pdf.set_x(10)
        pdf.set_fill_color(secondary_color[0], secondary_color[1], secondary_color[2])
        pdf.set_text_color(255, 255, 255)
        pdf.set_font("Arial", "B", 10)
        pdf.cell(col_width, row_height, "Best 2 Avg", 1, 0, "C", 1)
        pdf.cell(col_width, row_height, f"{best_two_avg_total:.1f}", 1, 0, "C", 1)
        pdf.cell(col_width, row_height, f"{best_two_avg_obtained:.1f}", 1, 0, "C", 1)
        pdf.cell(col_width, row_height, f"{best_two_avg_percentage:.1f}%", 1, 1, "C", 1)
        
        # Course Outcome Attainment
        pdf.ln(10)
        pdf.set_text_color(text_color[0], text_color[1], text_color[2])
        pdf.set_font("Arial", "B", 14)
        pdf.cell(0, 10, "Course Outcome Attainment", 0, 1, "L")
        
        # CO attainment table
        co_col_width = [20, 100, 35, 35]  # CO No, Description, Attainment %, Level
        
        
        pdf.set_fill_color(primary_color[0], primary_color[1], primary_color[2])
        pdf.set_text_color(255, 255, 255)
        pdf.set_font("Arial", "B", 10)
        pdf.set_x(10)
        pdf.cell(co_col_width[0], row_height, "CO No", 1, 0, "C", 1)
        pdf.cell(co_col_width[1], row_height, "Description", 1, 0, "C", 1)
        pdf.cell(co_col_width[2], row_height, "Attainment %", 1, 0, "C", 1)
        pdf.cell(co_col_width[3], row_height, "Level", 1, 1, "C", 1)
        
        
        pdf.set_text_color(text_color[0], text_color[1], text_color[2])
        pdf.set_font("Arial", "", 10)
        
        for i, co in enumerate(co_attainment):
           
            if i % 2 == 0:
                pdf.set_fill_color(245, 245, 245)
            else:
                pdf.set_fill_color(255, 255, 255)
                
            pdf.set_x(10)
            pdf.cell(co_col_width[0], row_height, co['co_no'], 1, 0, "C", 1)
            pdf.cell(co_col_width[1], row_height, co['description'], 1, 0, "L", 1)
            
            # Show "No Data" when percentage is 0
            if co['attainment'] == 0:
                pdf.cell(co_col_width[2], row_height, "No Data", 1, 0, "C", 1)
                pdf.cell(co_col_width[3], row_height, "N/A", 1, 1, "C", 1)
            else:
                pdf.cell(co_col_width[2], row_height, f"{co['attainment']:.1f}%", 1, 0, "C", 1)
                
                
                if co['level'] == "High":
                    pdf.set_text_color(high_color[0], high_color[1], high_color[2])
                elif co['level'] == "Medium":
                    pdf.set_text_color(medium_color[0], medium_color[1], medium_color[2])
                else:
                    pdf.set_text_color(low_color[0], low_color[1], low_color[2])
                    
                pdf.cell(co_col_width[3], row_height, co['level'], 1, 1, "C", 1)
                pdf.set_text_color(text_color[0], text_color[1], text_color[2])
        
        # Add a summary box with overall CO attainment
        pdf.ln(5)
        pdf.set_fill_color(accent_color[0], accent_color[1], accent_color[2])
        pdf.set_text_color(255, 255, 255)
        pdf.set_font("Arial", "B", 11)
        pdf.set_x(10)
        pdf.cell(190, 10, f"Overall CO Attainment: {overall_co_percentage:.1f}%", 1, 1, "C", 1)
        
        # Add a new page for Bloom's taxonomy
        pdf.add_page()
        
       
        pdf.set_fill_color(primary_color[0], primary_color[1], primary_color[2])
        pdf.rect(0, 0, 210, 30, 'F')
        pdf.set_y(10)
        pdf.set_text_color(255, 255, 255)
        pdf.set_font("Arial", "B", 18)
        pdf.cell(0, 10, "KNOWLEDGE LEVEL PERFORMANCE", 0, 1, "C")
        
        
        pdf.set_font("Arial", "", 12)
        pdf.cell(0, 6, "Bloom's Taxonomy Analysis", 0, 1, "C")
        
        
        pdf.set_y(33)
        pdf.set_draw_color(200, 200, 200)
        pdf.line(10, 33, 200, 33)
        
        # Bloom's Taxonomy Performance
        pdf.set_y(40)
        pdf.set_text_color(text_color[0], text_color[1], text_color[2])
        pdf.set_font("Arial", "B", 14)
        pdf.cell(0, 10, "Bloom's Taxonomy Performance", 0, 1, "L")
        
        # Bloom's table
        bloom_col_width = [40, 30, 40, 40, 40]  # Knowledge Level, Questions, Max Marks, Obtained, Percentage
        
        
        pdf.set_fill_color(primary_color[0], primary_color[1], primary_color[2])
        pdf.set_text_color(255, 255, 255)
        pdf.set_font("Arial", "B", 10)
        pdf.set_x(10)
        pdf.cell(bloom_col_width[0], row_height, "Knowledge Level", 1, 0, "C", 1)
        pdf.cell(bloom_col_width[1], row_height, "Questions", 1, 0, "C", 1)
        pdf.cell(bloom_col_width[2], row_height, "Max Marks", 1, 0, "C", 1)
        pdf.cell(bloom_col_width[3], row_height, "Obtained", 1, 0, "C", 1)
        pdf.cell(bloom_col_width[4], row_height, "Percentage", 1, 1, "C", 1)
        
        
        pdf.set_x(10)
        pdf.set_fill_color(accent_color[0], accent_color[1], accent_color[2])
        pdf.set_text_color(255, 255, 255)
        pdf.set_font("Arial", "B", 10)
        pdf.cell(bloom_col_width[0], row_height, "Overall", 1, 0, "L", 1)
        pdf.cell(bloom_col_width[1], row_height, str(total_questions), 1, 0, "C", 1)
        pdf.cell(bloom_col_width[2], row_height, f"{total_max_marks:.1f}", 1, 0, "C", 1)
        pdf.cell(bloom_col_width[3], row_height, f"{total_obtained:.1f}", 1, 0, "C", 1)
        pdf.cell(bloom_col_width[4], row_height, f"{overall_knowledge_percentage:.1f}%", 1, 1, "C", 1)
        
        # Analysis & Recommendations
        pdf.ln(10)
        pdf.set_text_color(text_color[0], text_color[1], text_color[2])
        pdf.set_font("Arial", "B", 14)
        pdf.cell(0, 10, "Analysis & Recommendations", 0, 1, "L")
        
        
        pdf.set_fill_color(light_bg[0], light_bg[1], light_bg[2])
        pdf.set_draw_color(secondary_color[0], secondary_color[1], secondary_color[2])
        pdf.rect(10, pdf.get_y(), 190, 90, 'DF')
        
       
        pdf.set_font("Arial", "B", 12)
        y_pos = pdf.get_y() + 5
        pdf.set_y(y_pos)
        pdf.set_x(15)
        pdf.cell(0, 10, "Performance Summary:", 0, 1, "L")
        
        # Find strongest and weakest knowledge levels
        if knowledge_level_performance:
            
            sorted_levels = sorted(knowledge_level_performance.items(), key=lambda x: x[1], reverse=True)
            strongest_level = sorted_levels[0] if sorted_levels else None
            weakest_level = sorted_levels[-1] if len(sorted_levels) > 1 else None
        else:
            strongest_level = None
            weakest_level = None
        
        
        valid_cos = [co for co in co_attainment if co['attainment'] > 0]
        
        
        pdf.set_font("Arial", "", 10)
        
        # Overall CO Attainment
        y_pos = pdf.get_y() + 3
        pdf.set_y(y_pos)
        pdf.set_x(20)
        
       
        pdf.set_fill_color(secondary_color[0], secondary_color[1], secondary_color[2])
        pdf.rect(15, pdf.get_y() + 2, 3, 3, 'F')
        
        if not valid_cos:
            pdf.cell(0, 6, "Insufficient data available for CO attainment analysis.", 0, 1, "L")
        else:
            
            pdf.set_x(20)
            if overall_co_percentage >= 70:
                pdf.set_text_color(high_color[0], high_color[1], high_color[2])
                label = "Excellent"
            elif overall_co_percentage >= 50:
                pdf.set_text_color(medium_color[0], medium_color[1], medium_color[2])
                label = "Satisfactory"
            else:
                pdf.set_text_color(low_color[0], low_color[1], low_color[2])
                label = "Needs Improvement"
                
            pdf.cell(0, 6, f"Overall CO Attainment: {overall_co_percentage:.1f}% - {label}", 0, 1, "L")
            pdf.set_text_color(text_color[0], text_color[1], text_color[2])
        
        
        if len(valid_cos) >= 2:
            strongest_co = max(valid_cos, key=lambda x: x['attainment'])
            weakest_co = min(valid_cos, key=lambda x: x['attainment'])
            
            # Strongest CO
            y_pos = pdf.get_y() + 3
            pdf.set_y(y_pos)
            pdf.set_x(20)
            
            pdf.set_fill_color(high_color[0], high_color[1], high_color[2])
            pdf.rect(15, pdf.get_y() + 2, 3, 3, 'F')
            
            pdf.set_text_color(high_color[0], high_color[1], high_color[2])
            pdf.cell(0, 6, f"Strongest in {strongest_co['co_no']} ({strongest_co['attainment']:.1f}%)", 0, 1, "L")
            pdf.set_text_color(text_color[0], text_color[1], text_color[2])
            
            # Weakest CO
            y_pos = pdf.get_y() + 3
            pdf.set_y(y_pos)
            pdf.set_x(20)
            
            pdf.set_fill_color(low_color[0], low_color[1], low_color[2])
            pdf.rect(15, pdf.get_y() + 2, 3, 3, 'F')
            
            pdf.set_text_color(low_color[0], low_color[1], low_color[2])
            pdf.cell(0, 6, f"Needs improvement in {weakest_co['co_no']} ({weakest_co['attainment']:.1f}%)", 0, 1, "L")
            pdf.set_text_color(text_color[0], text_color[1], text_color[2])
        
        # Knowledge levels
        if strongest_level:
            level_number = {"Remember": 1, "Understand": 2, "Apply": 3, "Analyze": 4, "Evaluate": 5, "Create": 6}.get(strongest_level[0], 1)
            level_desc = {"Remember": "Knowledge Recall", "Understand": "Comprehension", "Apply": "Application", 
                        "Analyze": "Analysis", "Evaluate": "Evaluation", "Create": "Creation"}.get(strongest_level[0], "Knowledge Recall")
            
            y_pos = pdf.get_y() + 3
            pdf.set_y(y_pos)
            pdf.set_x(20)
            
            pdf.set_fill_color(high_color[0], high_color[1], high_color[2])
            pdf.rect(15, pdf.get_y() + 2, 3, 3, 'F')
            
            pdf.set_text_color(high_color[0], high_color[1], high_color[2])
            pdf.cell(0, 6, f"Strongest in {strongest_level[0]} (Level {level_number} - {level_desc}) questions ({strongest_level[1]:.1f}%)", 0, 1, "L")
            pdf.set_text_color(text_color[0], text_color[1], text_color[2])
        
        if weakest_level:
            level_number = {"Remember": 1, "Understand": 2, "Apply": 3, "Analyze": 4, "Evaluate": 5, "Create": 6}.get(weakest_level[0], 1)
            level_desc = {"Remember": "Knowledge Recall", "Understand": "Comprehension", "Apply": "Application", 
                        "Analyze": "Analysis", "Evaluate": "Evaluation", "Create": "Creation"}.get(weakest_level[0], "Knowledge Recall")
            
            y_pos = pdf.get_y() + 3
            pdf.set_y(y_pos)
            pdf.set_x(20)
            
            pdf.set_fill_color(low_color[0], low_color[1], low_color[2])
            pdf.rect(15, pdf.get_y() + 2, 3, 3, 'F')
            
            pdf.set_text_color(low_color[0], low_color[1], low_color[2])
            pdf.cell(0, 6, f"Needs improvement in {weakest_level[0]} (Level {level_number} - {level_desc}) questions ({weakest_level[1]:.1f}%)", 0, 1, "L")
            pdf.set_text_color(text_color[0], text_color[1], text_color[2])
        
        # Recommendation for low COs
        if valid_cos:
            low_cos = [co for co in valid_cos if co['level'] == 'Low']
            if low_cos:
                y_pos = pdf.get_y() + 3
                pdf.set_y(y_pos)
                pdf.set_x(20)
                
                pdf.set_fill_color(medium_color[0], medium_color[1], medium_color[2])
                pdf.rect(15, pdf.get_y() + 2, 3, 3, 'F')
                
                pdf.set_text_color(medium_color[0], medium_color[1], medium_color[2])
                pdf.cell(0, 6, f"Focus on improving {', '.join([co['co_no'] for co in low_cos])} which have low attainment", 0, 1, "L")
                pdf.set_text_color(text_color[0], text_color[1], text_color[2])
        
        # Overall Test Performance
        y_pos = pdf.get_y() + 3
        pdf.set_y(y_pos)
        pdf.set_x(20)
        
        pdf.set_fill_color(secondary_color[0], secondary_color[1], secondary_color[2])
        pdf.rect(15, pdf.get_y() + 2, 3, 3, 'F')
        
        pdf.cell(0, 6, f"Overall Test Performance: {best_two_avg_percentage:.1f}%", 0, 1, "L")
        
       
        pdf.set_y(270)
        pdf.set_font("Arial", "I", 8)
        pdf.set_text_color(100, 100, 100)
        pdf.cell(0, 10, f"Generated by AuraAI on {datetime.now().strftime('%d %b %Y at %H:%M')} - Page {pdf.page_no()}", 0, 0, "C")
        
        # Save the PDF
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"{student_info['register_no']}_{timestamp}_co_report.pdf"
        pdf_path = os.path.join(reports_dir, filename)
        pdf.output(pdf_path)
        
        # Return a URL path accessible by the web server
        web_path = f"/reports/student_co/{filename}"
        return web_path
    
    def debug_student_co_data(self, register_no):
        """Debug method to diagnose CO attainment calculation issues for a student"""
        try:
            # Connect to the database
            connection = get_db_connection()
            cursor = connection.cursor(dictionary=True)
            
            # Get tests taken by the student
            tests_query = """
                SELECT DISTINCT t.id, t.test_no, t.fc_id, fc.course_id
                FROM test t
                JOIN mark m ON t.id = m.test_id
                JOIN faculty_course fc ON t.fc_id = fc.id
                WHERE m.student_id = %s
                ORDER BY t.test_no
            """
            cursor.execute(tests_query, (register_no,))
            tests = cursor.fetchall()
            
            print(f"=== DEBUG INFO FOR STUDENT {register_no} ===")
            print(f"Found {len(tests)} tests taken by the student")
            
            for test in tests:
                print(f"\nTest #{test['test_no']} (ID: {test['id']}):")
                print(f"Course: {test['course_id']}")
                
                # Get questions for this test
                questions_query = """
                    SELECT q.id, q.question_no, q.max_mark
                    FROM question q
                    WHERE q.test_id = %s
                    ORDER BY q.question_no
                """
                cursor.execute(questions_query, (test['id'],))
                questions = cursor.fetchall()
                
                print(f"Found {len(questions)} questions in test #{test['test_no']}")
                
                # Check CO mappings for each question
                for q in questions:
                    co_mapping_query = """
                        SELECT qc.co_id
                        FROM question_co qc
                        WHERE qc.question_id = %s
                    """
                    cursor.execute(co_mapping_query, (q['id'],))
                    co_mappings = cursor.fetchall()
                    
                    # Get marks for this question
                    marks_query = """
                        SELECT m.obtained_mark
                        FROM mark m
                        WHERE m.student_id = %s AND m.question_id = %s
                    """
                    cursor.execute(marks_query, (register_no, q['id']))
                    mark_data = cursor.fetchone()
                    
                    co_ids = [m['co_id'] for m in co_mappings]
                    
                    if co_ids:
                        # Get CO numbers for these IDs
                        co_numbers_query = """
                            SELECT co.id, co.co_number
                            FROM course_outcome co
                            WHERE co.id IN ({})
                        """.format(','.join(['%s'] * len(co_ids)))
                        
                        cursor.execute(co_numbers_query, co_ids)
                        co_numbers = cursor.fetchall()
                        
                        co_info = [f"CO{co['co_number']} (ID: {co['id']})" for co in co_numbers]
                        print(f"  Q{q['question_no']} (ID: {q['id']}): Max={q['max_mark']}, Obtained={mark_data['obtained_mark'] if mark_data else 'No mark'}, Mapped to {', '.join(co_info) if co_info else 'No CO'}")
                    else:
                        print(f"  Q{q['question_no']} (ID: {q['id']}): Max={q['max_mark']}, Obtained={mark_data['obtained_mark'] if mark_data else 'No mark'}, Not mapped to any CO")
            
            # Check course outcomes for the course
            if tests:
                course_id = tests[0]['course_id']
                co_query = """
                    SELECT co.id, co.co_number
                    FROM course_outcome co
                    WHERE co.course_id = %s
                    ORDER BY co.co_number
                """
                cursor.execute(co_query, (course_id,))
                course_outcomes = cursor.fetchall()
                
                print(f"\nCourse Outcomes for {course_id}:")
                if course_outcomes:
                    for co in course_outcomes:
                        print(f"CO{co['co_number']} (ID: {co['id']})")
                else:
                    print("No course outcomes defined for this course")
            
            cursor.close()
            connection.close()
            
            print("\n=== END DEBUG INFO ===")
            return True
            
        except Exception as e:
            import traceback
            traceback.print_exc()
            print(f"Error in debug: {str(e)}")
            return False 