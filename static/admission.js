window.addEventListener('load', function () {
  // 1. Guest View: Generate Random App ID
  const year = new Date().getFullYear().toString().slice(-2); // Get last 2 digits of year, e.g., 26 for 2026
  const randomNum = Math.floor(1000 + Math.random() * 9000); // 4-digit random number
  const randomId = 'TUPM-' + year + '-' + randomNum;
  const display = document.getElementById('gen-id');
  const input = document.getElementById('input-app-id');

  if (display) display.innerText = randomId;
  if (input) input.value = randomId;

  // 2. Student View: Generate RICH QR Code (Info + ID)
  const name = document.getElementById('student-name');
  const appId = document.getElementById('student-app-id');
  const course = document.getElementById('student-course');
  const examDate = document.getElementById('exam-date');
  const examTime = document.getElementById('exam-time');
  const examVenue = document.getElementById('exam-venue');

  if (name && appId) {
    // Construct the info string
    const qrContent = `TUP EXAM PERMIT\n----------------\nName: ${name.value}\nID: ${appId.value}\nCourse: ${course.value}\nDate: ${examDate.value}\nTime: ${examTime.value}\nVenue: ${examVenue.value}`;

    // Web Display (Ticket in Tab 3)
    const webDisplay = document.getElementById('qr-display-web');
    if (webDisplay) {
      webDisplay.innerHTML = '';
      new QRCode(webDisplay, {
        text: qrContent,
        width: 150,
        height: 150,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M,
      });
    }

    // Print Display (Hidden until printed)
    const printDisplay = document.getElementById('qr-display-print');
    if (printDisplay) {
      printDisplay.innerHTML = '';
      new QRCode(printDisplay, {
        text: qrContent,
        width: 150,
        height: 150,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M,
      });
    }
  }

  const passwordInput = document.getElementById('password-input');
  const confirmPasswordInput = document.getElementById('confirm-password-input');
  const strengthBar = document.getElementById('password-strength-bar');
  const strengthLabel = document.getElementById('password-strength-label');
  const matchLabel = document.getElementById('password-match-label');
  const clientErrorBox = document.getElementById('client-error-box');
  const admissionForm = document.getElementById('admission-form');
  window.eligibilityFilters = window.eligibilityFilters || [];

  const loadEligibilityFilters = () => {
    if (!window.courseOfferings) return;
    // Data is now passed directly from admission.php, so we no longer need fetch()
    filterCoursesByEligibility();
  };

  loadEligibilityFilters();

  const evaluatePasswordStrength = (password) => {
    let score = 0;
    if (password.length >= 8) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score++;
    return score;
  };

  const updatePasswordStrength = () => {
    if (!passwordInput || !strengthBar || !strengthLabel) return;
    const password = passwordInput.value;
    const score = evaluatePasswordStrength(password);
    const percent = Math.min(100, (score / 5) * 100);
    strengthBar.style.width = `${percent}%`;

    if (score <= 2) {
      strengthLabel.textContent = 'Strength: Short';
      strengthBar.style.background = '#ff6b6b';
    } else if (score === 3) {
      strengthLabel.textContent = 'Strength: Medium';
      strengthBar.style.background = '#f1c40f';
    } else {
      strengthLabel.textContent = 'Strength: Strong';
      strengthBar.style.background = '#7ed321';
    }
  };

  const updatePasswordMatch = () => {
    if (!passwordInput || !confirmPasswordInput || !matchLabel) return;
    const password = passwordInput.value;
    const confirm = confirmPasswordInput.value;

    if (confirm === '') {
      matchLabel.textContent = 'Please confirm your password.';
      matchLabel.classList.remove('valid');
      return;
    }

    if (password === confirm) {
      matchLabel.textContent = 'Passwords match.';
      matchLabel.classList.add('valid');
    } else {
      matchLabel.textContent = 'Passwords do not match.';
      matchLabel.classList.remove('valid');
    }
  };

  const showClientError = (message) => {
    if (!clientErrorBox) return;
    clientErrorBox.textContent = message;
    clientErrorBox.style.display = 'block';
  };

  const hideClientError = () => {
    if (!clientErrorBox) return;
    clientErrorBox.style.display = 'none';
  };

  if (passwordInput) {
    passwordInput.addEventListener('input', () => {
      updatePasswordStrength();
      updatePasswordMatch();
      hideClientError();
    });
  }

  if (confirmPasswordInput) {
    confirmPasswordInput.addEventListener('input', () => {
      updatePasswordMatch();
      hideClientError();
    });
  }

  if (admissionForm) {
    admissionForm.addEventListener('submit', function (event) {
      hideClientError();
      const password = passwordInput?.value || '';
      const confirm = confirmPasswordInput?.value || '';
      const score = evaluatePasswordStrength(password);
      const invalidReasons = [];

      if (password !== confirm) {
        invalidReasons.push('Passwords do not match.');
      }
      if (score < 5) {
        invalidReasons.push('Password must have at least 8 characters and include uppercase, lowercase, number, and special character.');
      }

      // Check required fields
      const requiredFields = admissionForm.querySelectorAll('input[required], select[required], textarea[required]');
      let hasErrors = false;
      requiredFields.forEach(field => {
        const formGroup = field.closest('.form-group');
        if (!formGroup) return;
        // Remove previous error
        formGroup.classList.remove('field-error');
        const existingMsg = formGroup.querySelector('.error-message');
        if (existingMsg) existingMsg.remove();

        if (field.value.trim() === '') {
          hasErrors = true;
          formGroup.classList.add('field-error');
          const errorMsg = document.createElement('span');
          errorMsg.className = 'error-message';
          errorMsg.textContent = 'This is a required question';
          field.parentNode.insertBefore(errorMsg, field.nextSibling);
        }
      });

      if (hasErrors) {
        invalidReasons.push('Please fill in all required fields.');
      }

      if (invalidReasons.length > 0) {
        event.preventDefault();
        showClientError(invalidReasons.join(' '));
      }
    });
  }

  // Remove field errors on input
  if (admissionForm) {
    const allFields = admissionForm.querySelectorAll('input, select, textarea');
    allFields.forEach(field => {
      field.addEventListener('input', function() {
        const formGroup = field.closest('.form-group');
        if (formGroup && formGroup.classList.contains('field-error')) {
          formGroup.classList.remove('field-error');
          const existingMsg = formGroup.querySelector('.error-message');
          if (existingMsg) existingMsg.remove();
        }
        hideClientError();
      });
    });
  }

  window.togglePasswordVisibility = function (inputId, button) {
    const input = document.getElementById(inputId);
    if (!input || !button) return;
    const icon = button.querySelector('i');
    if (input.type === 'password') {
      input.type = 'text';
      if (icon) {
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      }
    } else {
      input.type = 'password';
      if (icon) {
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }
  };
});

// --- Tab Switching Logic ---
function switchStep(stepNum) {
  const steps = document.querySelectorAll('.modern-tab');
  const clickedStep = steps[stepNum - 1];

  if (!clickedStep.classList.contains('locked')) {
    steps.forEach(el => el.classList.remove('active'));
    clickedStep.classList.add('active');

    document
      .querySelectorAll('.step-pane')
      .forEach(el => el.classList.remove('active'));
    document.getElementById('step-' + stepNum).classList.add('active');
  }
}

function updateCourseOptions() {
  const select1 = document.getElementById('course_1');
  const select2 = document.getElementById('course_2');
  const select3 = document.getElementById('course_3');

  if (!select1 || !select2 || !select3) return;

  const val1 = select1.value;
  const val2 = select2.value;
  const val3 = select3.value;

  const disableOptions = (targetSelect, ...excludedValues) => {
    const options = targetSelect.querySelectorAll('option');
    options.forEach(option => {
      if (option.value === '') return;

      if (excludedValues.includes(option.value)) {
        option.disabled = true;
        option.style.color = '#ccc';
      } else {
        option.disabled = false;
        option.style.color = '';
      }
    });
  };

  disableOptions(select2, val1, val3);

  disableOptions(select3, val1, val2);

  disableOptions(select1, val2, val3);
}

function toggleScheduleForm() {
  const form = document.getElementById('scheduleForm');
  if (form.style.display === 'none') {
    form.style.display = 'block';
  } else {
    form.style.display = 'none';
  }
}

function filterCoursesByEligibility() {
  const strandInput = document.querySelector('select[name="strand"]');
  const gwaInput = document.querySelector('input[name="final_gwa"]');
  const courseSelects = [
    document.getElementById('course_1'),
    document.getElementById('course_2'),
    document.getElementById('course_3')
  ].filter(Boolean);

  if (!strandInput || !gwaInput || courseSelects.length === 0 || !window.courseOfferings) return;

  const strand = strandInput.value;
  const gwa = parseFloat(gwaInput.value);
  const isFilterActive = strand && !isNaN(gwa);

  let eligibleColleges = Object.keys(window.courseOfferings);
  if (isFilterActive) {
eligibleColleges = window.eligibilityFilters
      .filter(filter => {
        const minGwa = parseFloat(filter.min_gwa);
        const allowedStrands = filter.allowed_strands.split(',').map(s => s.trim());
        return gwa >= minGwa && allowedStrands.includes(strand);
      })
      .map(filter => filter.college_name);
  }

  courseSelects.forEach(select => {
    const currentValue = select.value;
    const placeholderText = select.id === 'course_1'
      ? '-- Select Priority Course --'
      : select.id === 'course_2'
        ? '-- Select 2nd Option --'
        : '-- Select 3rd Option --';

    select.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = placeholderText;
    placeholder.disabled = true;
    placeholder.selected = true;
    select.appendChild(placeholder);

    if (isFilterActive && eligibleColleges.length === 0) {
      const noOption = document.createElement('option');
      noOption.value = '';
      noOption.textContent = 'No eligible programs available for selected GWA/strand';
      noOption.disabled = true;
      select.appendChild(noOption);
      select.value = '';
      return;
    }

    eligibleColleges.forEach(college => {
      const programs = window.courseOfferings[college] || [];
      if (programs.length === 0) return;

      const group = document.createElement('optgroup');
      group.label = college;

      programs.forEach(program => {
        const option = document.createElement('option');
        option.value = program;
        option.textContent = program;
        if (program === currentValue) {
          option.selected = true;
        }
        group.appendChild(option);
      });

      select.appendChild(group);
    });

    if (!select.querySelector('option[selected]')) {
      select.value = '';
    }
  });

  updateCourseOptions();
}
