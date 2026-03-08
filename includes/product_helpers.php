<?php
declare(strict_types=1);

function lf_product_image_url(?string $image): string
{
    $image = trim((string)$image);
    if ($image === '') {
        return 'images/no-image.png';
    }

    $adminPath = __DIR__ . '/../admin/uploads/' . $image;
    if (is_file($adminPath)) {
        return 'admin/uploads/' . rawurlencode($image);
    }

    $publicPath = __DIR__ . '/../images/' . $image;
    if (is_file($publicPath)) {
        return 'images/' . rawurlencode($image);
    }

    return 'images/no-image.png';
}

/**
 * @return array<int, array<string, mixed>>
 */
function lf_products_optional_columns(mysqli $conn): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $cache = [
        'net_content' => true,
        'packaging' => true,
        'description' => true,
    ];

    $dbRes = $conn->query("SELECT DATABASE() AS db");
    $dbRow = $dbRes ? $dbRes->fetch_assoc() : null;
    $dbName = $dbRow['db'] ?? null;
    if (!$dbName) {
        return $cache;
    }

    $stmt = $conn->prepare("
        SELECT column_name
        FROM information_schema.columns
        WHERE table_schema = ? AND table_name = 'products'
          AND column_name IN ('net_content','packaging','description')
    ");
    $stmt->bind_param("s", $dbName);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $col = $row['column_name'] ?? '';
        if ($col !== '' && array_key_exists($col, $cache)) {
            $cache[$col] = true;
        }
    }

    return $cache;
}

/**
 * @return array<int, array<string, mixed>>
 */
function lf_fetch_products_by_category(mysqli $conn, string $categoryName, int $limit = 24): array
{
    $limit = max(1, min(60, $limit));

    $cols = [
        'net_content' => true, 
        'packaging' => true, 
        'description' => true
    ];

    $cols = lf_products_optional_columns($conn);
    $net = $cols['net_content'] ? 'p.net_content' : "NULL AS net_content";
    $pack = $cols['packaging'] ? 'p.packaging' : "NULL AS packaging";
    $desc = $cols['description'] ? 'p.description' : "NULL AS description";

    $sql = "
        SELECT
            p.product_id,
            p.product_name,
            p.price,
            p.stock,
            p.image,
            $net,
            $pack,
            $desc,
            c.category_name,
            pr.province_name
        FROM products p
        INNER JOIN categories c ON p.category_id = c.category_id
        INNER JOIN provinces pr ON p.province_id = pr.province_id
        WHERE c.category_name = ?
        AND p.status = 'Active'
        ORDER BY p.created_at DESC
        LIMIT $limit
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $categoryName);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Bestsellers based on order_items total quantity; falls back to newest products.
 *
 * @return array<int, array<string, mixed>>
 */
function lf_fetch_bestsellers(mysqli $conn, int $limit = 24): array
{
    $limit = max(1, min(60, $limit));

     $cols = [
        'net_content' => true, 
        'packaging' => true, 
        'description' => true
    ];

    $cols = lf_products_optional_columns($conn);
    $net = $cols['net_content'] ? 'p.net_content' : "NULL AS net_content";
    $pack = $cols['packaging'] ? 'p.packaging' : "NULL AS packaging";
    $desc = $cols['description'] ? 'p.description' : "NULL AS description";

    $sql = "
        SELECT
            p.product_id,
            p.product_name,
            p.price,
            p.stock,
            p.image,
            $net,
            $pack,
            $desc,
            c.category_name,
            pr.province_name,
            COALESCE(SUM(oi.quantity), 0) AS sold_qty
        FROM products p
        INNER JOIN categories c ON p.category_id = c.category_id
        INNER JOIN provinces pr ON p.province_id = pr.province_id
        LEFT JOIN order_items oi ON oi.product_id = p.product_id
        GROUP BY p.product_id
        ORDER BY sold_qty DESC, p.created_at DESC
        LIMIT $limit
    ";

    $res = $conn->query($sql);
    if ($res === false) {
        return [];
    }

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}
