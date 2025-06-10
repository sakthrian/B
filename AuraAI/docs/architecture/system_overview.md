# System Overview

## Introduction
ObeAI™ is an intelligent Outcome-Based Education (OBE) management system that combines natural language processing, automated question paper generation, and comprehensive analytics for educational institutions.

## System Architecture

### 1. Core Components

#### 1.1 Chatbot Engine (Rasa)
- **Version**: Rasa 3.6.2
- **Purpose**: Handles natural language understanding and dialogue management
- **Key Files**:
  - `config.yml`: NLU pipeline configuration
  - `domain.yml`: Intent, entity, and action definitions
  - `data/nlu.yml`: Training data for NLU
  - `data/rules.yml`: Conversation rules
  - `data/stories.yml`: Conversation flows

#### 1.2 Question Generation System
- **Files**: 
  - `question_gen.php`: Main question generation logic
  - `qp_gen.php`: Question paper generation
- **Features**:
  - Bloom's taxonomy level consideration
  - Question paper formatting

#### 1.3 Report Generation System
- **Location**: `actions/utils/pdf_generator.py`
- **Types**:
  - Student reports
  - Course  reports
  - Faculty reports

#### 1.4 CO Attainment System
- **Files**:
  - `actions/handlers/co_attainment_handler.py`
  - `actions/utils/co_visualization.py`
- **Features**:
  - CO calculation algorithms
  - Visualization tools
  - Batch comparison analytics

### 2. Technology Stack

#### 2.1 Frontend
- HTML5/CSS3/JavaScript
- Modern responsive design
- Real-time chat interface
- Dynamic report visualization

#### 2.2 Backend
- PHP 8.2.12
- Python 3.10.11
- MySQL Database 10.4.32
- Rasa Framework 3.6.2
- Ollama LLM Integration 

#### 2.3 Integration Points
- Rasa Server (Port 5005)
- Ollama Server (Port 11434)
- MySQL Database
- PHP Web Server (Apache/XAMPP)

### 3. Data Flow

#### 3.1 User Interaction Flow
1. User sends query through web interface
2. Query processed by chatbot engine
3. Intent classification and entity extraction
4. Routing to appropriate handler
5. Database query execution
6. Response generation
7. Response formatting and delivery

#### 3.2 Question Generation Flow
1. Course and CO selection
2. Knowledge level specification
3. Unit content retrieval
4. LLM-based question generation
5. Question validation
6. Response formatting

#### 3.3 Report Generation Flow
1. Report type classification based on intents and entities
2. Intent classification and entity extraction
3. Routing to appropriate handler
4. Data aggregation from database
5. Analytics computation
6. Visualization generation
7. PDF compilation
8. Delivery to user

### 4. Security Measures

#### 4.1 Data Protection
- Secure database connections
- Input validation
- SQL injection prevention
- XSS protection

#### 4.2 Authentication
- Session management
- Access control
- Role-based permissions

### 5. Scalability Features

#### 5.1 System Design
- Modular architecture
- Loose coupling
- Configurable components
- Extensible handlers


## Dependencies
```plaintext
# Core Dependencies
rasa==3.6.2
rasa-sdk==3.6.2
mysql-connector-python>=8.0.0

# Visualization
matplotlib<3.6,>=3.1
numpy>=1.20.0

# PDF Generation
fpdf==1.7.2

# Analytics
pandas>=1.3.0
scikit-learn>=1.0.0
seaborn>=0.12.0
```

## System Requirements
- **CPU**: Multi-core processor (4+ cores recommended)
- **RAM**: 8GB minimum (16GB recommended)
- **Storage**: 20GB minimum
- **OS**: Cross-platform (Windows/Linux/MacOS)
- **Additional**: GPU optional but recommended for LLM operations 