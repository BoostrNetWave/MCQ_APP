// Admin Log in
$(document).on("submit", "#adminLoginFrm", function () {
  $.post("query/loginExe.php", $(this).serialize(), function (data) {
    if (data.res == "invalid") {
      Swal.fire(
        'Invalid',
        'Please input valid username / password',
        'error'
      )
    }
    else if (data.res == "success") {
      $('body').fadeOut();
      window.location.href = 'home.php';
    }
  }, 'json');

  return false;
});

// Delete Exam
$(document).on("click", "#deleteExam", function (e) {
  e.preventDefault();
  var id = $(this).data("id");
  $.ajax({
    type: "post",
    url: "query/deleteExamExe.php",
    dataType: "json",
    data: { id: id },
    cache: false,
    success: function (data) {
      if (data.res == "success") {
        Swal.fire(
          'Success',
          'Selected Course successfully deleted',
          'success'
        )
        refreshDiv();
      }
    },
    error: function (xhr, ErrorStatus, error) {
      console.log(status.error);
    }

  });



  return false;
});



// Add Exam 
$(document).on("submit", "#addExamFrm", function () {
  $.post("query/addExamExe.php", $(this).serialize(), function (data) {
    if (data.res == "exist") {
      Swal.fire(
        'Already Exist',
        data.examTitle.toUpperCase() + '<br>Already Exist',
        'error'
      )
    }
    else if (data.res == "success") {
      Swal.fire(
        'Success',
        data.examTitle.toUpperCase() + '<br>Successfully Added',
        'success'
      )
      $('#addExamFrm')[0].reset();
      $('#course_name').val("");
      refreshDiv();
    }
  }, 'json')
  return false;
});



// Update Exam 
$(document).on("submit", "#updateExamFrm", function () {
  $.post("query/updateExamExe.php", $(this).serialize(), function (data) {
    if (data.res == "success") {
      Swal.fire(
        'Update Successfully',
        data.msg + ' <br>are now successfully updated',
        'success'
      )
      refreshDiv();
    }
    else if (data.res == "failed") {
      Swal.fire(
        "Something's went wrong!",
        'Somethings went wrong',
        'error'
      )
    }

  }, 'json')
  return false;
});

// Update Question
$(document).on("submit", "#updateQuestionFrm", function () {
  $.post("query/updateQuestionExe.php", $(this).serialize(), function (data) {
    if (data.res == "success") {
      Swal.fire(
        'Success',
        'Selected question has been successfully updated!',
        'success'
      )
      refreshDiv();
    }
  }, 'json')
  return false;
});


// Delete Question
$(document).on("click", "#deleteQuestion", function (e) {
  e.preventDefault();
  var id = $(this).data("id");
  $.ajax({
    type: "post",
    url: "query/deleteQuestionExe.php",
    dataType: "json",
    data: { id: id },
    cache: false,
    success: function (data) {
      if (data.res == "success") {
        Swal.fire(
          'Deleted Success',
          'Selected question successfully deleted',
          'success'
        )
        refreshDiv();
      }
    },
    error: function (xhr, ErrorStatus, error) {
      console.log(status.error);
    }

  });



  return false;
});


// Add Question 
$(document).on("submit", "#addQuestionFrm", function () {
  $.post("query/addQuestionExe.php", $(this).serialize(), function (data) {
    if (data.res == "exist") {
      Swal.fire(
        'Already Exist',
        data.msg + ' question <br>already exist in this exam',
        'error'
      )
    }
    else if (data.res == "success") {
      Swal.fire(
        'Success',
        data.msg + ' question <br>Successfully added',
        'success'
      )
      $('#addQuestionFrm')[0].reset();
      refreshDiv();
    }

  }, 'json')
  return false;
});


// Add Examinee
$(document).on("submit", "#addExamineeFrm", function () {
  $.post("query/addExamineeExe.php", $(this).serialize(), function (data) {
    if (data.res == "noGender") {
      Swal.fire(
        'No Gender',
        'Please select gender',
        'error'
      )
    }
    else if (data.res == "noCourse") {
      Swal.fire(
        'No Course',
        'Please select course',
        'error'
      )
    }
    else if (data.res == "noLevel") {
      Swal.fire(
        'No Year Level',
        'Please select year level',
        'error'
      )
    }
    else if (data.res == "fullnameExist") {
      Swal.fire(
        'Fullname Already Exist',
        data.msg + ' are already exist',
        'error'
      )
    }
    else if (data.res == "emailExist") {
      Swal.fire(
        'Email Already Exist',
        data.msg + ' are already exist',
        'error'
      )
    }
    else if (data.res == "success") {
      Swal.fire(
        'Success',
        data.msg + ' are now successfully added',
        'success'
      )
      refreshDiv();
      $('#addExamineeFrm')[0].reset();
    }
    else if (data.res == "failed") {
      Swal.fire(
        "Something's Went Wrong",
        '',
        'error'
      )
    }



  }, 'json')
  return false;
});



// Update Examinee
$(document).on("submit", "#updateExamineeFrm", function () {
  $.post("query/updateExamineeExe.php", $(this).serialize(), function (data) {
    if (data.res == "success") {
      Swal.fire(
        'Success',
        data.exFullname + ' <br>has been successfully updated!',
        'success'
      )
      refreshDiv();
    }
  }, 'json')
  return false;
});


function refreshDiv() {
  $('#tableList').load(document.URL + ' #tableList');
  $('#refreshData').load(document.URL + ' #refreshData');

}


//excel work
$(document).ready(function () {
  $('#preview').click(function (e) {
    e.preventDefault();
        
    var fileInput = $('#spreedsheetfile')[0];
    
    // Check if file input is empty
    if (fileInput.files.length === 0) {
        $('#validationMessages').text("Please select a file.");
        return;
    }
    var formData = new FormData($('#uploadForm')[0]);

    $.ajax({
      url: 'query/excelValidation.php?action=validate',
      type: 'POST',
      data: formData,
      contentType: false,
      processData: false,
      success: function(response) {
        try {
            var jsonResponse = JSON.parse(response);
            console.log(jsonResponse);
    
            var message = jsonResponse.message ? jsonResponse.message + "\n" : "";
            var errorsMessage = jsonResponse.errors ? "\n" : "";
            var emptyCellsMessage = jsonResponse.emptyCells ? "Empty Cells:\n" : "";
    
            if (jsonResponse.errors) {
                if (typeof jsonResponse.errors === "string") {
                    errorsMessage += jsonResponse.errors + "\n"; // If error is a string
                } else if (Array.isArray(jsonResponse.errors)) {
                    jsonResponse.errors.forEach(function(error) {
                        errorsMessage += "- " + error + "\n"; // If error is an array
                    });
                }
            }
    
            if (jsonResponse.emptyCells) {
                jsonResponse.emptyCells.forEach(function(emptyCell) {
                    emptyCellsMessage += "- " + emptyCell + "\n";
                });
            }
    
            message += errorsMessage + emptyCellsMessage;
    
            $('#validationMessages').text(message);
    
            if (!jsonResponse.errors && !jsonResponse.emptyCells && jsonResponse.tableHTML) {
                $('#excelTable').html(jsonResponse.tableHTML);
            }
        } catch (error) {
            console.error("Error parsing JSON response:", error);
            $('#validationMessages').text("Error: Invalid JSON response from server.");
        }
    }
    ,
    
      error: function(jqXHR, textStatus, errorThrown) {
          console.error("AJAX Error:", textStatus, errorThrown);
          var errorMessage = "AJAX Error: ";
          if (jqXHR.responseJSON && jqXHR.responseJSON.error) {
              errorMessage += jqXHR.responseJSON.error;
          } else {
              errorMessage += textStatus + " " + errorThrown;
          }
          $('#validationMessages').text(errorMessage);
      }
  });
  
    return false; // Prevent default form submission behavior
  });

  $('#spreedsheetfile').focus(function () {
    $('#validationMessages').empty();
    $('#excelTable').empty();
  });

  $(document).on('click', '#uploadButton', function (e) {
    var file = $('#spreedsheetfile')[0].files[0];
    console.log("Selected file:", file);

    // Perform validation
    var validationResult = validateFile(file);
    console.log("Validation result:", validationResult);

    if (validationResult === "File is valid.") {
      var formData = new FormData($('#uploadForm')[0]);
      console.log(formData);
      $.ajax({
        url: 'query/excelValidation.php?action=excelupload',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          try {
            var jsonResponse = JSON.parse(response);
            console.log(jsonResponse);
            var message = jsonResponse.message ? jsonResponse.message : "";
            $('#validationMessages').text(message);
            // $('#spreedModal').modal('hide');
            window.location.href = window.location.pathname + window.location.search;
          } catch (error) {
            console.error("Error parsing JSON response:", error);
            $('#validationMessages').text("Error: Invalid JSON response from server.");
          }
        },
        error: function (jqXHR, textStatus, error) {
          console.error("AJAX Error:", error);
          var errorMessage = "AJAX Error: ";
          if (jqXHR.responseJSON && jqXHR.responseJSON.error) {
            errorMessage += jqXHR.responseJSON.error;
          } else {
            errorMessage += textStatus + " " + error;
          }
          $('#validationMessages').text(errorMessage);
        }
      });
    } else {
      $('#validationMessages').text("File is not valid.");
    }
  });
});



function validateFile(file) {
  // Implement file validation logic here
  // Example validation: Check file size, file type, etc.
  if (file.size > 1024 * 1024) {
    return "File size exceeds the maximum allowed size.";
  }
  return "File is valid.";
}
