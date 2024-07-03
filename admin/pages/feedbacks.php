<div class="app-main__outer">
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div><b>REPORTED QUESTIONS</b></div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="main-card mb-3 card">
                <div class="card-header">Question's List</div>
                <div class="table-responsive">
                    <table class="align-middle mb-0 table table-borderless table-striped table-hover" id="tableList">
                        <thead>
                        <tr>
                            <th class="text-left pl-4" width="20%">Name</th>
                            <th class="text-left">Question Id</th>
                            <th class="text-left">Question</th>
                            <th class="text-left">Reported Reason</th>
                            <th class="text-center" width="15%">Date</th>
                            <th class="text-center" width="15%">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php

                        $selExam = $conn->query("SELECT e.exmne_fullname AS Name, r.question_id, r.report_reason, r.report_time, q.exam_question ,r.status
                                                 FROM examinee_tbl e  
                                                 INNER JOIN reported_questions r ON e.exmne_id = r.user_id 
                                                 INNER JOIN exam_question_tbl q ON q.eqt_id = r.question_id
                                                 WHERE r.status != 'issue resolved'
                                                 ORDER BY r.id DESC");

                        if ($selExam->rowCount() > 0) {
                            while ($selExamRow = $selExam->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                <tr>
                                    <td class="pl-4"><?php echo $selExamRow['Name']; ?></td>
                                    <td><?php echo $selExamRow['question_id']; ?></td>
                                    <td><?php echo $selExamRow['exam_question']; ?></td>
                                    <td><?php echo $selExamRow['report_reason']; ?></td>
                                    <td><?php echo $selExamRow['report_time']; ?></td>
                                    <td>
                                        <a rel="facebox" href="facebox_modal/updateQuestion.php?id=<?php echo $selExamRow['question_id']; ?>" class="btn btn-sm btn-primary">Update</a><br><br>
                                        <button class="btn btn-success resolve-btn" data-id="<?php echo $selExamRow['question_id']; ?>">Issue Resolved</button>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="6">
                                    <h3 class="p-3">No Reported Questions found</h3>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>