

<div class="app-main__outer">
<div id="refreshData">
<div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon">
                        <i class="pe-7s-car icon-gradient bg-mean-fruit">
                        </i>
                    </div>
                    <div style="color:#000;"><B>WELCOME TO ADMIN DASHBOARD</B>
                        </div>
                    </div>
                </div>
             </div>
        </div>            

    <div class="row">
            <div class="col-md-6 col-xl-4">
                <div class="card mb-3 widget-content bg-arielle-smile">
                    <div class="widget-content-wrapper text-white">
                        <div class="widget-content-left">
                            <div class="widget-heading">Total Exam</div>
                            <div class="widget-subheading" style="color:transparent;">.</div>
                        </div>
                        <div class="widget-content-right">
                            <div class="widget-numbers text-white">
                                <span><?php echo $totalCourse = $selExam['totExam']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card mb-3 widget-content bg-grow-early">
                    <div class="widget-content-wrapper text-white">
                        <div class="widget-content-left">
                            <div class="widget-heading">Total Examinee</div>
                            <div class="widget-subheading" style="color:transparent;">.</div>
                        </div>
                        <div class="widget-content-right">
                            <div class="widget-numbers text-white">
                                <span><?php echo $totalCourse = $selExaminee['totExmne'];?> </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="card mb-3 widget-content bg-mixed-hopes">
                    <div class="widget-content-wrapper text-white">
                        <div class="widget-content-left">
                            <div class="widget-heading">Total Pending Reported Questions</div>
                            <div class="widget-subheading" style="color:transparent;">.</div>
                        </div>
                        <div class="widget-content-right">
                            <div class="widget-numbers text-white">
                                <span><?php 
                                $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM reported_questions WHERE status != 'issue resolved'");
                                $stmt->execute();
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                echo $result[total];
                                ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card mb-3 widget-content bg-love-kiss">
                    <div class="widget-content-wrapper text-white">
                        <div class="widget-content-left">
                            <div class="widget-heading">Total Question</div>
                            <div class="widget-subheading" style="color:transparent;">.</div>
                        </div>
                        <div class="widget-content-right">
                            <div class="widget-numbers text-white">
                                <span><?php echo $totalCourse = $selQuesion['totQuestion'];?> </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card mb-3 widget-content bg-vicious-stance ">
                    <div class="widget-content-wrapper text-white">
                        <div class="widget-content-left">
                            <div class="widget-heading">Total Withdraw Pending Requests</div>
                            <div class="widget-subheading" style="color:transparent;">.</div>
                        </div>
                        <div class="widget-content-right">
                            <div class="widget-numbers text-white">
                                <span><?php 
                                $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM withdrawals WHERE status != 'Payment Done'");
                                $stmt->execute();
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                echo $result[total];
                                ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        
            
        </div>
        </div>     
</div>

