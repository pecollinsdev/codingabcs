<?php require_once '../app/views/layouts/header.php'; ?>

<div class="d-flex">
    <!-- Sidebar -->
    <?php require_once '../app/views/layouts/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="content flex-grow-1 p-4">
        <h2 class="mb-4">Leaderboard</h2>

        <?php if (!empty($leaderboard)): ?>
           <!-- Table for Desktop -->
           <div class="d-none d-md-block table-responsive">
               <table class="table table-bordered table-striped">
                   <thead class="thead-dark">
                       <tr>
                           <th>Rank</th>
                           <th>Username</th>
                           <th>Correct Answer Percentage</th>
                           <th>Total Questions</th>
                           <th>Last Attempt Date</th>
                       </tr>
                   </thead>
                   <tbody>
                       <?php foreach ($leaderboard as $rank => $entry): ?>
                           <tr>
                               <td><?= $rank + 1 ?></td>
                               <td><?= htmlspecialchars($entry['username']) ?></td>
                               <td><?= round($entry['correct_percentage'], 2) ?>%</td>
                               <td><?= $entry['total_questions'] ?></td>
                               <td><?= date("M j, Y, g:i A", strtotime($entry['last_attempt'])) ?></td>
                           </tr>
                       <?php endforeach; ?>
                   </tbody>
               </table>
           </div>

           <!-- Mobile Card View -->
           <div class="d-block d-md-none">
               <?php foreach ($leaderboard as $rank => $entry): ?>
                   <div class="card mb-3">
                       <div class="card-body">
                           <h5 class="card-title"><?= htmlspecialchars($entry['username']) ?> (Rank <?= $rank + 1 ?>)</h5>
                           <p class="mb-1"><strong>Correct Answer Percentage:</strong> <?= round($entry['correct_percentage'], 2) ?>%</p>
                           <p class="mb-1"><strong>Total Questions:</strong> <?= $entry['total_questions'] ?></p>
                           <p class="mb-0"><strong>Last Attempt Date:</strong> <?= date("M j, Y, g:i A", strtotime($entry['last_attempt'])) ?></p>
                       </div>
                   </div>
               <?php endforeach; ?>
           </div>

        <?php else: ?>
            <p class="text-muted">No leaderboard data available yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../app/views/layouts/footer.php'; ?>
