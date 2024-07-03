<div class="app-main__outer">
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div><b>WITHDRAWAL</b></div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="main-card mb-3 card">
                <div class="card-header">Withdraw Request List</div>
                <div class="table-responsive">
                    <form id="payment" method="POST" action="">
                        <table class="align-middle mb-0 table table-borderless table-striped table-hover" id="tableList">
                            <thead>
                                <tr>
                                    <th class="text-left pl-4" width="15%">WITHDRAW ID</th>
                                    <th class="text-left pl-4" width="15%">NAME</th>
                                    <th class="text-left pr-4" width="15%">WITHDRAW AMOUNT</th>
                                    <th class="text-left " width="15%">TOTAL POINTS</th>
                                    <th class="text-left " width="15%">UPI ID</th>
                                    <th class="text-left" width="15%">REQUESTED DATE</th>
                                    <th class="text-left" width="15%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                $selExam = $conn->query("SELECT w.withdrawal_id, w.user_id, w.amount, w.upi_id, w.withdrawal_date, u.exmne_fullname, SUM(p.points) AS points
                                                        FROM withdrawals w
                                                        INNER JOIN points p ON w.user_id = p.user_id
                                                        INNER JOIN examinee_tbl u ON u.exmne_id = w.user_id
                                                        WHERE w.status = 'pending'
                                                        GROUP BY w.withdrawal_id, w.user_id, w.amount, w.upi_id, w.withdrawal_date, u.exmne_fullname
                                                        ");
                                
                                if ($selExam->rowCount() > 0) {
                                while ($selExamRow = $selExam->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                <tr>
                                <td class="pl-4"><?php echo $selExamRow['withdrawal_id']; ?></td>
                                <td class="pl-4"><?php echo $selExamRow['exmne_fullname']; ?></td>
                                <td class="pl-4"><?php echo $selExamRow['amount']; ?></td>
                                <td class="pl-4"><?php echo $selExamRow['points']; ?></td>
                                <td><?php echo $selExamRow['upi_id']; ?></td>
                                <td><?php echo $selExamRow['withdrawal_date']; ?></td>
                                <td>
                                <form method="POST" action="">
                                <input type="hidden" name="withdrawal_id" value="<?php echo $selExamRow['withdrawal_id']; ?>">
                                <button type="submit" name="update_btn" id="update-btn" class="btn btn-primary">Update</button>
                                </form>
                                </td>
                                </tr>
                                <?php
                                }
                                } else {
                                ?>
                                <tr>
                                <td colspan="7">
                                <h3 class="p-3">No Withdraw Request</h3>
                                </td>
                                </tr>
                                <?php
                                }
                                } catch(PDOException $e) {
                                echo "Error: " . $e->getMessage();
                                }
                                ?>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>