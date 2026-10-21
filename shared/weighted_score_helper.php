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
function evaluationContextFilterSql(
    string $alias,
    ?int $subject_id = null,
    ?int $class_id = null,
    ?int $period_id = null,
): array {
    $sql = "";
    $types = "";
    $params = [];

    if ($subject_id !== null && $subject_id > 0) {
        $sql .= " AND {$alias}.subject_id = ?";
        $types .= "i";
        $params[] = $subject_id;
    }

    if ($class_id !== null) {
        $sql .= " AND {$alias}.class_id = ?";
        $types .= "i";
        $params[] = max(0, $class_id);
    }

    if ($period_id !== null && $period_id > 0) {
        $sql .= " AND (
            {$alias}.period_id = ?
            OR (
                {$alias}.period_id IS NULL
                AND EXISTS (
                    SELECT 1
                    FROM evaluation_periods ep_filter
                    WHERE ep_filter.id = ?
                      AND DATE({$alias}.created_at) BETWEEN ep_filter.start_date AND ep_filter.end_date
                )
            )
        )";
        $types .= "ii";
        $params[] = $period_id;
        $params[] = $period_id;
    }

    return ["sql" => $sql, "types" => $types, "params" => $params];
}

function bindDynamicParams(
    mysqli_stmt $stmt,
    string $types,
    array $params,
): void {
    if ($types === "") {
        return;
    }
    $refs = [$types];
    foreach ($params as $key => $value) {
        $refs[] = &$params[$key];
    }
    call_user_func_array([$stmt, "bind_param"], $refs);
}

function normaliseCategoryWeights(array $weights): array
{
    if (empty($weights)) {
        return [];
    }

    $totalWeight = array_sum($weights);
    $categoryCount = count($weights);

    if ($totalWeight <= 0) {
        return array_fill_keys(array_keys($weights), 100.0 / $categoryCount);
    }

    if ($totalWeight < 100) {
        $unassignedKeys = array_keys(
            array_filter($weights, static fn($weight) => $weight <= 0),
        );
        $keysToAdjust = $unassignedKeys ?: array_keys($weights);
        $remainderPerCategory =
            (100.0 - $totalWeight) / count($keysToAdjust);
        foreach ($keysToAdjust as $key) {
            $weights[$key] += $remainderPerCategory;
        }
        return $weights;
    }

    foreach ($weights as $key => $weight) {
        $weights[$key] = ($weight / $totalWeight) * 100.0;
    }

    return $weights;
}

function calcWeightedScore(
    mysqli $conn,
    int $faculty_id,
    ?int $subject_id = null,
    ?int $class_id = null,
    ?int $period_id = null,
): float {
    $context = evaluationContextFilterSql(
        "e",
        $subject_id,
        $class_id,
        $period_id,
    );
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
                                     {$context["sql"]}
        GROUP BY c.id, c.weight
        ORDER BY c.section_number ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0.0;
    }
    bindDynamicParams(
        $stmt,
        "i" . $context["types"],
        array_merge([$faculty_id], $context["params"]),
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        if ($row["cat_avg"] === null) {
            continue;
        } // skip categories with no answers
        $rows[] = [
            "weight" => floatval($row["cat_weight"]),
            "avg" => floatval($row["cat_avg"]),
        ];
    }

    if (empty($rows)) {
        return 0.0;
    }

    $normalisedWeights = normaliseCategoryWeights(
        array_column($rows, "weight"),
    );

    $weighted = 0.0;
    foreach ($rows as $index => $row) {
        $weighted += $row["avg"] * ($normalisedWeights[$index] / 100.0);
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
    if (empty($answers)) {
        return 0.0;
    }

    // Build question_id -> rating map
    $ratingMap = [];
    foreach ($answers as $a) {
        $ratingMap[intval($a["question_id"])] = intval($a["rating"]);
    }

    // Fetch category weights and their question ids
    $sql = "
        SELECT c.id AS cat_id, COALESCE(c.weight, 0) AS cat_weight, q.id AS q_id
        FROM add_categories c
        JOIN add_questions q ON q.category_id = c.id
        ORDER BY c.section_number ASC
    ";
    $result = $conn->query($sql);
    if (!$result) {
        return 0.0;
    }

    // Group questions by category
    $categories = []; // cat_id => ['weight'=>float, 'ratings'=>[]]
    while ($row = $result->fetch_assoc()) {
        $cid = intval($row["cat_id"]);
        if (!isset($categories[$cid])) {
            $categories[$cid] = [
                "weight" => floatval($row["cat_weight"]),
                "ratings" => [],
            ];
        }
        $qid = intval($row["q_id"]);
        if (isset($ratingMap[$qid])) {
            $categories[$cid]["ratings"][] = $ratingMap[$qid];
        }
    }

    // Filter out categories with no answered questions
    $active = array_filter($categories, fn($c) => !empty($c["ratings"]));
    if (empty($active)) {
        return 0.0;
    }

    $normalisedWeights = normaliseCategoryWeights(
        array_column($active, "weight"),
    );

    $weighted = 0.0;
    foreach (array_values($active) as $index => $category) {
        $catAvg = array_sum($category["ratings"]) / count($category["ratings"]);
        $weighted += $catAvg * ($normalisedWeights[$index] / 100.0);
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
function getCategoryStats(
    mysqli $conn,
    int $faculty_id,
    ?int $subject_id = null,
    ?int $class_id = null,
    ?int $period_id = null,
): array {
    $context = evaluationContextFilterSql(
        "e",
        $subject_id,
        $class_id,
        $period_id,
    );
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
                                     {$context["sql"]}
        GROUP BY c.id, c.category_name, c.weight
        ORDER BY c.section_number ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    bindDynamicParams(
        $stmt,
        "i" . $context["types"],
        array_merge([$faculty_id], $context["params"]),
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    // Normalise weights for display
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $activeRows = array_filter(
        $rows,
        static fn($row) => intval($row["responses"] ?? 0) > 0,
    );
    $normalisedWeights = normaliseCategoryWeights(
        array_column($activeRows, "weight"),
    );
    $activeIndex = 0;
    foreach ($rows as &$row) {
        $row["weight"] = floatval($row["weight"]);
        $hasResponses = intval($row["responses"] ?? 0) > 0;
        $row["normalised_weight"] = $hasResponses
            ? round($normalisedWeights[$activeIndex++], 2)
            : 0;
        $row["avg_rating"] = number_format(floatval($row["avg_rating"]), 2);
        $row["responses"] = intval($row["responses"]);
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
function calcWeightedScoreByProgram(
    mysqli $conn,
    int $faculty_id,
    string $program_name,
    ?int $subject_id = null,
    ?int $class_id = null,
    ?int $period_id = null,
): float {
    $context = evaluationContextFilterSql(
        "e",
        $subject_id,
        $class_id,
        $period_id,
    );
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
                                     {$context["sql"]}
                                     AND EXISTS (
                                         SELECT 1
                                         FROM add_students s
                                         INNER JOIN add_programs p
                                             ON TRIM(p.program_code) = TRIM(s.program)
                                             OR CAST(p.id AS CHAR) = TRIM(s.program)
                                         WHERE s.id = e.student_id
                                           AND p.program_name = ?
                                     )
        GROUP BY c.id, c.weight
        ORDER BY c.section_number ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0.0;
    }
    bindDynamicParams(
        $stmt,
        "i" . $context["types"] . "s",
        array_merge([$faculty_id], $context["params"], [$program_name]),
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        if ($row["cat_avg"] === null) {
            continue;
        }
        $rows[] = [
            "weight" => floatval($row["cat_weight"]),
            "avg" => floatval($row["cat_avg"]),
        ];
    }

    if (empty($rows)) {
        return 0.0;
    }

    $normalisedWeights = normaliseCategoryWeights(
        array_column($rows, "weight"),
    );

    $weighted = 0.0;
    foreach ($rows as $index => $row) {
        $weighted += $row["avg"] * ($normalisedWeights[$index] / 100.0);
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
function getCategoryStatsByProgram(
    mysqli $conn,
    int $faculty_id,
    string $program_name,
    ?int $subject_id = null,
    ?int $class_id = null,
    ?int $period_id = null,
): array {
    $context = evaluationContextFilterSql(
        "e",
        $subject_id,
        $class_id,
        $period_id,
    );
    $sql = "
        SELECT
            c.id                                                                    AS cat_id,
            c.category_name,
            COALESCE(c.weight, 0)                                                  AS weight,
            COUNT(DISTINCT CASE WHEN e.id IS NOT NULL THEN e.id END)               AS responses,
            COALESCE(AVG(CASE WHEN e.id IS NOT NULL THEN ea.rating END), 0)        AS avg_rating
        FROM add_categories c
        LEFT JOIN add_questions q    ON q.category_id = c.id
        LEFT JOIN evaluation_answers ea ON ea.question_id = q.id
        LEFT JOIN evaluations e      ON e.id = ea.evaluation_id
                                     AND e.faculty_id = ?
                                     {$context["sql"]}
                                     AND EXISTS (
                                         SELECT 1
                                         FROM add_students s
                                         INNER JOIN add_programs p
                                             ON TRIM(p.program_code) = TRIM(s.program)
                                             OR CAST(p.id AS CHAR) = TRIM(s.program)
                                         WHERE s.id = e.student_id
                                           AND p.program_name = ?
                                     )
        GROUP BY c.id, c.category_name, c.weight
        ORDER BY c.section_number ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    bindDynamicParams(
        $stmt,
        "i" . $context["types"] . "s",
        array_merge([$faculty_id], $context["params"], [$program_name]),
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $activeRows = array_filter(
        $rows,
        static fn($row) => intval($row["responses"] ?? 0) > 0,
    );
    $normalisedWeights = normaliseCategoryWeights(
        array_column($activeRows, "weight"),
    );
    $activeIndex = 0;
    foreach ($rows as &$row) {
        $row["weight"] = floatval($row["weight"]);
        $hasResponses = intval($row["responses"] ?? 0) > 0;
        $row["normalised_weight"] = $hasResponses
            ? round($normalisedWeights[$activeIndex++], 2)
            : 0;
        $row["avg_rating"] = number_format(floatval($row["avg_rating"]), 2);
        $row["responses"] = intval($row["responses"]);
    }
    unset($row);

    return $rows;
}
