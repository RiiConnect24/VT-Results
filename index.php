<html>
  <head>
    <title>RC24 + Everybody Votes!</title>
    <link href="https://miicontest.wii.rc24.xyz/css/style.css" rel="Stylesheet" type="text/css" />
    <?php
      // Root of the page's directroy
      $root = './';
      // Width of all tables
      $tableWidth = '700px';
      // Size of the table images (width and height)
      $imageSize = '64';
      // Used to only show poll results X days old. This can be used to only show polls
      // that have fallen off of the channel to keep people using the channel and not just
      // check the page for results.
      $dayDelay = 20;

      function questionTable($row) {
        global $root, $imageSize;
        echo '
        <table class="table">
          <tr>
            <td rowspan="2">
              <a href="'.$root.'?qid='.$row['questionID'].'">
                <img width="'.$imageSize.'" height="'.$imageSize.'" alt="'.$image.'" src="'.$root.'images/'.$row['type'].'_question.png" />
              </a>
            </td>
            <td colspan="2" class="question">'.$row['content_english'].'</td>
          </tr>
          <tr>
            <td width="50%" class="choice1">'.$row['choice1_english'].'</td>
            <td width="50%" class="choice2">'.$row['choice2_english'].'</td>
          </tr>
        </table>';
      }

      function predictionAccuracyTable($type, $prediction_accuracy, $prediction_accuracy_width) {
        global $root, $imageSize;
        echo '
        <table class="table">
          <tr>
            <td rowspan="2">
              <img width="'.$imageSize.'" height="'.$imageSize.'" alt="prediction" src="'.$root.'images/'.$type.'_prediction.png" />
            </td>
            <td colspan="2" class="question">
              <div class="headercol1">&nbsp;</div>
              <div class="headercol2">Prediction Accuracy</div>
              <div class="headercol3">'.$prediction_accuracy.'%</div>
            </td>
          </tr>
          <tr>
            <td width="'.$prediction_accuracy_width.'%" class="prediction">&nbsp;</td>
            <td width="'.(100 - $prediction_accuracy_width).'%">&nbsp;</td>
          </tr>
        </table>';
      }

      function voteBreakdownTable($title, $image, $votes_choice1, $votes_choice2) {
        global $root, $imageSize;
        $votes_choice1_perc = round(($votes_choice1 / ($votes_choice1 + $votes_choice2)) * 100, 1);
        $votes_choice2_perc = 100 - $votes_choice1_perc;
        $choice1_width = round($votes_choice1_perc);
        $choice2_width = round($votes_choice2_perc);

        echo '
        <table class="table">
          <tr>
            <td rowspan="2">
              <img width="'.$imageSize.'" height="'.$imageSize.'" alt="'.$image.'" src="'.$root.'images/'.$image.'.png" />
            </td>
            <td colspan="2" class="question">
              <div class="headercol1">'.$votes_choice1.' ('.$votes_choice1_perc.'%)</div>
              <div class="headercol2">'.$title.'</div>
              <div class="headercol3">('.$votes_choice2_perc.'%) '.$votes_choice2.'</div>
            </td>
          </tr>
          <tr>
            <td class="choice1" width="'.$choice1_width.'%">&nbsp;</td>
            <td class="choice2" width="'.$choice2_width.'%">&nbsp;</td>
          </tr>
        </table>';
      }
    ?>
    <style type="text/css">
      body {
        background-color: #EEEEEE;
      }
      .banner {
        background-image: url('<?php echo $root; ?>images/banner.png');
        background-repeat: repeat-x;
        height: 120px;
      }
      .table {
        margin-left: auto;
        margin-right: auto;
        margin-bottom: 24px;
        border-spacing: 0;
        border-collapse: collapse;
        width: <?php echo $tableWidth; ?>;
      }
      td.question {
        background-color: black;
        color: white;
        text-align: center;
        padding: 0 12px;
      }
      .choice1 {
        background-color: #f540f1;
        color: white;
        text-align: left;
        padding-left: 12px;
      }
      .choice2 {
        background-color: #2ef709;
        color: white;
        text-align: right;
        padding-right: 12px;
      }
      .prediction {
        background-color: #00bdce;
      }
      .headercol1 {
        float: left;
        width: 25%;
        text-align: left;
      }
      .headercol2 {
        float: left;
        width: 50%;
      }
      .headercol3 {
        float: right;
        width: 25%;
        text-align: right;
      }
    </style>
  </head>
  <body>
    <div class="banner"></div>
    <?php
	  require "config/config.php";

      $conn = connectMySQL(); 

      if(isset($_GET['qid'])) {
        $stmt = $conn->prepare('
          SELECT questionID, content_english, choice1_english, choice2_english, date, type
          FROM questions
          WHERE DATE(date) <= CURDATE() - INTERVAL ? DAY
          AND questionID = ?
        ');
        $stmt->bind_param('ii', $dayDelay, $_GET['qid']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $type = $row['type'];
        questionTable($row);

        $stmt = $conn->prepare('
          SELECT typeCD, ansCNT
          FROM votes
          WHERE questionID = ?
        ');
        $stmt->bind_param('i', $_GET['qid']);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $votes_choice1 = 0;
        $votes_choice2 = 0;
        $male_votes_choice1 = 0;
        $female_votes_choice1 = 0;
        $male_votes_choice2 = 0;
        $female_votes_choice2 = 0;
        $prediction_choice1 = 0;
        $prediction_choice2 = 0;
        foreach($data as $row) {
          $ansCNT = str_split(str_pad($row['ansCNT'], 4, "0", STR_PAD_LEFT));
          if ($row['typeCD'] == 0) {
            $male_votes_choice1 += (int) $ansCNT[0];
            $female_votes_choice1 += (int) $ansCNT[1];
            $male_votes_choice2 += (int) $ansCNT[2];
            $female_votes_choice2 += (int) $ansCNT[3];
          } else {
            $prediction_choice1 += ((int) $ansCNT[0] + (int) $ansCNT[1]);
            $prediction_choice2 += ((int) $ansCNT[2] + (int) $ansCNT[3]);
          }
        }
        $votes_choice1 = $male_votes_choice1 + $female_votes_choice1;
        $votes_choice2 = $male_votes_choice2 + $female_votes_choice2;
        if ($votes_choice1 == $votes_choice2) {
          $prediction_accuracy = 0;
        } elseif ($votes_choice1 > $votes_choice2) {
          $prediction_accuracy = round(($prediciton_choice1 / ($prediciton_choice1 + $prediciton_choice2)) * 100, 1);
          $prediction_accuracy_width = round($prediction_accuracy);
        } else {
          $prediction_accuracy = round(($prediciton_choice2 / ($prediciton_choice1 + $prediciton_choice2)) * 100, 1);
          $prediction_accuracy_width = round($prediction_accuracy);
        }

        voteBreakDownTable("Total Votes", "votes", $votes_choice1, $votes_choice2);
        voteBreakDownTable("Male Vote", "male", $male_votes_choice1, $male_votes_choice2);
        voteBreakDownTable("Female Vote", "female", $female_votes_choice1, $female_votes_choice2);
        predictionAccuracyTable($type, $prediction_accuracy, $prediction_accuracy_width);
        echo '<div class="table"><a href="./"><< Back</a></div>';
      } else {
        $stmt = $conn->prepare('
          SELECT questionID, content_english, choice1_english, choice2_english, date, type
          FROM questions
          WHERE DATE(date) <= CURDATE() - INTERVAL ? DAY
          ORDER BY questionID DESC
        ');
        $stmt->bind_param('i', $dayDelay);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
