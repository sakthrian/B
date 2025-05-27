document.addEventListener('DOMContentLoaded', () => {
    const marksCourseSelect = document.getElementById('marks-course-select');
    const testSelect = document.getElementById('test-select');
    const studentMarksTable = document.getElementById('student-marks-table');
    const saveMarksButton = document.getElementById('save-student-marks');
    const freezeMarksButton = document.getElementById('freeze-marks');
    const frozenMessage = document.getElementById('frozen-message');

    if (!marksCourseSelect || !testSelect || !studentMarksTable || !saveMarksButton) {
        console.error('One or more required elements not found in the DOM.');
        return;
    }

    marksCourseSelect.addEventListener('change', (e) => {
        const fc_id = e.target.value;
        const type = document.getElementById("degree-select-mark").value;
        // console.log(fc_id, "-", type);
        // console.log(courseId,' ',facultyId);

        fetch(`fetch_students.php?fc_id=${fc_id}&type=${type}`)
            .then(response => response.json())
            .then(data => {
                const { semester, students } = data;
                studentMarksTable.querySelector('tbody').innerHTML = '';
                students.forEach((student, index) => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${index + 1}</td> <!-- SI/No -->
                        <td>${student.register_no}</td>
                        <td>${student.name}</td>
                        <!-- Add columns for marks based on the number of questions -->
                    `;
                    studentMarksTable.querySelector('tbody').appendChild(row);
                    fetchExistingMarks(fc_id, type);
                });
            })
            .catch(error => console.error('Error fetching students:', error));
    });

    testSelect.addEventListener('change', (e) => {
        const input = String(e.target.value);
        const parts = input.split(" ");
        const Id = parseInt(parts[0], 10);
        const total_marks = parseFloat(parts[1]);
        const selectedText = e.target.selectedOptions[0].textContent;
        const testType = selectedText.split(" ")[0].toLowerCase();
        console.log(Id, " ", total_marks, " ", testType);

        fetch(`fetch_${testType}_questions.php?test_id=${Id}`)
            .then(response => response.json())
            .then(data => {
                const questions = data.questions;
                // Update the table headers and rows with question columns
                const tableHeader = studentMarksTable.querySelector('thead tr');
                const tableBody = studentMarksTable.querySelector('tbody');
                // Clear existing question columns
                tableHeader.innerHTML = `<th>SI/No</th><th>Reg No</th><th>Student Name</th>`;
                tableBody.querySelectorAll('tr').forEach(row => {
                    row.innerHTML = `<td>${row.cells[0].textContent}</td><td>${row.cells[1].textContent}</td><td>${row.cells[2].textContent}</td>`;
                });
                // Add question columns
                questions.forEach(question => {
                    const th = document.createElement('th');
                    th.innerHTML = `Q${question.question_no}<br>${question.max_mark}`;
                    tableHeader.appendChild(th);
                    tableBody.querySelectorAll('tr').forEach(row => {
                        const td = document.createElement('td');
                        td.innerHTML = `<input type="number" min="0" max="${question.max_mark}" class="mark-input" data-question="${question.id}" value="0" required>`;
                        row.appendChild(td);
                    });
                });
                // Add total marks column
                const totalTh = document.createElement('th');
                totalTh.textContent = 'Total';
                tableHeader.appendChild(totalTh);
                tableBody.querySelectorAll('tr').forEach(row => {
                    const totalTd = document.createElement('td');
                    totalTd.classList.add('total-marks');
                    totalTd.textContent = '0';
                    row.appendChild(totalTd);
                });
                // Add focus event listeners to clear default value
                const markInputs = studentMarksTable.querySelectorAll('.mark-input');
                markInputs.forEach(input => {
                    input.addEventListener('focus', (e) => {
                        e.target.value = '';
                    });
                });

                // Calculate total marks
                calculateTotalMarks(total_marks);

                // Fetch existing marks for the selected test/assignment
                fetchExistingMarks(Id, testType);
            })
            .catch(error => console.error('Error fetching questions:', error));
    });

    function fetchExistingMarks(testId, testType, total_marks) {
        if(testType == 'test' || testType == 'assignment'){
            fetch(`fetch_${testType}_marks.php?test_id=${testId}`)
            .then(response => response.json())
            .then(data => {
                console.log('Fetched existing marks data:', data);
                const existingMarks = data.marks;
                const testStatus = data.status;

                if (testStatus === 'Freeze') {
                    saveMarksButton.style.display = 'none';
                    freezeMarksButton.style.display = 'none';
                    frozenMessage.style.display = 'block';

                    // Make all mark inputs read-only
                    studentMarksTable.querySelectorAll('.mark-input').forEach(input => {
                        input.setAttribute('readonly', 'readonly');
                        input.classList.add('readonly');
                    });
                } else {
                    saveMarksButton.style.display = 'inline-block';
                    freezeMarksButton.style.display = 'inline-block';
                    frozenMessage.style.display = 'none';

                    // Remove read-only attribute from mark inputs
                    studentMarksTable.querySelectorAll('.mark-input').forEach(input => {
                        input.removeAttribute('readonly');
                        input.classList.remove('readonly');
                    });
                }

                if (!Array.isArray(existingMarks)) {
                    console.error('Existing marks data is not an array:', existingMarks);
                    return;
                }
                const tableBody = studentMarksTable.querySelector('tbody');
                tableBody.querySelectorAll('tr').forEach(row => {
                    const registerNo = row.cells[1].textContent; // Changed index to 1 for Reg No
                    console.log('Processing student:', registerNo);
                    const studentMarks = existingMarks.filter(m => m.student_id === registerNo);
                    if (studentMarks.length === 0) {
                        console.log('No existing marks found for student:', registerNo);
                        return;
                    }

                    row.querySelectorAll('.mark-input').forEach(input => {
                        const questionId = input.getAttribute('data-question');
                        const mark = studentMarks.find(m => m.question_id.toString() === questionId);
                        if (mark) {
                            console.log('Found mark for question:', questionId, mark.obtained_mark);
                            input.value = mark.obtained_mark;
                        } else {
                            console.log('No mark found for question:', questionId);
                        }
                    });
                });
                calculateTotalMarks(total_marks);
            })
            .catch(error => console.error('Error fetching existing marks:', error));
        }
    }

    function calculateTotalMarks(total_marks) {
        const markInputs = studentMarksTable.querySelectorAll('.mark-input');
        const totalMarksCells = studentMarksTable.querySelectorAll('.total-marks');

        markInputs.forEach((input) => {
            const row = input.closest('tr');
            const totalCell = row.querySelector('.total-marks');
            let total = 0;

            // Calculate the total marks for the current row
            row.querySelectorAll('.mark-input').forEach(input => {
                let value = parseFloat(input.value) || 0;
                if (value == -1) {
                    value = 0;
                    // console.log(` -1 -> ${value}`);
                }
                total += value;
            });

            if (total > total_marks) {
                alert(`Total marks cannot exceed the maximum total marks of ${total_marks}`);

                // Find the input that caused the excess
                let excess = total - total_marks;
                let inputs = Array.from(row.querySelectorAll('.mark-input'));

                // Iterate through the inputs in reverse order to find the last input that caused the excess
                for (let i = inputs.length - 1; i >= 0; i--) {
                    const inputValue = parseFloat(inputs[i].value) || 0;
                    if (inputValue > 0) {
                        if (inputValue >= excess) {
                            inputs[i].value = (inputValue - excess).toFixed(2);
                            excess = 0;
                        } else {
                            excess -= inputValue;
                            inputs[i].value = 0;
                        }
                    }
                    if (excess <= 0) break;
                }

                // Recalculate the total marks after resetting the value
                total = 0;
                row.querySelectorAll('.mark-input').forEach(input => {
                    const value = parseFloat(input.value) || 0;
                    total += value;
                });

                totalCell.textContent = total.toFixed(2);
            } else {
                totalCell.textContent = total.toFixed(2);
            }
        });
    }

    studentMarksTable.addEventListener('input', (e) => {
        if (e.target.classList.contains('mark-input')) {
            const maxMark = e.target.getAttribute('max');
            const value = parseFloat(e.target.value);

            if (value > maxMark) {
                alert(`Mark cannot exceed the maximum mark of ${maxMark}`);
                e.target.value = maxMark;
            }
            if (value < -1) {
                alert(`Negative mark not allowed`);
                e.target.value = 0;
            }

            if (value === -1) {
                e.target.classList.add('input-absent');
            } else {
                e.target.classList.remove('input-absent');
            }

            // Get the total marks for the test from the testSelect value
            const testSelectValue = testSelect.value;
            const total_marks = parseFloat(testSelectValue.split(" ")[1]);

            calculateTotalMarks(total_marks);
        }
    });

    studentMarksTable.addEventListener('focusin', (e) => {
        if (e.target.classList.contains('mark-input')) {
            const row = e.target.closest('tr');
            studentMarksTable.querySelectorAll('tr').forEach(r => r.classList.remove('highlight'));
            row.classList.add('highlight');
        }
    });

    studentMarksTable.addEventListener('focusout', (e) => {
        if (e.target.classList.contains('mark-input')) {
            const row = e.target.closest('tr');
            row.classList.remove('highlight');
        }
    });

    saveMarksButton.addEventListener('click', () => {
        if (!testSelect || !testSelect.value) {
            alert('Please select a test/assignment.');
            return;
        }

        const testSelectValue = testSelect.value;
        const parts = testSelectValue.split(" ");
        const testId = parseInt(parts[0], 10);
        const testType = testSelect.selectedOptions[0].textContent.split(" ")[0].toLowerCase();

        console.log(testSelectValue, testId, testType);

        if (isNaN(testId)) {
            alert('Invalid test ID.');
            return;
        }

        const rows = studentMarksTable.querySelectorAll('tbody tr');
        const marksData = [];
        let allMarksFilled = true; // Flag to track if all marks are filled

        rows.forEach(row => {
            const registerNo = row.cells[1].textContent; // Changed index to 1 for Reg No
            const markInputs = row.querySelectorAll('.mark-input');

            markInputs.forEach(input => {
                if (input.value.trim() === '') {
                    allMarksFilled = false;
                }
            });

            if (!allMarksFilled) {
                return;
            }

            markInputs.forEach(input => {
                const questionId = parseInt(input.getAttribute('data-question'), 10); // Convert to integer
                const obtainedMark = parseFloat(input.value) || 0;

                // Include marks with a value of 0
                marksData.push({
                    student_id: registerNo,
                    test_id: testId,
                    question_id: questionId,
                    obtained_mark: obtainedMark
                });
            });
        });

        if (!allMarksFilled) {
            alert('Question mark cannot be empty!');
            return;
        }

        if (marksData.length === 0) {
            alert('No marks entered to save.');
            return;
        }

        // Log the data being sent
        // console.log('Marks Data (Object):', marksData);
        const stringifiedMarksData = JSON.stringify(marksData);
        // console.log('Stringified Marks Data (String):', stringifiedMarksData);

        fetch(`save_${testType}_marks.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: stringifiedMarksData
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Server Response:', data);
                if (data.success) {
                    alert('Marks saved successfully!');
                } else {
                    alert('Failed to save marks: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error saving marks:', error);
                alert('An error occurred while saving marks: ' + error.message);
            });
    });

    //Freeze Button
    freezeMarksButton.addEventListener('click', () => {
        if (!testSelect || !testSelect.value) {
            alert('Please select a test/assignment.');
            return;
        }

        const testSelectValue = testSelect.value;
        const parts = testSelectValue.split(" ");
        const testId = parseInt(parts[0], 10);
        const testType = testSelect.selectedOptions[0].textContent.split(" ")[0].toLowerCase();

        if (isNaN(testId)) {
            alert('Invalid test ID.');
            return;
        }

        const userConfirmed = confirm("Warning: Once the test is frozen, you cannot change the test and mark details. Are you sure you want to proceed?");
        if (!userConfirmed) {
            return;
        }

        const rows = studentMarksTable.querySelectorAll('tbody tr');
        const marksData = [];
        let allMarksFilled = true;

        rows.forEach(row => {
            const registerNo = row.cells[1].textContent;
            const markInputs = row.querySelectorAll('.mark-input');

            markInputs.forEach(input => {
                if (input.value.trim() === '') {
                    allMarksFilled = false;
                }
            });

            if (!allMarksFilled) {
                return;
            }

            markInputs.forEach(input => {
                const questionId = parseInt(input.getAttribute('data-question'), 10);
                const obtainedMark = parseFloat(input.value) || 0;

                marksData.push({
                    student_id: registerNo,
                    test_id: testId,
                    question_id: questionId,
                    obtained_mark: obtainedMark
                });
            });
        });

        if (!allMarksFilled) {
            alert('Question mark cannot be empty!');
            return;
        }

        if (marksData.length === 0) {
            alert('No marks entered to save.');
            return;
        }

        fetch(`save_${testType}_marks.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(marksData)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Marks saved successfully!');
                    updateTestStatus(testId, 'Freeze', testType);
                } else {
                    alert('Failed to save marks: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error saving marks:', error);
                alert('An error occurred while saving marks: ' + error.message);
            });
    });

    //Test status (freeze or null)
    function updateTestStatus(testId, status, testType) {
        fetch(`update_${testType}_status.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ test_id: testId, status: status })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`${testType} status updated to ` + status);
                    if (status === 'Freeze') {
                        saveMarksButton.style.display = 'none';
                        freezeMarksButton.style.display = 'none';
                        frozenMessage.style.display = 'block';

                        // Make all mark inputs read-only
                        studentMarksTable.querySelectorAll('.mark-input').forEach(input => {
                            input.setAttribute('readonly', 'readonly');
                            input.classList.add('readonly');
                        });
                    } else {
                        saveMarksButton.style.display = 'inline-block';
                        freezeMarksButton.style.display = 'inline-block';
                        frozenMessage.style.display = 'none';

                        // Remove read-only attribute from mark inputs
                        studentMarksTable.querySelectorAll('.mark-input').forEach(input => {
                            input.removeAttribute('readonly');
                            input.classList.remove('readonly');
                        });
                    }
                } else {
                    alert(`Failed to update ${testType} status: ` + data.message);
                }
            })
            .catch(error => {
                console.error('Error updating test status:', error);
                alert(`An error occurred while updating ${testType} status: ` + error.message);
            });
    }
});