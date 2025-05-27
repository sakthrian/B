from actions.handlers.student_handler import ActionStudentQuery
from actions.handlers.faculty_handler import ActionFacultyQuery
from actions.handlers.course_handler import ActionCourseQuery
from actions.handlers.greeting_handler import ActionGreeting, ActionGoodbye
from actions.handlers.query_router import ActionQueryRouter
from actions.handlers.fallback_handler import ActionFallback
from actions.handlers.student_co_report_handler import StudentCOReportHandler
from actions.handlers.co_attainment_handler import ActionCompareCOAttainment
import logging


for handler in logging.root.handlers[:]:
    logging.root.removeHandler(handler)


logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(name)s - %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S',
    force=True 
)


logging.getLogger('actions').setLevel(logging.INFO)
logging.getLogger('actions.handlers').setLevel(logging.INFO)


logger = logging.getLogger(__name__)

__all__ = [
    'ActionStudentQuery',
    'ActionFacultyQuery',
    'ActionCourseQuery',
    'ActionGreeting',
    'ActionGoodbye',
    'ActionQueryRouter',
    'ActionFallback',
    'StudentCOReportHandler',
    'ActionCompareCOAttainment'
]
