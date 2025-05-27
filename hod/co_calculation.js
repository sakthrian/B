document.addEventListener("DOMContentLoaded", () => {
  const coCourseSelect = document.getElementById("co-course-select");
  const reportCourseSelect = document.getElementById("report-course-select");
  const coAttainmentTable = document.getElementById("co-attainment-table");
  var testAssignmentCards = null;

  coCourseSelect.addEventListener("change", (e) => {
    testAssignmentCards = document.getElementById("test-assignment-cards");
    const fc_id = e.target.value;
    fetchCourseData(fc_id);
    fetchTestAssignmentData(fc_id);
  });

  reportCourseSelect.addEventListener("change", (e) => {
    testAssignmentCards = document.getElementById(
      "test-assignment-cards-report"
    );
    const fc_id = e.target.value;
    fetchCourseData(fc_id);
    fetchTestAssignmentData(fc_id);
  });

  function fetchCourseData(fc_id) {
    fetch(`../Faculty/CoCalculation/fetch_course_data.php?fc_id=${fc_id}`)
      .then((response) => response.json())
      .then((data) => {
        fetchActionsData(fc_id).then((actionsData) => {
          console.log(actionsData);
          generateTable(data, actionsData);
        });
      })
      .catch((error) => console.error("Error:", error));
  }

  function fetchTestAssignmentData(fc_id) {
    fetch(`fetch_progress.php?fc_id=${fc_id}`)
      .then((response) => response.json())
      .then((data) => {
        generateTestAssignmentCards(data, fc_id);
      })
      .catch((error) => console.error("Error:", error));
  }

  function generateTestAssignmentCards(data, fcId) {
    const {
      testsData,
      assignmentsData,
      questionsData,
      assignmentQuestionsData,
    } = data;
    testAssignmentCards.innerHTML = "";

    let testCards = [];
    let assignmentCards = [];

    // Generate test cards
    if (testsData) {
      testsData.forEach((test) => {
        const [year, month, day] = test.test_date.split("-");
        const reversedTestDate = `${day}-${month}-${year}`;
        const questions = questionsData[test.id] || [];

        const testCard = `
          <div class="card" data-id="${test.id}" data-type="test">
            <div class="card-header">
              <h3>Test ${test.test_no}</h3>
              <button class="btn minimize-btn" style="display: none;">
                <p>Back to Metadata</p>
              </button>
            </div>
            <div class="card-body">
              <p><strong>Total Mark:</strong> ${test.total_mark}</p>
              <p><strong>Date:</strong> ${reversedTestDate}</p>
              <table>
                <thead>
                  <tr>
                    <th>Q.No</th>
                    <th>Max</th>
                    <th>Target</th>
                    <th>CO</th>
                    <th>Level</th>
                  </tr>
                </thead>
                <tbody>
                  ${questions
                    .map(
                      (q) => `
                    <tr>
                      <td>${q.question_no}</td>
                      <td>${q.max_mark}</td>
                      <td>${q.target_mark}</td>
                      <td>${q.co_number}</td>
                      <td>${q.knowledge_level}</td>
                    </tr>
                  `
                    )
                    .join("")}
                </tbody>
              </table>
              <button class="btn view-mark-btn" data-id="${
                test.id
              }" data-type="test" data-fc-id="${fcId}">
                View Marks
              </button>
            </div>
          </div>
        `;
        testCards.push({ testNo: test.test_no, html: testCard });
      });
    }

    // Generate assignment cards
    if (assignmentsData) {
      assignmentsData.forEach((assignment) => {
        const [year, month, day] = assignment.assignment_date.split("-");
        const reversedAssignmentDate = `${day}-${month}-${year}`;
        const questions = assignmentQuestionsData[assignment.id] || [];

        const assignmentCard = `
          <div class="card" data-id="${assignment.id}" data-type="assignment">
            <div class="card-header">
              <h3>Assignment ${assignment.assignment_no}</h3>
              <button class="btn minimize-btn" style="display: none;">
                <p>Back to Metadata</p>
              </button>
            </div>
            <div class="card-body">
              <p><strong>Total Mark:</strong> ${assignment.total_mark}</p>
              <p><strong>Date:</strong> ${reversedAssignmentDate}</p>
              <table>
                <thead>
                  <tr>
                    <th>Q.No</th>
                    <th>Max</th>
                    <th>Target</th>
                    <th>CO</th>
                    <th>Level</th>
                  </tr>
                </thead>
                <tbody>
                  ${questions
                    .map(
                      (q) => `
                    <tr>
                      <td>${q.question_no}</td>
                      <td>${q.max_mark}</td>
                      <td>${q.target_mark}</td>
                      <td>${q.co_number}</td>
                      <td>${q.knowledge_level}</td>
                    </tr>
                  `
                    )
                    .join("")}
                </tbody>
              </table>
              <button class="btn view-mark-btn" data-id="${
                assignment.id
              }" data-type="assignment" data-fc-id="${fcId}">
                View Marks
              </button>
            </div>
          </div>
        `;
        assignmentCards.push({
          assignmentNo: assignment.assignment_no,
          html: assignmentCard,
        });
      });
    }

    // Sort and display cards
    testCards.sort((a, b) => a.testNo - b.testNo);
    assignmentCards.sort((a, b) => a.assignmentNo - b.assignmentNo);

    testCards.forEach((card) => {
      const cardElement = document.createElement("div");
      cardElement.innerHTML = card.html;
      cardElement
        .querySelector(".card")
        .setAttribute("data-original", card.html);
      testAssignmentCards.appendChild(cardElement);
    });

    assignmentCards.forEach((card) => {
      const cardElement = document.createElement("div");
      cardElement.innerHTML = card.html;
      cardElement
        .querySelector(".card")
        .setAttribute("data-original", card.html);
      testAssignmentCards.appendChild(cardElement);
    });

    testAssignmentCards.addEventListener("click", (e) => {
      if (e.target.classList.contains("view-mark-btn")) {
        viewMarkHandler(e);
      }
    });
    // Add event listeners to view mark buttons
      async function viewMarkHandler(e) {
        const card = e.target.closest(".card");
        const testId = card.dataset.id;
        const testType = card.dataset.type;
        const fcId = e.target.dataset.fcId; 

        try {
          // Fetch test details
          const testDetailsResponse = await fetch(
            `../Faculty/fetch_${testType}_details.php?id=${testId}`
          );
          const testDetails = await testDetailsResponse.json();

          // Fetch questions
          const questionsResponse = await fetch(
            `../Faculty/fetch_${testType}_questions.php?test_id=${testId}`
          );
          const questionsData = await questionsResponse.json();
          const questions = questionsData.questions;

          // Fetch marks
          const marksResponse = await fetch(
            `../Faculty/fetch_${testType}_marks.php?test_id=${testId}`
          );
          const marksData = await marksResponse.json();
          const marks = marksData.marks;

          // Fetch students
          const studentsResponse = await fetch(
            `../Faculty/fetch_students.php?fc_id=${fcId}&type=${testDetails.degree}`
          );
          const studentsData = await studentsResponse.json();
          const students = studentsData.students;

          // Create marks view HTML
          const marksViewHTML = `
            <div class="marks-view">
              <h3>${testType === "test" ? "Test" : "Assignment"} ${
            testDetails.test_no || testDetails.assignment_no
          } - Student Marks</h3>
              <div class="marks-table-container">
                <table class="marks-table">
                  <thead>
                    <tr>
                      <th>SI No</th>
                      <th>Reg No</th>
                      <th>Student Name</th>
                      ${questions
                        .map(
                          (q) => `<th>Q${q.question_no}<br>${q.max_mark}</th>`
                        )
                        .join("")}
                      <th>Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${students
                      .map((student, index) => {
                        const studentMarks = marks.filter(
                          (m) => m.student_id === student.register_no
                        );
                        let totalMarks = 0;
                        return `
                        <tr>
                          <td>${index + 1}</td>
                          <td>${student.register_no}</td>
                          <td>${student.name}</td>
                          ${questions
                            .map((q) => {
                              const mark = studentMarks.find(
                                (m) => m.question_id == q.id
                              );
                              const markValue = mark
                                ? mark.obtained_mark == -1
                                  ? "a"
                                  : mark.obtained_mark
                                : "-";
                              if (mark && mark.obtained_mark != -1) {
                                totalMarks += parseFloat(mark.obtained_mark);
                              }
                              return `<td>${markValue}</td>`;
                            })
                            .join("")}
                          <td>${totalMarks.toFixed(2)}</td>
                        </tr>
                      `;
                      })
                      .join("")}
                  </tbody>
                </table>
              </div>
              <div class="marks-actions">
                <button class="btn print-btn" onclick="window.open('../Faculty/ReportGeneration/generate_report.php?fc_id=${fcId}&test_id=${testId}&test_type=${testType}')">
                  <i class="fas fa-print"></i> Print
                </button>
              </div>
            </div>
          `;

          // Replace card body with marks view
          const cardBody = card.querySelector(".card-body");
          cardBody.innerHTML = marksViewHTML;

          // Show minimize button
          const minimizeBtn = card.querySelector(".minimize-btn");
          minimizeBtn.style.display = "block";

          // Add minimize button event
          minimizeBtn.addEventListener("click", () => {
            const originalHTML = card.getAttribute("data-original");
          
            // Create a wrapper to parse original HTML
            const wrapper = document.createElement("div");
            wrapper.innerHTML = originalHTML;
          
            // Replace card's content with parsed DOM, not raw HTML string
            const restoredCard = wrapper.querySelector(".card");
            if (restoredCard) {
              card.replaceWith(restoredCard);
          
              const viewMarkBtn = restoredCard.querySelector(".view-mark-btn");
              if (viewMarkBtn) {
                viewMarkBtn.addEventListener("click", viewMarkHandler);
              }
            } else {
              console.error("Failed to restore card HTML properly");
            }
          });
        } catch (error) {
          console.error("Error loading marks:", error);
          alert("Failed to load marks data");
        }
      }
  }

  function fetchActionsData(fc_id) {
    return fetch(
      `../Faculty/CoCalculation/fetch_actions_data.php?fc_id=${fc_id}`
    )
      .then((response) => response.json())
      .catch((error) => console.error("Error:", error));
  }

  function generateTable(data, actionsData) {
    const cos = data.cos;
    const tests = data.tests;
    const assignments = data.assignments;
    const coResults = data.co_results;
    const coOverallData = data.co_overall_data || [];
    coAttainmentTable.innerHTML = "";

    const headerRow = document.createElement("tr");
    const headerEmpty = document.createElement("th");
    headerRow.appendChild(headerEmpty);

    cos.forEach((co) => {
      const th = document.createElement("th");
      th.textContent = `CO${co.co_number}`;
      headerRow.appendChild(th);
    });

    const headers = ["Action Required", "Date", "Action Taken", "Date"];
    headers.forEach((h) => {
      const th = document.createElement("th");
      th.textContent = h;
      headerRow.appendChild(th);
    });

    coAttainmentTable.appendChild(headerRow);

    const tableBody = document.createElement("tbody");

    function createRow(item, type) {
      const row = document.createElement("tr");
      row.classList.add(`${type}-row`);
      const header = document.createElement("td");
      header.textContent =
        type === "test" ? `T${item.test_no}` : `A${item.assignment_no}`;
      row.appendChild(header);

      cos.forEach((co) => {
        const td = document.createElement("td");
        const result = coResults.find(
          (r) => r.co_id === co.id && r[`${type}_id`] === item.id
        );
        td.textContent = result ? result.co_level : 0;
        row.appendChild(td);
      });

      // Action Required Input
      const actionRequiredCell = document.createElement("td");
      const actionRequiredInput = document.createElement("input");
      actionRequiredInput.type = "text";
      actionRequiredInput.placeholder = "Enter text";
      actionRequiredInput.classList.add("mark-input-text");
      actionRequiredCell.appendChild(actionRequiredInput);
      row.appendChild(actionRequiredCell);

      // Date Required Input (DATE input)
      const dateRequiredCell = document.createElement("td");
      const dateRequiredInput = document.createElement("input");
      dateRequiredInput.type = "date";
      dateRequiredInput.classList.add("date-required");
      dateRequiredCell.appendChild(dateRequiredInput);
      row.appendChild(dateRequiredCell);

      // Action Taken (read-only)
      const actionTakenCell = document.createElement("td");
      const actionTakenInput = document.createElement("input");
      actionTakenInput.type = "text";
      actionTakenInput.placeholder = "Faculty will enter";
      actionTakenInput.classList.add("mark-input-text");
      actionTakenInput.disabled = true;
      actionTakenCell.appendChild(actionTakenInput);
      row.appendChild(actionTakenCell);

      // Date Taken (read-only)
      const dateTakenCell = document.createElement("td");
      const dateTakenInput = document.createElement("input");
      dateTakenInput.type = "date";
      dateTakenInput.placeholder = "Enter text";
      dateTakenInput.classList.add("date-required");
      dateTakenInput.disabled = true;
      dateTakenCell.appendChild(dateTakenInput);
      row.appendChild(dateTakenCell);

      // Fill from actionsData if available
      const actionData = actionsData.find(
        (action) =>
          action.category.toLowerCase() === type.toLowerCase() &&
          Number(action.category_id) ===
            Number(type === "test" ? item.test_no : item.assignment_no)
      );
      if (actionData) {
        actionRequiredInput.value = actionData.action_required || "";
        if (actionData.action_required_date) {
          dateRequiredInput.value = actionData.action_required_date;
        }
        if (actionData.action_taken) {
          actionTakenInput.value = actionData.action_taken;
        }
        if (actionData.action_taken_date) {
          dateTakenInput.value = actionData.action_taken_date;
        }
      }

      // Set current date when actionRequired changes
      actionRequiredInput.addEventListener("input", () => {
        if (actionRequiredInput.value.trim() !== "") {
          dateRequiredInput.value = getCurrentDateISO();
        } else {
          dateRequiredInput.value = "";
        }
      });

      tableBody.appendChild(row);
    }

    tests.forEach((test) => createRow(test, "test"));
    assignments.forEach((assignment) => createRow(assignment, "assignment"));

    const additionalHeaders = ["CIA", "SE", "DA", "IA", "CA"];
    additionalHeaders.forEach((header) => {
      const row = document.createElement("tr");
      row.classList.add("additional-row");
      const headerCell = document.createElement("td");
      headerCell.textContent = header;
      row.appendChild(headerCell);

      cos.forEach((co, index) => {
        const td = document.createElement("td");
        td.setAttribute("data-header", header);
        td.setAttribute("data-index", index);

        if (header === "CIA") {
          row.classList.add("cia-row");
          const coLevels = [];

          tests.forEach((test) => {
            const result = coResults.find(
              (result) => result.co_id === co.id && result.test_id === test.id
            );
            if (result && result.co_level) {
              coLevels.push(parseInt(result.co_level, 10));
            }
          });

          assignments.forEach((assignment) => {
            const result = coResults.find(
              (result) =>
                result.co_id === co.id && result.assignment_id === assignment.id
            );
            if (result && result.co_level) {
              coLevels.push(parseInt(result.co_level, 10));
            }
          });

          const average =
            coLevels.length > 0
              ? (
                  coLevels.reduce((sum, val) => sum + val, 0) / coLevels.length
                ).toFixed(2)
              : 0;
          td.textContent = average;
        } else if (header === "SE" || header === "IA") {
          const input = document.createElement("input");
          input.type = "number";
          input.classList.add("mark-input");
          input.min = 0;
          input.max = 10;
          input.value = "";

          const existingData = coOverallData.find(
            (item) => item.co_id === co.id
          );
          if (existingData) {
            input.value = existingData[header.toLowerCase()];
          }

          input.disabled = true;
          td.appendChild(input);
        } else if (header === "DA" || header === "CA") {
          const existingData = coOverallData.find(
            (item) => item.co_id === co.id
          );
          td.textContent = existingData
            ? existingData[header.toLowerCase()]
            : 0;
        }

        row.appendChild(td);
      });

      // Add 4 blank cells for Action columns
      for (let i = 0; i < 4; i++) {
        const emptyCell = document.createElement("td");
        row.appendChild(emptyCell);
      }

      tableBody.appendChild(row);
    });

    coAttainmentTable.appendChild(tableBody);
  }

  function getCurrentDateISO() {
    return new Date().toISOString().split("T")[0]; // YYYY-MM-DD
  }

  document.getElementById("send-response").addEventListener("click", () => {
    const courseSelect = document.getElementById("co-course-select");
    const fcId = courseSelect.value;

    const tableRows = document.querySelectorAll(".test-row, .assignment-row");
    const actionsData = [];

    tableRows.forEach((row) => {
      const categoryId = row
        .querySelector("td:first-child")
        .textContent.replace(/[^0-9]/g, "");
      const category = row.classList.contains("test-row")
        ? "test"
        : "assignment";

      const actionRequiredCell = row.querySelector("td:nth-last-child(4)");
      const actionRequiredDateCell = row.querySelector("td:nth-last-child(3)");

      const actionRequiredInput = actionRequiredCell.querySelector("input");
      const actionDateInput = actionRequiredDateCell.querySelector("input");

      const actionRequired = actionRequiredInput
        ? actionRequiredInput.value.trim()
        : "";
      const actionDate = actionDateInput ? actionDateInput.value.trim() : "";

      actionsData.push({
        fc_id: fcId,
        category: category,
        category_id: categoryId,
        action_required: actionRequired,
        action_required_date: actionDate,
      });
    });

    console.log(actionsData);

    fetch(`../Faculty/CoCalculation/save_actions_data.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(actionsData),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok");
        }
        return response.json();
      })
      .then((data) => {
        alert("Actions data saved successfully!");
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("Failed to save actions data: " + error.message);
      });
  });
});
