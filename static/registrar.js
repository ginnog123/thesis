// --- Page Load ---
$(document).ready(function() {
    // Load initial data
    loadRequests();
    loadDocumentTypes();

    // --- Event Listeners for Filters ---
    
    // Use 'input' for instant search as user types
    $('#searchInput').on('input', function() {
        loadRequests();
    });

    // Use 'change' for dropdowns and date picker
    $('#statusFilter, #docTypeFilter, #dateFilter').on('change', function() {
        loadRequests();
    });

    // Clear filters button
    $('#clearFiltersBtn').on('click', function() {
        $('#searchInput').val('');
        $('#statusFilter').val('');
        $('#docTypeFilter').val('');
        $('#dateFilter').val('');
        loadRequests(); // Reload with all filters cleared
    });
});


// --- AJAX Functions ---

function loadRequests() {
    // Get filter values
    let search = $('#searchInput').val();
    let status = $('#statusFilter').val();
    let docType = $('#docTypeFilter').val();
    let date = $('#dateFilter').val();

    // Show a loading indicator (optional but good UX)
    $("#request-table").html("<tr><td colspan='6'>Loading...</td></tr>");

    $.post("registrar.php", { 
        action: "load_requests",
        search: search,
        status: status,
        docType: docType,
        date: date
    }, function(data) {
        $("#request-table").html(data);
    });
}

function loadDocumentTypes() {
    $.post("registrar.php", { action: "load_document_types" }, function(data) {
        const response = JSON.parse(data);
        // Populate request form dropdown
        $("#documentName").html(response.options); 
        // Populate manage types list
        $("#doc-type-list").html(response.list);   
        // Populate FILTER dropdown
        $("#docTypeFilter").html(response.filterOptions); 
    });
}

function submitRequest() {
    let student_name = $("#studentName").val().trim();
    let document_name = $("#documentName").val();

    if (!student_name || !document_name) {
        return alert("Please fill out all fields.");
    }

    $.post("registrar.php", {
        action: "submit_request",
        student_name: student_name,
        document: document_name
    }, function(response) {
        alert(response);
        if (response.includes("successfully")) {
            closeRequestPopup();
            $("#studentName").val("");
            loadRequests(); // Refresh table
        }
    });
}

function addDocumentType() {
    let name = $("#docTypeName").val().trim();
    if (!name) {
        return alert("Please enter a document name!");
    }

    $.post("registrar.php", {
        action: "add_document_type",
        name: name
    }, function(response) {
        alert(response);
        if (response.includes("successfully")) {
            $("#docTypeName").val("");
            loadDocumentTypes(); // Refresh all doc type lists
        }
    });
}

function deleteDocumentType(id) {
    if (!confirm("Are you sure you want to delete this document type?")) {
        return;
    }
    
    $.post("registrar.php", { action: "delete_document_type", id: id }, function(response) {
        alert(response);
        loadDocumentTypes(); // Refresh all doc type lists
    });
}

function saveStatus() {
    let id = $("#updateRequestID").val();
    let status = $("#statusSelect").val();

    $.post("registrar.php", { 
        action: "update_status", 
        id: id, 
        status: status 
    }, function(response) {
        alert(response);
        if (response.includes("successfully")) {
            closeUpdateStatusPopup();
            loadRequests(); // Refresh table
        }
    });
}


// --- Popup Control Functions ---

function showRequestPopup() {
    if ($("#documentName option").length <= 1) {
        alert("Please add document types using the 'Manage Document Types' button before submitting a new request.");
        return;
    }
    $("#addRequestPopup").fadeIn();
}
function closeRequestPopup() {
    $("#addRequestPopup").fadeOut();
}

function showManageDocsPopup() {
    $("#manageDocsPopup").fadeIn();
}
function closeManageDocsPopup() {
    $("#manageDocsPopup").fadeOut();
}

function showUpdateStatusPopup(id, currentStatus) {
    $("#updateRequestID").val(id);
    $("#statusSelect").val(currentStatus);
    $("#updateStatusPopup").fadeIn();
}
function closeUpdateStatusPopup() {
    $("#updateStatusPopup").fadeOut();
}