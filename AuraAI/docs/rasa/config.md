# Configuration Settings

## Overview
The Config file in ObeAI™ defines the model architecture, pipeline components, and policies for both NLU and Core components. It controls how the bot processes language and makes decisions.

## File Location
`config.yml`

## Core Components

### 1. Recipe Configuration
```yaml
recipe: default.v1
assistant_id: 20240520-143642-dull-phrasing
language: en
```

### 2. Pipeline Configuration
```yaml
pipeline:
  - name: WhitespaceTokenizer
  - name: RegexFeaturizer
  - name: LexicalSyntacticFeaturizer
  - name: CountVectorsFeaturizer
  - name: DIETClassifier
  - name: EntitySynonymMapper
  - name: ResponseSelector
  - name: FallbackClassifier
```

### 3. Policy Configuration
```yaml
policies:
  - name: MemoizationPolicy
  - name: RulePolicy
  - name: TEDPolicy
```

## Pipeline Components

### 1. Text Processing
- `WhitespaceTokenizer`: Splits text into tokens
- `RegexFeaturizer`: Extracts regex patterns
- `LexicalSyntacticFeaturizer`: Extracts lexical and syntactic features

### 2. Feature Extraction
```yaml
- name: CountVectorsFeaturizer
  analyzer: char_wb
  min_ngram: 1
  max_ngram: 4
```

### 3. Intent Classification
```yaml
- name: DIETClassifier
  epochs: 100
  constrain_similarities: true
  entity_recognition: true
  intent_classification: true
  use_masked_language_model: true
```

### 4. Entity Processing
```yaml
- name: EntitySynonymMapper
- name: ResponseSelector
  epochs: 100
  constrain_similarities: true
```

### 5. Fallback Handling
```yaml
- name: FallbackClassifier
  threshold: 0.6
  ambiguity_threshold: 0.1
```

## Policy Configuration

### 1. MemoizationPolicy
```yaml
- name: MemoizationPolicy
  max_history: 5
  featurizer:
    - name: MaxHistoryTrackerFeaturizer
      state_featurizer:
        - name: SingleStateFeaturizer
```

### 2. RulePolicy
```yaml
- name: RulePolicy
  core_fallback_threshold: 0.3
  core_fallback_action_name: "action_default_fallback"
  enable_fallback_prediction: true
```

### 3. TEDPolicy
```yaml
- name: TEDPolicy
  max_history: 5
  epochs: 100
  constrain_similarities: true
```

## Component Settings

### 1. DIET Classifier
- Epochs: 100
- Entity Recognition: Enabled
- Intent Classification: Enabled
- Masked Language Model: Enabled
- Evaluation Settings:
  - Examples: 0
  - Epoch Interval: 10
  - Checkpoint: Enabled

### 2. Response Selector
- Epochs: 100
- Similarity Constraints: Enabled

### 3. Fallback Settings
- Threshold: 0.6
- Ambiguity Threshold: 0.1
- Core Fallback Action: "action_default_fallback"

## Integration Points

### 1. NLU Integration
- Pipeline processes training data
- Features feed into DIET classifier
- Entity mapping handled automatically

### 2. Core Integration
- Policies determine dialogue flow
- Rules processed by RulePolicy
- Machine learning via TEDPolicy

## Best Practices

### 1. Pipeline Design
- Order components logically
- Configure appropriate thresholds
- Balance accuracy vs. speed
- Monitor component performance

### 2. Policy Configuration
- Set appropriate history length
- Configure fallback thresholds
- Balance rule vs. ML policies
- Monitor policy decisions

### 3. Performance Tuning
- Adjust epoch counts
- Fine-tune thresholds
- Optimize feature extraction
- Monitor training metrics

