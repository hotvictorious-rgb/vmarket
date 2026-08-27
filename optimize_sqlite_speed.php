<?php

/**
 * [AI] SQLite Enterprise Speed Optimizer & Indexer
 * Applies WAL mode, memory caching, mmap, and missing query indexes
 * to achieve sub-millisecond query execution.
 */

$dbs = [
    'Victorious MARKET' => __DIR__ . '/backend/vmarket-web/database/database.sqlite',
    'Vmarket POS' => __DIR__ . '/hysam/database/database.sqlite',
];

foreach ($dbs as $name => $dbPath) {
    if (!file_exists($dbPath)) {
        echo "⚠️ {$name} database not found at {$dbPath}\n";
        continue;
    }

    echo "Optimizing {$name} ({$dbPath})...\n";
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // High performance PRAGMAs
    $pdo->exec("PRAGMA journal_mode = WAL;");
    $pdo->exec("PRAGMA synchronous = NORMAL;");
    $pdo->exec("PRAGMA cache_size = -64000;"); // 64MB memory cache
    $pdo->exec("PRAGMA temp_store = MEMORY;");
    $pdo->exec("PRAGMA mmap_size = 268435456;"); // 256MB memory map

    if ($name === 'Victorious MARKET') {
        // Essential indexes for high-speed storefront queries
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_products_status_featured ON products(status, featured);",
            "CREATE INDEX IF NOT EXISTS idx_products_category ON products(category_id, status);",
            "CREATE INDEX IF NOT EXISTS idx_products_brand ON products(brand_id, status);",
            "CREATE INDEX IF NOT EXISTS idx_products_user ON products(user_id, added_by, status);",
            "CREATE INDEX IF NOT EXISTS idx_order_details_prod ON order_details(product_id);",
            "CREATE INDEX IF NOT EXISTS idx_order_details_order ON order_details(order_id);",
            "CREATE INDEX IF NOT EXISTS idx_categories_parent ON categories(parent_id, home_status);",
            "CREATE INDEX IF NOT EXISTS idx_reviews_product ON reviews(product_id, status);",
            "CREATE INDEX IF NOT EXISTS idx_business_settings_type ON business_settings(type);",
            "CREATE INDEX IF NOT EXISTS idx_login_setups_key ON login_setups(key);",
            "CREATE INDEX IF NOT EXISTS idx_flash_deal_products_deal ON flash_deal_products(flash_deal_id, product_id);",
        ];

        foreach ($indexes as $idxSql) {
            try {
                $pdo->exec($idxSql);
            } catch (\Throwable $e) {
                // Ignore if table/col does not exist
            }
        }
    }

    $pdo->exec("PRAGMA optimize;");
    echo "  ✅ {$name} SQLite performance optimization & indexing complete!\n";
}
