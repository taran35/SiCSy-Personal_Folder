<?php
function getExcludedUsernames(mysqli $mysqli, string $currentUserPseudo): array
{
    $excluded = [];

    $sql = "SELECT pseudo FROM users WHERE pseudo != ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        return $excluded;
    }
    $stmt->bind_param("s", $currentUserPseudo);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $excluded[] = $row['pseudo'];
    }

    $stmt->close();

    return $excluded;
}
