# Domain Configuration

## Overview
The Domain configuration in ObeAI™ defines the universe of the bot's capabilities, including intents, entities, slots, actions, and responses. It serves as the central configuration that ties together all components of the conversational AI system.

## File Location
`domain.yml`

## Core Components

### 1. Intents
```yaml
intents:
  - greet
  - goodbye
  - affirm
  - deny
  - bot_challenge
  - faculty_subject
  - student_info
  - course_by_code
```

### 2. Entities
```yaml
entities:
  - faculty_name
  - subject_name
  - student_name
  - register_number
  - year
  - semester
  - section
  - batch
  - course_code
```

### 3. Slots
```yaml
slots:
  student_name:
    type: text
    influence_conversation: true
    mappings:
    - type: from_entity
      entity: student_name
```

## Configuration Categories

### 1. Intent Configuration
- Basic conversation intents
- Faculty query intents
- Student query intents
- Course query intents
- CO attainment intents

### 2. Entity Configuration
- Personal identifiers (names, numbers)
- Academic parameters (year, semester)
- Course identifiers (codes, names)
- Batch information

### 3. Slot Configuration
Each slot includes:
- Type definition
- Conversation influence
- Entity mapping
- Value validation

### 4. Action Configuration
```yaml
actions:
  - utter_greet
  - action_query_router
  - action_student_query
  - action_faculty_query
  - action_course_query
  - action_compare_co_attainment
```

### 5. Response Configuration
```yaml
responses:
  utter_greet:
  - text: "Hi, I'm ObeAI™. How can I assist you today?"

  utter_iamabot:
  - text: "Hi, I'm ObeAI™, your intelligent assistant for Outcome-Based Education (OBE)."
```

## Session Configuration
```yaml
session_config:
  session_expiration_time: 60
  carry_over_slots_to_new_session: true
```

## Integration Points

### 1. NLU Integration
- Intents match NLU training data
- Entities align with NLU examples
- Slots store extracted entities

### 2. Rules Integration
- Actions referenced in rules
- Responses used in conversations
- Slot updates trigger rules

## Slot Types and Usage

### 1. Text Slots
```yaml
student_name:
  type: text
  influence_conversation: true
```

### 2. Categorical Slots
```yaml
semester:
  type: text
  influence_conversation: true
```

### 3. Boolean Slots
```yaml
is_student:
  type: bool
  influence_conversation: true
```

## Response Templates

### 1. Basic Responses
```yaml
utter_greet:
- text: "Hi, I'm AuraAI™. How can I assist you today?"

utter_goodbye:
- text: "Bye"
```

### 2. Dynamic Responses
```yaml
utter_student_info:
- text: "Here are the details for student {student_name}"
```

## Best Practices

### 1. Domain Organization
- Group related intents
- Maintain clear entity names
- Document slot purposes
- Organize responses logically

### 2. Slot Management
- Use appropriate slot types
- Set influence flags correctly
- Handle slot updates
- Clear slots when needed

### 3. Response Design
- Keep responses consistent
- Use variables appropriately
- Consider conversation flow
- 

