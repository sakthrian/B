document.addEventListener('DOMContentLoaded', () => {

    window.addEventListener('error', (event) => {
        console.error('Unhandled Error:', {
            message: event.message,
            filename: event.filename,
            lineno: event.lineno,
            colno: event.colno,
            error: event.error
        });
    });

    const switchSection = (sectionId) => {
        document.querySelectorAll('.section, .sidebar-menu ul li')
            .forEach(el => el.classList.remove('active', 'section-enter', 'section-exit'));

        const targetSection = document.getElementById(sectionId);
        const targetMenuItem = document.querySelector(`[data-section="${sectionId}"]`);

        if (targetSection && targetMenuItem) {
            targetSection.classList.add('active', 'section-enter');
            targetMenuItem.classList.add('active');

            setTimeout(() => targetSection.classList.remove('section-enter'), 300);
        }
    };

    document.querySelectorAll('.sidebar-menu ul li').forEach(item => {
        item.addEventListener('click', (e) => {
            const sectionId = e.currentTarget.getAttribute('data-section');
            if (sectionId) switchSection(sectionId);
        });
    });

    // Global function to create a question element
    const createQuestionElement = (questionNumber) => {
        const questionDiv = document.createElement('div');
        questionDiv.classList.add('question-details');
        questionDiv.id = `question-${questionNumber}`;
        questionDiv.innerHTML = `
            <div class="question-header">
                <h3>Question ${questionNumber}</h3>
                <div class="question-buttons">
                    <button type="button" class="add-question-btn">
                        <i class="fas fa-plus"></i>
                    </button>
                    ${questionNumber > 1 ? `
                    <button type="button" class="remove-question-btn">
                        <i class="fas fa-times"></i>
                    </button>
                    ` : ''}
                </div>
            </div>
            <div class="form-row marks-row">
                <div class="form-group">
                    <label>Maximum Mark</label>
                    <input type="number" class="max-mark" name="question_marks[]" step="any" placeholder="Enter max mark" required>
                </div>
                <div class="form-group">
                    <label>Targeted Mark</label>
                    <input type="number" class="targeted-mark" name="target_marks[]" step="any" placeholder="Enter targeted mark" required>
                </div>
            </div>
            <div class="form-row levels-row">
                <div class="form-group">
                    <label>Knowledge Level</label>
                    <select class="knowledge-level dark-select" name="knowledge_levels[]" required>
                        <option value="">Select Level</option>
                        <option value="1">Remembering (1)</option>
                        <option value="2">Understanding (2)</option>
                        <option value="3">Applying (3)</option>
                        <option value="4">Analyzing (4)</option>
                        <option value="5">Evaluating (5)</option>
                        <option value="6">Creating (6)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>CO Level</label>
                    <select class="co-level dark-select" name="co_levels[]" required>
                        <option value="">Select CO</option>
                        <option value=1>CO1</option>
                        <option value=2>CO2</option>
                        <option value=3>CO3</option>
                        <option value=4>CO4</option>
                        <option value=5>CO5</option>
                        <option value=6>CO6</option>
                    </select>
                </div>
            </div>
        `;
        return questionDiv;
    };

    const setupQuestionManagement = (containerSelector) => {
        console.group(`🔍 Question Management: ${containerSelector}`);

        try {
            const questionsContainer = document.querySelector(containerSelector);

            if (!questionsContainer) {
                console.error(`❌ Container not found: ${containerSelector}`);
                console.groupEnd();
                return;
            }

            // Attach global event delegation
            const delegatedHandler = (event) => {
                try {
                    const addButton = event.target.closest('.add-question-btn, .add-question-btn i');
                    const removeButton = event.target.closest('.remove-question-btn, .remove-question-btn i');

                    if (addButton) {
                        event.preventDefault();
                        addQuestionHandler(questionsContainer);
                    } else if (removeButton) {
                        event.preventDefault();
                        removeQuestionHandler(event, questionsContainer);
                    }
                } catch (handlerError) {
                    console.error('Error in event handler:', handlerError);
                }
            };

            // Remove previous listeners to prevent multiple attachments
            questionsContainer.removeEventListener('click', delegatedHandler);
            questionsContainer.addEventListener('click', delegatedHandler);

            const addQuestionHandler = (container) => {
                console.log('➕ Add question button clicked');
                const currentQuestions = container.querySelectorAll('.question-details');
                const newQuestionNumber = currentQuestions.length + 1;

                const newQuestion = createQuestionElement(newQuestionNumber);
                container.appendChild(newQuestion);

                console.log(`✅ Added new question: ${newQuestionNumber}`);
            };

            const removeQuestionHandler = (event, container) => {
                console.log('➖ Remove question button clicked');
                const questions = container.querySelectorAll('.question-details');

                if (questions.length > 1) {
                    const questionToRemove = event.target.closest('.question-details');
                    questionToRemove.remove();

                    const remainingQuestions = container.querySelectorAll('.question-details');
                    remainingQuestions.forEach((question, index) => {
                        question.id = `question-${index + 1}`;
                        question.querySelector('h3').textContent = `Question ${index + 1}`;
                    });

                    console.log(`✅ Removed question. Remaining: ${remainingQuestions.length}`);
                } else {
                    alert('At least one question must remain.');
                }
            };

            console.log('✨ Question Management Setup Complete');
            console.groupEnd();

        } catch (setupError) {
            console.error('Error in question management setup:', setupError);
            console.groupEnd();
        }
    };

    // Initialize for both sections
    setupQuestionManagement('#test-upload .questions-container');
    setupQuestionManagement('#assignment-upload .questions-container');

    const setupFileUploadHandlers = (sectionId, fileInputId, chooseButtonId) => {
        const section = document.getElementById(sectionId);
        const chooseFileBtn = document.getElementById(chooseButtonId);
        const fileInput = document.getElementById(fileInputId);
        const fileNameSpan = section.querySelector('.file-name');

        chooseFileBtn.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            fileNameSpan.textContent = file ? file.name : 'No file chosen';
        });
    };

    setupFileUploadHandlers('test-upload', 'question-paper', 'choose-question-paper');
    setupFileUploadHandlers('assignment-upload', 'assignment-file', 'choose-assignment-file');

    const validateQuestions = () => {
        const questions = document.querySelectorAll('.question-details');
        let isValid = true;

        questions.forEach(question => {
            const maxMarkInput = question.querySelector('.max-mark');
            const targetMarkInput = question.querySelector('.targeted-mark');
            const maxMark = parseFloat(maxMarkInput.value);
            const targetMark = parseFloat(targetMarkInput.value);

            if (targetMark > maxMark) {
                alert(`Target mark for Question ${question.querySelector('h3').textContent} cannot be greater than the maximum mark.`);
                isValid = false;
            }
        });

        return isValid;
    };

    const setupDetailsHandling = () => {
        document.getElementById('test-upload-form').addEventListener('submit', (e) => {
            e.preventDefault();

            const fileInput = document.getElementById('question-paper');
            const fileNameSpan = document.querySelector('#test-upload .file-name');
            let existingFilePath = fileNameSpan.textContent;

            if (existingFilePath == "No file chosen") {
                existingFilePath = null;
            }

            if (!fileInput.files.length && !existingFilePath) {
                alert('Please upload a question paper.');
                return;
            }

            if (!validateQuestions()) {
                return;
            }

            const formData = new FormData(e.target);
            formData.forEach((value, key) => {
                console.log(key + ": " + value);
            });
            if (!fileInput.files.length && existingFilePath) {
                formData.append('existing_question_paper', existingFilePath);
            }

            fetch('upload_test_details.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    alert("This test marks are already entered and freezed. You cannot further edit this test metadata.");
                    console.error('Error:', error);
                });
        });
    };

    setupDetailsHandling();

    const setupAssignmentDetailsHandling = () => {
        document.getElementById('assignment-upload-form').addEventListener('submit', (e) => {
            e.preventDefault();

            const fileInput = document.getElementById('assignment-file');
            const fileNameSpan = document.querySelector('#assignment-upload .file-name');
            let existingFilePath = fileNameSpan.textContent;

            if (existingFilePath == "No file chosen") {
                existingFilePath = null;
            }

            if (!fileInput.files.length && !existingFilePath) {
                alert('Please upload a question paper.');
                return;
            }

            if (!validateQuestions()) {
                return;
            }

            const formData = new FormData(e.target);
            if (!fileInput.files.length && existingFilePath) {
                formData.append('existing_question_paper', existingFilePath);
            }

            fetch('upload_assignment_details.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    alert("This test marks are already entered and freezed. You cannot further edit this test metadata.");
                    console.error('Error:', error);
                });
        });
    };

    setupAssignmentDetailsHandling();

    //Edit marks button
    document.getElementById('view-progress-content').addEventListener('click', async (event) => {
        if (event.target.classList.contains('edit-btn') && event.target.textContent.includes('Edit Mark')) {
            const testId = event.target.getAttribute('data-id');
            const testType = event.target.getAttribute('data-type');
            const fcId = event.target.getAttribute('data-fc-id');
            const total = event.target.getAttribute('data-total');
    
            // Switch to the "Enter Marks" section
            switchSection('student-marks');
    
            // Get the necessary select elements
            const marksCourseSelect = document.getElementById('marks-course-select');
            const degreeSelectMark = document.getElementById('degree-select-mark');
            const batchSelectMark = document.getElementById('batch-select-mark');
            const testSelect = document.getElementById('test-select');
    
            try {
                // Fetch the existing test details to get the degree and batch
                const response = await fetch(`fetch_${testType}_details.php?id=${testId}`);
                const data = await response.json();
                console.log(data);
    
                // Set the degree and trigger change
                degreeSelectMark.value = data.degree;
                degreeSelectMark.dispatchEvent(new Event('change'));
    
                // Wait for batch dropdown to populate
                await new Promise((resolve) => {
                    const interval = setInterval(() => {
                        if (batchSelectMark.options.length > 1) {
                            clearInterval(interval);
                            resolve();
                        }
                    }, 100);
                });
    
                batchSelectMark.value = [...batchSelectMark.options].find(opt =>
                    opt.value.includes(data.batch.trim())
                )?.value || "";
                console.log(batchSelectMark.value);
                batchSelectMark.dispatchEvent(new Event('change'));
    
                // Wait for course dropdown to populate
                await new Promise((resolve) => {
                    const interval = setInterval(() => {
                        if (marksCourseSelect.options.length > 1) {
                            clearInterval(interval);
                            resolve();
                        }
                    }, 100);
                });
    
                // Find the course option that matches the fcId
                const courseOption = [...marksCourseSelect.options].find(opt => {
                    return opt.value.trim().toLowerCase().split(' ')[0] === fcId.trim().toLowerCase();
                });
    
                if (courseOption) {
                    marksCourseSelect.value = courseOption.value;
                    marksCourseSelect.dispatchEvent(new Event('change'));
    
                    // Wait for test/assignment dropdown to populate
                    await new Promise((resolve) => {
                        const interval = setInterval(() => {
                            if (testSelect.options.length > 1) {
                                clearInterval(interval);
                                resolve();
                            }
                        }, 100);
                    });
    
                    // Find the test/assignment option that matches the testId
                    const testOption = [...testSelect.options].find(opt => {
                        return opt.value.split(' ')[0] === testId;
                    });
    
                    if (testOption) {
                        testSelect.value = testOption.value;
                        testSelect.dispatchEvent(new Event('change'));
                    } else {
                        console.warn(`Test ID '${testId}' not found in #test-select.`);
                    }
                } else {
                    console.warn(`Course ID '${fcId}' not found in #marks-course-select.`);
                }
            } catch (error) {
                console.error('Error fetching test details:', error);
                alert('Failed to fetch test details');
            }
        }
    });

    //Edit Test/Assignment in View section
    document.getElementById('view-progress-content').addEventListener('click', (event) => {
        if (event.target.classList.contains('edit-btn') && (event.target.textContent.includes('Edit Assignment') || event.target.textContent.includes('Edit Test'))) {
            const testId = event.target.getAttribute('data-id');
            const testType = event.target.getAttribute('data-type');
            const fcId = event.target.getAttribute('data-fc-id');
            const total = event.target.getAttribute('data-total');

            const sectionId = testType === 'test' ? 'test-upload' : 'assignment-upload';
            switchSection(sectionId);

            fetch(`fetch_${testType}_details.php?id=${testId}`)
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                    if (testType === 'test') {
                        const testForm = document.getElementById('test-upload-form');

                        // First set degree and trigger change
                        testForm.querySelector('#degree-select-test').value = data.degree;
                        testForm.querySelector('#degree-select-test').dispatchEvent(new Event('change'));

                        // Wait for batch dropdown to populate
                        setTimeout(() => {
                            testForm.querySelector('#batch-select-test').value = data.batch;
                            testForm.querySelector('#batch-select-test').dispatchEvent(new Event('change'));

                            // Wait for course dropdown to populate
                            setTimeout(() => {
                                // Find the course option that matches the fcId
                                const courseSelect = testForm.querySelector('#course-select');
                                const courseOption = [...courseSelect.options].find(opt => {
                                    return opt.value.split(' ')[0] === fcId;
                                });

                                if (courseOption) {
                                    courseSelect.value = courseOption.value;
                                }

                            }, 300);
                        }, 300);

                        testForm.querySelector('#test-type').value = data.test_no;
                        testForm.querySelector('#test-date').value = data.test_date;
                        testForm.querySelector('#max-mark').value = total;

                        // Display existing file name
                        const fileNameSpan = testForm.querySelector('.file-name');
                        fileNameSpan.textContent = data.question_paper_image || 'No file chosen';

                        // Populate questions
                        const questionsContainer = testForm.querySelector('.questions-container');
                        questionsContainer.innerHTML = '';
                        data.questions.forEach((question, index) => {
                            const questionElement = createQuestionElement(index + 1);
                            questionElement.querySelector('.max-mark').value = question.max_mark;
                            questionElement.querySelector('.targeted-mark').value = question.target_mark;
                            questionElement.querySelector('.knowledge-level').value = question.knowledge_level;
                            questionElement.querySelector('.co-level').value = question.co_level;
                            questionsContainer.appendChild(questionElement);
                        });
                    } else if (testType === 'assignment') {
                        const assignmentForm = document.getElementById('assignment-upload-form');
                        
                        // First set degree and trigger change
                        assignmentForm.querySelector('#degree-select-assignment').value = data.degree;
                        assignmentForm.querySelector('#degree-select-assignment').dispatchEvent(new Event('change'));

                        // Wait for batch dropdown to populate
                        setTimeout(() => {
                            assignmentForm.querySelector('#batch-select-assignment').value = data.batch;
                            assignmentForm.querySelector('#batch-select-assignment').dispatchEvent(new Event('change'));

                            // Wait for course dropdown to populate
                            setTimeout(() => {
                                // Find the course option that matches the fcId
                                const courseSelect = assignmentForm.querySelector('#assignment-course-select');
                                const courseOption = [...courseSelect.options].find(opt => {
                                    return opt.value.split(' ')[0] === fcId;
                                });

                                if (courseOption) {
                                    courseSelect.value = courseOption.value;
                                }

                            }, 300);
                        }, 300);

                        assignmentForm.querySelector('#assignment-type').value = data.assignment_no;
                        assignmentForm.querySelector('#assignment-course-select').value = fcId;
                        assignmentForm.querySelector('#assignment-date').value = data.assignment_date;
                        assignmentForm.querySelector('#assignment-max-mark').value = total;

                        // Display existing file name
                        const fileNameSpan = assignmentForm.querySelector('.file-name');
                        fileNameSpan.textContent = data.question_paper_image || 'No file chosen';

                        // Populate questions
                        const questionsContainer = assignmentForm.querySelector('.questions-container');
                        questionsContainer.innerHTML = ''; // Clear existing questions
                        data.questions.forEach((question, index) => {
                            const questionElement = createQuestionElement(index + 1);
                            questionElement.querySelector('.max-mark').value = question.max_mark;
                            questionElement.querySelector('.targeted-mark').value = question.target_mark;
                            questionElement.querySelector('.knowledge-level').value = question.knowledge_level;
                            questionElement.querySelector('.co-level').value = question.co_level;
                            questionsContainer.appendChild(questionElement);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    alert('Failed to fetch data');
                });
        }
    });

    document.getElementById('view-progress-content').addEventListener('click', async (event) => {
        if (event.target.classList.contains('delete-btn')) {
            const testId = event.target.getAttribute('data-id');
            const testType = event.target.getAttribute('data-type');
            const fcId = event.target.getAttribute('data-fc-id');
    
            // Confirm deletion
            const confirmed = confirm(`Are you sure you want to delete this ${testType}?`);
            if (!confirmed) {
                return;
            }
    
            try {
                // Send delete request to the server
                const response = await fetch(`delete_${testType}.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: testId, fc_id: fcId })
                });
    
                const data = await response.json();
                if (data.status === 'success') {
                    alert(data.message);
                    
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error deleting:', error);
                alert('Failed to delete');
            }
        }
    });

    document.getElementById('complete-detail-btn').addEventListener('click', () => {
        const fcId = document.getElementById('report-course-select').value;
        const testType = document.getElementById('report-test-select').options[document.getElementById('report-test-select').selectedIndex].textContent.toLowerCase().split(' ')[0];

        if (!fcId) {
            alert('Please select a course.');
            return;
        }

        const reportUrl = `./ReportGeneration/complete_detail_report.php?fc_id=${fcId}&test_type=${testType}`;
        window.open(reportUrl, '_blank');
    });
});