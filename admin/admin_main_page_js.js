document.addEventListener('DOMContentLoaded',()=>{

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

    document.getElementById('export-co-attainment').addEventListener('click', () => {
        const courseSelect = document.getElementById('co-course-select');
        const fcId = courseSelect.value;

        const data = {
            fc_id: fcId
        };

        fetch('../Faculty/ReportGeneration/generate_co_attainment_pdf.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        }).then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.blob();
        }).then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'co_attainment_report.pdf';
            document.body.appendChild(a);
            a.click();
            a.remove();
        }).catch(error => {
            console.error('Error:', error);
            alert('Failed to generate PDF: ' + error.message);
        });
    });

});