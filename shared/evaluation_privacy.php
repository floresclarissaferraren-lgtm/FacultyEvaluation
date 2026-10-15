<?php
const EVALUATION_PRIVACY_MIN_RESPONDENTS = 5;

function evaluationMeetsPrivacyThreshold(int $respondentCount): bool
{
    return $respondentCount >= EVALUATION_PRIVACY_MIN_RESPONDENTS;
}
?>
