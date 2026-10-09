<?php

function ensureUserRatingsTable(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS user_ratings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            purchase_id INT UNSIGNED NOT NULL,
            reviewer_id INT UNSIGNED NOT NULL,
            reviewee_id INT UNSIGNED NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            review VARCHAR(500) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_rating_purchase_reviewer (purchase_id, reviewer_id),
            INDEX idx_rating_reviewee (reviewee_id, created_at),
            CONSTRAINT fk_rating_purchase FOREIGN KEY (purchase_id)
                REFERENCES purchases(id) ON DELETE CASCADE,
            CONSTRAINT fk_rating_reviewer FOREIGN KEY (reviewer_id)
                REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_rating_reviewee FOREIGN KEY (reviewee_id)
                REFERENCES users(id) ON DELETE CASCADE,
            CHECK (rating BETWEEN 1 AND 5)
        ) ENGINE=InnoDB"
    );
}