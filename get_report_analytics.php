<?php
require_once 'security.php';
requireRole('admin');
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

include 'connect.php';
require_once 'weighted_score_helper.php';
require_once 'evaluation_schema.php';

try {
    ensureEvaluationsSchema($conn);

    $academicYear = trim((string)($_GET['academic_year'] ?? ''));
    $semester = trim((string)($_GET['semester'] ?? ''));
    $where = [];
    $params = [];
    $types = '';

    if ($academicYear !== '') {
        $where[] = 'TRIM(ep.ay) = ?';
        $params[] = $academicYear;
        $types .= 's';
    }
    if ($semester !== '') {
        $where[] = 'LOWER(TRIM(ep.semester)) = LOWER(?)';
        $params[] = $semester;
        $types .= 's';
    }

    $query = "SELECT e.overall_rating, e.created_at, e.faculty_id,
                     COALESCE(e.period_id,
                         (SELECT ep2.id
                          FROM evaluation_periods ep2
                          WHERE DATE(e.created_at) BETWEEN ep2.start_date AND ep2.end_date
                          ORDER BY ep2.id DESC
                          LIMIT 1)) AS resolved_period_id,
                     f.firstname, f.lastname, f.suffix,
                     p.program_name, ep.ay AS academic_year, ep.semester
              FROM evaluations e
              INNER JOIN add_faculties f ON f.id = e.faculty_id
              LEFT JOIN add_subjects s ON s.id = e.subject_id
              LEFT JOIN add_programs p ON p.id = s.program_id
              LEFT JOIN evaluation_periods ep
                                ON ep.id = COALESCE(
                                        e.period_id,
                                        (SELECT ep2.id
                                         FROM evaluation_periods ep2
                                         WHERE DATE(e.created_at) BETWEEN ep2.start_date AND ep2.end_date
                                         ORDER BY ep2.id DESC
                                         LIMIT 1)
                                )";
    if ($where) {
        $query .= ' WHERE ' . implode(' AND ', $where);
    }
    $query .= ' ORDER BY ep.ay ASC, ep.semester ASC, e.created_at ASC';

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Unable to prepare report analytics query.');
    }
    if ($types !== '') {
        bindDynamicParams($stmt, $types, $params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $distribution = ['Excellent' => 0, 'Very Good' => 0, 'Good' => 0, 'Fair' => 0, 'Poor' => 0];
    $programs = [];
    $trend = [];
    $faculty = [];
    $facultyPeriodIds = [];
    $total = 0;

    while ($row = $result->fetch_assoc()) {
        $score = (float)$row['overall_rating'];
        $facultyId = intval($row['faculty_id']);
        $name = trim($row['firstname'] . ' ' . $row['lastname'] . ' ' . $row['suffix']);
        if (!isset($faculty[$facultyId])) {
            $faculty[$facultyId] = [
                'name' => $name,
                'period_ids' => []
            ];
        }
        $faculty[$facultyId]['period_ids'][intval($row['resolved_period_id'])] = true;
        if ($score <= 0) {
            continue;
        }
        $total++;
        $rating = $score >= 4.5 ? 'Excellent' : ($score >= 3.5 ? 'Very Good' : ($score >= 2.5 ? 'Good' : ($score >= 1.5 ? 'Fair' : 'Poor')));

        $facultyPeriodIds[$facultyId][intval($row['resolved_period_id'])] = true;

        $program = trim((string)($row['program_name'] ?? '')) ?: 'Unassigned Program';
        if (!isset($programs[$program])) $programs[$program] = ['sum' => 0, 'count' => 0];
        $programs[$program]['sum'] += $score;
        $programs[$program]['count']++;

        $period = trim((string)($row['academic_year'] ?? ''));
        if ($period !== '' && ($row['semester'] ?? '') !== '') $period .= ' - ' . $row['semester'];
        if ($period === '') $period = date('Y-m', strtotime($row['created_at']));
        if (!isset($trend[$period])) $trend[$period] = ['sum' => 0, 'count' => 0];
        $trend[$period]['sum'] += $score;
        $trend[$period]['count']++;

    }
    $stmt->close();

    $toAverages = static function (array $items): array {
        $output = [];
        foreach ($items as $label => $item) {
            $output[] = ['label' => $label, 'average' => round($item['sum'] / $item['count'], 2), 'responses' => $item['count']];
        }
        usort($output, static fn($a, $b) => $b['average'] <=> $a['average']);
        return $output;
    };

    $distribution = ['Excellent' => 0, 'Very Good' => 0, 'Good' => 0, 'Fair' => 0, 'Poor' => 0];
    $distributionFacultyCount = 0;
    foreach ($facultyPeriodIds as $facultyId => $periodIds) {
        $periodIds = array_values(array_filter(array_keys($periodIds), static fn($id) => $id > 0));
        $score = count($periodIds) === 1
            ? calcWeightedScore($conn, $facultyId, null, null, $periodIds[0])
            : calcWeightedScore($conn, $facultyId);
        if ($score <= 0) {
            continue;
        }

        $distributionFacultyCount++;
        $rating = $score >= 4.5 ? 'Excellent' : ($score >= 3.5 ? 'Very Good' : ($score >= 2.5 ? 'Good' : ($score >= 1.5 ? 'Fair' : 'Poor')));
        $distribution[$rating]++;
    }
    $total = $distributionFacultyCount;

    $programData = $toAverages($programs);
    $rankingData = [];
    foreach ($faculty as $facultyId => $facultyData) {
        $periodIds = array_values(array_filter(array_keys($facultyData['period_ids']), static fn($id) => $id > 0));
        $score = count($periodIds) === 1
            ? calcWeightedScore($conn, $facultyId, null, null, $periodIds[0])
            : calcWeightedScore($conn, $facultyId);
        if ($score > 0) {
            $rankingData[] = [
                'label' => $facultyData['name'],
                'average' => $score,
                'responses' => count($periodIds)
            ];
        }
    }
    usort($rankingData, static fn($a, $b) => $b['average'] <=> $a['average']);
    $trendData = $toAverages($trend);
    usort($trendData, static fn($a, $b) => strcmp($a['label'], $b['label']));

    echo json_encode([
        'success' => true,
        'data' => [
            'total' => $total,
            'distribution' => $distribution,
            'programs' => $programData,
            'trend' => $trendData,
            'rankings' => $rankingData
        ]
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching report analytics: ' . $e->getMessage()]);
}

$conn->close();
?>
