let currentTabDoc = ""; 

$(document).ready(function() {
    loadRequests();
    loadDocumentTypes();

    $('#searchInput').on('input', loadRequests);
    $('#statusInput').on('change', loadRequests);
    $('#dateInput').on('change', loadRequests);
});

function filterByTab(docName, btnElement) {
    currentTabDoc = docName;
    
    $('.tab-btn').removeClass('active');
    $(btnElement).addClass('active');

    if (docName === "") {
        $('#admin-actions').fadeIn();
    } else {
        $('#admin-actions').fadeOut();
    }

    loadRequests();
}

function loadRequests() {
    let search = $('#searchInput').val();
    let status = $('#statusInput').val();
    let date = $('#dateInput').val();

    $("#table-container").html("<p style='text-align:center; color:white;'>Loading...</p>");

    $.post("registrar.php", { 
        action: "load_requests",
        docType: currentTabDoc, 
        search: search,
        status: status,
        date: date
    }, function(data) {
        $("#table-container").html(data);
    });
}

function clearFilters() {
    $('#searchInput').val('');
    $('#statusInput').val('');
    $('#dateInput').val('');
    loadRequests();
}

function loadDocumentTypes() {
    $.post("registrar.php", { action: "load_document_types" }, function(data) {
        const response = JSON.parse(data);
        $("#documentName").html(response.options); 
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
            loadRequests(); 
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
            closeManageDocsPopup();
            loadDocumentTypes();
        }
    });
}

function showUpdateStatusPopup(id, currentStatus) {
    $("#updateRequestID").val(id);
    $("#statusSelect").val(currentStatus);
    $("#updateStatusPopup").fadeIn();
}

function closeUpdateStatusPopup() {
    $("#updateStatusPopup").fadeOut();
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
        closeUpdateStatusPopup();
        loadRequests();
    });
}

function showRequestPopup() {
    if ($("#documentName option").length <= 1) {
        alert("Please add document types first.");
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
