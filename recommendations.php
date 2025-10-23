<?php
// recommendations.php
// Lightweight recommendation helpers using existing tables only.
// Provides:
// - getContentBasedRecommendations($conn, $productId, $limit)
// - getCollaborativeRecommendationsByUser($conn, $userId, $limit)

if (!function_exists('getContentBasedRecommendations')) {
    function getContentBasedRecommendations(mysqli $conn, int $productId, int $limit = 8): array {
        $products = [];

        // Fetch seed product's attributes
        $stmt = $conn->prepare("SELECT id, name, brand, type FROM products WHERE id = ? LIMIT 1");
        if (!$stmt) return $products;
        $stmt->bind_param('i', $productId);
        if (!$stmt->execute()) return $products;
        $seed = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$seed) return $products;

        $brand = $seed['brand'] ?? null;
        $type  = $seed['type'] ?? null;
        if ($brand === null && $type === null) return $products;

        // Fetch all other products with aggregated ratings once; compute match score in PHP.
        $all = [];
        $sqlAll = "
            SELECT p.id, p.name, p.price, p.image, p.brand, p.type,
                   COALESCE(AVG(r.rating), 0) AS avg_rating,
                   COUNT(r.id) AS rating_count
            FROM products p
            LEFT JOIN reviews r ON r.product_id = p.id
            WHERE p.id <> ?
            GROUP BY p.id
        ";
        if ($stmt = $conn->prepare($sqlAll)) {
            $stmt->bind_param('i', $productId);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) { $all[] = $row; }
            }
            $stmt->close();
        }

        foreach ($all as $row) {
            $score = 0;
            $rowBrand = $row['brand'] ?? null;
            $rowType  = $row['type'] ?? null;
            if ($rowBrand !== null && $rowType !== null && $brand !== null && $type !== null && $rowBrand === $brand && $rowType === $type) {
                $score += 2; // exact brand+type
            } else {
                if ($brand !== null && $rowBrand === $brand) $score += 1; // brand-only
                if ($type  !== null && $rowType  === $type)  $score += 1; // type-only
            }
            if ($score <= 0) continue;
            $avg = (float)($row['avg_rating'] ?? 0);
            $cnt = (int)($row['rating_count'] ?? 0);
            $score += ($avg * 0.5) + ($cnt * 0.05);
            $row['match_score'] = $score;
            $products[] = $row;
        }

        usort($products, function($a, $b) {
            if ($b['match_score'] == $a['match_score']) {
                return $b['id'] <=> $a['id'];
            }
            return $b['match_score'] <=> $a['match_score'];
        });

        return array_slice($products, 0, $limit);
    }
}

// User-provided collaborative filtering helpers
if (!function_exists('getUserRatingsMatrix')) {
    function getUserRatingsMatrix($conn) {
        $sql = "SELECT user_id, product_id, rating FROM reviews";
        $result = $conn->query($sql);

        $ratings = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $ratings[$row['user_id']][$row['product_id']] = $row['rating'];
            }
        }
        return $ratings;
    }
}

if (!function_exists('pearsonCorrelation')) {
    function pearsonCorrelation($ratings1, $ratings2) {
        $common = array_intersect_key($ratings1, $ratings2);
        $n = count($common);
        if ($n == 0) return 0;

        $sum1 = array_sum(array_intersect_key($ratings1, $common));
        $sum2 = array_sum(array_intersect_key($ratings2, $common));

        $sum1Sq = array_sum(array_map(fn($x) => $x * $x, array_intersect_key($ratings1, $common)));
        $sum2Sq = array_sum(array_map(fn($x) => $x * $x, array_intersect_key($ratings2, $common)));

        $pSum = 0;
        foreach ($common as $pid => $_) {
            $pSum += $ratings1[$pid] * $ratings2[$pid];
        }

        $num = $pSum - (($sum1 * $sum2) / $n);
        $den = sqrt(($sum1Sq - pow($sum1, 2) / $n) * ($sum2Sq - pow($sum2, 2) / $n));

        return ($den == 0) ? 0 : $num / $den;
    }
}

if (!function_exists('getCollaborativeRecommendationsByUser')) {
    function getCollaborativeRecommendationsByUser(mysqli $conn, int $userId, int $limit = 8): array {
        // Implemented exactly as per the user-provided algorithm
        $ratingsMatrix = getUserRatingsMatrix($conn);

        if (!isset($ratingsMatrix[$userId])) {
            return [];
        }

        $scores = [];
        $similaritySums = [];

        foreach ($ratingsMatrix as $otherUserId => $otherRatings) {
            if ($otherUserId == $userId) continue;

            $sim = pearsonCorrelation($ratingsMatrix[$userId], $otherRatings);
            if ($sim <= 0) continue;

            foreach ($otherRatings as $productId => $rating) {
                if (!isset($ratingsMatrix[$userId][$productId])) {
                    // Weighted sum of rating × similarity
                    $scores[$productId] = ($scores[$productId] ?? 0) + $rating * $sim;
                    $similaritySums[$productId] = ($similaritySums[$productId] ?? 0) + $sim;
                }
            }
        }

        // Final predicted ratings
        $predictions = [];
        foreach ($scores as $productId => $score) {
            $predictions[$productId] = $score / $similaritySums[$productId];
        }

        // Sort highest predicted rating first
        arsort($predictions);

        // Fetch product details (one-by-one, as provided) and filter by rating >= 3
        $recommendedProducts = [];
        foreach (array_keys($predictions) as $productId) {
            $sql = "
                SELECT p.id, p.name, p.price, p.image, p.brand, p.type,
                       COALESCE(AVG(r.rating), 0) AS avg_rating,
                       COUNT(r.id) AS rating_count
                FROM products p
                LEFT JOIN reviews r ON r.product_id = p.id
                WHERE p.id = ?
                GROUP BY p.id
            ";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $productId);
                if ($stmt->execute()) {
                    $res = $stmt->get_result()->fetch_assoc();
                    if ($res) {
                        $avg = (float)($res['avg_rating'] ?? 0);
                        $cnt = (int)($res['rating_count'] ?? 0);
                        // Exclude items with average rating < 3 if they have ratings
                        if ($cnt > 0 && $avg < 3.0) {
                            // skip low-rated
                        } else {
                            // keep (also keep unrated items)
                            unset($res['avg_rating'], $res['rating_count']);
                            $recommendedProducts[] = $res;
                        }
                    }
                }
                $stmt->close();
            }
            if (count($recommendedProducts) >= $limit) break; // respect limit
        }

        return $recommendedProducts;
    }
}

if (!function_exists('getPurchaseBasedRecommendationsByUser')) {
    // Derive preferences from user's purchases by parsing orders.total_products
    function getPurchaseBasedRecommendationsByUser(mysqli $conn, int $userId, int $limit = 8, ?int $currentProductId = null): array {
        $recommended = [];
        $currentType = null;
        // 0) If a current product context is provided, fetch its type and exclude the current product
        if ($currentProductId !== null) {
            if ($stmt = $conn->prepare("SELECT type FROM products WHERE id = ? LIMIT 1")) {
                $stmt->bind_param('i', $currentProductId);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    if ($row = $res->fetch_assoc()) {
                        $t = trim((string)($row['type'] ?? ''));
                        if ($t !== '') { $currentType = $t; }
                    }
                }
                $stmt->close();
            }
            // Ensure the current product never appears in recommendations
            if (!isset($exclude)) { $exclude = []; }
            $exclude[(int)$currentProductId] = true;
        }

        // 1) Collect purchased product names and quantities from orders.total_products
        //    Aggregate counts so frequently bought items drive preferences.
        $nameQty = [];
        if ($stmt = $conn->prepare("SELECT total_products FROM orders WHERE user_id = ?")) {
            $stmt->bind_param('i', $userId);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $tp = (string)$row['total_products'];
                    // Normalize separators and split on comma/semicolon/newline
                    $parts = preg_split('/[;\,\n]+/', $tp);
                    foreach ($parts as $chunk) {
                        $chunk = trim($chunk);
                        if ($chunk === '') continue;
                        // Extract quantity markers like "(2)" or "x2"; default qty=1
                        $qty = 1;
                        if (preg_match('/\((\d+)\)/', $chunk, $m)) {
                            $qty = max(1, (int)$m[1]);
                        } elseif (preg_match('/\bx\s*(\d+)\b/i', $chunk, $m)) {
                            $qty = max(1, (int)$m[1]);
                        }
                        // Remove quantity tokens and price fragments to isolate name
                        $clean = preg_replace([
                            '/\s*\(\d+\)\s*/',   // (2)
                            '/\bx\s*\d+\b/i',      // x2
                            '/-\s*\d+[\.,]?\d*/',  // - 500
                            '/Rs\s*\d+[\.,]?\d*/i' // Rs 500
                        ], '', $chunk);
                        $clean = trim($clean, " -\t\r\n");
                        if ($clean !== '') {
                            $nameQty[$clean] = ($nameQty[$clean] ?? 0) + $qty;
                        }
                    }
                }
            }
            $stmt->close();
        }

        if (empty($nameQty)) return $recommended;

        // 2) Map names -> brand/type using products table and collect purchased IDs to exclude
        $nameMeta = [];
        $exclude = [];
        foreach ($nameQty as $nm => $qty) {
            $nm = trim($nm);
            if ($nm === '') continue;
            // Try exact name match first
            $brand = '';
            $type  = '';
            $foundId = null;
            if ($stmt = $conn->prepare("SELECT id, brand, type FROM products WHERE name = ? LIMIT 1")) {
                $stmt->bind_param('s', $nm);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    if ($row = $res->fetch_assoc()) {
                        $foundId = isset($row['id']) ? (int)$row['id'] : null;
                        $brand = $row['brand'] ?? '';
                        $type  = $row['type'] ?? '';
                    }
                }
                $stmt->close();
            }
            // If not found, try LIKE as fallback
            if ($foundId === null && $brand === '' && $type === '' && ($stmt = $conn->prepare("SELECT id, brand, type FROM products WHERE name LIKE CONCAT('%', ?, '%') LIMIT 1"))) {
                $stmt->bind_param('s', $nm);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    if ($row = $res->fetch_assoc()) {
                        $foundId = isset($row['id']) ? (int)$row['id'] : null;
                        $brand = $row['brand'] ?? '';
                        $type  = $row['type'] ?? '';
                    }
                }
                $stmt->close();
            }
            if ($brand !== '' || $type !== '') {
                // Keep meta for this purchased name
                $nameMeta[$nm] = [
                    'qty' => max(1, (int)$qty),
                    'brand' => $brand,
                    'type'  => $type,
                    'id'    => $foundId,
                ];
            }
            if ($foundId !== null) {
                $exclude[$foundId] = true; // exclude purchased products from recommendations
            }
        }

        if (empty($nameMeta)) return $recommended;

        // 3) Determine the single most purchased product (by aggregated quantity)

        $topBrand = null; $topType = null;
        if (!empty($nameMeta)) {
            uasort($nameMeta, function($a, $b) { return $b['qty'] <=> $a['qty']; });
            $first = reset($nameMeta);
            if (!empty($first['type']))  $topType  = trim($first['type']);
            if (!empty($first['brand'])) $topBrand = trim($first['brand']);
        }

        // 4) Priority 1: Recommend by TYPE of the CURRENT product (if provided)
        if (!isset($seen)) { $seen = []; }
        if (count($recommended) < $limit && $currentType) {
            $q = "
                SELECT p.id, p.name, p.price, p.image,
                       p.brand, p.type,
                       COALESCE(AVG(r.rating), 0) AS avg_rating,
                       COUNT(r.id) AS rating_count
                FROM products p
                LEFT JOIN reviews r ON r.product_id = p.id
                WHERE (LOWER(TRIM(p.type)) = LOWER(?) OR LOWER(p.type) LIKE CONCAT('%', LOWER(?), '%'))
                GROUP BY p.id
                ORDER BY avg_rating DESC, rating_count DESC, p.id DESC
                LIMIT ?
            ";
            $take = min(24, max(8, $limit));
            if ($stmt = $conn->prepare($q)) {
                $stmt->bind_param('ssi', $currentType, $currentType, $take);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_assoc()) {
                        $pid = (int)$row['id'];
                        if (isset($exclude[$pid]) || isset($seen[$pid])) continue;
                        $recommended[] = $row;
                        $seen[$pid] = true;
                        if (count($recommended) >= $limit) break;
                    }
                }
                $stmt->close();
            }
        }

        // 5) Priority 2: Recommend by TYPE of the most purchased product (exclude purchased and seen)
        if (count($recommended) < $limit && $topType) {
            $q = "
                SELECT p.id, p.name, p.price, p.image,
                       p.brand, p.type,
                       COALESCE(AVG(r.rating), 0) AS avg_rating,
                       COUNT(r.id) AS rating_count
                FROM products p
                LEFT JOIN reviews r ON r.product_id = p.id
                WHERE LOWER(TRIM(p.type)) = LOWER(?) OR LOWER(p.type) LIKE CONCAT('%', LOWER(?), '%')
                GROUP BY p.id
                ORDER BY avg_rating DESC, rating_count DESC, p.id DESC
                LIMIT ?
            ";
            $take = min(24, max(8, $limit));
            if ($stmt = $conn->prepare($q)) {
                $stmt->bind_param('ssi', $topType, $topType, $take);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_assoc()) {
                        $pid = (int)$row['id'];
                        if (isset($exclude[$pid]) || isset($seen[$pid])) continue;
                        $recommended[] = $row;
                        $seen[$pid] = true;
                        if (count($recommended) >= $limit) break;
                    }
                }
                $stmt->close();
            }
        }

        if (count($recommended) < $limit && $topBrand) {
            $q = "
                SELECT p.id, p.name, p.price, p.image,
                       p.brand, p.type,
                       COALESCE(AVG(r.rating), 0) AS avg_rating,
                       COUNT(r.id) AS rating_count
                FROM products p
                LEFT JOIN reviews r ON r.product_id = p.id
                WHERE LOWER(TRIM(p.brand)) = LOWER(?)
                GROUP BY p.id
                ORDER BY avg_rating DESC, rating_count DESC, p.id DESC
                LIMIT ?
            ";
            $take = min(24, max(8, $limit));
            if ($stmt = $conn->prepare($q)) {
                $stmt->bind_param('si', $topBrand, $take);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_assoc()) {
                        $pid = (int)$row['id'];
                        if (isset($exclude[$pid]) || isset($seen[$pid])) continue;
                        $recommended[] = $row;
                        $seen[$pid] = true;
                        if (count($recommended) >= $limit) break;
                    }
                }
                $stmt->close();
            }
        }

        // Strict behavior: TYPE of current product first (if provided), then most purchased TYPE, then most purchased BRAND.
        return array_slice($recommended, 0, $limit);
    }
}
