
<!-- Modal For Add Exam -->
<div class="modal fade" id="modalForExam" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form class="refreshFrm" id="addExamFrm" method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add Exam</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Exam Title</label>
                            <input type="" name="examTitle" class="form-control" placeholder="Input Exam Title"
                                required="">
                        </div>

                        <div class="form-group">
                            <label>Exam Description</label>
                            <textarea name="examDesc" class="form-control" rows="4" placeholder="Input Exam Description"
                                required=""></textarea>
                        </div>


                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Now</button>
                </div>
            </div>
        </form>
    </div>
</div>


<!-- Modal For Add Examinee -->
<div class="modal fade" id="modalForAddExaminee" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form class="refreshFrm" id="addExamineeFrm" method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add Examinee</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Fullname</label>
                            <input type="" name="fullname" id="fullname" class="form-control"
                                placeholder="Input Fullname" autocomplete="off" required="">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="Input Email"
                                autocomplete="off" required="">
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" id="password" class="form-control"
                                placeholder="Input Password" autocomplete="off" required="">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Now</button>
                </div>
            </div>
        </form>
    </div>
</div>



<!-- Modal For Add Question -->
<div class="modal fade" id="modalForAddQuestion" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form class="refreshFrm" id="addQuestionFrm" method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add Question for
                        <br><?php echo $selExamRow['ex_title']; ?>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form class="refreshFrm" method="post" id="addQuestionFrm">
                    <div class="modal-body">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Question</label>
                                <input type="hidden" name="examId" value="<?php echo $exId; ?>">
                                <input type="" name="question" id="course_name" class="form-control"
                                    placeholder="Input question" autocomplete="off">
                            </div>

                            <fieldset>
                                <legend>Input word for choice's</legend>
                                <div class="form-group">
                                    <label>Choice A</label>
                                    <input type="" name="choice_A" id="choice_A" class="form-control"
                                        placeholder="Input choice A" autocomplete="off">
                                </div>

                                <div class="form-group">
                                    <label>Choice B</label>
                                    <input type="" name="choice_B" id="choice_B" class="form-control"
                                        placeholder="Input choice B" autocomplete="off">
                                </div>

                                <div class="form-group">
                                    <label>Choice C</label>
                                    <input type="" name="choice_C" id="choice_C" class="form-control"
                                        placeholder="Input choice C" autocomplete="off">
                                </div>

                                <div class="form-group">
                                    <label>Choice D</label>
                                    <input type="" name="choice_D" id="choice_D" class="form-control"
                                        placeholder="Input choice D" autocomplete="off">
                                </div>

                                <div class="form-group">
                                    <label>Correct Answer</label>
                                    <input type="" name="correctAnswer" id="correctAnswer" class="form-control"
                                        placeholder="Input correct answer" autocomplete="off">
                                </div>
                                

                            </fieldset>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Now</button>
                    </div>
                </form>
            </div>
        </form>
    </div>
</div>


<!-- modal for upload question in excel-->
<div class="modal fade bd-example-modal-lg" id="spreedModal" tabindex="-1" aria-labelledby="spreedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="spreedModalLabel">Upload Excel</h1>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <a href="./excel/spreedsheet.php">Click here</a> to Download Template.
                <form id="uploadForm" enctype="multipart/form-data">
                    <input type="hidden" name="examId" value="<?php echo $exId; ?>">
                    <div class="m-2">
                        <label for="spreedsheetfile">Upload File</label><br>
                        <input type="file" id="spreedsheetfile" name="spreedsheetfile"><br><br>
                    </div>

                    <div id="validationMessages"></div>
                    <div id="excelTable"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="preview">Preview</button><br><br>
                        <!-- <button type="button" class="btn btn-success" id="uploadButton">Upload</button> -->
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="toastContainer" aria-live="polite" aria-atomic="true"
    style="position: absolute; top: 0; right: 0; z-index: 9999;"></div>