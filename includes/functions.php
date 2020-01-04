<?php
  defined('IN_PAGE') or die();

  function questionsList() {
    global $conn, $day_delay;

    $stmt = $conn->prepare('
      SELECT questionID, content_english, choice1_english, choice2_english, date, type
      FROM questions
      WHERE DATE(date) <= CURDATE() - INTERVAL ? DAY
      ORDER BY questionID DESC
    ');
    $stmt->bind_param('i', $day_delay);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return $data;
  }

  function getQuestion($qid) {
    global $conn, $day_delay;

    $stmt = $conn->prepare('
      SELECT questionID, content_english, choice1_english, choice2_english, date, type
      FROM questions
      WHERE DATE(date) <= CURDATE() - INTERVAL ? DAY
      AND questionID = ?
    ');
    $stmt->bind_param('ii', $day_delay, $qid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return $row;
  }

  function getQuestionCountries($qid) {
    global $conn, $countries_mapping;

    $countries = array();
    $stmt = $conn->prepare('
      SELECT DISTINCT countryID
      FROM votes
      WHERE questionID = ?
    ');
    $stmt->bind_param('i', $qid);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach($data as $row) {
      $strCountryId = strVal($row['countryID']);
      $countries[$strCountryId] = $countries_mapping[$strCountryId];
    }
    asort($countries, SORT_STRING);
    return $countries;
  }

  function getVotesForQuestion($qid, $cid=0) {
    global $conn;

    if ($cid > 0) {
      $stmt = $conn->prepare('
        SELECT typeCD, ansCNT, countryID
        FROM votes
        WHERE questionID = ?
        AND countryID = ?
      ');
      $stmt->bind_param('ii', $qid, $cid);
    } else {
      $stmt = $conn->prepare('
        SELECT typeCD, ansCNT, countryID
        FROM votes
        WHERE questionID = ?
      ');
      $stmt->bind_param('i', $qid);
    }
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return $data;
  }

  function generateVoteData($data) {
    $vote_data = array(
      'votes_choice1' => 0,
      'votes_choice2' => 0,
      'male_votes_choice1' => 0,
      'female_votes_choice1' => 0,
      'male_votes_choice2' => 0,
      'female_votes_choice2' => 0,
      'prediction_choice1' => 0,
      'prediction_choice2' => 0,
      'prediction_accuracy' => 0,
      'prediction_accuracy_width' => 0
    );
    foreach($data as $row) {
      $ansCNT = str_split(str_pad($row['ansCNT'], 4, '0', STR_PAD_LEFT));
      if ($row['typeCD'] == 0) {
        $vote_data['male_votes_choice1'] += (int) $ansCNT[0];
        $vote_data['female_votes_choice1'] += (int) $ansCNT[1];
        $vote_data['male_votes_choice2'] += (int) $ansCNT[2];
        $vote_data['female_votes_choice2'] += (int) $ansCNT[3];
      } else {
        $vote_data['prediction_choice1'] += ((int) $ansCNT[0] + (int) $ansCNT[1]);
        $vote_data['prediction_choice2'] += ((int) $ansCNT[2] + (int) $ansCNT[3]);
      }
    }
    $vote_data['votes_choice1'] = $vote_data['male_votes_choice1'] + $vote_data['female_votes_choice1'];
    $vote_data['votes_choice2'] = $vote_data['male_votes_choice2'] + $vote_data['female_votes_choice2'];
    if ($vote_data['prediction_choice1'] > 0 || $vote_data['prediction_choice2'] > 0) {
      if ($vote_data['votes_choice1'] > $vote_data['votes_choice2']) {
        $vote_data['prediction_accuracy'] = round(($vote_data['prediction_choice1'] / ($vote_data['prediction_choice1'] + $vote_data['prediction_choice2'])) * 100, 1);
        $vote_data['prediction_accuracy_width'] = round($vote_data['prediction_accuracy']);
      } else {
        $vote_data['prediction_accuracy'] = round(($vote_data['prediction_choice2'] / ($vote_data['prediction_choice1'] + $vote_data['prediction_choice2'])) * 100, 1);
        $vote_data['prediction_accuracy_width'] = round($vote_data['prediction_accuracy']);
      }
    }

    return $vote_data;
  }
?>
