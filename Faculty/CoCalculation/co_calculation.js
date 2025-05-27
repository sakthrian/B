document.addEventListener("DOMContentLoaded", () => {
  const coCourseSelect = document.getElementById("co-course-select");
  const coAttainmentTable = document.getElementById("co-attainment-table");

  coCourseSelect.addEventListener("change", (e) => {
    const fc_id = e.target.value;
    fetchCourseData(fc_id);
  });

  function fetchCourseData(fc_id) {
    fetch(`../Faculty/CoCalculation/fetch_course_data.php?fc_id=${fc_id}`)
      .then((response) => response.json())
      .then((data) => {
        fetchActionsData(fc_id).then((actionsData) => {
          generateTable(data, actionsData);
        });
      })
      .catch((error) => console.error("Error:", error));
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

    ["Action Required", "Date", "Action Taken", "Date"].forEach((h) => {
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

      // Action Required (readonly)
      const actionRequiredCell = document.createElement("td");
      const actionRequiredInput = document.createElement("input");
      actionRequiredInput.type = "text";
      actionRequiredInput.readOnly = true;
      actionRequiredInput.classList.add("mark-input-text");
      actionRequiredCell.appendChild(actionRequiredInput);
      row.appendChild(actionRequiredCell);

      // Date Required (readonly)
      const dateRequiredCell = document.createElement("td");
      const dateRequiredInput = document.createElement("input");
      dateRequiredInput.type = "date";
      dateRequiredInput.readOnly = true;
      dateRequiredInput.classList.add("date-required");
      dateRequiredCell.appendChild(dateRequiredInput);
      row.appendChild(dateRequiredCell);

      // Action Taken (editable)
      const actionTakenCell = document.createElement("td");
      const actionTakenInput = document.createElement("input");
      actionTakenInput.type = "text";
      actionTakenInput.placeholder = "Enter Action Taken";
      actionTakenInput.classList.add("mark-input-text");
      actionTakenInput.addEventListener("input", () => {
        if (!dateTakenInput.value) {
          dateTakenInput.value = getCurrentDateISO();
        }
      });
      actionTakenCell.appendChild(actionTakenInput);
      row.appendChild(actionTakenCell);

      // Date Taken (auto-filled)
      const dateTakenCell = document.createElement("td");
      const dateTakenInput = document.createElement("input");
      dateTakenInput.type = "date";
      dateTakenInput.classList.add("date-required");
      dateTakenCell.appendChild(dateTakenInput);
      row.appendChild(dateTakenCell);

      // Fill data if exists
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

      tableBody.appendChild(row);
    }

    tests.forEach((test) => createRow(test, "test"));
    assignments.forEach((assignment) => createRow(assignment, "assignment"));

    // Additional CIA/SE/DA/IA/CA
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
          const coLevels = [];

          tests.forEach((test) => {
            const result = coResults.find(
              (result) => result.co_id === co.id && result.test_id === test.id
            );
            if (result?.co_level) {
              coLevels.push(parseInt(result.co_level, 10));
            }
          });

          assignments.forEach((assignment) => {
            const result = coResults.find(
              (result) =>
                result.co_id === co.id && result.assignment_id === assignment.id
            );
            if (result?.co_level) {
              coLevels.push(parseInt(result.co_level, 10));
            }
          });

          const avg = coLevels.length
            ? (coLevels.reduce((a, b) => a + b, 0) / coLevels.length).toFixed(2)
            : 0;
          td.textContent = avg;
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

          // ✅ Add these:
          input.setAttribute("data-header", header);
          input.setAttribute("data-index", index);

          // ✅ Recalculate when value changes
          input.addEventListener("input", (e) => {
            const value = e.target.value.trim();
            if (value === "") return;
            const numericValue = parseFloat(value);
            if (isNaN(numericValue) || numericValue < 0 || numericValue > 10) {
              alert("Please enter a valid number between 0 and 10.");
              e.target.value = "";
            } else {
              updateDependentValues(index);
            }
          });

          td.appendChild(input);
        } else if (header === "DA" || header === "CA") {
          const existing = coOverallData.find((item) => item.co_id === co.id);
          td.textContent = existing ? existing[header.toLowerCase()] : 0;
        }

        row.appendChild(td);
      });

      // Blank 4 action cells
      for (let i = 0; i < 4; i++) {
        const emptyCell = document.createElement("td");
        row.appendChild(emptyCell);
      }

      tableBody.appendChild(row);
    });

    coAttainmentTable.appendChild(tableBody);
  }

  function getCurrentDateISO() {
    return new Date().toISOString().split("T")[0];
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

      // Select the second-to-last and last cells in the row
      const actionTakenCell = row.querySelector("td:nth-last-child(2)");
      const actionTakenDateCell = row.querySelector("td:last-child");

      const actionTakenInput = actionTakenCell.querySelector("input");
      const actionTakenDateInput = actionTakenDateCell.querySelector("input");

      const actionTaken = actionTakenInput ? actionTakenInput.value.trim() : "";
      const actionTakenDate = actionTakenDateInput
        ? actionTakenDateInput.value.trim()
        : "";

      actionsData.push({
        fc_id: fcId,
        category: category,
        category_id: categoryId,
        action_taken: actionTaken,
        action_taken_date: actionTakenDate,
      });
    });
    console.log(actionsData);
    fetch(`./CoCalculation/save_response_data.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(actionsData),
    })
      .then((response) => {
        if (!response.ok) throw new Error("Network error");
        return response.json();
      })
      .then((data) => {
        alert("Response saved successfully!");
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("Failed to save response.");
      });
  });

  function updateDependentValues(index) {
    const ciaCell = document.querySelector(
      `td[data-header="CIA"][data-index="${index}"]`
    );
    const seInput = document.querySelector(
      `input[data-header="SE"][data-index="${index}"]`
    );
    const daCell = document.querySelector(
      `td[data-header="DA"][data-index="${index}"]`
    );
    const iaInput = document.querySelector(
      `input[data-header="IA"][data-index="${index}"]`
    );
    const caCell = document.querySelector(
      `td[data-header="CA"][data-index="${index}"]`
    );

    const ciaValue = parseFloat(ciaCell?.textContent) || 0;
    const seValue = parseFloat(seInput?.value) || 0;
    const iaValue = parseFloat(iaInput?.value) || 0;

    const daValue = (0.4 * ciaValue + 0.6 * seValue).toFixed(2);
    daCell.textContent = daValue;

    const caValue = (0.8 * parseFloat(daValue) + 0.2 * iaValue).toFixed(2);
    caCell.textContent = caValue;
  }

  const saveButton = document.getElementById("save-co-attainment");

  saveButton.addEventListener("click", () => {
    const coAttainmentTable = document.getElementById("co-attainment-table");
    const rows = coAttainmentTable.getElementsByTagName("tr");
    const data = [];

    const courseSelect = document.getElementById("co-course-select");
    const fc_id = courseSelect.value;

    // Get CO numbers
    const cos = [];
    for (let i = 1; i < rows[0].cells.length; i++) {
      const coText = rows[0].cells[i].textContent;
      if (coText.startsWith("CO")) {
        const coNumber = coText.replace("CO", "").trim();
        cos.push({ index: i, coNumber });
      } else if (coText === "Action Required") {
        // Stop when we reach non-CO columns
        break;
      }
    }

    // Collect data for CIA, SE, DA, IA, CA
    for (let i = 1; i < rows.length; i++) {
      const rowLabel = rows[i].cells[0].textContent.trim();
      if (["CIA", "SE", "DA", "IA", "CA"].includes(rowLabel)) {
        cos.forEach((co) => {
          let value;
          const cell = rows[i].cells[co.index];

          if (rowLabel === "SE" || rowLabel === "IA") {
            const input = cell.querySelector("input");
            value = input ? parseFloat(input.value) || 0 : 0;
          } else {
            value = parseFloat(cell.textContent.trim()) || 0;
          }

          data.push({
            co_number: co.coNumber,
            category: rowLabel,
            value: value,
          });
        });
      }
    }
    // console.log("Sending Data:", JSON.stringify({
    //     course_id: courseId,
    //     faculty_id: facultyId,
    //     attainment_data: data
    // }));

    // Send data to PHP
    fetch("./CoCalculation/save_co_attainment.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        fc_id: fc_id,
        attainment_data: data,
      }),
    })
      .then((response) => response.json())
      .then((result) => {
        if (result.success) {
          alert("CO Attainment saved successfully!");
        } else {
          alert("Error saving data: " + result.message);
        }
      })
      .catch((error) => console.error("Error:", error));
  });

  function getCurrentDate() {
    const today = new Date();
    const dd = String(today.getDate()).padStart(2, "0");
    const mm = String(today.getMonth() + 1).padStart(2, "0");
    const yyyy = today.getFullYear();
    return `${dd}/${mm}/${yyyy}`;
  }

  const freezeBtn = document.getElementById("freeze-co-attainment");
  const courseSelect = document.getElementById("co-course-select");

  freezeBtn.addEventListener("click", () => {
    const fcId = courseSelect.value;

    if (!fcId) {
      alert("Please select a course.");
      return;
    }

    // Confirm before proceeding
    const confirmation = confirm(
      "Are you sure you want to freeze the marks? This action cannot be undone."
    );
    if (confirmation) {
      // Send the fc_id to the backend
      fetch("freeze_co_attainment.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ fc_id: fcId }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            alert("Marks successfully frozen.");
          } else {
            alert("Failed to freeze marks: " + data.message);
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("An error occurred while freezing marks.");
        });
    }
  });
});
