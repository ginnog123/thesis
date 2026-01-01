let currentTabDoc = '';

function openSubmitPopup() {
  console.log('openSubmitPopup called');
  const popup = document.getElementById('submitPopup');
  console.log('submitPopup element:', popup);
  if (popup) {
    popup.style.display = 'flex';
  }
}

function closeSubmitPopup() {
  const popup = document.getElementById('submitPopup');
  if (popup) {
    popup.style.display = 'none';
  }
}

$(document).ready(function () {
  if (typeof $ === 'undefined') {
    console.error('jQuery not loaded');
    return;
  }
  loadRequests();
  loadDocumentTypes();

  // LIVE SEARCH (instant)
  $('#searchInput').on('keyup', function () {
    loadRequests();
  });

  // STATUS & DATE — instant
  $('#statusInput').on('change', function () {
    loadRequests();
  });

  $('#dateInput').on('change', function () {
    loadRequests();
  });
});

// TAB CLICK
function filterByTab(docName, btn) {
  currentTabDoc = docName;

  $('.tab-btn').removeClass('active');
  $(btn).addClass('active');

  loadRequests();
}

// AJAX CALL
function loadRequests() {
  $.ajax({
    url: 'registrar.php',
    type: 'POST',
    data: {
      action: 'load_requests',
      docType: currentTabDoc,
      search: $('#searchInput').val().trim(),
      status: $('#statusInput').val(),
      date: $('#dateInput').val(),
    },
    success: function (data) {
      $('#table-container').html(data);
    },
  });
}

// CLEAR FILTERS
function clearFilters() {
  $('#searchInput').val('');
  $('#statusInput').val('');
  $('#dateInput').val('');

  currentTabDoc = '';

  $('.tab-btn').removeClass('active');
  $('.tab-btn').first().addClass('active');

  loadRequests();
}
// ================= REQUEST SUBMISSION =================

function submitRequest() {
  let student_name = $('#studentName').val().trim();
  let document_name = $('#documentName').val();

  if (!student_name || !document_name) {
    alert('Please fill out all fields.');
    return;
  }

  $.post(
    'registrar.php',
    {
      action: 'submit_request',
      student_name: student_name,
      document: document_name,
    },
    function (response) {
      alert(response);
      if (response.toLowerCase().includes('success')) {
        closeRequestPopup();
        $('#studentName').val('');
        loadRequests();
      }
    }
  );
}

function showRequestPopup() {
  if ($('#documentName option').length <= 1) {
    alert('Please add document types first.');
    return;
  }
  $('#addRequestPopup').fadeIn();
}

function closeRequestPopup() {
  $('#addRequestPopup').fadeOut();
}

function toggleCertInput() {
  if ($('#reqDocument').val() === 'Certification') {
    $('#certType').show().attr('required', true);
  } else {
    $('#certType').hide().val('').removeAttr('required');
  }
}

function submitRegistrarRequest() {
  let documentType = $('#reqDocument').val();
  let certExtra = $('#certType').val().trim();

  if (documentType === 'Certification' && certExtra !== '') {
    documentType = 'Certification of ' + certExtra;
  }

  $.post(
    'registrar.php',
    {
      action: 'submit_request',
      student_name: $('#reqName').val(),
      tup_id: $('#reqTupId').val(),
      course: $('#reqCourse').val(),
      email: $('#reqEmail').val(),
      contact: $('#reqContact').val(),
      document: documentType,
    },
    function (response) {
      alert(response);
      if (response.toLowerCase().includes('success')) {
        closeSubmitPopup();
        loadRequests();
      }
    }
  );
}
