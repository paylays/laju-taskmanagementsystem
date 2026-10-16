
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `exports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `status` enum('pending','processing','done','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `filters` json DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `row_count` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exports_user_id_foreign` (`user_id`),
  CONSTRAINT `exports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `exports` WRITE;
/*!40000 ALTER TABLE `exports` DISABLE KEYS */;
/*!40000 ALTER TABLE `exports` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` VALUES (4,'2026_10_16_000001_create_tasks_table',1);
INSERT INTO `migrations` VALUES (5,'2026_10_17_000001_add_lane_sort_index_to_tasks',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `revoked_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `revoked_tokens` (
  `jti` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  PRIMARY KEY (`jti`),
  KEY `revoked_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `revoked_tokens` WRITE;
/*!40000 ALTER TABLE `revoked_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `revoked_tokens` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `task_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint unsigned NOT NULL,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint unsigned NOT NULL,
  `mime_type` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scan_status` enum('pending','clean','infected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `task_attachments_task_id_foreign` (`task_id`),
  KEY `task_attachments_uploaded_by_foreign` (`uploaded_by`),
  KEY `task_attachments_scan_status_index` (`scan_status`),
  CONSTRAINT `task_attachments_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `task_attachments` WRITE;
/*!40000 ALTER TABLE `task_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_attachments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `task_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `task_comments_user_id_foreign` (`user_id`),
  KEY `task_comments_task_id_created_at_index` (`task_id`,`created_at`),
  CONSTRAINT `task_comments_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `task_comments` WRITE;
/*!40000 ALTER TABLE `task_comments` DISABLE KEYS */;
INSERT INTO `task_comments` VALUES (1,4,2,'Found it. The rate comes from a config value that was never updated after April.','2026-09-29 08:33:02');
INSERT INTO `task_comments` VALUES (2,4,1,'Please add a test with a Bali address so this does not come back.','2026-09-29 09:33:02');
INSERT INTO `task_comments` VALUES (3,4,2,'Test added. Waiting on review.','2026-09-29 10:33:02');
INSERT INTO `task_comments` VALUES (4,5,3,'Two of the three jobs are moved. The report job needs a lock first.','2026-09-30 11:33:02');
INSERT INTO `task_comments` VALUES (5,6,5,'Going with GD for now. Imagick is not installed on web-02.','2026-10-01 12:33:02');
INSERT INTO `task_comments` VALUES (6,6,4,'Fine by me, as long as EXIF orientation is respected.','2026-10-01 13:33:02');
INSERT INTO `task_comments` VALUES (7,9,5,'Their v2 renames sku to item_code. That breaks our sync job.','2026-10-04 14:33:02');
INSERT INTO `task_comments` VALUES (8,9,1,'Let us ask them for a deprecation window before we change anything.','2026-10-04 15:33:02');
INSERT INTO `task_comments` VALUES (9,10,3,'Scheduled for Thursday night, low traffic window.','2026-10-05 16:33:02');
INSERT INTO `task_comments` VALUES (10,15,2,'EXPLAIN shows a full scan on product_tags. Adding a composite index.','2026-10-10 17:33:02');
INSERT INTO `task_comments` VALUES (11,15,5,'Search is down to 400ms on staging with the index.','2026-10-10 18:33:02');
INSERT INTO `task_comments` VALUES (12,1,3,'Done. Credentials are in the team vault.','2026-09-26 19:33:02');
INSERT INTO `task_comments` VALUES (13,3,4,'Confirmed fixed in production, thanks.','2026-09-28 20:33:02');
INSERT INTO `task_comments` VALUES (14,11,6,'I will pair with Dewi on this one, she knows the table component.','2026-10-06 21:33:02');
/*!40000 ALTER TABLE `task_comments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `task_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `task_events_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `task_events` WRITE;
/*!40000 ALTER TABLE `task_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_events` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('todo','in_progress','done') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'todo',
  `priority` enum('low','medium','high') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `assigned_user_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_created_by_foreign` (`created_by`),
  KEY `tasks_assigned_user_id_status_index` (`assigned_user_id`,`status`),
  KEY `tasks_status_due_date_index` (`status`,`due_date`),
  KEY `tasks_priority_index` (`priority`),
  KEY `tasks_created_at_index` (`created_at`),
  KEY `tasks_status_created_at_index` (`status`,`created_at`),
  CONSTRAINT `tasks_assigned_user_id_foreign` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (1,'Set up staging database','Provision MySQL 8 on the staging box and import last week\'s anonymised dump.','done','high',3,1,'2026-10-10','2026-09-26 05:33:02','2026-10-01 05:33:02');
INSERT INTO `tasks` VALUES (2,'Write onboarding checklist','One page for new hires: accounts, repo access, local setup.','done','low',6,1,'2026-10-13','2026-09-27 05:33:02','2026-10-02 05:33:02');
INSERT INTO `tasks` VALUES (3,'Fix password reset email','Reset link points to localhost in production. Check APP_URL on the mail worker.','done','high',2,4,'2026-10-14','2026-09-28 05:33:02','2026-10-03 05:33:02');
INSERT INTO `tasks` VALUES (4,'Invoice PDF shows wrong tax rate','Customers in Bali see 10% instead of 11%. Rate is hard-coded in InvoiceRenderer.','in_progress','high',2,1,'2026-10-17','2026-09-29 05:33:02','2026-10-04 05:33:02');
INSERT INTO `tasks` VALUES (5,'Migrate cron jobs to the scheduler','Move the three crontab entries on web-01 into routes/console.php.','in_progress','medium',3,3,'2026-10-20','2026-09-30 05:33:02','2026-10-05 05:33:02');
INSERT INTO `tasks` VALUES (6,'Compress product images on upload','Originals are 4 to 8 MB. Resize to 1600px max and keep the original in cold storage.','in_progress','medium',5,4,'2026-10-22','2026-10-01 05:33:02','2026-10-06 05:33:02');
INSERT INTO `tasks` VALUES (7,'Draft Q4 roadmap','Collect requests from support and sales, rank by effort.','in_progress','medium',1,1,'2026-10-25','2026-10-02 05:33:02','2026-10-07 05:33:02');
INSERT INTO `tasks` VALUES (8,'Add CSV import for customers','Sales wants to bulk import leads from trade fairs. Validate email and phone.','todo','medium',4,6,'2026-10-28','2026-10-03 05:33:02','2026-10-08 05:33:02');
INSERT INTO `tasks` VALUES (9,'Review warehouse API contract','Partner sent v2 of the spec. Check breaking changes against our client.','todo','high',5,1,'2026-10-19','2026-10-04 05:33:02','2026-10-09 05:33:02');
INSERT INTO `tasks` VALUES (10,'Rotate S3 access keys','Keys are 14 months old. Rotate and update the secret store.','todo','high',3,1,'2026-10-18','2026-10-05 05:33:02','2026-10-10 05:33:02');
INSERT INTO `tasks` VALUES (11,'Mobile layout for order history','Table overflows below 400px. Switch to stacked rows.','todo','medium',6,4,'2026-10-26','2026-10-06 05:33:02','2026-10-11 05:33:02');
INSERT INTO `tasks` VALUES (12,'Clean up unused feature flags','At least 9 flags have been 100% on for months.','todo','low',NULL,2,NULL,'2026-10-07 05:33:02','2026-10-12 05:33:02');
INSERT INTO `tasks` VALUES (13,'Load test checkout','Target 200 concurrent sessions. Use k6 against staging.','todo','medium',5,3,'2026-10-31','2026-10-08 05:33:02','2026-10-13 05:33:02');
INSERT INTO `tasks` VALUES (14,'Update privacy policy page','Legal sent new wording for the data retention section.','todo','low',6,1,'2026-11-05','2026-10-09 05:33:02','2026-10-14 05:33:02');
INSERT INTO `tasks` VALUES (15,'Investigate slow search','Product search takes 3s with more than 2 filters. Check missing indexes.','todo','high',2,5,'2026-10-21','2026-10-10 05:33:02','2026-10-15 05:33:02');
INSERT INTO `tasks` VALUES (16,'Archive old support tickets','Tickets closed before 2025 can move to the archive table.','todo','low',NULL,4,NULL,'2026-10-11 05:33:02','2026-10-16 05:33:02');
INSERT INTO `tasks` VALUES (17,'Write release notes for 2.4',NULL,'todo','low',4,1,'2026-10-23','2026-10-12 05:33:02','2026-10-16 05:33:02');
INSERT INTO `tasks` VALUES (18,'Replace deprecated payment SDK','Vendor ends support for v3 at the end of the quarter.','todo','high',3,2,'2026-11-03','2026-10-13 05:33:02','2026-10-16 05:33:02');
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','member') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin Lajur','admin@lajur.test','$2y$12$HK9bcDnegr.TpU.K2hwbdee6irqqV7ng9HoJlnF9wIOZgQt1gaZ0C','admin','2026-10-16 05:33:02','2026-10-16 05:33:02');
INSERT INTO `users` VALUES (2,'Rina Pratama','rina@lajur.test','$2y$12$HK9bcDnegr.TpU.K2hwbdee6irqqV7ng9HoJlnF9wIOZgQt1gaZ0C','member','2026-10-16 05:33:02','2026-10-16 05:33:02');
INSERT INTO `users` VALUES (3,'Bagus Santoso','bagus@lajur.test','$2y$12$HK9bcDnegr.TpU.K2hwbdee6irqqV7ng9HoJlnF9wIOZgQt1gaZ0C','member','2026-10-16 05:33:02','2026-10-16 05:33:02');
INSERT INTO `users` VALUES (4,'Dewi Kartika','dewi@lajur.test','$2y$12$HK9bcDnegr.TpU.K2hwbdee6irqqV7ng9HoJlnF9wIOZgQt1gaZ0C','member','2026-10-16 05:33:02','2026-10-16 05:33:02');
INSERT INTO `users` VALUES (5,'Fajar Nugroho','fajar@lajur.test','$2y$12$HK9bcDnegr.TpU.K2hwbdee6irqqV7ng9HoJlnF9wIOZgQt1gaZ0C','member','2026-10-16 05:33:02','2026-10-16 05:33:02');
INSERT INTO `users` VALUES (6,'Sari Wulandari','sari@lajur.test','$2y$12$HK9bcDnegr.TpU.K2hwbdee6irqqV7ng9HoJlnF9wIOZgQt1gaZ0C','member','2026-10-16 05:33:02','2026-10-16 05:33:02');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

