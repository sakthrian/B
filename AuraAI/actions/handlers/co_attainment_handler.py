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

logger = logging.getLogger(__name__)

class ActionCompareCOAttainment(Action):
    """Handler for comparing Course Outcome attainment between different batches"""

    def name(self) -> Text:
        return "action_compare_co_attainment"

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

    def _extract_batches(self, message):
        """Extract batch years from message, allowing spaces around the hyphen"""
        # Pattern to match YYYY-YYYY with optional spaces
        pattern = r'(\d{4}\s*-\s*\d{4})'
        return [b.replace(' ', '') for b in re.findall(pattern, message)]

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

    def _generate_subject_plot(self, df, subject_name):
        """Generate plot for a specific subject and return as bytes"""
        try:
            
            plt.style.use('ggplot')
            
            # Create a figure 
            fig, ax = plt.subplots(figsize=(12, 6.5))
            
           
            plt.subplots_adjust(top=0.88, bottom=0.15, left=0.12, right=0.85)
            
            
            subject_data = df[df['course_name'] == subject_name]
            plot_data = subject_data.pivot(index='batch', columns='co_number', values='attainment_percentage')
            
            # Create the bar chart
            bars = plot_data.plot(kind='bar', width=0.75, ax=ax, edgecolor='white', linewidth=0.7)
            
            # title and labels 
            plt.title(f'CO Attainment Comparison for {subject_name}', fontsize=14, fontweight='bold', pad=20)
            plt.xlabel('Batch', fontsize=12, labelpad=10)
            plt.ylabel('Attainment Value', fontsize=12, labelpad=10)
            
            
            plt.legend(title='Course Outcomes', bbox_to_anchor=(1.04, 1), loc='upper left', 
                      title_fontsize=11, fontsize=10, frameon=True, facecolor='white', edgecolor='lightgray')
            
            # Rotate x-axis labels 
            plt.xticks(rotation=45, ha='right', fontsize=10)
            plt.yticks(fontsize=10)
            
            
            plt.grid(True, axis='y', linestyle='--', alpha=0.6, color='gray')
            
            # Add value labels on top of bars 
            for container in ax.containers:
                ax.bar_label(container, fmt='%.2f', padding=3, fontsize=9, fontweight='bold')
            
           
            max_value = plot_data.values.max()
            plt.ylim(0, max_value * 1.15)  
            
           
            for spine in ax.spines.values():
                spine.set_edgecolor('lightgray')
                spine.set_linewidth(0.8)
            
            
            plt.tight_layout()
            
            # Save the plot to a BytesIO object
            buf = BytesIO()
            plt.savefig(buf, format='png', bbox_inches='tight', dpi=300)
            buf.seek(0)
            plt.close(fig)
            
            return buf.getvalue()
            
        except Exception as e:
            logger.error(f"Error generating plot for {subject_name}: {e}")
            return None

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
            
            # Get unique batches (up to 3 )
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

    def _generate_pdf_report(self, df, batches):
        """Generate PDF report with comparisons using the common PDF generator."""
        try:
            # import fpdf2 for Unicode support
            try:
                from fpdf import FPDF2 as PDF
                use_fpdf2 = True
                logger.info("Using FPDF2 for better Unicode support")
            except ImportError:
                from fpdf import FPDF as PDF
                use_fpdf2 = False
                logger.info("Using standard FPDF - some Unicode characters may not display correctly")
            
            class ModernPDF(PDF):
                def __init__(self):
                    # Initialize with UTF-8 support if using FPDF2
                    if use_fpdf2:
                        super().__init__(orientation='P', unit='mm', format='A4')
                    else:
                        super().__init__()
                
                def header(self):
                    
                    self.set_fill_color(41, 128, 185)  
                    self.rect(0, 0, 210, 15, 'F')
                    
                    
                    self.set_font('Arial', 'B', 10)
                    self.set_text_color(255, 255, 255)
                    self.cell(0, 15, "AuraAI Analytics - CO Attainment Report", 0, 0, 'R')
                    
                   
                    self.set_text_color(0, 0, 0)
                    self.ln(20)
                
                def footer(self):
                    
                    self.set_y(-15)
                    self.set_font('Arial', 'I', 8)
                    self.set_text_color(128, 128, 128)
                    self.cell(0, 10, f'Page {self.page_no()}', 0, 0, 'C')
            
            # Generate a timestamp for temporary files
            timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
            
           
            batch_text = ', '.join(batches)
            pdf = ModernPDF()
            pdf.set_auto_page_break(auto=True, margin=15)
            
            # Check fallback for FPDF encoding issues
            if not use_fpdf2:
                
                logger.info("Using standard FPDF - avoiding special characters")
            
            pdf.add_page()
            
            
            title_color = (41, 128, 185)  
            subtitle_color = (52, 73, 94)  
            text_color = (44, 62, 80)      
            highlight_color = (231, 76, 60) 
            
           
            pdf.set_font("Arial", "B", 18)
            pdf.set_text_color(*title_color)
            pdf.cell(0, 10, f"CO Attainment Comparison", 0, 1, "L")
            pdf.set_font("Arial", "", 12)
            pdf.set_text_color(*subtitle_color)
            pdf.cell(0, 10, f"Batches: {batch_text}", 0, 1, "L")
            pdf.ln(5)
            
           
            pdf.set_draw_color(*title_color)
            pdf.set_line_width(0.5)
            pdf.line(10, pdf.get_y(), 200, pdf.get_y())
            pdf.ln(10)
            
            
            pdf.set_font("Arial", "B", 14)
            pdf.set_text_color(*title_color)
            pdf.cell(0, 10, "OVERALL SUMMARY", 0, 1)
            pdf.ln(5)
            
            # Calculate batch averages
            batch_avg = df.groupby('batch')['attainment_percentage'].mean()
            
            
            pdf.set_font("Arial", "B", 12)
            pdf.set_text_color(*subtitle_color)
            pdf.cell(0, 10, "Batch Performance Overview:", 0, 1)
            pdf.set_font("Arial", "", 11)
            pdf.set_text_color(*text_color)
            
            # Find the best performing batch
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
            
            # Add best subject-batch combination
            best_combo = df.groupby(['course_name', 'batch'])['attainment_percentage'].mean()
            if not best_combo.empty:
                best_subject, best_batch = best_combo.idxmax()
                
                pdf.ln(5)
                pdf.set_font("Arial", "B", 12)
                pdf.set_text_color(*subtitle_color)
                pdf.cell(0, 10, "Best Overall Performance:", 0, 1)
                
                
                pdf.set_fill_color(245, 245, 245)
                pdf.set_font("Arial", "", 11)
                pdf.set_text_color(*text_color)
                pdf.multi_cell(0, 8, 
                    f"Subject: {best_subject}\n"
                    f"Batch: {best_batch}\n"
                    f"Average Attainment: {best_combo.max():.2f}", 
                    1, 'L', 1)
            
            
            pdf.add_page()
            pdf.set_font("Arial", "B", 14)
            pdf.set_text_color(*title_color)
            pdf.cell(0, 10, "CO ATTAINMENT VISUALIZATION", 0, 1)
            
            
            pdf.set_draw_color(*title_color)
            pdf.line(10, pdf.get_y(), 200, pdf.get_y())
            pdf.ln(10)
            
            
            try:
                logger.info("Generating simple visualization chart")
                
               
                plt.figure(figsize=(10, 6))
                plt.style.use('ggplot')  
                
                
                co_batch_avg = df.pivot_table(
                    values='attainment_percentage', 
                    index='batch', 
                    columns='co_number', 
                    aggfunc='mean'
                )
                
                logger.info(f"Created pivot table: {co_batch_avg.shape}")
                
                
                if not co_batch_avg.empty:
                    
                    colors = plt.cm.tab10.colors
                    
                    ax = co_batch_avg.plot(
                        kind='bar',
                        figsize=(10, 6),
                        rot=0,
                        width=0.7,
                        color=colors,
                        edgecolor='white',
                        linewidth=1.5,
                        legend=True
                    )
                    
                    
                    plt.title('CO Attainment by Batch', fontsize=16, fontweight='bold', pad=20)
                    plt.ylabel('Attainment Value', fontsize=12)
                    plt.xlabel('Batch', fontsize=12)
                    plt.grid(True, linestyle='--', alpha=0.4, axis='y')
                    
                   
                    legend = plt.legend(title='Course Outcomes', frameon=True, fancybox=True, framealpha=0.8, 
                                      fontsize=10, title_fontsize=12)
                    legend.get_frame().set_edgecolor('lightgray')
                    
                    
                    for container in ax.containers:
                        ax.bar_label(container, fmt='%.1f', fontsize=9, fontweight='bold', padding=5)
                    
                    
                    plt.tight_layout()
                    ax.spines['top'].set_visible(False)
                    ax.spines['right'].set_visible(False)
                    ax.spines['left'].set_linewidth(0.5)
                    ax.spines['bottom'].set_linewidth(0.5)
                    
                    logger.info("Chart created successfully")
                    
                    # Save the figure to a temporary file
                    temp_dir = os.path.join(os.getcwd(), "pdf_reports", "temp")
                    os.makedirs(temp_dir, exist_ok=True)
                    chart_path = os.path.join(temp_dir, f"co_viz_{timestamp}.png")
                    plt.savefig(chart_path, dpi=200, bbox_inches='tight')
                    plt.close()
                    
                    logger.info(f"Chart saved to: {chart_path}")
                    
                    # Verify file exists before adding to PDF
                    if os.path.exists(chart_path):
                        pdf.image(chart_path, x=10, w=190)
                        logger.info("Chart added to PDF successfully")
                        
                        # Clean up temporary file
                        try:
                            os.remove(chart_path)
                        except Exception as e:
                            logger.warning(f"Could not remove temp chart file: {e}")
                    else:
                        logger.error(f"Chart file not found: {chart_path}")
                        pdf.cell(0, 10, "Error: Could not generate visualization chart.", 0, 1)
                else:
                    logger.warning("No data available for visualization")
                    pdf.cell(0, 10, "No data available for visualization.", 0, 1)
                
                
                logger.info("Generating density plot comparison")
                pdf.ln(5)
                pdf.set_font("Arial", "B", 12)
                pdf.set_text_color(*subtitle_color)
                pdf.cell(0, 10, "Distribution of CO Attainment Across Batches", 0, 1)
                
                
                def _generate_modern_density_plot(self, df, subject_name=None, co_number=None):
                    """Generate modernized density plot"""
                    try:
                        plt.style.use('ggplot')  
                        fig, ax = plt.subplots(figsize=(12, 6))
                        
                        
                        filtered_data = df.copy()
                        if subject_name:
                            filtered_data = filtered_data[filtered_data['course_name'] == subject_name]
                        if co_number:
                            filtered_data = filtered_data[filtered_data['co_number'] == co_number]
                        
                        
                        if filtered_data.empty or len(filtered_data['batch'].unique()) < 2:
                            return None
                        
                        # Get unique batches (up to 3 )
                        batches = filtered_data['batch'].unique()[:3]
                        
                      
                        colors = ['#3498db', '#e74c3c', '#2ecc71']
                        
                       
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
                        
                       
                        title_text = "Distribution of CO Attainment Values"
                        if subject_name:
                            title_text += f" for {subject_name}"
                        if co_number:
                            title_text += f" - CO{co_number}"
                            
                        plt.title(title_text, fontsize=16, fontweight='bold', pad=20)
                        plt.xlabel('CO Attainment Value', fontsize=12, labelpad=10)
                        plt.ylabel('Density', fontsize=12, labelpad=10)
                        
                        
                        plt.grid(True, linestyle='--', alpha=0.3)
                        
                       
                        legend = plt.legend(fontsize=10, frameon=True, fancybox=True, framealpha=0.8)
                        legend.get_frame().set_edgecolor('lightgray')
                        
                        
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
                
               
                density_plot = _generate_modern_density_plot(self, df)
                if density_plot:
                    # Save the density plot temporarily
                    density_path = os.path.join(temp_dir, f"density_plot_{timestamp}.png")
                    with open(density_path, 'wb') as f:
                        f.write(density_plot)
                    
                    # Add to PDF
                    if os.path.exists(density_path):
                        pdf.image(density_path, x=10, w=190)
                        logger.info("Density plot added to PDF successfully")
                        
                        # Clean up temporary file
                        try:
                            os.remove(density_path)
                        except Exception as e:
                            logger.warning(f"Could not remove temp density plot file: {e}")
                    else:
                        logger.error(f"Density plot file not found: {density_path}")
                else:
                    logger.warning("Could not generate density plot")
                    pdf.cell(0, 10, "Insufficient data for distribution comparison.", 0, 1)
                
                # Add a new page for the batch comparison table
                pdf.add_page()
                pdf.set_font("Arial", "B", 14)
                pdf.set_text_color(*title_color)
                pdf.cell(0, 10, "Batch Performance Summary", 0, 1)
                
                
                pdf.set_draw_color(*title_color)
                pdf.line(10, pdf.get_y(), 200, pdf.get_y())
                pdf.ln(10)
                
                
                pdf.set_font("Arial", "B", 11)
                pdf.set_text_color(255, 255, 255)
                
               
                col_widths = [60, 60, 60]
                pdf.set_fill_color(*title_color)
                pdf.cell(col_widths[0], 10, "Batch", 1, 0, "C", 1)
                pdf.cell(col_widths[1], 10, "Avg. Attainment", 1, 0, "C", 1)
                pdf.cell(col_widths[2], 10, "Best Subject", 1, 1, "C", 1)
                
                
                pdf.set_font("Arial", "", 10)
                pdf.set_text_color(*text_color)
                batch_avg = df.groupby('batch')['attainment_percentage'].mean()
                best_subject_by_batch = {}
                
                for batch in sorted(df['batch'].unique()):
                    batch_data = df[df['batch'] == batch]
                    if not batch_data.empty:
                        subject_avgs = batch_data.groupby('course_name')['attainment_percentage'].mean()
                        if not subject_avgs.empty:
                            best_subject_by_batch[batch] = subject_avgs.idxmax()
                        else:
                            best_subject_by_batch[batch] = "N/A"
                    else:
                        best_subject_by_batch[batch] = "N/A"
                
               
                for i, batch in enumerate(sorted(df['batch'].unique())):
                    
                    if i % 2 == 0:
                        pdf.set_fill_color(245, 245, 245) 
                    else:
                        pdf.set_fill_color(255, 255, 255)  
                    
                    pdf.cell(col_widths[0], 8, str(batch), 1, 0, "C", 1)
                    
                    if batch in batch_avg.index:
                        pdf.cell(col_widths[1], 8, f"{batch_avg[batch]:.2f}", 1, 0, "C", 1)
                    else:
                        pdf.cell(col_widths[1], 8, "N/A", 1, 0, "C", 1)
                    
                    # For subject name, truncate if too long
                    subject_name = best_subject_by_batch.get(batch, "N/A")
                    if subject_name != "N/A" and len(subject_name) > 30:
                        subject_name = subject_name[:27] + "..."
                    pdf.cell(col_widths[2], 8, subject_name, 1, 1, "C", 1)
                
            except Exception as viz_error:
                logger.error(f"Error generating visualization: {viz_error}")
                import traceback
                logger.error(f"Traceback: {traceback.format_exc()}")
                pdf.cell(0, 10, "Could not generate visualization due to an error.", 0, 1)
            
            # Add subject-wise analysis
            for subject in df['course_name'].unique():
                pdf.add_page()
                
                
                pdf.set_fill_color(*title_color)
                pdf.set_draw_color(*title_color)
                pdf.set_text_color(255, 255, 255)
                pdf.set_font("Arial", "B", 14)
                pdf.cell(0, 10, f"ANALYSIS: {subject}", 0, 1, 'L', 1)
                pdf.ln(5)
                
               
                pdf.set_text_color(*text_color)
                
                # Generate and add subject plot with modern styling
                def _generate_modern_subject_plot(self, df, subject_name):
                    """Generate modernized plot for a specific subject"""
                    try:
                        
                        plt.style.use('ggplot')  
                        
                        
                        fig, ax = plt.subplots(figsize=(12, 6.5))
                        
                        # Extract and prepare data
                        subject_data = df[df['course_name'] == subject_name]
                        plot_data = subject_data.pivot(index='batch', columns='co_number', values='attainment_percentage')
                        
                        
                        colors = plt.cm.tab10.colors
                        
                        
                        bars = plot_data.plot(
                            kind='bar', 
                            width=0.75, 
                            ax=ax, 
                            color=colors,
                            edgecolor='white', 
                            linewidth=1.5
                        )
                        
                       
                        plt.title(f'CO Attainment Comparison for {subject_name}', 
                                fontsize=16, fontweight='bold', pad=20)
                        plt.xlabel('Batch', fontsize=12, labelpad=10)
                        plt.ylabel('Attainment Value', fontsize=12, labelpad=10)
                        
                        
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
                        
                        
                        plt.xticks(rotation=45, ha='right', fontsize=10)
                        plt.yticks(fontsize=10)
                        
                        
                        plt.grid(True, axis='y', linestyle='--', alpha=0.3, color='gray')
                        
                        # Add value labels on top of bars 
                        for container in ax.containers:
                            ax.bar_label(container, fmt='%.2f', padding=3, fontsize=9, fontweight='bold')
                        
                        
                        max_value = plot_data.values.max()
                        plt.ylim(0, max_value * 1.15)  
                        
                        
                        ax.spines['top'].set_visible(False)
                        ax.spines['right'].set_visible(False)
                        ax.spines['left'].set_linewidth(0.5)
                        ax.spines['bottom'].set_linewidth(0.5)
                        
                        
                        plt.tight_layout()
                        
                        # Save the plot to a BytesIO object
                        buf = BytesIO()
                        plt.savefig(buf, format='png', dpi=200, bbox_inches='tight')
                        buf.seek(0)
                        plt.close(fig)
                        
                        return buf.getvalue()
                        
                    except Exception as e:
                        logger.error(f"Error generating plot for {subject_name}: {e}")
                        return None
                
                plot_data = _generate_modern_subject_plot(self, df, subject)
                if plot_data:
                   
                    temp_dir = os.path.join(os.getcwd(), "pdf_reports", "temp")
                    os.makedirs(temp_dir, exist_ok=True)
                    
                    # Save the plot temporarily
                    img_path = os.path.join(temp_dir, f"temp_{subject.replace(' ', '_')}.png")
                    with open(img_path, 'wb') as f:
                        f.write(plot_data)
                    
                    # Add the image to the PDF
                    pdf.image(img_path, x=10, w=190)
                    
                    # Clean up the temporary file
                    try:
                        os.remove(img_path)
                    except Exception as e:
                        logger.warning(f"Could not remove temporary file: {e}")
                
                
                pdf.ln(10)
                
                subject_data = df[df['course_name'] == subject]
                
                
                pdf.set_fill_color(245, 245, 245)  
                pdf.set_font("Arial", "B", 12)
                pdf.set_text_color(*subtitle_color)
                pdf.cell(0, 10, 'Faculty Information:', 0, 1, 'L')
                pdf.set_font("Arial", "", 11)
                pdf.set_text_color(*text_color)
                
                faculty_info = subject_data.groupby(['batch', 'faculty_name']).size().reset_index()
                for _, row in faculty_info.iterrows():
                    pdf.cell(0, 8, f"- {row['faculty_name']} (Batch {row['batch']})", 0, 1, 'L')
                
                
                pdf.ln(5)
                pdf.set_font("Arial", "B", 12)
                pdf.set_text_color(*subtitle_color)
                pdf.cell(0, 10, 'CO-wise Performance:', 0, 1, 'L')
                
                # Create modern card for CO performance
                co_avg = subject_data.groupby(['batch', 'co_number'])['attainment_percentage'].mean().unstack()
                
                # Create a modern table for CO performance
                pdf.set_font("Arial", "B", 11)
                pdf.set_text_color(255, 255, 255)
                pdf.set_fill_color(*title_color)
                
                
                pdf.cell(40, 8, "CO Number", 1, 0, "C", 1)
                pdf.cell(60, 8, "Best Batch", 1, 0, "C", 1)
                pdf.cell(40, 8, "Attainment", 1, 1, "C", 1)
                
                
                pdf.set_text_color(*text_color)
                pdf.set_font("Arial", "", 11)
                
                for i, co in enumerate(co_avg.columns):
                    
                    if i % 2 == 0:
                        pdf.set_fill_color(245, 245, 245)  
                    else:
                        pdf.set_fill_color(255, 255, 255)  
                    
                    best_batch_co = co_avg[co].idxmax()
                    pdf.cell(40, 8, f"CO{co}", 1, 0, "C", 1)
                    pdf.cell(60, 8, f"{best_batch_co}", 1, 0, "C", 1)
                    pdf.cell(40, 8, f"{co_avg[co][best_batch_co]:.2f}", 1, 1, "C", 1)
                
              
            
            # Generate filename and save PDF
            filename = f"co_attainment_report_{timestamp}.pdf"
            pdf_dir = os.path.join(os.getcwd(), "pdf_reports")
            os.makedirs(pdf_dir, exist_ok=True)
            
            physical_path = os.path.join(pdf_dir, filename)
            pdf.output(physical_path)
            
            # Return file path and URL
            web_url = f"http://localhost/B/AuraAI/pdf_reports/{filename}"
            logger.info(f"Generated PDF at physical path: {physical_path}, web URL: {web_url}")
            
            return physical_path, web_url
            
        except Exception as e:
            logger.error(f"Error generating PDF report: {e}")
            import traceback
            logger.error(f"Traceback: {traceback.format_exc()}")
            
           
            try:
                # Create a basic PDF 
                basic_pdf = FPDF()
                basic_pdf.add_page()
                basic_pdf.set_font("Arial", "B", 16)
                basic_pdf.cell(0, 10, f"CO Attainment Comparison - {', '.join(batches)}", 0, 1, "C")
                basic_pdf.ln(10)
                
                # Add basic data
                basic_pdf.set_font("Arial", "B", 12)
                basic_pdf.cell(0, 10, "Batch Performance Summary:", 0, 1)
                basic_pdf.ln(5)
                
                # Add batch averages
                basic_pdf.set_font("Arial", "", 12)
                batch_avg = df.groupby('batch')['attainment_percentage'].mean()
                for batch in batch_avg.index:
                    basic_pdf.cell(0, 10, f"Batch {batch}: {batch_avg[batch]:.2f}", 0, 1)
                
                # Add best performing batch info
                basic_pdf.ln(10)
                basic_pdf.set_font("Arial", "B", 12)
                basic_pdf.cell(0, 10, "Best Performances:", 0, 1)
                basic_pdf.ln(5)
                
                # Find best batch
                best_batch_overall = batch_avg.idxmax()
                basic_pdf.set_font("Arial", "", 12)
                basic_pdf.cell(0, 10, f"Best Batch: {best_batch_overall} ({batch_avg[best_batch_overall]:.2f})", 0, 1)
                
                # Find best subject-batch
                best_combo = df.groupby(['course_name', 'batch'])['attainment_percentage'].mean()
                if not best_combo.empty:
                    best_subject, best_batch = best_combo.idxmax()
                    basic_pdf.cell(0, 10, f"Best Subject: {best_subject} (Batch {best_batch}, {best_combo.max():.2f})", 0, 1)
                
                # Subject performance section
                basic_pdf.ln(10)
                basic_pdf.set_font("Arial", "B", 12)
                basic_pdf.cell(0, 10, "Subject Performance:", 0, 1)
                basic_pdf.ln(5)
                
                # Add top 5 subjects
                subject_avg = df.groupby('course_name')['attainment_percentage'].mean().sort_values(ascending=False)
                basic_pdf.set_font("Arial", "", 12)
                for i, (subject, avg) in enumerate(subject_avg.items()):
                    if i >= 5:
                        break
                    basic_pdf.cell(0, 10, f"{i+1}. {subject}: {avg:.2f}", 0, 1)
                
                # Save this simplified PDF
                timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
                basic_filename = f"simple_co_report_{timestamp}.pdf"
                pdf_dir = os.path.join(os.getcwd(), "pdf_reports")
                os.makedirs(pdf_dir, exist_ok=True)
                basic_path = os.path.join(pdf_dir, basic_filename)
                basic_pdf.output(basic_path)
                
                # Return the basic PDF
                web_url = f"http://localhost/B/AuraAI/pdf_reports/{basic_filename}"
                logger.info(f"Generated basic PDF at: {basic_path}, web URL: {web_url}")
                return basic_path, web_url
                
            except Exception as basic_error:
                logger.error(f"Even basic PDF failed: {basic_error}")
                return None, None

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
            
            
            logger.info(f"PDF path: {pdf_path}")
            
            
            dispatcher.utter_message(text="Generating CO attainment visualizations and PDF report. This may take a moment...")
            
            # Make sure the directory exists
            pdf_dir = os.path.dirname(pdf_path)
            if not os.path.exists(pdf_dir):
                os.makedirs(pdf_dir, exist_ok=True)
                logger.info(f"Created PDF directory: {pdf_dir}")
            
            
            if pdf_path and os.path.exists(pdf_path):
                dispatcher.utter_message(
                    text=f"I've generated a detailed CO attainment comparison report. [Download Report]({public_url})"
                )
                
                # Quick summary in chat
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