CREATE TABLE users (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(50) NOT NULL,
    `name` VARCHAR(20) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `is_admin` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `joined_at` TIMESTAMP NULL,
    CONSTRAINT `users_id_primary` PRIMARY KEY (`id`),
    CONSTRAINT `users_email_unique` UNIQUE (`email`)
) ENGINE = InnoDB;

CREATE TABLE boards (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `board_name` VARCHAR(32) NOT NULL,
    `last_number` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT `boards_id_primary` PRIMARY KEY (`id`)
) ENGINE = InnoDB;

CREATE TABLE articles (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `board_id` INT(11) UNSIGNED NOT NULL,
    `user_id` INT(11) UNSIGNED NOT NULL,
    `article_number` INT(11) UNSIGNED NOT NULL,
    `article_writer` VARCHAR(20) NOT NULL,
    `article_title` VARCHAR(255) NOT NULL,
    `article_contents` TEXT NOT NULL,
    `comment_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    CONSTRAINT `articles_id_primary` PRIMARY KEY (`id`),
    CONSTRAINT `articles_board_id_foreign` FOREIGN KEY (`board_id`) REFERENCES `boards` (`id`),
    CONSTRAINT `articles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE = InnoDB;

CREATE TABLE comments (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `article_id` INT(11) UNSIGNED NOT NULL,
    `user_id` INT(11) UNSIGNED NOT NULL,
    `comment_writer` VARCHAR(20) NOT NULL,
    `comment_contents` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    CONSTRAINT `comments_id_primary` PRIMARY KEY (`id`),
    CONSTRAINT `comments_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE = InnoDB;
