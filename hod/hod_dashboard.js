document.addEventListener("DOMContentLoaded", () => {
  window.addEventListener("error", (event) => {
    console.error("Unhandled Error:", {
      message: event.message,
      filename: event.filename,
      lineno: event.lineno,
      colno: event.colno,
      error: event.error,
    });
  });

  const switchSection = (sectionId) => {
    document
      .querySelectorAll(".section, .sidebar-menu ul li")
      .forEach((el) =>
        el.classList.remove("active", "section-enter", "section-exit")
      );

    const targetSection = document.getElementById(sectionId);
    const targetMenuItem = document.querySelector(
      `[data-section="${sectionId}"]`
    );

    if (targetSection && targetMenuItem) {
      targetSection.classList.add("active", "section-enter");
      targetMenuItem.classList.add("active");

      setTimeout(() => targetSection.classList.remove("section-enter"), 300);
    }
  };

  document.querySelectorAll(".sidebar-menu ul li").forEach((item) => {
    item.addEventListener("click", (e) => {
      const sectionId = e.currentTarget.getAttribute("data-section");
      if (sectionId) switchSection(sectionId);
    });
  });

  document.getElementById('complete-detail-btn').addEventListener('click', () => {
    const fcId = document.getElementById('report-course-select').value;

    if (!fcId) {
        alert('Please select a course.');
        return;
    }

    const reportUrl = `../Faculty/ReportGeneration/complete_detail_report.php?fc_id=${fcId}`;
    window.open(reportUrl, '_blank');
  });

  document.getElementById('overall-marklist-btn').addEventListener('click', () => {
    const fcId = document.getElementById('report-course-select').value;

    if (!fcId) {
        alert('Please select a course.');
        return;
    }

    const reportUrl = `../Faculty/ReportGeneration/overall_report.php?fc_id=${fcId}`;
    window.open(reportUrl, '_blank');
  });

});
