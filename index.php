<html>
  <head>
    <title>RC24 + Everybody Votes!</title>
    <?php
      define('IN_PAGE', true);

      // Used to only show poll results X days old. This can be used to only show polls
      // that have fallen off of the channel to keep people using the channel and not just
      // check the page for results.
      $day_delay = 20;

      require 'includes/functions.php';
      require 'includes/tables.php';
      require 'includes/mappings.php';
      require 'config/config.php';
    ?>
    <link rel="stylesheet" href="./css/styles.css" type="text/css">
  </head>
  <body>
    <div class="banner"></div>
    <?php
      $conn = connectMySQL();

      if(isset($_GET['qid'])) {
        $qid = (int) $_GET['qid'];
        $cid = (int) $_GET['cid'];
        $question = getQuestion($qid);
        if ($question) {
          $type = $question['type'];
          questionTable($question);
          $countries = getQuestionCountries($qid);
          questionCountryTable($qid, $countries, $cid);

          $data = getVotesForQuestion($qid, $cid);
          $vote_data = generateVoteData($data);

          voteBreakDownTable('Total Votes', 'votes', $vote_data['votes_choice1'], $vote_data['votes_choice2']);
          voteBreakDownTable('Male Vote', 'male', $vote_data['male_votes_choice1'], $vote_data['male_votes_choice2']);
          voteBreakDownTable('Female Vote', 'female', $vote_data['female_votes_choice1'], $vote_data['female_votes_choice2']);
          predictionAccuracyTable($type, $vote_data['prediction_accuracy'], $vote_data['prediction_accuracy_width']);
        } else {
          echo '<div class="table">Question not found</div>';
        }
        echo '<div class="table"><a class="button" href="./"><< Back</a></div>';
      } else {
        $data = questionsList();
        foreach($data as $row) {
          questionTable($row);
        }
      }
    ?>
    <div class="banner"></div>
  </body>
  <?php
   $conn->close();
  ?>
</html>
