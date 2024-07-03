<div class="app-main__outer">
    <div class="app-main__inner">
        <?php 
            // Fetch examinee data along with their scores
            $stmt = $conn->prepare("SELECT e.exmne_fullname AS fullname, 
                                            (SELECT COUNT(*) FROM game_answers WHERE is_correct = 1 AND user_id = e.exmne_id) + 
                                            (SELECT COUNT(*) FROM user_scores WHERE correct_answers != 0 AND user_id = e.exmne_id) AS total_correct_answers, 
                                            SUM(p.points) AS points, 
                                            l.label_name AS Level 
                                    FROM examinee_tbl e 
                                    INNER JOIN points p ON e.exmne_id = p.user_id 
                                    INNER JOIN level_tbl l ON e.exmne_id = l.user_id 
                                    GROUP BY e.exmne_id, e.exmne_fullname, l.label_name 
                                    ORDER BY points DESC 
                                    LIMIT 100");
            $stmt->execute();
            $selExmne = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div><b class="text-primary">LEADER BOARD</b><br></div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="align-middle mb-0 table table-borderless table-striped table-hover" id="tableList" style="table-layout: fixed; width: 100%;">
                <thead>
                    <tr>
                        <th class="pl-4" style="width: 5%; text-align: center;">RANK</th>
                        <th class="pl-4" style="width: 23%; text-align: center;">Name</th>
                        <th class="pl-4" style="width: 24%; text-align: center;">Points</th>
                        <th class="pl-4" style="width: 24%; text-align: center;">Correctly Answered</th>
                        <th class="pl-4" style="width: 24%; text-align: center;">Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $index = 1;
                    foreach ($selExmne as $row) { ?>
                        <tr>
                            <td class="pl-4" style="text-align: center;"><?php echo $index++; ?></td>
                            <td class="pl-4" style="text-align: center;"><?php echo htmlspecialchars($row['fullname']); ?></td>
                            <td class="pl-4" style="text-align: center;"><?php echo htmlspecialchars($row['points']); ?></td>
                            <td class="pl-4" style="text-align: center;"><?php echo htmlspecialchars($row['total_correct_answers']); ?></td>
                            <td class="pl-4" style="text-align: center;"><?php echo 'Level ' . htmlspecialchars($row['Level']); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
