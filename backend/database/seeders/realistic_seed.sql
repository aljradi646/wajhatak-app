/* ============================================================
   وجهة | REALISTIC DATA SEED (VERSION FIXED)
   MariaDB 10.4+
   Database: luxs
   ============================================================ */

USE `luxs`;

SET NAMES utf8mb4;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

START TRANSACTION;

/* ============================================================
   1) تنظيف بيانات Seeder السابقة فقط (نطاقات محددة)
   ============================================================ */

DELETE FROM `messages`
WHERE `property_id` BETWEEN 11 AND 110;

DELETE FROM `viewing_requests`
WHERE `property_id` BETWEEN 11 AND 110;

DELETE FROM `favorites`
WHERE `property_id` BETWEEN 11 AND 110
   OR `user_id` BETWEEN 26 AND 55;

DELETE FROM `property_images`
WHERE `property_id` BETWEEN 11 AND 110;

DELETE FROM `property_feature`
WHERE `property_id` BETWEEN 11 AND 110;

DELETE FROM `conversations`
WHERE `property_id` BETWEEN 11 AND 110
   OR (`client_id` BETWEEN 26 AND 55 AND `agent_id` BETWEEN 16 AND 25);

DELETE FROM `properties`
WHERE `id` BETWEEN 11 AND 110;

DELETE FROM `saved_searches`
WHERE `user_id` BETWEEN 26 AND 55;

DELETE FROM `notifications`
WHERE `notifiable_type` = 'App\\Models\\User'
  AND `notifiable_id` BETWEEN 16 AND 55;

DELETE FROM `user_devices`
WHERE `user_id` BETWEEN 16 AND 55;

DELETE FROM `user_notification_preferences`
WHERE `user_id` BETWEEN 16 AND 55;

DELETE FROM `model_has_roles`
WHERE `model_type` = 'App\\Models\\User'
  AND `model_id` BETWEEN 16 AND 55;

DELETE FROM `model_has_permissions`
WHERE `model_type` = 'App\\Models\\User'
  AND `model_id` BETWEEN 16 AND 55;

DELETE FROM `agents`
WHERE `id` BETWEEN 5 AND 14
  AND `user_id` BETWEEN 16 AND 25;

DELETE FROM `users`
WHERE `id` BETWEEN 16 AND 55;

DELETE FROM `property_locations`
WHERE `id` BETWEEN 7 AND 106;

/* ============================================================
   2) بيانات أنواع العقارات (إضافة أنواع جديدة)
   ============================================================ */

INSERT INTO `property_types`
(
    `id`,
    `name_ar`,
    `name_en`,
    `slug`,
    `is_active`,
    `created_at`,
    `updated_at`
)
VALUES
(
    5,
    'بنتهاوس',
    'Penthouse',
    'penthouse',
    1,
    '2026-08-27 16:00:00',
    '2026-08-27 16:00:00'
),
(
    6,
    'أرض سكنية',
    'Residential Land',
    'residential-land',
    1,
    '2026-08-27 16:00:00',
    '2026-08-27 16:00:00'
)
ON DUPLICATE KEY UPDATE
    `name_ar` = VALUES(`name_ar`),
    `name_en` = VALUES(`name_en`),
    `slug` = VALUES(`slug`),
    `is_active` = 1,
    `updated_at` = VALUES(`updated_at`);

/* ============================================================
   3) ميزات إضافية (إضافة ميزات جديدة)
   ============================================================ */

INSERT INTO `property_features`
(
    `id`,
    `name_ar`,
    `name_en`,
    `slug`,
    `icon`,
    `is_active`,
    `created_at`,
    `updated_at`
)
VALUES
(
    6,
    'تكييف مركزي',
    'Central AC',
    'central-ac',
    'ac_unit',
    1,
    '2026-08-27 16:00:00',
    '2026-08-27 16:00:00'
),
(
    7,
    'مطبخ مجهز',
    'Fitted Kitchen',
    'fitted-kitchen',
    'kitchen',
    1,
    '2026-08-27 16:00:00',
    '2026-08-27 16:00:00'
),
(
    8,
    'شرفة',
    'Balcony',
    'balcony',
    'balcony',
    1,
    '2026-08-27 16:00:00',
    '2026-08-27 16:00:00'
),
(
    9,
    'غرفة خادمة',
    'Maid Room',
    'maid-room',
    'room_service',
    1,
    '2026-08-27 16:00:00',
    '2026-08-27 16:00:00'
),
(
    10,
    'نظام دخول ذكي',
    'Smart Entry',
    'smart-entry',
    'lock',
    1,
    '2026-08-27 16:00:00',
    '2026-08-27 16:00:00'
)
ON DUPLICATE KEY UPDATE
    `name_ar` = VALUES(`name_ar`),
    `name_en` = VALUES(`name_en`),
    `slug` = VALUES(`slug`),
    `icon` = VALUES(`icon`),
    `updated_at` = VALUES(`updated_at`);

/* ============================================================
   4) جدول أرقام مساعد (0..99) مع اسم مستعار مختلف
   ============================================================ */

CREATE TEMPORARY TABLE IF NOT EXISTS `seed_numbers`
(
    `num` INT NOT NULL PRIMARY KEY
);

INSERT INTO `seed_numbers` (`num`)
SELECT
    tens.num * 10 + ones.num
FROM
(
    SELECT 0 AS num
    UNION ALL SELECT 1
    UNION ALL SELECT 2
    UNION ALL SELECT 3
    UNION ALL SELECT 4
    UNION ALL SELECT 5
    UNION ALL SELECT 6
    UNION ALL SELECT 7
    UNION ALL SELECT 8
    UNION ALL SELECT 9
) tens
CROSS JOIN
(
    SELECT 0 AS num
    UNION ALL SELECT 1
    UNION ALL SELECT 2
    UNION ALL SELECT 3
    UNION ALL SELECT 4
    UNION ALL SELECT 5
    UNION ALL SELECT 6
    UNION ALL SELECT 7
    UNION ALL SELECT 8
    UNION ALL SELECT 9
) ones;

/* ============================================================
   5) المستخدمون - وكلاء + عملاء (نفس المحتوى السابق)
   ============================================================ */

INSERT INTO `users`
(
    `id`,
    `name`,
    `email`,
    `phone`,
    `email_verified_at`,
    `password`,
    `avatar_path`,
    `locale`,
    `is_active`,
    `remember_token`,
    `created_at`,
    `updated_at`
)
VALUES

/* ==================== AGENTS ==================== */

(
    16,
    'محمد عبدالله',
    'agent.mohammed@luxe.local',
    '+96777160001',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=12',
    'ar',
    1,
    NULL,
    '2026-08-10 09:00:00',
    '2026-08-27 10:00:00'
),
(
    17,
    'أحمد علي',
    'agent.ahmed@luxe.local',
    '+96777160002',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=13',
    'ar',
    1,
    NULL,
    '2026-08-11 09:00:00',
    '2026-08-27 10:00:00'
),
(
    18,
    'خالد محمد',
    'agent.khaled@luxe.local',
    '+96777160003',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=14',
    'ar',
    1,
    NULL,
    '2026-08-12 09:00:00',
    '2026-08-27 10:00:00'
),
(
    19,
    'عبدالله ياسر',
    'agent.abdullah@luxe.local',
    '+96777160004',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=15',
    'ar',
    1,
    NULL,
    '2026-08-13 09:00:00',
    '2026-08-27 10:00:00'
),
(
    20,
    'فهد ناصر',
    'agent.fahad@luxe.local',
    '+96777160005',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=16',
    'ar',
    1,
    NULL,
    '2026-08-14 09:00:00',
    '2026-08-27 10:00:00'
),
(
    21,
    'عمر سالم',
    'agent.omar@luxe.local',
    '+96777160006',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=17',
    'ar',
    1,
    NULL,
    '2026-08-15 09:00:00',
    '2026-08-27 10:00:00'
),
(
    22,
    'يحيى أحمد',
    'agent.yahya@luxe.local',
    '+96777160007',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=18',
    'ar',
    1,
    NULL,
    '2026-08-16 09:00:00',
    '2026-08-27 10:00:00'
),
(
    23,
    'وليد صالح',
    'agent.waleed@luxe.local',
    '+96777160008',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=19',
    'ar',
    1,
    NULL,
    '2026-08-17 09:00:00',
    '2026-08-27 10:00:00'
),
(
    24,
    'سامي حسن',
    'agent.sami@luxe.local',
    '+96777160009',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=20',
    'ar',
    1,
    NULL,
    '2026-08-18 09:00:00',
    '2026-08-27 10:00:00'
),
(
    25,
    'مازن خالد',
    'agent.mazen@luxe.local',
    '+96777160010',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=21',
    'ar',
    1,
    NULL,
    '2026-08-19 09:00:00',
    '2026-08-27 10:00:00'
),

/* ==================== CLIENTS ==================== */

(
    26,
    'سارة محمد',
    'client.sara@luxe.local',
    '+96777260001',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=25',
    'ar',
    1,
    NULL,
    '2026-08-01 10:00:00',
    '2026-08-27 10:00:00'
),
(
    27,
    'نورة عبدالله',
    'client.nora@luxe.local',
    '+96777260002',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=26',
    'ar',
    1,
    NULL,
    '2026-08-02 10:00:00',
    '2026-08-27 10:00:00'
),
(
    28,
    'ريم أحمد',
    'client.reem@luxe.local',
    '+96777260003',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=27',
    'ar',
    1,
    NULL,
    '2026-08-03 10:00:00',
    '2026-08-27 10:00:00'
),
(
    29,
    'دانة علي',
    'client.dana@luxe.local',
    '+96777260004',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=28',
    'ar',
    1,
    NULL,
    '2026-08-04 10:00:00',
    '2026-08-27 10:00:00'
),
(
    30,
    'لمى خالد',
    'client.lama@luxe.local',
    '+96777260005',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=29',
    'ar',
    1,
    NULL,
    '2026-08-05 10:00:00',
    '2026-08-27 10:00:00'
),
(
    31,
    'جود سالم',
    'client.joud@luxe.local',
    '+96777260006',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=30',
    'ar',
    1,
    NULL,
    '2026-08-06 10:00:00',
    '2026-08-27 10:00:00'
),
(
    32,
    'ليان ياسر',
    'client.layan@luxe.local',
    '+96777260007',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=31',
    'ar',
    1,
    NULL,
    '2026-08-07 10:00:00',
    '2026-08-27 10:00:00'
),
(
    33,
    'شهد ناصر',
    'client.shahad@luxe.local',
    '+96777260008',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=32',
    'ar',
    1,
    NULL,
    '2026-08-08 10:00:00',
    '2026-08-27 10:00:00'
),
(
    34,
    'رنا صالح',
    'client.rana@luxe.local',
    '+96777260009',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=33',
    'ar',
    1,
    NULL,
    '2026-08-09 10:00:00',
    '2026-08-27 10:00:00'
),
(
    35,
    'هيا حسن',
    'client.haya@luxe.local',
    '+96777260010',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=34',
    'ar',
    1,
    NULL,
    '2026-08-10 10:00:00',
    '2026-08-27 10:00:00'
),
(
    36,
    'مريم محمد',
    'client.mariam@luxe.local',
    '+96777260011',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=35',
    'ar',
    1,
    NULL,
    '2026-08-11 10:00:00',
    '2026-08-27 10:00:00'
),
(
    37,
    'أروى علي',
    'client.arwa@luxe.local',
    '+96777260012',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=36',
    'ar',
    1,
    NULL,
    '2026-08-12 10:00:00',
    '2026-08-27 10:00:00'
),
(
    38,
    'روان عبدالله',
    'client.rawan@luxe.local',
    '+96777260013',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=37',
    'ar',
    1,
    NULL,
    '2026-08-13 10:00:00',
    '2026-08-27 10:00:00'
),
(
    39,
    'جنى أحمد',
    'client.jana@luxe.local',
    '+96777260014',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=38',
    'ar',
    1,
    NULL,
    '2026-08-14 10:00:00',
    '2026-08-27 10:00:00'
),
(
    40,
    'فرح سالم',
    'client.farah@luxe.local',
    '+96777260015',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=39',
    'ar',
    1,
    NULL,
    '2026-08-15 10:00:00',
    '2026-08-27 10:00:00'
),
(
    41,
    'ملاك حسن',
    'client.malak@luxe.local',
    '+96777260016',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=40',
    'ar',
    1,
    NULL,
    '2026-08-16 10:00:00',
    '2026-08-27 10:00:00'
),
(
    42,
    'حنين خالد',
    'client.haneen@luxe.local',
    '+96777260017',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=41',
    'ar',
    1,
    NULL,
    '2026-08-17 10:00:00',
    '2026-08-27 10:00:00'
),
(
    43,
    'آية ياسر',
    'client.aya@luxe.local',
    '+96777260018',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=42',
    'ar',
    1,
    NULL,
    '2026-08-18 10:00:00',
    '2026-08-27 10:00:00'
),
(
    44,
    'نورا فهد',
    'client.noura@luxe.local',
    '+96777260019',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=43',
    'ar',
    1,
    NULL,
    '2026-08-19 10:00:00',
    '2026-08-27 10:00:00'
),
(
    45,
    'ريهام محمد',
    'client.reham@luxe.local',
    '+96777260020',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=44',
    'ar',
    1,
    NULL,
    '2026-08-20 10:00:00',
    '2026-08-27 10:00:00'
),
(
    46,
    'عبدالملك علي',
    'client.abdulmalik@luxe.local',
    '+96777260021',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=45',
    'ar',
    1,
    NULL,
    '2026-08-20 11:00:00',
    '2026-08-27 10:00:00'
),
(
    47,
    'أنس عبدالله',
    'client.anas@luxe.local',
    '+96777260022',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=46',
    'ar',
    1,
    NULL,
    '2026-08-20 12:00:00',
    '2026-08-27 10:00:00'
),
(
    48,
    'معاذ أحمد',
    'client.muath@luxe.local',
    '+96777260023',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=47',
    'ar',
    1,
    NULL,
    '2026-08-20 13:00:00',
    '2026-08-27 10:00:00'
),
(
    49,
    'حمزة خالد',
    'client.hamza@luxe.local',
    '+96777260024',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=48',
    'ar',
    1,
    NULL,
    '2026-08-20 14:00:00',
    '2026-08-27 10:00:00'
),
(
    50,
    'زياد سالم',
    'client.ziad@luxe.local',
    '+96777260025',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=49',
    'ar',
    1,
    NULL,
    '2026-08-20 15:00:00',
    '2026-08-27 10:00:00'
),
(
    51,
    'رامي ناصر',
    'client.rami@luxe.local',
    '+96777260026',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=50',
    'ar',
    1,
    NULL,
    '2026-08-20 16:00:00',
    '2026-08-27 10:00:00'
),
(
    52,
    'يزن صالح',
    'client.yazan@luxe.local',
    '+96777260027',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=51',
    'ar',
    1,
    NULL,
    '2026-08-20 17:00:00',
    '2026-08-27 10:00:00'
),
(
    53,
    'باسل حسن',
    'client.bassel@luxe.local',
    '+96777260028',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=52',
    'ar',
    1,
    NULL,
    '2026-08-20 18:00:00',
    '2026-08-27 10:00:00'
),
(
    54,
    'كريم محمد',
    'client.karim@luxe.local',
    '+96777260029',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=53',
    'ar',
    1,
    NULL,
    '2026-08-20 19:00:00',
    '2026-08-27 10:00:00'
),
(
    55,
    'طارق علي',
    'client.tareq@luxe.local',
    '+96777260030',
    '2026-08-27 10:00:00',
    '$2y$12$xThNrmgMQVgaqyxEx.feg.T0OLD3bQhauUPh/Np2S7E7ja1o03dum',
    'https://i.pravatar.cc/300?img=54',
    'ar',
    1,
    NULL,
    '2026-08-20 20:00:00',
    '2026-08-27 10:00:00'
);

/* ============================================================
   6) ربط المستخدمين بالأدوار
   ============================================================ */

INSERT INTO `model_has_roles`
(
    `role_id`,
    `model_type`,
    `model_id`
)
SELECT
    2,
    'App\\Models\\User',
    `id`
FROM `users`
WHERE `id` BETWEEN 16 AND 25
ON DUPLICATE KEY UPDATE
    `model_id` = VALUES(`model_id`);

INSERT INTO `model_has_roles`
(
    `role_id`,
    `model_type`,
    `model_id`
)
SELECT
    3,
    'App\\Models\\User',
    `id`
FROM `users`
WHERE `id` BETWEEN 26 AND 55
ON DUPLICATE KEY UPDATE
    `model_id` = VALUES(`model_id`);

/* ============================================================
   7) الوكلاء
   ============================================================ */

INSERT INTO `agents`
(
    `id`,
    `user_id`,
    `license_number`,
    `bio`,
    `rating`,
    `reviews_count`,
    `is_active`,
    `created_at`,
    `updated_at`
)
VALUES
(
    5,
    16,
    'YE-RE-2026-0005',
    'وكيل عقاري متخصص في الفلل والشقق السكنية داخل صنعاء وعدن، مع خبرة في التفاوض وإدارة المعاينات.',
    4.90,
    126,
    1,
    '2026-08-10 09:00:00',
    '2026-08-27 10:00:00'
),
(
    6,
    17,
    'YE-RE-2026-0006',
    'متخصص في العقارات العائلية والشقق الحديثة ومساعدة العملاء في اختيار المواقع المناسبة.',
    4.80,
    94,
    1,
    '2026-08-11 09:00:00',
    '2026-08-27 10:00:00'
),
(
    7,
    18,
    'YE-RE-2026-0007',
    'وكيل يركز على العقارات السكنية والاستثمارية في تعز وإب مع متابعة كاملة للعميل حتى إتمام المعاينة.',
    4.70,
    81,
    1,
    '2026-08-12 09:00:00',
    '2026-08-27 10:00:00'
),
(
    8,
    19,
    'YE-RE-2026-0008',
    'متخصص في العقارات الراقية والمساكن العائلية والمجمعات السكنية.',
    4.90,
    143,
    1,
    '2026-08-13 09:00:00',
    '2026-08-27 10:00:00'
),
(
    9,
    20,
    'YE-RE-2026-0009',
    'متخصص في البيع والتأجير للعقارات الحديثة والمفروشة.',
    4.60,
    72,
    1,
    '2026-08-14 09:00:00',
    '2026-08-27 10:00:00'
),
(
    10,
    21,
    'YE-RE-2026-0010',
    'وكيل سكني يهتم بالعقارات المناسبة للعائلات والاستثمار متوسط الأجل.',
    4.80,
    108,
    1,
    '2026-08-15 09:00:00',
    '2026-08-27 10:00:00'
),
(
    11,
    22,
    'YE-RE-2026-0011',
    'متخصص في العقارات الاستثمارية والوحدات التجارية والسكنية الحديثة.',
    4.70,
    65,
    1,
    '2026-08-16 09:00:00',
    '2026-08-27 10:00:00'
),
(
    12,
    23,
    'YE-RE-2026-0012',
    'وكيل عقاري يركز على المواقع الهادئة والفلل ذات الحدائق والمواقف الخاصة.',
    4.90,
    119,
    1,
    '2026-08-17 09:00:00',
    '2026-08-27 10:00:00'
),
(
    13,
    24,
    'YE-RE-2026-0013',
    'متخصص في تأجير الشقق المفروشة والوحدات السكنية داخل المدن الرئيسية.',
    4.50,
    58,
    1,
    '2026-08-18 09:00:00',
    '2026-08-27 10:00:00'
),
(
    14,
    25,
    'YE-RE-2026-0014',
    'وكيل عقاري للعقارات العائلية والاستثمارية مع اهتمام كبير بسرعة الرد وتنظيم المعاينات.',
    4.80,
    97,
    1,
    '2026-08-19 09:00:00',
    '2026-08-27 10:00:00'
);

/* ============================================================
   8) المواقع - 100 موقع (تم إصلاح صياغة SELECT)
   ============================================================ */

INSERT INTO `property_locations`
(
    `id`,
    `city`,
    `district`,
    `neighborhood`,
    `address`,
    `latitude`,
    `longitude`,
    `created_at`,
    `updated_at`
)
SELECT
    7 + nums.num AS id,
    CASE MOD(nums.num, 10)
        WHEN 0 THEN 'صنعاء'
        WHEN 1 THEN 'عدن'
        WHEN 2 THEN 'تعز'
        WHEN 3 THEN 'إب'
        WHEN 4 THEN 'الحديدة'
        WHEN 5 THEN 'المكلا'
        WHEN 6 THEN 'سيئون'
        WHEN 7 THEN 'مأرب'
        WHEN 8 THEN 'الرياض'
        WHEN 9 THEN 'جدة'
    END AS city,
    CASE MOD(nums.num, 10)
        WHEN 0 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'حدة'
                WHEN 1 THEN 'شملان'
                ELSE 'بيت بوس'
            END
        WHEN 1 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'المنصورة'
                WHEN 1 THEN 'خور مكسر'
                ELSE 'إنماء'
            END
        WHEN 2 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'المسبح'
                WHEN 1 THEN 'وادي القاضي'
                ELSE 'الروضة'
            END
        WHEN 3 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'السبل'
                WHEN 1 THEN 'شارع العدين'
                ELSE 'المشنة'
            END
        WHEN 4 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'ال7 من يوليو'
                WHEN 1 THEN 'الشرفية'
                ELSE 'الحد'
            END
        WHEN 5 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'فوة'
                WHEN 1 THEN 'الديس'
                ELSE 'الشرج'
            END
        WHEN 6 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'السحيل'
                WHEN 1 THEN 'القرن'
                ELSE 'الفلج'
            END
        WHEN 7 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'الروضة'
                WHEN 1 THEN 'المطار'
                ELSE 'الوادي'
            END
        WHEN 8 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'الياسمين'
                WHEN 1 THEN 'الملقا'
                ELSE 'النرجس'
            END
        WHEN 9 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'الزهراء'
                WHEN 1 THEN 'الحمدانية'
                ELSE 'السلامة'
            END
    END AS district,
    CASE MOD(nums.num, 10)
        WHEN 0 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'حدة'
                WHEN 1 THEN 'شملان'
                ELSE 'بيت بوس'
            END
        WHEN 1 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'المنصورة'
                WHEN 1 THEN 'خور مكسر'
                ELSE 'إنماء'
            END
        WHEN 2 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'المسبح'
                WHEN 1 THEN 'وادي القاضي'
                ELSE 'الروضة'
            END
        WHEN 3 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'السبل'
                WHEN 1 THEN 'شارع العدين'
                ELSE 'المشنة'
            END
        WHEN 4 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'ال7 من يوليو'
                WHEN 1 THEN 'الشرفية'
                ELSE 'الحد'
            END
        WHEN 5 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'فوة'
                WHEN 1 THEN 'الديس'
                ELSE 'الشرج'
            END
        WHEN 6 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'السحيل'
                WHEN 1 THEN 'القرن'
                ELSE 'الفلج'
            END
        WHEN 7 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'الروضة'
                WHEN 1 THEN 'المطار'
                ELSE 'الوادي'
            END
        WHEN 8 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'الياسمين'
                WHEN 1 THEN 'الملقا'
                ELSE 'النرجس'
            END
        WHEN 9 THEN
            CASE MOD(nums.num, 3)
                WHEN 0 THEN 'الزهراء'
                WHEN 1 THEN 'الحمدانية'
                ELSE 'السلامة'
            END
    END AS neighborhood,
    CASE MOD(nums.num, 10)
        WHEN 0 THEN CONCAT('حي سكني هادئ - شارع رئيسي رقم ', 7 + nums.num)
        WHEN 1 THEN CONCAT('شارع رئيسي قريب من الخدمات - مبنى ', 7 + nums.num)
        WHEN 2 THEN CONCAT('موقع سكني عائلي - شارع رقم ', 7 + nums.num)
        WHEN 3 THEN CONCAT('شارع تجاري قريب من المرافق - رقم ', 7 + nums.num)
        WHEN 4 THEN CONCAT('حي سكني منظم - شارع رقم ', 7 + nums.num)
        WHEN 5 THEN CONCAT('منطقة سكنية قريبة من الخدمات - رقم ', 7 + nums.num)
        WHEN 6 THEN CONCAT('حي سكني هادئ - شارع رقم ', 7 + nums.num)
        WHEN 7 THEN CONCAT('موقع استثماري متنامٍ - شارع رقم ', 7 + nums.num)
        WHEN 8 THEN CONCAT('شارع سكني راقٍ - رقم ', 7 + nums.num)
        WHEN 9 THEN CONCAT('حي عائلي حديث - شارع رقم ', 7 + nums.num)
    END AS address,
    CASE MOD(nums.num, 10)
        WHEN 0 THEN ROUND(15.3400000 + (MOD(nums.num,10) * 0.001), 7)
        WHEN 1 THEN ROUND(12.7900000 + (MOD(nums.num,10) * 0.001), 7)
        WHEN 2 THEN ROUND(13.5800000 + (MOD(nums.num,10) * 0.001), 7)
        WHEN 3 THEN ROUND(13.9700000 + (MOD(nums.num,10) * 0.001), 7)
        WHEN 4 THEN ROUND(14.8000000 + (MOD(nums.num,10) * 0.001), 7)
        WHEN 5 THEN ROUND(14.5400000 + (MOD(nums.num,10) * 0.001), 7)
        WHEN 6 THEN ROUND(15.9600000 + (MOD(nums.num,10) * 0.001), 7)
        WHEN 7 THEN ROUND(15.4700000 + (MOD(nums.num,10) * 0.001), 7)
        WHEN 8 THEN ROUND(24.8300000 + (MOD(nums.num,10) * 0.001), 7)
        WHEN 9 THEN ROUND(21.5600000 + (MOD(nums.num,10) * 0.001), 7)
    END + (MOD(nums.num,10) * 0.002) AS latitude,
    CASE MOD(nums.num, 10)
        WHEN 0 THEN ROUND(44.2200000 + (MOD(nums.num,10) * 0.001), 7)
        WHEN 1 THEN ROUND(45.0300000 + (MOD(nums.num,10) * 0.001), 7)
        WHEN 2 THEN ROUND(44.0100000 + (MOD(nums.num,10) * 0.001), 7)
        WHEN 3 THEN ROUND(44.1700000 + (MOD(nums.num,10) * 0.001), 7)
        WHEN 4 THEN ROUND(42.9600000 + (MOD(nums.num,10) * 0.001), 7)
        WHEN 5 THEN ROUND(49.1300000 + (MOD(nums.num,10) * 0.001), 7)
        WHEN 6 THEN ROUND(48.4400000 + (MOD(nums.num,10) * 0.001), 7)
        WHEN 7 THEN ROUND(45.3300000 + (MOD(nums.num,10) * 0.001), 7)
        WHEN 8 THEN ROUND(46.6500000 + (MOD(nums.num,10) * 0.001), 7)
        WHEN 9 THEN ROUND(39.1700000 + (MOD(nums.num,10) * 0.001), 7)
    END + (MOD(nums.num,10) * 0.002) AS longitude,
    '2026-08-27 16:00:00' AS created_at,
    '2026-08-27 16:00:00' AS updated_at
FROM `seed_numbers` nums;

/* ============================================================
   9) 100 عقار
   ============================================================ */

INSERT INTO `properties`
(
    `id`,
    `agent_id`,
    `property_type_id`,
    `property_location_id`,
    `title`,
    `slug`,
    `reference_code`,
    `description`,
    `transaction_type`,
    `status`,
    `price`,
    `currency`,
    `area`,
    `bedrooms`,
    `bathrooms`,
    `parking_spaces`,
    `is_furnished`,
    `is_new`,
    `is_featured`,
    `published_at`,
    `deleted_at`,
    `created_at`,
    `updated_at`
)
SELECT
    11 + nums.num AS id,
    5 + MOD(nums.num, 10) AS agent_id,
    1 + MOD(nums.num, 6) AS property_type_id,
    7 + nums.num AS property_location_id,

    CONCAT(
        CASE (1 + MOD(nums.num, 6))
            WHEN 1 THEN 'فيلا عائلية حديثة'
            WHEN 2 THEN 'شقة أنيقة بإطلالة مفتوحة'
            WHEN 3 THEN 'تاون هاوس راقٍ'
            WHEN 4 THEN 'دور سكني مستقل'
            WHEN 5 THEN 'بنتهاوس فاخر'
            WHEN 6 THEN 'أرض سكنية مميزة'
        END,
        ' في ',
        CASE MOD(nums.num, 10)
            WHEN 0 THEN 'صنعاء'
            WHEN 1 THEN 'عدن'
            WHEN 2 THEN 'تعز'
            WHEN 3 THEN 'إب'
            WHEN 4 THEN 'الحديدة'
            WHEN 5 THEN 'المكلا'
            WHEN 6 THEN 'سيئون'
            WHEN 7 THEN 'مأرب'
            WHEN 8 THEN 'الرياض'
            WHEN 9 THEN 'جدة'
        END,
        ' - ',
        LPAD(11 + nums.num, 3, '0')
    ) AS title,

    CONCAT(
        'seed-property-',
        LPAD(11 + nums.num, 3, '0')
    ) AS slug,

    CONCAT(
        'LUX-YE-SEED-',
        LPAD(11 + nums.num, 3, '0')
    ) AS reference_code,

    CONCAT(
        'عقار ',
        CASE (1 + MOD(nums.num, 6))
            WHEN 1 THEN 'سكني عائلي مستقل'
            WHEN 2 THEN 'سكني حديث'
            WHEN 3 THEN 'ضمن مجتمع هادئ ومناسب للعائلات'
            WHEN 4 THEN 'بمدخل مستقل ومساحات عملية'
            WHEN 5 THEN 'راقي بتشطيب حديث وموقع مميز'
            WHEN 6 THEN 'مناسب لبناء مشروع سكني أو استثماري'
        END,
        '، قريب من الخدمات الأساسية والطرق الرئيسية، مع توزيع عملي للمساحات وموقع مناسب للسكن أو الاستثمار.'
    ) AS description,

    CASE
        WHEN MOD(nums.num, 2) = 0 THEN 'sale'
        ELSE 'rent'
    END AS transaction_type,

    CASE MOD(nums.num, 10)
        WHEN 7 THEN 'pending'
        WHEN 8 THEN 'draft'
        WHEN 9 THEN 'archived'
        ELSE 'published'
    END AS status,

    CASE MOD(nums.num, 10)

        /* صنعاء */
        WHEN 0 THEN
            CASE WHEN MOD(nums.num,2)=0
                THEN 45000000 + (nums.num * 550000)
                ELSE 280000 + (nums.num * 3500)
            END

        /* عدن */
        WHEN 1 THEN
            CASE WHEN MOD(nums.num,2)=0
                THEN 60000000 + (nums.num * 750000)
                ELSE 320000 + (nums.num * 4500)
            END

        /* تعز */
        WHEN 2 THEN
            CASE WHEN MOD(nums.num,2)=0
                THEN 30000000 + (nums.num * 420000)
                ELSE 180000 + (nums.num * 2800)
            END

        /* إب */
        WHEN 3 THEN
            CASE WHEN MOD(nums.num,2)=0
                THEN 28000000 + (nums.num * 390000)
                ELSE 170000 + (nums.num * 2500)
            END

        /* الحديدة */
        WHEN 4 THEN
            CASE WHEN MOD(nums.num,2)=0
                THEN 23000000 + (nums.num * 350000)
                ELSE 150000 + (nums.num * 2200)
            END

        /* المكلا */
        WHEN 5 THEN
            CASE WHEN MOD(nums.num,2)=0
                THEN 55000000 + (nums.num * 700000)
                ELSE 300000 + (nums.num * 4000)
            END

        /* سيئون */
        WHEN 6 THEN
            CASE WHEN MOD(nums.num,2)=0
                THEN 38000000 + (nums.num * 500000)
                ELSE 210000 + (nums.num * 2800)
            END

        /* مأرب */
        WHEN 7 THEN
            CASE WHEN MOD(nums.num,2)=0
                THEN 42000000 + (nums.num * 600000)
                ELSE 240000 + (nums.num * 3500)
            END

        /* الرياض */
        WHEN 8 THEN
            CASE WHEN MOD(nums.num,2)=0
                THEN 1250000 + (nums.num * 26000)
                ELSE 48000 + (nums.num * 950)
            END

        /* جدة */
        WHEN 9 THEN
            CASE WHEN MOD(nums.num,2)=0
                THEN 950000 + (nums.num * 22000)
                ELSE 42000 + (nums.num * 850)
            END
    END AS price,

    CASE
        WHEN MOD(nums.num,10) IN (8,9) THEN 'SAR'
        ELSE 'YER'
    END AS currency,

    CASE (1 + MOD(nums.num, 6))
        WHEN 1 THEN 350 + (MOD(nums.num,6) * 25)
        WHEN 2 THEN 110 + (MOD(nums.num,6) * 18)
        WHEN 3 THEN 240 + (MOD(nums.num,6) * 20)
        WHEN 4 THEN 210 + (MOD(nums.num,6) * 22)
        WHEN 5 THEN 180 + (MOD(nums.num,6) * 18)
        WHEN 6 THEN 600 + (MOD(nums.num,6) * 35)
    END AS area,

    CASE
        WHEN 1 + MOD(nums.num,6) IN (2,4) THEN 2 + MOD(nums.num,4)
        WHEN 1 + MOD(nums.num,6) IN (1,3,5) THEN 3 + MOD(nums.num,4)
        ELSE NULL
    END AS bedrooms,

    CASE
        WHEN 1 + MOD(nums.num,6) IN (2,4) THEN 2 + MOD(nums.num,3)
        WHEN 1 + MOD(nums.num,6) IN (1,3,5) THEN 3 + MOD(nums.num,3)
        ELSE NULL
    END AS bathrooms,

    CASE
        WHEN 1 + MOD(nums.num,6) IN (1,3) THEN 2 + MOD(nums.num,2)
        WHEN 1 + MOD(nums.num,6) IN (2,4,5) THEN 1 + MOD(nums.num,2)
        ELSE NULL
    END AS parking_spaces,

    CASE
        WHEN MOD(nums.num,5) IN (1,4) THEN 1
        ELSE 0
    END AS is_furnished,

    CASE
        WHEN MOD(nums.num,4) IN (0,1) THEN 1
        ELSE 0
    END AS is_new,

    CASE
        WHEN MOD(nums.num,10) IN (0,1,3) THEN 1
        ELSE 0
    END AS is_featured,

    CASE
        WHEN MOD(nums.num,10) IN (7,8,9) THEN NULL
        ELSE DATE_ADD('2026-08-15 12:00:00', INTERVAL MOD(nums.num,12) DAY)
    END AS published_at,

    NULL AS deleted_at,

    DATE_ADD('2026-07-20 09:00:00', INTERVAL nums.num DAY) AS created_at,
    DATE_ADD('2026-07-20 09:00:00', INTERVAL nums.num DAY) AS updated_at

FROM `seed_numbers` nums;

/* ============================================================
   10) ربط العقارات بالميزات (مع INSERT IGNORE)
   ============================================================ */

INSERT IGNORE INTO `property_feature`
(
    `property_id`,
    `property_feature_id`
)
SELECT
    p.`id`,
    1 + MOD(p.`id` + s.num, 10)
FROM `properties` p
JOIN
(
    SELECT 0 AS num
    UNION ALL SELECT 1
    UNION ALL SELECT 2
    UNION ALL SELECT 3
    UNION ALL SELECT 4
) s
    ON s.num < (3 + MOD(p.`id`, 3))
WHERE p.`id` BETWEEN 11 AND 110;

/* ============================================================
   11) صور العقارات
   ============================================================ */

INSERT INTO `property_images`
(
    `property_id`,
    `path`,
    `alt_text`,
    `sort_order`,
    `is_cover`,
    `created_at`,
    `updated_at`
)
SELECT
    p.`id`,
    CONCAT(
        'https://picsum.photos/seed/luxe-property-',
        p.`id`,
        '-',
        s.num,
        '/1200/800.jpg'
    ),
    CONCAT(
        p.`title`,
        ' - صورة ',
        s.num + 1
    ),
    s.num,
    CASE WHEN s.num = 0 THEN 1 ELSE 0 END,
    p.`created_at`,
    p.`updated_at`
FROM `properties` p
JOIN
(
    SELECT 0 AS num
    UNION ALL SELECT 1
    UNION ALL SELECT 2
    UNION ALL SELECT 3
    UNION ALL SELECT 4
    UNION ALL SELECT 5
    UNION ALL SELECT 6
    UNION ALL SELECT 7
) s
    ON s.num < (4 + MOD(p.`id`, 5))
WHERE p.`id` BETWEEN 11 AND 110;

/* ============================================================
   12) المفضلة
   ============================================================ */

INSERT INTO `favorites`
(
    `user_id`,
    `property_id`,
    `created_at`,
    `updated_at`
)
SELECT
    26 + MOD(nums.num, 30),
    11 + nums.num,
    DATE_ADD('2026-08-20 10:00:00', INTERVAL nums.num DAY),
    DATE_ADD('2026-08-20 10:00:00', INTERVAL nums.num DAY)
FROM `seed_numbers` nums
WHERE MOD(nums.num, 2) = 0;

/* ============================================================
   13) المحادثات
   ============================================================ */

INSERT INTO `conversations`
(
    `property_id`,
    `client_id`,
    `agent_id`,
    `last_message_at`,
    `created_at`,
    `updated_at`
)
SELECT
    11 + nums.num,
    26 + MOD(nums.num, 30),
    16 + MOD(nums.num, 10),
    DATE_ADD('2026-08-25 11:00:00', INTERVAL nums.num HOUR),
    DATE_ADD('2026-08-18 11:00:00', INTERVAL nums.num DAY),
    DATE_ADD('2026-08-25 11:00:00', INTERVAL nums.num HOUR)
FROM `seed_numbers` nums
WHERE nums.num < 30;

/* ============================================================
   14) رسائل المحادثات
   ============================================================ */

INSERT INTO `messages`
(
    `conversation_id`,
    `sender_id`,
    `body`,
    `message_type`,
    `read_at`,
    `created_at`,
    `updated_at`,
    `property_id`
)
SELECT
    c.`id`,
    CASE
        WHEN m.num = 0 THEN c.`agent_id`
        WHEN m.num = 1 THEN c.`client_id`
        ELSE c.`agent_id`
    END,
    CASE
        WHEN m.num = 0 THEN
            'مرحبًا، شكرًا لتواصلك. العقار ما زال متاحًا ويمكن ترتيب موعد للمعاينة.'
        WHEN m.num = 1 THEN
            'ممتاز، أريد معرفة الموقع والتفاصيل الخاصة بالعقار وموعد المعاينة المناسب.'
        ELSE
            'بالتأكيد، يمكننا ترتيب المعاينة في الموعد المناسب لك، وسأرسل لك التفاصيل كاملة.'
    END,
    'text',
    CASE
        WHEN m.num = 2 THEN NULL
        ELSE DATE_ADD(c.`created_at`, INTERVAL 2 HOUR)
    END,
    DATE_ADD(c.`created_at`, INTERVAL (m.num + 1) HOUR),
    DATE_ADD(c.`created_at`, INTERVAL (m.num + 1) HOUR),
    c.`property_id`
FROM `conversations` c
CROSS JOIN
(
    SELECT 0 AS num
    UNION ALL SELECT 1
    UNION ALL SELECT 2
) m
WHERE c.`property_id` BETWEEN 11 AND 110;

/* ============================================================
   15) طلبات المعاينة
   ============================================================ */

INSERT INTO `viewing_requests`
(
    `property_id`,
    `client_id`,
    `agent_id`,
    `scheduled_date`,
    `scheduled_time`,
    `notes`,
    `status`,
    `created_at`,
    `updated_at`
)
SELECT
    11 + nums.num,
    26 + MOD(nums.num, 30),
    5 + MOD(nums.num, 10),
    DATE_ADD('2026-09-01', INTERVAL nums.num DAY),
    CASE MOD(nums.num, 4)
        WHEN 0 THEN '10:00:00'
        WHEN 1 THEN '13:30:00'
        WHEN 2 THEN '16:00:00'
        ELSE '18:30:00'
    END,
    CASE MOD(nums.num, 4)
        WHEN 0 THEN 'أفضل المعاينة في الفترة الصباحية وأرغب في معرفة تفاصيل المواقف.'
        WHEN 1 THEN 'أرغب في معاينة العقار مع الأسرة ومراجعة تفاصيل الحي والخدمات.'
        WHEN 2 THEN 'أفضّل الموعد بعد الظهر وأحتاج تفاصيل إضافية عن التشطيبات.'
        ELSE 'أرغب في زيارة العقار ومناقشة خيارات السعر والتفاوض.'
    END,
    CASE MOD(nums.num, 5)
        WHEN 0 THEN 'pending'
        WHEN 1 THEN 'confirmed'
        WHEN 2 THEN 'completed'
        WHEN 3 THEN 'rejected'
        ELSE 'cancelled'
    END,
    DATE_ADD('2026-08-20 09:00:00', INTERVAL nums.num DAY),
    DATE_ADD('2026-08-20 12:00:00', INTERVAL nums.num DAY)
FROM `seed_numbers` nums
WHERE nums.num < 40;

/* ============================================================
   16) عمليات البحث المحفوظة
   ============================================================ */

INSERT INTO `saved_searches`
(
    `user_id`,
    `name`,
    `filters`,
    `notifications_enabled`,
    `created_at`,
    `updated_at`
)
SELECT
    26 + nums.num,
    CASE MOD(nums.num, 5)
        WHEN 0 THEN 'فلل عائلية للبيع'
        WHEN 1 THEN 'شقق للإيجار'
        WHEN 2 THEN 'عقارات استثمارية'
        WHEN 3 THEN 'عقار قريب من الخدمات'
        ELSE 'عقارات حديثة'
    END,
    CASE MOD(nums.num, 5)
        WHEN 0 THEN
            JSON_OBJECT(
                'property_type', 'villa',
                'transaction_type', 'sale',
                'city',
                    CASE MOD(nums.num,4)
                        WHEN 0 THEN 'صنعاء'
                        WHEN 1 THEN 'عدن'
                        WHEN 2 THEN 'الرياض'
                        ELSE 'جدة'
                    END,
                'min_bedrooms', 3,
                'notifications', true
            )
        WHEN 1 THEN
            JSON_OBJECT(
                'property_type', 'apartment',
                'transaction_type', 'rent',
                'max_bedrooms', 3,
                'min_bathrooms', 2,
                'notifications', true
            )
        WHEN 2 THEN
            JSON_OBJECT(
                'transaction_type', 'sale',
                'min_area', 200,
                'featured', true,
                'notifications', true
            )
        WHEN 3 THEN
            JSON_OBJECT(
                'transaction_type', 'rent',
                'furnished', true,
                'notifications', false
            )
        ELSE
            JSON_OBJECT(
                'is_new', true,
                'transaction_type', 'sale',
                'min_bedrooms', 2,
                'notifications', true
            )
    END,
    CASE WHEN MOD(nums.num, 3) <> 0 THEN 1 ELSE 0 END,
    DATE_ADD('2026-08-10 12:00:00', INTERVAL nums.num DAY),
    DATE_ADD('2026-08-10 12:00:00', INTERVAL nums.num DAY)
FROM `seed_numbers` nums
WHERE nums.num < 20;

/* ============================================================
   17) تفضيلات الإشعارات لكل حساب جديد
   ============================================================ */

INSERT INTO `user_notification_preferences`
(
    `user_id`,
    `message_notifications`,
    `viewing_notifications`,
    `property_updates`,
    `created_at`,
    `updated_at`
)
SELECT
    `id`,
    1,
    1,
    CASE WHEN MOD(`id`, 4) = 0 THEN 0 ELSE 1 END,
    '2026-08-20 10:00:00',
    '2026-08-27 10:00:00'
FROM `users`
WHERE `id` BETWEEN 16 AND 55;

/* ============================================================
   18) أجهزة المستخدمين
   ============================================================ */

INSERT INTO `user_devices`
(
    `user_id`,
    `device_id`,
    `platform`,
    `push_token`,
    `last_seen_at`,
    `created_at`,
    `updated_at`
)
SELECT
    u.`id`,
    CONCAT('luxe-seed-device-', u.`id`),
    CASE
        WHEN MOD(u.`id`, 4) = 0 THEN 'ios'
        ELSE 'android'
    END,
    SHA2(CONCAT('luxe-seed-push-token-', u.`id`), 256),
    DATE_ADD('2026-08-27 08:00:00', INTERVAL MOD(u.`id`, 10) HOUR),
    '2026-08-20 09:00:00',
    '2026-08-27 09:00:00'
FROM `users` u
WHERE u.`id` BETWEEN 16 AND 55;

/* ============================================================
   19) إشعارات واقعية
   ============================================================ */

INSERT INTO `notifications`
(
    `id`,
    `type`,
    `notifiable_type`,
    `notifiable_id`,
    `data`,
    `read_at`,
    `created_at`,
    `updated_at`
)
SELECT
    UUID(),
    CASE MOD(nums.num, 5)
        WHEN 0 THEN 'App\\Notifications\\MessageReceived'
        WHEN 1 THEN 'App\\Notifications\\ViewingRequestCreated'
        WHEN 2 THEN 'App\\Notifications\\PropertyUpdated'
        WHEN 3 THEN 'App\\Notifications\\ViewingRequestConfirmed'
        ELSE 'App\\Notifications\\NewPropertyMatch'
    END,
    'App\\Models\\User',
    26 + MOD(nums.num, 30),
    CASE MOD(nums.num, 5)
        WHEN 0 THEN
            JSON_OBJECT(
                'title', 'رسالة جديدة',
                'body', 'لديك رسالة جديدة من الوكيل حول العقار.',
                'property_id', 11 + MOD(nums.num, 100)
            )
        WHEN 1 THEN
            JSON_OBJECT(
                'title', 'طلب معاينة جديد',
                'body', 'تم إنشاء طلب معاينة جديد لعقار محفوظ لديك.',
                'property_id', 11 + MOD(nums.num, 100)
            )
        WHEN 2 THEN
            JSON_OBJECT(
                'title', 'تحديث عقار',
                'body', 'تم تحديث تفاصيل أحد العقارات التي تتابعها.',
                'property_id', 11 + MOD(nums.num, 100)
            )
        WHEN 3 THEN
            JSON_OBJECT(
                'title', 'تم تأكيد المعاينة',
                'body', 'تم تأكيد موعد معاينة العقار بنجاح.',
                'property_id', 11 + MOD(nums.num, 100)
            )
        ELSE
            JSON_OBJECT(
                'title', 'عقار مناسب لبحثك',
                'body', 'وجدنا عقارًا جديدًا قد يناسب تفضيلات البحث المحفوظة.',
                'property_id', 11 + MOD(nums.num, 100)
            )
    END,
    CASE
        WHEN MOD(nums.num, 3) = 0 THEN NULL
        ELSE DATE_ADD('2026-08-25 10:00:00', INTERVAL nums.num HOUR)
    END,
    DATE_ADD('2026-08-23 10:00:00', INTERVAL nums.num HOUR),
    DATE_ADD('2026-08-23 10:00:00', INTERVAL nums.num HOUR)
FROM `seed_numbers` nums
WHERE nums.num < 50;

/* ============================================================
   20) إعدادات المنصة
   ============================================================ */

INSERT INTO `settings`
(
    `key`,
    `value`,
    `type`,
    `created_at`,
    `updated_at`
)
VALUES
(
    'platform_name',
    'وِجهة',
    'string',
    '2026-08-27 10:00:00',
    '2026-08-27 10:00:00'
),
(
    'platform_tagline',
    'وجهتك إلى العقار المناسب.',
    'string',
    '2026-08-27 10:00:00',
    '2026-08-27 10:00:00'
),
(
    'default_locale',
    'ar',
    'string',
    '2026-08-27 10:00:00',
    '2026-08-27 10:00:00'
),
(
    'default_currency',
    'YER',
    'string',
    '2026-08-27 10:00:00',
    '2026-08-27 10:00:00'
),
(
    'supported_currencies',
    'YER,SAR,OMR',
    'string',
    '2026-08-27 10:00:00',
    '2026-08-27 10:00:00'
),
(
    'supported_countries',
    'YE,SA,OM',
    'string',
    '2026-08-27 10:00:00',
    '2026-08-27 10:00:00'
),
(
    'property_image_limit',
    '12',
    'integer',
    '2026-08-27 10:00:00',
    '2026-08-27 10:00:00'
),
(
    'featured_properties_enabled',
    '1',
    'boolean',
    '2026-08-27 10:00:00',
    '2026-08-27 10:00:00'
),
(
    'maintenance_mode',
    '0',
    'boolean',
    '2026-08-27 10:00:00',
    '2026-08-27 10:00:00'
),
(
    'contact_email',
    'hello@wejha.local',
    'string',
    '2026-08-27 10:00:00',
    '2026-08-27 10:00:00'
),
(
    'contact_phone',
    '+967770000000',
    'string',
    '2026-08-27 10:00:00',
    '2026-08-27 10:00:00'
),
(
    'seed_version',
    '2026.08.realistic.01',
    'string',
    '2026-08-27 10:00:00',
    '2026-08-27 10:00:00'
)
ON DUPLICATE KEY UPDATE
    `value` = VALUES(`value`),
    `type` = VALUES(`type`),
    `updated_at` = VALUES(`updated_at`);

/* ============================================================
   21) ضبط Auto Increment
   ============================================================ */

ALTER TABLE `users`
    AUTO_INCREMENT = 56;

ALTER TABLE `agents`
    AUTO_INCREMENT = 15;

ALTER TABLE `property_locations`
    AUTO_INCREMENT = 107;

ALTER TABLE `properties`
    AUTO_INCREMENT = 111;

/* ============================================================
   22) التحقق النهائي
   ============================================================ */

SELECT
    'users' AS `table_name`,
    COUNT(*) AS `count`
FROM `users`
WHERE `id` BETWEEN 16 AND 55

UNION ALL

SELECT
    'agents',
    COUNT(*)
FROM `agents`
WHERE `id` BETWEEN 5 AND 14

UNION ALL

SELECT
    'property_locations',
    COUNT(*)
FROM `property_locations`
WHERE `id` BETWEEN 7 AND 106

UNION ALL

SELECT
    'properties',
    COUNT(*)
FROM `properties`
WHERE `id` BETWEEN 11 AND 110

UNION ALL

SELECT
    'property_images',
    COUNT(*)
FROM `property_images`
WHERE `property_id` BETWEEN 11 AND 110

UNION ALL

SELECT
    'property_feature',
    COUNT(*)
FROM `property_feature`
WHERE `property_id` BETWEEN 11 AND 110

UNION ALL

SELECT
    'favorites',
    COUNT(*)
FROM `favorites`
WHERE `property_id` BETWEEN 11 AND 110

UNION ALL

SELECT
    'conversations',
    COUNT(*)
FROM `conversations`
WHERE `property_id` BETWEEN 11 AND 110

UNION ALL

SELECT
    'messages',
    COUNT(*)
FROM `messages`
WHERE `property_id` BETWEEN 11 AND 110

UNION ALL

SELECT
    'viewing_requests',
    COUNT(*)
FROM `viewing_requests`
WHERE `property_id` BETWEEN 11 AND 110

UNION ALL

SELECT
    'saved_searches',
    COUNT(*)
FROM `saved_searches`
WHERE `user_id` BETWEEN 26 AND 55

UNION ALL

SELECT
    'user_devices',
    COUNT(*)
FROM `user_devices`
WHERE `user_id` BETWEEN 16 AND 55

UNION ALL

SELECT
    'user_notification_preferences',
    COUNT(*)
FROM `user_notification_preferences`
WHERE `user_id` BETWEEN 16 AND 55

UNION ALL

SELECT
    'notifications',
    COUNT(*)
FROM `notifications`
WHERE `notifiable_type` = 'App\\Models\\User'
  AND `notifiable_id` BETWEEN 26 AND 55;

/* ============================================================
   23) إتمام العملية
   ============================================================ */

COMMIT;

DROP TEMPORARY TABLE IF EXISTS `seed_numbers`;
