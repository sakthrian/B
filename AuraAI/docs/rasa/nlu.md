# Natural Language Understanding (NLU)

## Overview
The NLU configuration in ObeAI™ defines how the system understands and interprets user inputs. It includes intent definitions, training examples, and entity recognition patterns.

## File Location
`data/nlu.yml`

## Core Components

### 1. Intents
```yaml
nlu:
- intent: greet
  examples: |
    - hey
    - hello
    - hi
    - hello there
    - good morning
```

### 2. Entity Recognition
The system recognizes several entity types:
- Faculty names
- Subject names
- Student names
- Register numbers
- Years/Semesters
- Course codes

## Intent Categories

### 1. Basic Conversation
- `greet`: Handle greetings
- `goodbye`: Handle farewells
- `affirm`: Handle affirmative responses
- `deny`: Handle negative responses
- `bot_challenge`: Respond to identity questions

### 2. Faculty Queries
- `faculty_subject`: Find subjects taught by faculty
- `faculty_for_subject`: Find faculty teaching a subject

### 3. Student Queries
- `student_info`: Get student details by register number
- `student_by_name`: Find student by name
- `students_by_year`: List students in a specific year
- `students_by_semester`: List students in a semester
- `students_by_section`: List students in a section
- `students_by_batch`: List students in a batch

### 4. Course Queries
- `course_by_code`: Find course by code
- `course_by_name`: Find course by name
- `courses_by_semester`: List courses in a semester
- `courses_by_credits`: List courses by credit value
- `course_co_attainment`: Get CO attainment details

## Entity Patterns

### 1. Faculty Name
```yaml
[Dr. Saruladha](faculty_name)
[sreenath](faculty_name)
[Dr. V. Akila](faculty_name)

```

### 2. Register Number
```yaml
[21CS1001](register_no)
[2101107056](register_no)
```

### 3. Student Name
```yaml
[AAMIR KHAN](student_name)
[GAYATHRI RAMESH](student_name)
```

### 4. Subject/Course
```yaml
[computer networks](subject_name)
[operating systems](subject_name)
[CS208](subject_name)
```

## Training Examples

### 1. Faculty Queries
```yaml
- what subject does [sreenath](faculty_name) handle
- who teaches computer networks
- which faculty handles [database management systems](subject_name)
```

### 2. Student Queries
```yaml
- tell me about student [21CS1001](register_no)
- find student named [AAMIR KHAN](student_name)
- show all students in year [3](year)
```

### 3. Course Queries
```yaml
- tell me about course CS301
- what are the prerequisites for Database Management
- show CO attainment for CS208
```

## Best Practices

### 1. Intent Design
- Keep intents focused and specific
- Provide diverse training examples
- Include variations in language
- Consider common misspellings

### 2. Entity Recognition
- Use consistent entity naming
- Include entity variations
- Consider context-specific entities
- Handle edge cases

### 3. Training Data
- Balance example distribution
- Include real-world examples
- Update regularly with new patterns
- Validate entity annotations

## Integration Points

### 1. Rules Integration
```yaml
- rule: Handle faculty subject query
  steps:
  - intent: faculty_subject
  - action: action_faculty_query
```

### 2. Domain Integration
```yaml
intents:
  - faculty_subject
  - student_info
  - course_by_code
```

