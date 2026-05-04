function changeQuantity(delta) {
  const quantityInput = document.getElementById('reqQuantity');
  let value = parseInt(quantityInput.value, 10) || 1;
  value = Math.max(1, value + delta);
  quantityInput.value = value;
}

function toggleCertInput() {
  const documentSelect = document.getElementById('reqDocument');
  const certType = document.getElementById('certType');
  const otherType = document.getElementById('otherType');

  if (documentSelect.value === 'Certification') {
    certType.style.display = 'block';
    certType.required = true;
    otherType.style.display = 'none';
    otherType.required = false;
    otherType.value = '';
  } else if (documentSelect.value === 'Others') {
    otherType.style.display = 'block';
    otherType.required = true;
    certType.style.display = 'none';
    certType.required = false;
    certType.value = '';
  } else {
    certType.style.display = 'none';
    certType.required = false;
    certType.value = '';
    otherType.style.display = 'none';
    otherType.required = false;
    otherType.value = '';
  }
}

function openRequestFormPopup() {
  document.getElementById('requestFormPopup').style.display = 'flex';
}

function closeRequestFormPopup() {
  document.getElementById('requestFormPopup').style.display = 'none';
}

async function submitRegistrarRequest() {
  const name = document.getElementById('reqName').value.trim();
  const tupId = document.getElementById('reqTupId').value.trim();
  const course = document.getElementById('reqCourse').value.trim();
  const email = document.getElementById('reqEmail').value.trim();
  const contact = document.getElementById('reqContact').value.trim();
  const documentType = document.getElementById('reqDocument').value;
  const certExtra = document.getElementById('certType').value.trim();
  const otherExtra = document.getElementById('otherType').value.trim();
  const quantity =
    parseInt(document.getElementById('reqQuantity').value, 10) || 1;

  if (!name || !tupId || !course || !email || !contact || !documentType) {
    alert('Please complete all required fields.');
    return;
  }

  let finalDocument = documentType;
  if (documentType === 'Certification' && certExtra !== '') {
    finalDocument = `Certification of ${certExtra}`;
  } else if (documentType === 'Others' && otherExtra !== '') {
    finalDocument = `Other Document: ${otherExtra}`;
  }
  if (quantity > 1) {
    finalDocument = `${finalDocument} x${quantity}`;
  }

  const formData = new FormData();
  formData.append('action', 'submit_request');
  formData.append('student_name', name);
  formData.append('tup_id', tupId);
  formData.append('course', course);
  formData.append('email', email);
  formData.append('contact', contact);
  formData.append('document', finalDocument);
  formData.append('quantity', quantity);

  try {
    const response = await fetch('registrar.php', {
      method: 'POST',
      body: formData,
    });

    const text = await response.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch (parseError) {
      console.error('Non-JSON response:', text);
      alert('Server error: invalid response from registrar endpoint.');
      return;
    }

    if (!response.ok || !data.success) {
      console.error('Registrar request failed:', response.status, data);
      alert(data.message || 'Unable to submit request.');
      return;
    }

    data.request.quantity = quantity;
    showSummary(data.request);
    document.getElementById('requestForm').reset();
    document.getElementById('reqQuantity').value = '1';
    toggleCertInput();
    closeRequestFormPopup();
  } catch (error) {
    console.error(error);
    alert('An error occurred while sending the request.');
  }
}

function showSummary(request) {
  document.getElementById('summaryRequestId').textContent = request.request_id;
  document.getElementById('summaryName').textContent = request.student_name;
  document.getElementById('summaryTupId').textContent = request.tup_id;
  document.getElementById('summaryCourse').textContent = request.course;
  document.getElementById('summaryEmail').textContent = request.email;
  document.getElementById('summaryContact').textContent = request.contact;
  document.getElementById('summaryDocument').textContent = request.document;
  const quantity = parseInt(request.quantity, 10) || 1;
  document.getElementById('summaryQuantity').textContent = quantity;

  const baseDocument = getDocumentBaseType(request.document);
  const unitPrice = getDocumentUnitPrice(baseDocument);
  const totalPrice = unitPrice * quantity;

  document.getElementById('summaryUnitPrice').textContent =
    formatCurrency(unitPrice);
  document.getElementById('summaryTotalPrice').textContent =
    formatCurrency(totalPrice);
  document.getElementById('summaryDate').textContent = request.date_requested;

  const qrContent = `Request ID: ${request.request_id}\nName: ${request.student_name}\nTUP ID: ${request.tup_id}\nCourse: ${request.course}\nDocument: ${request.document}\nDate: ${request.date_requested}`;
  const qrContainer = document.getElementById('summaryQr');
  qrContainer.innerHTML = '';

  if (typeof QRCode !== 'undefined') {
    new QRCode(qrContainer, {
      text: qrContent,
      width: 250,
      height: 250,
      colorDark: '#111827',
      colorLight: '#f8fafc',
      correctLevel: QRCode.CorrectLevel.H,
    });
  } else {
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(qrContent)}`;
    const fallbackImg = document.createElement('img');
    fallbackImg.src = qrUrl;
    fallbackImg.alt = 'QR code';
    fallbackImg.style.maxWidth = '250px';
    fallbackImg.style.borderRadius = '16px';
    qrContainer.appendChild(fallbackImg);
  }

  document.getElementById('requestSummaryPopup').style.display = 'flex';
}

function getDocumentBaseType(documentString) {
  let doc = documentString.replace(/\s+x\d+$/, '');
  if (doc.startsWith('Certification of ')) {
    return 'Certification';
  }
  if (doc.startsWith('Other Document:')) {
    return 'Others';
  }
  return doc;
}

function getDocumentUnitPrice(documentType) {
  if (!window.registrarPricing) {
    return 0;
  }
  return parseFloat(window.registrarPricing[documentType] || 0);
}

function formatCurrency(amount) {
  return (
    '₱' +
    amount.toLocaleString('en-PH', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  );
}

function closeSummaryPopup() {
  document.getElementById('requestSummaryPopup').style.display = 'none';
}

function filterRequestTable() {
  const searchTerm = document
    .getElementById('requestSearch')
    .value.toLowerCase();
  const statusValue = document.getElementById('statusFilter').value;
  const documentValue = document.getElementById('documentFilter').value;
  const rows = document.querySelectorAll('.table-section tbody tr');

  rows.forEach(row => {
    const cells = row.querySelectorAll('td');
    if (cells.length === 0) {
      return;
    }

    const requestId = cells[0].textContent.trim().toLowerCase();
    const studentName = cells[1].textContent.trim().toLowerCase();
    const documentText = cells[2].textContent.trim().toLowerCase();
    const statusText = cells[3].textContent.trim().toLowerCase();

    const searchMatch =
      searchTerm === '' ||
      requestId.includes(searchTerm) ||
      studentName.includes(searchTerm) ||
      documentText.includes(searchTerm) ||
      statusText.includes(searchTerm);

    const statusMatch =
      statusValue === '' || statusText === statusValue.toLowerCase();

    const documentMatch =
      documentValue === '' || documentText === documentValue.toLowerCase();

    row.style.display =
      searchMatch && statusMatch && documentMatch ? '' : 'none';
  });
}

function clearRequestFilters() {
  document.getElementById('requestSearch').value = '';
  document.getElementById('statusFilter').value = '';
  document.getElementById('documentFilter').value = '';
  filterRequestTable();
}

window.addEventListener('DOMContentLoaded', () => {
  toggleCertInput();

  const requestSearch = document.getElementById('requestSearch');
  const statusFilter = document.getElementById('statusFilter');
  const documentFilter = document.getElementById('documentFilter');

  if (requestSearch) {
    requestSearch.addEventListener('input', filterRequestTable);
  }
  if (statusFilter) {
    statusFilter.addEventListener('change', filterRequestTable);
  }
  if (documentFilter) {
    documentFilter.addEventListener('change', filterRequestTable);
  }

  document.querySelectorAll('.close-btn').forEach(btn => {
    btn.addEventListener('click', event => {
      event.stopPropagation();
      const popup = event.target.closest('.popup-overlay');
      if (!popup) return;
      if (popup.id === 'requestFormPopup') {
        closeRequestFormPopup();
      } else if (popup.id === 'requestSummaryPopup') {
        closeSummaryPopup();
      }
    });
  });
});
