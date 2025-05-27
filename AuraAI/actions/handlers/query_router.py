import re
import logging
from typing import Any, Dict, List, Text

from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher


logger = logging.getLogger(__name__)

class ActionQueryRouter(Action):
    """Route queries to the appropriate handler based on content analysis"""

    def name(self) -> Text:
        return "action_query_router"

    def run(self, dispatcher: CollectingDispatcher, tracker: Tracker, domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
        """Analyze the message and route to the appropriate handler"""
        
        # Get the message
        message = tracker.latest_message.get('text', '')
        intent = tracker.latest_message.get('intent', {}).get('name')
        confidence = tracker.latest_message.get('intent', {}).get('confidence', 0)
        
       
        print("\n")
        print("*" * 100)
        print("DEBUGGING INFORMATION")
        print("*" * 100)
        print(f"RECEIVED MESSAGE: '{message}'")
        print(f"DETECTED INTENT: {intent}")
        print(f"CONFIDENCE SCORE: {confidence:.2%}")
        print("*" * 100)
        print("\n")
        
       
        logger.info("\n" + "="*50)
        logger.info("QUERY ANALYSIS START")
        logger.info("="*50)
        logger.info(f"User Query: '{message}'")
        logger.info(f"NLU Intent: {intent}")
        logger.info(f"Confidence Score: {confidence:.2%}")
        
       
        debug_info = f"\n[DEBUG INFO]\nQuery: '{message}'\nDetected Intent: {intent}\nConfidence: {confidence:.2%}"
        
        # routing to course handler
        if "course" in message.lower() or "courses" in message.lower():
            logger.info("ROUTING: Detected course-related query, routing to course handler")
            from actions.handlers.course_handler import ActionCourseQuery
            return ActionCourseQuery().run(dispatcher, tracker, domain)
            
        # route based on degree type -> student
        if (("btech" in message.lower() or "b.tech" in message.lower() or "b tech" in message.lower() or 
             "mtech" in message.lower() or "m.tech" in message.lower() or "m tech" in message.lower() or
             "mca" in message.lower() or "phd" in message.lower())):
            
     
            if "student" in message.lower() or "students" in message.lower():
                
                if re.search(r'm\.?tech\s+is|m\s*tech\s+is', message, re.IGNORECASE) or re.search(r'm\.?tech\s+ds|m\s*tech\s+ds', message, re.IGNORECASE):
                    logger.info("ROUTING: Detected M.Tech specialization query with student context, routing to student handler")
                    logger.info(f"Original message: '{message}'")
                    from actions.handlers.student_handler import ActionStudentQuery
                    return ActionStudentQuery().run(dispatcher, tracker, domain)
                
                logger.info("ROUTING: Detected degree-type query with student context, routing to student handler")
                logger.info(f"Original message: '{message}'")
                from actions.handlers.student_handler import ActionStudentQuery
                return ActionStudentQuery().run(dispatcher, tracker, domain)
            #route to course handler
            else:
                logger.info("ROUTING: Detected degree-type query without student context, routing to course handler")
                from actions.handlers.course_handler import ActionCourseQuery
                return ActionCourseQuery().run(dispatcher, tracker, domain)
        
        # If we have a high-confidence intent then go according to the NLU
        if intent != 'nlu_fallback' and confidence > 0.7:
            method_info = "USING: NLU (High Confidence)"
            print(f"PROCESSING METHOD: {method_info}")
            debug_info += f"\n{method_info}"
            logger.info(method_info)
            dispatcher.utter_message(text=debug_info)
            logger.info("QUERY ANALYSIS END")
            logger.info("="*50)
            return []
        
        # Otherwise, use regex 
        method_info = "USING: Regex Patterns (NLU confidence too low)"
        debug_info += f"\n{method_info}"
        logger.info(method_info)
        
        
        course_patterns = [
            r"\b([A-Z]{2,3}\s*\d{3})\b", 
            r"(?:course|subject)\s+(?:code)?\s*([A-Z]{2,3}\s*\d{3})",  
            r"(?:what is|tell me about|details of|information on|show|give me details of)\s+([A-Z]{2,3}\s*\d{3})",  
            r"(?:courses|subjects)(?:\s+in)?\s+(?:semester|sem)\s*([1-8])", 
            r"(?:courses|subjects)(?:\s+with)?\s+([0-9]\.[0-9]|[0-9])\s+credits",  
            r"(?:what|which)\s+(?:courses|subjects)(?:\s+are)?\s+(?:offered|available|taught)(?:\s+in)?\s+(?:semester|sem)?\s*([1-8])", 
        ]
        
        for pattern in course_patterns:
            if re.search(pattern, message, re.IGNORECASE):
                pattern_info = f"Matched Course Pattern: {pattern}"
                debug_info += f"\n{pattern_info}"
                logger.info(pattern_info)
                dispatcher.utter_message(text=debug_info)
                logger.info("QUERY ANALYSIS END")
                logger.info("="*50)
                from actions.handlers.course_handler import ActionCourseQuery
                return ActionCourseQuery().run(dispatcher, tracker, domain)
        
        
        student_patterns = [
            r"(?:who|tell me about|about)\s+(?:is|are)?\s*([A-Za-z\s]+)(?:\?)?$",  # Who is Aamir Khan?
            r"(?:about|tell me about|find|get)\s+(?:student|register number|reg no)?\s*([0-9]{2}[A-Za-z]{2}[0-9]{4})(?:\?)?",  # Tell me about 21CS1001
            r"(?:students|student|show|list|get|find).*(?:in|of|from)?\s+(?:year)?\s*([1-4])(?:\?)?",  # Show students in year 3
            r"(?:students|student|show|list|get|find).*(?:in|of|from)?\s+(?:semester|sem)?\s*([1-8])(?:\?)?",  # List students in semester 6
            r"(?:students|student|show|list|get|find).*(?:in|of|from)?\s+(?:section)?\s*([A-C])(?:\?)?",  # Show students in section A
            r"(?:students|student|show|list|get|find).*(?:in|of|from)?\s+(?:batch)?\s*(20\d{2}-20\d{2})(?:\?)?",  # List students in batch 2021-2025
        ]
        
        for pattern in student_patterns:
            if re.search(pattern, message, re.IGNORECASE):
                pattern_info = f"Matched Student Pattern: {pattern}"
                debug_info += f"\n{pattern_info}"
                logger.info(pattern_info)
                dispatcher.utter_message(text=debug_info)
                logger.info("QUERY ANALYSIS END")
                logger.info("="*50)
                from actions.handlers.student_handler import ActionStudentQuery
                return ActionStudentQuery().run(dispatcher, tracker, domain)
        
        
        faculty_patterns = [
            r"(?:what|which)\s+(?:subject|subjects)(?:\s+does)?\s+([A-Za-z\s\.]+)(?:\s+(?:teach|teaches|take|takes|handle|handles))?",  # What subjects does Dr. Sreenath teach?
            r"(?:who|which faculty)(?:\s+(?:is|are))?\s+(?:teaching|teaches|taking|takes|handling|handles)\s+([A-Za-z\s]+)",  # Who teaches Computer Networks?
            r"(?:faculty|teacher|professor)(?:\s+(?:for|of))\s+([A-Za-z\s]+)",  # Faculty for Computer Networks
        ]
        
        for pattern in faculty_patterns:
            if re.search(pattern, message, re.IGNORECASE):
                debug_info += f"\nMatched Pattern: Faculty Query ({pattern})"
                logger.info(f"Routing to faculty handler based on pattern match: {pattern}")
                dispatcher.utter_message(text=debug_info)
                from actions.handlers.faculty_handler import ActionFacultyQuery
                return ActionFacultyQuery().run(dispatcher, tracker, domain)
        
        
        greeting_patterns = [
            r"^(?:hi|hello|hey|greetings|good morning|good afternoon|good evening)(?:\s|$)",
        ]
        
        for pattern in greeting_patterns:
            if re.search(pattern, message, re.IGNORECASE):
                debug_info += f"\nMatched Pattern: Greeting ({pattern})"
                logger.info(f"Routing to greeting handler based on pattern match: {pattern}")
                dispatcher.utter_message(text=debug_info)
                from actions.handlers.greeting_handler import ActionGreeting
                return ActionGreeting().run(dispatcher, tracker, domain)
        
       
        goodbye_patterns = [
            r"^(?:bye|goodbye|see you|farewell|thanks|thank you)(?:\s|$)",
        ]
        
        for pattern in goodbye_patterns:
            if re.search(pattern, message, re.IGNORECASE):
                debug_info += f"\nMatched Pattern: Goodbye ({pattern})"
                logger.info(f"Routing to goodbye handler based on pattern match: {pattern}")
                dispatcher.utter_message(text=debug_info)
                from actions.handlers.greeting_handler import ActionGoodbye
                return ActionGoodbye().run(dispatcher, tracker, domain)
        
       
        if len(message.split()) <= 3:
            
            from actions.utils.database import get_faculty_by_subject
            faculty = get_faculty_by_subject(message.strip())
            if faculty:
                debug_info += "\nMatched Pattern: Simple Subject Query (Faculty)"
                logger.info(f"Routing to faculty handler for subject name: {message}")
                dispatcher.utter_message(text=debug_info)
                from actions.handlers.faculty_handler import ActionFacultyQuery
                return ActionFacultyQuery().run(dispatcher, tracker, domain)
            
            
            from actions.utils.database import get_course_by_name
            courses = get_course_by_name(message.strip())
            if courses:
                debug_info += "\nMatched Pattern: Simple Subject Query (Course)"
                logger.info(f"Routing to course handler for course name: {message}")
                dispatcher.utter_message(text=debug_info)
                from actions.handlers.course_handler import ActionCourseQuery
                return ActionCourseQuery().run(dispatcher, tracker, domain)
        
        
        fallback_info = "NO MATCHES: Using fallback handler"
        debug_info += f"\n{fallback_info}"
        logger.info(fallback_info)
        dispatcher.utter_message(text=debug_info)
        logger.info("QUERY ANALYSIS END")
        logger.info("="*50)
        from actions.handlers.fallback_handler import ActionFallback
        return ActionFallback().run(dispatcher, tracker, domain) 