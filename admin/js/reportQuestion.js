$(document).ready(function() {
    // Handle the "Issue Resolved" button click
    $(document).on('click', '.resolve-btn', function() {
        var questionId = $(this).data('id');
        $.ajax({
            url: 'query/reportQuestion.php',
            type: 'POST',
            data: { question_id: questionId },
            success: function(response) {
                    location.reload();
            }
        });
    });
});