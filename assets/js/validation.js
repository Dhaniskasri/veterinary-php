/**
 * Veterinary Care - Form Validation and Dynamic Prescription Table Helpers
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Bootstrap standard form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // 2. Dynamic Prescription Rows on Medical Record Consultation Form
    const prescriptionTableBody = document.getElementById('prescriptionTableBody');
    const addPrescriptionRowBtn = document.getElementById('addPrescriptionRowBtn');

    if (prescriptionTableBody && addPrescriptionRowBtn) {
        addPrescriptionRowBtn.addEventListener('click', function () {
            const rowIndex = prescriptionTableBody.querySelectorAll('tr').length;
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <input type="text" name="prescriptions[${rowIndex}][medicine_name]" class="form-control form-control-sm" placeholder="e.g. Amoxicillin 250mg" required>
                </td>
                <td>
                    <input type="text" name="prescriptions[${rowIndex}][dosage]" class="form-control form-control-sm" placeholder="e.g. 1 tab" required>
                </td>
                <td>
                    <input type="text" name="prescriptions[${rowIndex}][frequency]" class="form-control form-control-sm" placeholder="e.g. BID (twice daily)" required>
                </td>
                <td>
                    <input type="text" name="prescriptions[${rowIndex}][duration]" class="form-control form-control-sm" placeholder="e.g. 7 days" required>
                </td>
                <td>
                    <input type="text" name="prescriptions[${rowIndex}][instructions]" class="form-control form-control-sm" placeholder="e.g. Give with meals">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-rx-row-btn" title="Remove row">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            prescriptionTableBody.appendChild(newRow);
        });

        // Event delegation for removing rows
        prescriptionTableBody.addEventListener('click', function (e) {
            const removeBtn = e.target.closest('.remove-rx-row-btn');
            if (removeBtn) {
                const rows = prescriptionTableBody.querySelectorAll('tr');
                if (rows.length > 1) {
                    removeBtn.closest('tr').remove();
                } else {
                    alert('At least one prescription row is required, or clear the inputs if no medication is prescribed.');
                }
            }
        });
    }

    // 3. Animal DOB to Age auto-calculator
    const dobInput = document.getElementById('date_of_birth');
    const ageInput = document.getElementById('age_years');

    if (dobInput && ageInput) {
        dobInput.addEventListener('change', function () {
            if (this.value) {
                const birthDate = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                if (age >= 0) {
                    ageInput.value = age;
                }
            }
        });
    }
});
