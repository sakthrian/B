from typing import Any, Text, Dict, List
from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher
import logging
import re
import os

from actions.handlers.student_handler import ActionStudentQuery
from actions.handlers.faculty_handler import ActionFacultyQuery
from actions.handlers.course_handler import ActionCourseQuery
from actions.handlers.greeting_handler import ActionGreeting, ActionGoodbye
from actions.handlers.query_router import ActionQueryRouter
from actions.handlers.fallback_handler import ActionFallback
from actions.handlers.co_attainment_handler import ActionCompareCOAttainment as COAttainmentHandler
from actions.handlers.student_co_report_handler import StudentCOReportHandler


logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class ActionDefaultFallback(Action):
    """Legacy fallback action for backward compatibility"""

    def name(self) -> Text:
        return "action_default_fallback"

    def run(self, dispatcher: CollectingDispatcher,
            tracker: Tracker,
            domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
        
       
        return ActionFallback().run(dispatcher, tracker, domain)

class ActionQueryRouter(Action):
    """Route queries to the appropriate handler based on content analysis"""

    def name(self) -> Text:
        return "action_query_router"

    def run(self, dispatcher: CollectingDispatcher,
            tracker: Tracker,
            domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
        
        
        message = tracker.latest_message.get('text', '')
        logger.info(f"Received query: {message}")
        
       
        course_code_match = re.search(r"\b([A-Z]{2,3}\s*\d{3})\b", message, re.IGNORECASE)
        if course_code_match:
            logger.info(f"Detected course code pattern: {course_code_match.group(1)}")
            return ActionCourseQuery().run(dispatcher, tracker, domain)
        
      
        from actions.handlers.query_router import ActionQueryRouter as ModularQueryRouter
        return ModularQueryRouter().run(dispatcher, tracker, domain)

class ActionStudentQuery(Action):
    """
    Action for handling student-related queries
    """
    
    def name(self) -> Text:
        return "action_student_query"
    
    def run(self, dispatcher: CollectingDispatcher,
            tracker: Tracker,
            domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
        
     
        user_message = tracker.latest_message.get('text', '')
        intent = tracker.latest_message.get('intent', {}).get('name')
        confidence = tracker.latest_message.get('intent', {}).get('confidence', 0)
        
       
        print("\n")
        print("*" * 100)
        print("DEBUGGING INFORMATION")
        print("*" * 100)
        print(f"RECEIVED MESSAGE: '{user_message}'")
        print(f"DETECTED INTENT: {intent}")
        print(f"CONFIDENCE SCORE: {confidence:.2%}")
        print("*" * 100)
        print("\n")
        
        logger.info(f"Handling student query: {user_message}")
        
       
        from actions.handlers.student_handler import ActionStudentQuery as StudentHandler
        return StudentHandler().run(dispatcher, tracker, domain)

class ActionFacultyQuery(Action):
    """
    Action for handling faculty-related queries
    """
    
    def name(self) -> Text:
        return "action_faculty_query"
    
    def run(self, dispatcher: CollectingDispatcher,
            tracker: Tracker,
            domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
        
      
        user_message = tracker.latest_message.get('text', '')
        intent = tracker.latest_message.get('intent', {}).get('name')
        confidence = tracker.latest_message.get('intent', {}).get('confidence', 0)
        
       
        print("\n")
        print("*" * 100)
        print("DEBUGGING INFORMATION")
        print("*" * 100)
        print(f"RECEIVED MESSAGE: '{user_message}'")
        print(f"DETECTED INTENT: {intent}")
        print(f"CONFIDENCE SCORE: {confidence:.2%}")
        print("*" * 100)
        print("\n")
        
        logger.info(f"Handling faculty query: {user_message}")
        
        
        from actions.handlers.faculty_handler import ActionFacultyQuery as FacultyHandler
        return FacultyHandler().run(dispatcher, tracker, domain)

class ActionFallback(Action):
    """
    Action for handling fallback when no other action matches
    """
    
    def name(self) -> Text:
        return "action_fallback"
    
    def run(self, dispatcher: CollectingDispatcher,
            tracker: Tracker,
            domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
        
   
        user_message = tracker.latest_message.get('text', '')
        logger.info(f"Handling fallback for: {user_message}")
        
        
        from actions.handlers.fallback_handler import ActionFallback as FallbackHandler
        return FallbackHandler().run(dispatcher, tracker, domain)

class ActionGreeting(Action):
    """
    Action for handling greeting messages
    """
    
    def name(self) -> Text:
        return "action_greeting"
    
    def run(self, dispatcher: CollectingDispatcher,
            tracker: Tracker,
            domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
        
        user_message = tracker.latest_message.get('text', '')
        intent = tracker.latest_message.get('intent', {}).get('name')
        confidence = tracker.latest_message.get('intent', {}).get('confidence', 0)
        
        
        print("\n")
        print("*" * 100)
        print("DEBUGGING INFORMATION")
        print("*" * 100)
        print(f"RECEIVED MESSAGE: '{user_message}'")
        print(f"DETECTED INTENT: {intent}")
        print(f"CONFIDENCE SCORE: {confidence:.2%}")
        print("*" * 100)
        print("\n")
        
       
        dispatcher.utter_message(text="Hello! I'm AuraAI™, an intelligent assistant for Outcome-Based Education (OBE). I can help you with information about students, faculty, and more. How can I assist you today?")
        
        return []

class ActionGoodbye(Action):
    """
    Action for handling goodbye messages
    """
    
    def name(self) -> Text:
        return "action_goodbye"
    
    def run(self, dispatcher: CollectingDispatcher,
            tracker: Tracker,
            domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
      
        user_message = tracker.latest_message.get('text', '')
        intent = tracker.latest_message.get('intent', {}).get('name')
        confidence = tracker.latest_message.get('intent', {}).get('confidence', 0)
        
        
        print("\n")
        print("*" * 100)
        print("DEBUGGING INFORMATION")
        print("*" * 100)
        print(f"RECEIVED MESSAGE: '{user_message}'")
        print(f"DETECTED INTENT: {intent}")
        print(f"CONFIDENCE SCORE: {confidence:.2%}")
        print("*" * 100)
        print("\n")
        
       
        dispatcher.utter_message(text="Goodbye! Feel free to come back if you have more questions. Have a great day!")
        
        return []

class ActionHelp(Action):
    """Provide help information about available queries"""

    def name(self) -> Text:
        return "action_help"

    def run(self, dispatcher: CollectingDispatcher,
            tracker: Tracker,
            domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
        
        user_message = tracker.latest_message.get('text', '')
        intent = tracker.latest_message.get('intent', {}).get('name')
        confidence = tracker.latest_message.get('intent', {}).get('confidence', 0)
        
       
        print("\n")
        print("*" * 100)
        print("DEBUGGING INFORMATION")
        print("*" * 100)
        print(f"RECEIVED MESSAGE: '{user_message}'")
        print(f"DETECTED INTENT: {intent}")
        print(f"CONFIDENCE SCORE: {confidence:.2%}")
        print("*" * 100)
        print("\n")
        
     
        dispatcher.utter_message(text="I can help you with the following types of queries:")
        
        dispatcher.utter_message(text="Student Information:")
        dispatcher.utter_message(text="- Who is [student name]?")
        dispatcher.utter_message(text="- Tell me about student [register number]")
        dispatcher.utter_message(text="- Show all students in year [1-4]")
        dispatcher.utter_message(text="- List students in semester [1-8]")
        dispatcher.utter_message(text="- Show students in section [A-C]")
        dispatcher.utter_message(text="- List students in batch [YYYY-YYYY]")
        
        dispatcher.utter_message(text="Faculty Information:")
        dispatcher.utter_message(text="- Who teaches [subject name]?")
        dispatcher.utter_message(text="- What subjects does [faculty name] teach?")
        dispatcher.utter_message(text="- Faculty for [subject name]")
        
        dispatcher.utter_message(text="Course Information:")
        dispatcher.utter_message(text="- What is [course code]? (e.g., CS210)")
        dispatcher.utter_message(text="- Tell me about [course name] (e.g., Computer Networks)")
        dispatcher.utter_message(text="- Show courses in semester [1-8]")
        dispatcher.utter_message(text="- List courses with [X.X] credits (e.g., 3.0)")
        
        return []

class ActionCompareCOAttainment(Action):
    """
    Action for comparing Course Outcome attainment between different batches
    """
    
    def name(self) -> Text:
        return "action_compare_co_attainment"
    
    def run(self, dispatcher: CollectingDispatcher,
            tracker: Tracker,
            domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
        
        user_message = tracker.latest_message.get('text', '')
        intent = tracker.latest_message.get('intent', {}).get('name')
        confidence = tracker.latest_message.get('intent', {}).get('confidence', 0)
        
        print("\n")
        print("*" * 100)
        print("DEBUGGING INFORMATION")
        print("*" * 100)
        print(f"RECEIVED MESSAGE: '{user_message}'")
        print(f"DETECTED INTENT: {intent}")
        print(f"CONFIDENCE SCORE: {confidence:.2%}")
        print("*" * 100)
        print("\n")
        
        logger.info(f"Handling CO attainment comparison request: {user_message}")
        
        return COAttainmentHandler().run(dispatcher, tracker, domain)

# student CO report
class ActionStudentCOReport(Action):
    """
    Action for handling student CO attainment report generation
    """
    
    def name(self) -> Text:
        return "action_student_co_report"
    
    def run(self, dispatcher: CollectingDispatcher,
            tracker: Tracker,
            domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
        
        user_message = tracker.latest_message.get('text', '')
        intent = tracker.latest_message.get('intent', {}).get('name')
        confidence = tracker.latest_message.get('intent', {}).get('confidence', 0)
        
        print("\n")
        print("*" * 100)
        print("DEBUGGING INFORMATION")
        print("*" * 100)
        print(f"RECEIVED MESSAGE: '{user_message}'")
        print(f"DETECTED INTENT: {intent}")
        print(f"CONFIDENCE SCORE: {confidence:.2%}")
        print("*" * 100)
        print("\n")
        
        logger.info(f"Handling student CO report request: {user_message}")
        
        # Extract register number from entities
        register_no = None
        for entity in tracker.latest_message.get('entities', []):
            if entity["entity"] == "register_no":
                register_no = entity["value"]
        
        if not register_no:
            dispatcher.utter_message(text="Please provide a register number to generate the CO attainment report.")
            return []
        
        
        debug_mode = "debug" in user_message.lower()
        if debug_mode:
            dispatcher.utter_message(text=f"Running diagnostic mode for student {register_no}. Check logs for details.")
            student_co_handler = StudentCOReportHandler()
            student_co_handler.debug_student_co_data(register_no)
        
        # Generate student CO attainment report
        student_co_handler = StudentCOReportHandler()
        result = student_co_handler.generate_student_co_report(register_no)
        
        if result["status"] == "success":
            #  download link for the PDF
            file_url = f"/reports/student_co/{os.path.basename(result['pdf_path'])}"
            dispatcher.utter_message(text=f"Student CO attainment report generated successfully. [Download Report]({file_url})")
        else:
            dispatcher.utter_message(text=result["message"])
        
        return []