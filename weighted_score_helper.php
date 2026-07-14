<?php
/**
 * Weighted score helpers.
 *
 * Included by submit_evaluation.php, get_faculty_stats.php,
 * get_faculty_evaluation_report.php, get_faculty_report.php,
 * get_evaluations.php, and get_dashboard_stats.php.
 */

/**
 * Calculate the weighted overall score for a faculty member using
 * the evaluation_answers already stored in the database.
 *
 * Algorithm:
 *   1. For each category, compute avg rating across all answers in that
 *      category for this faculty (across all evaluations).
 *   2. Normalise category weights so they always sum to 100 % — this
 *      handles the case where the admin hasn't set weights yet (all 0)
 *      or where weights don't add up to exactly 100.
 *   3. weighted_score = SUM( category_avg * normalised_weight ) / 100
 *
 * Returns a float rounded to 2 decimal places, or 0.00 if no data.
 *
 * @param mysqli $conn
 * @param int    $faculty_id
 * @return float
 */
function evaluationContextFilterSql(string $alias, ?int $subject_id = null, ?int $class_id = null): array
{
    $sql = '';
    $types = '';
    $params = [];

    if ($subject_id !== null && $subject_id > 0) {
        $sql .= " AND {$alias}.subject_id = ?";
        $types .= 'i';
        $params[] = $subject_id;
    }

    if ($class_id !== null) {
        $sql .= " AND {$alias}.class_id = ?";
        $types .= 'i';
        $params[] = max(0, $class_id);
    }

    return ['sql' => $sql, 'types' => $types, 'params' => $params];
}

function bindDynamicParams(mysqli_stmt $stmt, string $types, array $params): void
{
    if ($types === '') return;
    $refs = [$types];
    foreach ($params as $key => $value) {
        $refs[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

function calcWeightedScore(mysqli $conn, int $faculty_id, ?int $subject_id = null, ?int $class_id = null): float
{
    $context = evaluationContextFilterSql('e', $subject_id, $class_id);
    // Fetch per-category averages + weights in one query.
    $sql = "
        SELECT
            c.id          AS cat_id,
            COALESCE(c.weight, 0)                                  AS cat_weight,
            COALESCE(AVG(CASE WHEN e.id IS NOT NULL THEN ea.rating END), NULL) AS cat_avg
        FROM add_categories c
        LEFT JOIN add_questions q    ON q.category_id = c.id
        LEFT JOIN evaluation_answers ea ON ea.question_id = q.id
        LEFT JOIN evaluations e      ON e.id = ea.evaluation_id
                                     AND e.faculty_id = ?
                                     {$context['sql']}
        GROUP BY c.id, c.weight
        ORDER BY c.section_number ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) return 0.00;
    bindDynamicParams($stmt, 'i' . $context['types'], array_merge([$faculty_id], $context['params']));
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['cat_avg'] === null) continue; // skip categories with no answers
        $rows[] = [
            'weight' => floatval($row['cat_weight']),
            'avg'    => floatval($row['cat_avg']),
        ];
    }

    if (empty($rows)) return 0.00;

    // Normalise weights
    $totalWeight = array_sum(array_column($rows, 'weight'));

    if ($totalWeight <= 0) {
        // Fall back: equal weight for each category that has data
        $equalWeight = 100.0 / count($rows);
        foreach ($rows as &$r) {
            $r['weight'] = $equalWeight;
        }
        unset($r);
        $totalWeight = 100.0;
    }

    $weighted = 0.0;
    foreach ($rows as $r) {
        $normalised  = ($r['weight'] / $totalWeight) * 100.0;
        $weighted   += $r['avg'] * ($normalised / 100.0);
    }

    return round($weighted, 2);
}

/**
 * Calculate weighted score from an in-memory array of answers.
 * Used during submission (before answers are persisted) so we can
 * store the correct overall_rating immediately.
 *
 * @param mysqli $conn
 * @param array  $answers  [ ['question_id'=>int, 'rating'=>int], ... ]
 * @return float
 */
function calcWeightedScoreFromAnswers(mysqli $conn, array $answers): float
{
    if (empty($answers)) return 0.00;

    // Build question_id -> rating map
    $ratingMap = [];
    foreach ($answers as $a) {
        $ratingMap[intval($a['question_id'])] = intval($a['rating']);
    }

    // Fetch category weights and their question ids
    $sql = "
        SELECT c.id AS cat_id, COALESCE(c.weight, 0) AS cat_weight, q.id AS q_id
        FROM add_categories c
        JOIN add_questions q ON q.category_id = c.id
        ORDER BY c.section_number ASC
    ";
    $result = $conn->query($sql);
    if (!$result) return 0.00;

    // Group questions by category
    $categories = []; // cat_id => ['weight'=>float, 'ratings'=>[]]
    while ($row = $result->fetch_assoc()) {
        $cid = intval($row['cat_id']);
        if (!isset($categories[$cid])) {
            $categories[$cid] = ['weight' => floatval($row['cat_weight']), 'ratings' => []];
        }
        $qid = intval($row['q_id']);
        if (isset($ratingMap[$qid])) {
            $categories[$cid]['ratings'][] = $ratingMap[$qid];
        }
    }

    // Filter out categories with no answered questions
    $active = array_filter($categories, fn($c) => !empty($c['ratings']));
    if (empty($active)) return 0.00;

    $totalWeight = array_sum(array_column($active, 'weight'));

    if ($totalWeight <= 0) {
        $equalWeight = 100.0 / count($active);
        foreach ($active as &$c) {
            $c['weight'] = $equalWeight;
        }
        unset($c);
        $totalWeight = 100.0;
    }

    $weighted = 0.0;
    foreach ($active as $c) {
        $catAvg      = array_sum($c['ratings']) / count($c['ratings']);
        $normalised  = ($c['weight'] / $totalWeight) * 100.0;
        $weighted   += $catAvg * ($normalised / 100.0);
    }

    return round($weighted, 2);
}

/**
 * Return per-category stats for a faculty member, including weights.
 *
 * @param mysqli $conn
 * @param int    $faculty_id
 * @return array
 */
function getCategoryStats(mysqli $conn, int $faculty_id, ?int $subject_id = null, ?int $class_id = null): array
{
    $context = evaluationContextFilterSql('e', $subject_id, $class_id);
    $sql = "
        SELECT
            c.id                                                            AS cat_id,
            c.category_name,
            COALESCE(c.weight, 0)                                          AS weight,
            COUNT(DISTINCT CASE WHEN e.id IS NOT NULL THEN e.id END)       AS responses,
            COALESCE(AVG(CASE WHEN e.id IS NOT NULL THEN ea.rating END), 0) AS avg_rating
        FROM add_categories c
        LEFT JOIN add_questions q    ON q.category_id = c.id
        LEFT JOIN evaluation_answers ea ON ea.question_id = q.id
        LEFT JOIN evaluations e      ON e.id = ea.evaluation_id
                                     AND e.faculty_id = ?
                                     {$context['sql']}
        GROUP BY c.id, c.category_name, c.weight
        ORDER BY c.section_number ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    bindDynamicParams($stmt, 'i' . $context['types'], array_merge([$faculty_id], $context['params']));
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    // Normalise weights for display
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $totalWeight = array_sum(array_column($rows, 'weight'));
    foreach ($rows as &$row) {
        $row['weight']            = floatval($row['weight']);
        $row['normalised_weight'] = $totalWeight > 0
            ? round(($row['weight'] / $totalWeight) * 100, 2)
            : (count($rows) > 0 ? round(100 / count($rows), 2) : 0);
        $row['avg_rating']        = number_format(floatval($row['avg_rating']), 2);
        $row['responses']         = intval($row['responses']);
    }
    unset($row);

    return $rows;
}

/**
 * Calculate weighted score for a faculty member filtered to one program.
 * Handles add_students.program storing either program_code or numeric id.
 *
 * @param mysqli $conn
 * @param int    $faculty_id
 * @param string $program_name  Full program name from add_programs.program_name
 * @return float
 */
function calcWeightedScoreByProgram(mysqli $conn, int $faculty_id, string $program_name, ?int $subject_id = null, ?int $class_id = null): float
{
    $context = evaluationContextFilterSql('e', $subject_id, $class_id);
    $sql = "
        SELECT
            c.id          AS cat_id,
            COALESCE(c.weight, 0) AS cat_weight,
            COALESCE(AVG(CASE WHEN e.id IS NOT NULL THEN ea.rating END), NULL) AS cat_avg
        FROM add_categories c
        LEFT JOIN add_questions q    ON q.category_id = c.id
        LEFT JOIN evaluation_answers ea ON ea.question_id = q.id
        LEFT JOIN evaluations e      ON e.id = ea.evaluation_id
                                     AND e.faculty_id = ?
                                     {$context['sql']}
        LEFT JOIN add_students s     ON s.id = e.student_id
        LEFT JOIN add_programs p     ON (TRIM(p.program_code) = TRIM(s.program)
                                     OR CAST(p.id AS CHAR) = TRIM(s.program))
                                     AND p.program_name = ?
        WHERE e.id IS NULL OR p.program_name = ?
        GROUP BY c.id, c.weight
        ORDER BY c.section_number ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) return 0.00;
    bindDynamicParams($stmt, 'i' . $context['types'] . 'ss', array_merge([$faculty_id], $context['params'], [$program_name, $program_name]));
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['cat_avg'] === null) continue;
        $rows[] = [
            'weight' => floatval($row['cat_weight']),
            'avg'    => floatval($row['cat_avg']),
        ];
    }

    if (empty($rows)) return 0.00;

    $totalWeight = array_sum(array_column($rows, 'weight'));
    if ($totalWeight <= 0) {
        $equalWeight = 100.0 / count($rows);
        foreach ($rows as &$r) { $r['weight'] = $equalWeight; }
        unset($r);
        $totalWeight = 100.0;
    }

    $weighted = 0.0;
    foreach ($rows as $r) {
        $normalised  = ($r['weight'] / $totalWeight) * 100.0;
        $weighted   += $r['avg'] * ($normalised / 100.0);
    }

    return round($weighted, 2);
}

/**
 * Return per-category stats for a faculty member filtered to one program.
 *
 * @param mysqli $conn
 * @param int    $faculty_id
 * @param string $program_name  Full program name from add_programs.program_name
 * @return array
 */
function getCategoryStatsByProgram(mysqli $conn, int $faculty_id, string $program_name, ?int $subject_id = null, ?int $class_id = null): array
{
    $context = evaluationContextFilterSql('e', $subject_id, $class_id);
    $sql = "
        SELECT
            c.id                                                                    AS cat_id,
            c.category_name,
            COALESCE(c.weight, 0)                                                  AS weight,
            COUNT(DISTINCT CASE WHEN e.id IS NOT NULL AND p.program_name = ? THEN e.id END)       AS responses,
            COALESCE(AVG(CASE WHEN e.id IS NOT NULL AND p.program_name = ? THEN ea.rating END), 0) AS avg_rating
        FROM add_categories c
        LEFT JOIN add_questions q    ON q.category_id = c.id
        LEFT JOIN evaluation_answers ea ON ea.question_id = q.id
        LEFT JOIN evaluations e      ON e.id = ea.evaluation_id
                                     AND e.faculty_id = ?
                                     {$context['sql']}
        LEFT JOIN add_students s     ON s.id = e.student_id
        LEFT JOIN add_programs p     ON TRIM(p.program_code) = TRIM(s.program)
                                     OR CAST(p.id AS CHAR)   = TRIM(s.program)
        GROUP BY c.id, c.category_name, c.weight
        ORDER BY c.section_number ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    bindDynamicParams($stmt, 'ssi' . $context['types'], array_merge([$program_name, $program_name, $faculty_id], $context['params']));
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $totalWeight = array_sum(array_column($rows, 'weight'));
    foreach ($rows as &$row) {
        $row['weight']            = floatval($row['weight']);
        $row['normalised_weight'] = $totalWeight > 0
            ? round(($row['weight'] / $totalWeight) * 100, 2)
            : (count($rows) > 0 ? round(100 / count($rows), 2) : 0);
        $row['avg_rating']        = number_format(floatval($row['avg_rating']), 2);
        $row['responses']         = intval($row['responses']);
    }
    unset($row);

    return $rows;
}
