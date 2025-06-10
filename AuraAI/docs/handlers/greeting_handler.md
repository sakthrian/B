# Greeting Handler

## Overview
The Greeting Handler manages basic conversational interactions in the ObeAI™ system. It handles greetings and farewells, providing appropriate responses to maintain a natural conversation flow.

## File Location
`actions/handlers/greeting_handler.py`

## Dependencies
```python
import logging
from typing import Any, Dict, List, Text
from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher
```

## Classes

### 1. ActionGreeting
```python
class ActionGreeting(Action):
    """Handle greeting messages"""
```

#### Methods

##### name()
```python
def name(self) -> Text:
    return "action_greeting"
```
Returns the action name for Rasa identification.

##### run()
```python
def run(self, dispatcher: CollectingDispatcher, tracker: Tracker, domain: Dict[Text, Any]) -> List[Dict[Text, Any]]
```
Processes and responds to greeting messages.

Parameters:
- `dispatcher`: For sending messages back to user
- `tracker`: Contains conversation history
- `domain`: Domain configuration

Returns:
- Empty list (no events to track)

### 2. ActionGoodbye
```python
class ActionGoodbye(Action):
    """Handle goodbye messages"""
```

#### Methods

##### name()
```python
def name(self) -> Text:
    return "action_goodbye"
```
Returns the action name for Rasa identification.

##### run()
```python
def run(self, dispatcher: CollectingDispatcher, tracker: Tracker, domain: Dict[Text, Any]) -> List[Dict[Text, Any]]
```
Processes and responds to farewell messages.

Parameters:
- `dispatcher`: For sending messages back to user
- `tracker`: Contains conversation history
- `domain`: Domain configuration

Returns:
- Empty list (no events to track)

## Response Templates

### 1. Greeting Response
```python
greeting_response = "Hello! I'm ObeAI, an intelligent assistant for Outcome-Based Education (OBE). I can help you with information about students, faculty, and more. How can I assist you today?"
```

### 2. Goodbye Response
```python
goodbye_response = "Goodbye! Feel free to come back if you have more questions. Have a great day!"
```

## Pattern Recognition

### 1. Greeting Patterns
```python
greeting_patterns = [
    r"^(?:hi|hello|hey|greetings)(?:\s|$)",
    r"^good\s+(?:morning|afternoon|evening)(?:\s|$)",
    r"^(?:howdy|hola|namaste)(?:\s|$)"
]
```

### 2. Goodbye Patterns
```python
goodbye_patterns = [
    r"^(?:bye|goodbye|see you|farewell)(?:\s|$)",
    r"^(?:thanks|thank you|thankyou)(?:\s|$)",
    r"^(?:have a|good)\s+(?:good|nice|great)\s+(?:day|night)(?:\s|$)"
]
```

## Logging System

### 1. Configuration
```python
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)
```

### 2. Log Categories
- Greeting detection
- Response generation
- Pattern matches

## Best Practices

### 1. Response Formatting
- Clear and concise messages
- Professional tone
- Consistent formatting

### 2. Pattern Matching
- Case-insensitive matching
- Handle multiple variations
- Support different languages

### 3. Error Handling
- Invalid pattern handling
- Response generation failures
- Logging errors

## Example Usage

### 1. Greeting Interaction
```
User: "Hello"
Bot: "Hello! I'm ObeAI, an intelligent assistant for Outcome-Based Education (OBE). I can help you with information about students, faculty, and more. How can I assist you today?"
```

### 2. Farewell Interaction
```
User: "Goodbye"
Bot: "Goodbye! Feel free to come back if you have more questions. Have a great day!"
```

### 3. Time-based Greeting
```
User: "Good morning"
Bot: "Hello! I'm ObeAI, an intelligent assistant for Outcome-Based Education (OBE). I can help you with information about students, faculty, and more. How can I assist you today?"
```

## Testing

### 1. Pattern Tests
```python
def test_greeting_patterns():
    assert is_greeting("hello")
    assert is_greeting("good morning")
    assert not is_greeting("show me students")
```

### 2. Response Tests
```python
def test_greeting_response():
    assert get_greeting_response() == greeting_response
    assert get_goodbye_response() == goodbye_response
```

## Troubleshooting

### Common Issues
1. Pattern not matching expected greetings
2. Response not generating
3. Logging failures

### Solutions
1. Update regex patterns
2. Verify response templates
3. Check logging configuration

## Integration Points

### 1. Query Router
```python
# In query_router.py
if re.match(greeting_patterns, message):
    return ActionGreeting().run(dispatcher, tracker, domain)
```

### 2. Main Pipeline
```python
# In domain.yml
intents:
  - greet
  - goodbye

responses:
  utter_greet:
    - text: {greeting_response}
  utter_goodbye:
    - text: {goodbye_response}
```

## Customization

### 1. Response Customization
```python
def customize_greeting(time_of_day: str) -> str:
    """Customize greeting based on time of day"""
    if time_of_day == "morning":
        return "Good morning! " + greeting_response
    elif time_of_day == "afternoon":
        return "Good afternoon! " + greeting_response
    elif time_of_day == "evening":
        return "Good evening! " + greeting_response
    else:
        return greeting_response
```

### 2. Language Support
```python
def get_localized_response(language: str) -> str:
    """Get greeting response in specified language"""
    responses = {
        "en": greeting_response,
        "es": "¡Hola! Soy ObeAI...",
        "fr": "Bonjour! Je suis ObeAI..."
    }
    return responses.get(language, greeting_response)
```

## Future Enhancements

### 1. Planned Improvements
- Multi-language support
- Time-based greetings
- User preference tracking

### 2. Feature Requests
- Custom greeting templates
- User mood detection
- Contextual responses 