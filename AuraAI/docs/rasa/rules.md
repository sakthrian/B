# Rules Configuration

## Overview
The Rules configuration in ObeAI™ defines the conversation flow and action mappings for specific intents. Rules ensure consistent bot behavior for well-defined conversation patterns.

## File Location
`data/rules.yml`

## Core Components

### 1. Rule Structure
```yaml
- rule: Rule Name
  steps:
  - intent: intent_name
  - action: action_name
```

## Rule Categories

### 1. Basic Conversation Rules
```yaml
- rule: Say goodbye anytime the user says goodbye
  steps:
  - intent: goodbye
  - action: action_goodbye

- rule: Say 'I am a bot' anytime the user challenges
  steps:
  - intent: bot_challenge
  - action: utter_iamabot

- rule: Greet the user
  steps:
  - intent: greet
  - action: action_greeting
```

### 2. Faculty Query Rules
```yaml
- rule: Handle faculty subject query
  steps:
  - intent: faculty_subject
  - action: action_faculty_query

- rule: Handle faculty for subject query
  steps:
  - intent: faculty_for_subject
  - action: action_faculty_query
```

### 3. Student Query Rules
```yaml
- rule: Handle student info query
  steps:
  - intent: student_info
  - action: action_student_query

- rule: Handle student by name query
  steps:
  - intent: student_by_name
  - action: action_student_query

- rule: Handle students by year query
  steps:
  - intent: students_by_year
  - action: action_student_query
```

### 4. Course Query Rules
```yaml
- rule: Handle course by code query
  steps:
  - intent: course_by_code
  - action: action_course_query

- rule: Handle course by name query
  steps:
  - intent: course_by_name
  - action: action_course_query

- rule: Course CO attainment
  steps:
  - intent: course_co_attainment
  - action: action_course_query
```

### 5. Fallback Rules
```yaml
- rule: Use query router for any unrecognized intent
  steps:
  - intent: nlu_fallback
  - action: action_query_router
```

## Action Mapping

### 1. Basic Actions
- `action_greeting`: Handle user greetings
- `action_goodbye`: Handle user farewells
- `utter_iamabot`: Respond to bot identity questions

### 2. Query Actions
- `action_faculty_query`: Handle faculty-related queries
- `action_student_query`: Handle student-related queries
- `action_course_query`: Handle course-related queries
- `action_query_router`: Route unrecognized queries

### 3. Specialized Actions
- `action_compare_co_attainment`: Compare CO attainment between batches
- `action_student_co_report`: Generate student CO reports

## Integration Points

### 1. NLU Integration
- Rules map to intents defined in `nlu.yml`
- Entity values are passed to actions

### 2. Domain Integration
- Actions must be registered in `domain.yml`
- Responses referenced in rules must be defined

## Best Practices

### 1. Rule Design
- Keep rules simple and focused
- Avoid overlapping rules
- Use clear, descriptive names

### 2. Action Handling
- Implement robust error handling
- Validate input parameters
- Return meaningful responses
- Log action execution

### 3. Maintenance
- Document rule changes
- Test rule interactions
- Monitor rule effectiveness
- Update as needed

