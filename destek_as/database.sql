CREATE DATABASE IF NOT EXISTS `destek_as` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `destek_as`;

-- 1. companies
CREATE TABLE IF NOT EXISTS `companies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `trade_name` VARCHAR(255) DEFAULT NULL,
  `tax_number` VARCHAR(50) DEFAULT NULL,
  `tax_office` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `sector` VARCHAR(100) DEFAULT NULL,
  `contact_person` VARCHAR(150) DEFAULT NULL,
  `working_hours` VARCHAR(100) DEFAULT '09:00-18:00',
  `default_language` VARCHAR(10) DEFAULT 'tr',
  `timezone` VARCHAR(50) DEFAULT 'Europe/Istanbul',
  `status` ENUM('active', 'passive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. company_settings
CREATE TABLE IF NOT EXISTS `company_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `idx_company_key` (`company_id`, `setting_key`)
) ENGINE=InnoDB;

-- 3. users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('active', 'passive', 'suspended') DEFAULT 'active',
  `two_factor_secret` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT DEFAULT NULL, -- NULL indicates global system roles
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_system` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. permissions
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6. role_permissions
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` INT NOT NULL,
  `permission_id` INT NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. user_roles
CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` INT NOT NULL,
  `role_id` INT NOT NULL,
  `company_id` INT DEFAULT NULL,
  PRIMARY KEY (`user_id`, `role_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 8. support_packages
CREATE TABLE IF NOT EXISTS `support_packages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `ticket_limit` INT DEFAULT -1, -- -1 for unlimited
  `support_hours` VARCHAR(100) DEFAULT '9/5', -- '24/7' or '9/5'
  `response_sla` INT DEFAULT NULL, -- in minutes
  `resolution_sla` INT DEFAULT NULL, -- in minutes
  `dedicated_agent` TINYINT(1) DEFAULT 0,
  `critical_intervention` TINYINT(1) DEFAULT 0,
  `price` DECIMAL(10, 2) DEFAULT 0.00,
  `status` ENUM('active', 'passive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. customers
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `contact_person` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `contract_start_date` DATE DEFAULT NULL,
  `contract_end_date` DATE DEFAULT NULL,
  `support_package_id` INT DEFAULT NULL,
  `monthly_ticket_limit` INT DEFAULT 0,
  `priority_support` TINYINT(1) DEFAULT 0,
  `custom_sla_rules` TEXT DEFAULT NULL,
  `status` ENUM('active', 'passive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`support_package_id`) REFERENCES `support_packages`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 10. customer_users
CREATE TABLE IF NOT EXISTS `customer_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `role` VARCHAR(100) DEFAULT 'standard', -- manager, technical, finance, standard, readonly
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 11. departments
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `manager_id` INT DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `working_hours` VARCHAR(100) DEFAULT '09:00-18:00',
  `default_priority` VARCHAR(50) DEFAULT 'Normal',
  `status` ENUM('active', 'passive') DEFAULT 'active',
  `daily_capacity` INT DEFAULT 10,
  `assignment_method` VARCHAR(50) DEFAULT 'round_robin', -- round_robin, least_workload, skills, priority, manual
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`manager_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 12. department_users
CREATE TABLE IF NOT EXISTS `department_users` (
  `department_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `is_manager` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`department_id`, `user_id`),
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 13. agent_skills
CREATE TABLE IF NOT EXISTS `agent_skills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `skill_name` VARCHAR(100) NOT NULL,
  `proficiency_level` INT DEFAULT 1, -- 1 to 5
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 14. categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `default_department_id` INT DEFAULT NULL,
  `default_priority` VARCHAR(50) DEFAULT 'Normal',
  `default_agent_id` INT DEFAULT NULL,
  `sla_duration` INT DEFAULT NULL, -- in minutes
  `canned_response_id` INT DEFAULT NULL,
  `status` ENUM('active', 'passive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`default_department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`default_agent_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 15. subcategories
CREATE TABLE IF NOT EXISTS `subcategories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 16. ticket_types
CREATE TABLE IF NOT EXISTS `ticket_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT DEFAULT NULL, -- NULL is system default
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_system` TINYINT(1) DEFAULT 0,
  `status` ENUM('active', 'passive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 17. priorities
CREATE TABLE IF NOT EXISTS `priorities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `level` INT NOT NULL, -- Numeric weight
  `first_response_time` INT DEFAULT NULL, -- in minutes
  `intervention_time` INT DEFAULT NULL, -- in minutes
  `resolution_time` INT DEFAULT NULL, -- in minutes
  `notification_rule` TEXT DEFAULT NULL,
  `escalation_rule` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 18. ticket_statuses
CREATE TABLE IF NOT EXISTS `ticket_statuses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `color` VARCHAR(50) DEFAULT '#ccc',
  `is_system` TINYINT(1) DEFAULT 0,
  `is_closed` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 19. tickets
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `ticket_number` VARCHAR(100) NOT NULL UNIQUE,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `category_id` INT DEFAULT NULL,
  `subcategory_id` INT DEFAULT NULL,
  `product_service` VARCHAR(150) DEFAULT NULL,
  `priority_id` INT DEFAULT NULL,
  `ticket_type_id` INT DEFAULT NULL,
  `department_id` INT DEFAULT NULL,
  `project_name` VARCHAR(150) DEFAULT NULL,
  `screenshot_path` VARCHAR(255) DEFAULT NULL,
  `attachment_path` VARCHAR(255) DEFAULT NULL,
  `communication_preference` VARCHAR(50) DEFAULT 'email', -- email, phone, portal
  `available_time` VARCHAR(150) DEFAULT NULL,
  `status_id` INT DEFAULT NULL,
  `customer_id` INT DEFAULT NULL,
  `customer_user_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`priority_id`) REFERENCES `priorities`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`ticket_type_id`) REFERENCES `ticket_types`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`status_id`) REFERENCES `ticket_statuses`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`customer_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 20. ticket_assignments
CREATE TABLE IF NOT EXISTS `ticket_assignments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `assigned_by` INT DEFAULT NULL,
  `method` VARCHAR(50) DEFAULT 'manual', -- round_robin, workload, manual etc.
  `status` ENUM('active', 'completed', 'reassigned') DEFAULT 'active',
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `unassigned_at` TIMESTAMP DEFAULT NULL,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 21. ticket_messages
CREATE TABLE IF NOT EXISTS `ticket_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `sender_id` INT NOT NULL,
  `message_type` VARCHAR(50) DEFAULT 'public', -- public, internal, manager, system, auto
  `content` TEXT NOT NULL,
  `attachment_path` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `edited_at` TIMESTAMP DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 22. ticket_internal_notes
CREATE TABLE IF NOT EXISTS `ticket_internal_notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `note_type` VARCHAR(50) DEFAULT 'staff', -- staff, manager
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 23. ticket_attachments
CREATE TABLE IF NOT EXISTS `ticket_attachments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `message_id` INT DEFAULT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT DEFAULT 0,
  `file_mime` VARCHAR(100) DEFAULT NULL,
  `uploaded_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`message_id`) REFERENCES `ticket_messages`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 24. tags
CREATE TABLE IF NOT EXISTS `tags` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `idx_company_tag` (`company_id`, `name`)
) ENGINE=InnoDB;

-- 25. ticket_tags
CREATE TABLE IF NOT EXISTS `ticket_tags` (
  `ticket_id` INT NOT NULL,
  `tag_id` INT NOT NULL,
  PRIMARY KEY (`ticket_id`, `tag_id`),
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 26. ticket_relations
CREATE TABLE IF NOT EXISTS `ticket_relations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `related_ticket_id` INT NOT NULL,
  `relation_type` VARCHAR(100) NOT NULL, -- blocks, blocked_by, related, duplicate, parent, child
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`related_ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 27. ticket_tasks
CREATE TABLE IF NOT EXISTS `ticket_tasks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `assigned_to` INT DEFAULT NULL,
  `priority` VARCHAR(50) DEFAULT 'Normal',
  `status` VARCHAR(50) DEFAULT 'Pending', -- Pending, In_Progress, Completed
  `completion_percentage` INT DEFAULT 0,
  `due_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 28. ticket_time_entries
CREATE TABLE IF NOT EXISTS `ticket_time_entries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `start_time` TIMESTAMP NOT NULL,
  `end_time` TIMESTAMP NULL DEFAULT NULL,
  `total_duration` INT DEFAULT 0, -- in minutes
  `description` TEXT DEFAULT NULL,
  `is_billable` TINYINT(1) DEFAULT 0,
  `hourly_rate` DECIMAL(10, 2) DEFAULT 0.00,
  `flat_rate` DECIMAL(10, 2) DEFAULT 0.00,
  `total_amount` DECIMAL(10, 2) DEFAULT 0.00,
  `approval_status` VARCHAR(50) DEFAULT 'Pending', -- Pending, Approved, Rejected
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 29. ticket_status_history
CREATE TABLE IF NOT EXISTS `ticket_status_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `old_status_id` INT DEFAULT NULL,
  `new_status_id` INT NOT NULL,
  `changed_by` INT NOT NULL,
  `reason` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`old_status_id`) REFERENCES `ticket_statuses`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`new_status_id`) REFERENCES `ticket_statuses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 30. ticket_priority_history
CREATE TABLE IF NOT EXISTS `ticket_priority_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `old_priority_id` INT DEFAULT NULL,
  `new_priority_id` INT NOT NULL,
  `changed_by` INT NOT NULL,
  `reason` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`old_priority_id`) REFERENCES `priorities`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`new_priority_id`) REFERENCES `priorities`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 31. sla_policies
CREATE TABLE IF NOT EXISTS `sla_policies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `first_response_time` INT NOT NULL, -- in minutes
  `resolution_time` INT NOT NULL, -- in minutes
  `priority_id` INT DEFAULT NULL,
  `support_package_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`priority_id`) REFERENCES `priorities`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`support_package_id`) REFERENCES `support_packages`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 32. sla_events
CREATE TABLE IF NOT EXISTS `sla_events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `event_type` VARCHAR(100) NOT NULL, -- response, resolution
  `deadline` TIMESTAMP NOT NULL,
  `triggered_at` TIMESTAMP NULL DEFAULT NULL,
  `is_breached` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 33. escalation_rules
CREATE TABLE IF NOT EXISTS `escalation_rules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `priority_id` INT NOT NULL,
  `trigger_duration` INT NOT NULL, -- in minutes
  `action_type` VARCHAR(100) NOT NULL, -- notify_manager, assign_user, change_priority
  `target_user_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`priority_id`) REFERENCES `priorities`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`target_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 34. escalation_events
CREATE TABLE IF NOT EXISTS `escalation_events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `rule_id` INT NOT NULL,
  `trigger_time` TIMESTAMP NOT NULL,
  `executed_at` TIMESTAMP NULL DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'Pending', -- Pending, Executed, Cancelled
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`rule_id`) REFERENCES `escalation_rules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 35. canned_responses
CREATE TABLE IF NOT EXISTS `canned_responses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 36. knowledge_base_categories
CREATE TABLE IF NOT EXISTS `knowledge_base_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 37. knowledge_base_articles
CREATE TABLE IF NOT EXISTS `knowledge_base_articles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `summary` TEXT DEFAULT NULL,
  `content` TEXT NOT NULL,
  `tags` VARCHAR(255) DEFAULT NULL,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `video_url` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
  `view_count` INT DEFAULT 0,
  `helpful_votes` INT DEFAULT 0,
  `unhelpful_votes` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `knowledge_base_categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 38. article_feedback
CREATE TABLE IF NOT EXISTS `article_feedback` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `article_id` INT NOT NULL,
  `user_id` INT DEFAULT NULL,
  `customer_user_id` INT DEFAULT NULL,
  `is_helpful` TINYINT(1) NOT NULL,
  `comment` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`article_id`) REFERENCES `knowledge_base_articles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`customer_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 39. customer_ratings
CREATE TABLE IF NOT EXISTS `customer_ratings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `customer_user_id` INT NOT NULL,
  `general_satisfaction` INT DEFAULT 5, -- 1 to 5
  `response_speed` INT DEFAULT 5,
  `solution_quality` INT DEFAULT 5,
  `communication_quality` INT DEFAULT 5,
  `agent_attitude` INT DEFAULT 5,
  `comment` TEXT DEFAULT NULL,
  `nps_score` INT DEFAULT 10,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 40. announcements
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `type` VARCHAR(100) DEFAULT 'General', -- Maintenance, Outage, Feature, Security, General
  `target_audience` VARCHAR(100) DEFAULT 'All', -- All, Customers, Staff, Specific
  `target_ids` TEXT DEFAULT NULL, -- Comma-separated customer/department IDs
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 41. notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `customer_user_id` INT DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `notification_type` VARCHAR(50) DEFAULT 'system', -- system, email, sms, browser
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 42. email_messages
CREATE TABLE IF NOT EXISTS `email_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `sender` VARCHAR(150) NOT NULL,
  `recipient` VARCHAR(150) NOT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `body` TEXT DEFAULT NULL,
  `attachment_paths` TEXT DEFAULT NULL,
  `processed_status` VARCHAR(50) DEFAULT 'Pending', -- Pending, Created_Ticket, Ignored
  `ticket_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 43. email_accounts
CREATE TABLE IF NOT EXISTS `email_accounts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `host` VARCHAR(150) DEFAULT NULL,
  `port` INT DEFAULT 993,
  `protocol` VARCHAR(50) DEFAULT 'IMAP',
  `status` ENUM('active', 'passive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 44. webhooks
CREATE TABLE IF NOT EXISTS `webhooks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `target_url` VARCHAR(255) NOT NULL,
  `secret_key` VARCHAR(255) DEFAULT NULL,
  `event_types` TEXT NOT NULL, -- Comma-separated list
  `status` ENUM('active', 'passive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 45. webhook_logs
CREATE TABLE IF NOT EXISTS `webhook_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `webhook_id` INT NOT NULL,
  `event_type` VARCHAR(100) NOT NULL,
  `payload` TEXT NOT NULL,
  `http_status` INT DEFAULT NULL,
  `retry_count` INT DEFAULT 0,
  `status` VARCHAR(50) DEFAULT 'Success', -- Success, Failed, Retrying
  `response` TEXT DEFAULT NULL,
  `error_message` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`webhook_id`) REFERENCES `webhooks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 46. api_keys
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `api_key` VARCHAR(100) NOT NULL UNIQUE,
  `token` VARCHAR(255) DEFAULT NULL,
  `scopes` VARCHAR(255) DEFAULT '*',
  `rate_limit` INT DEFAULT 60, -- requests per minute
  `ip_restrictions` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'passive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 47. audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT DEFAULT NULL,
  `user_id` INT DEFAULT NULL,
  `action` VARCHAR(150) NOT NULL,
  `record_type` VARCHAR(100) DEFAULT NULL,
  `record_id` INT DEFAULT NULL,
  `ip_address` VARCHAR(50) DEFAULT NULL,
  `old_value` TEXT DEFAULT NULL,
  `new_value` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Insert Initial Global System Roles
INSERT INTO `roles` (`name`, `description`, `is_system`) VALUES 
('Sistem Sahibi', 'Sistemin en yetkili kullanıcısı, tüm firmaları yönetebilir.', 1),
('Firma Yöneticisi', 'Kendi firmasına ait destek sistemini yöneten kullanıcı.', 1),
('Departman Yöneticisi', 'Kendi departmanındaki ticket ve personelleri yönetir.', 1),
('Destek Personeli', 'Kendisine veya departmanına atanan destek taleplerini çözer.', 1),
('Müşteri Kullanıcısı', 'Destek talebi (ticket) oluşturan ve takip eden kullanıcı.', 1),
('Gözlemci Kullanıcı', 'Sadece kendisine izin verilen ticketları görüntüleyebilen kullanıcı.', 1);

-- Insert Default Ticket Statuses
INSERT INTO `ticket_statuses` (`name`, `description`, `color`, `is_system`) VALUES
('Yeni', 'Talep henüz açıldı, işlem görmedi.', '#8b5cf6', 1),
('Atama bekliyor', 'Analiz edildi, ilgili departmana veya personele atanmayı bekliyor.', '#f59e0b', 1),
('Atandı', 'Destek personeline atandı.', '#3b82f6', 1),
('İnceleniyor', 'Personel tarafından detaylı inceleniyor.', '#06b6d4', 1),
('Müşteriden bilgi bekleniyor', 'Müşteriye soru soruldu, cevap bekleniyor.', '#ec4899', 1),
('Personelden işlem bekleniyor', 'Personelin işlem yapması gerekiyor.', '#f43f5e', 1),
('Çözüm üzerinde çalışılıyor', 'Sorunun çözümü üzerinde aktif çalışılıyor.', '#a855f7', 1),
('Test ediliyor', 'Çözüm test ediliyor.', '#10b981', 1),
('Üçüncü taraf bekleniyor', 'Dış entegrasyon veya kargo süreci bekleniyor.', '#6b7280', 1),
('Çözüldü', 'Sorun çözüldü, müşteri onayına sunuldu.', '#10b981', 1),
('Müşteri onayı bekleniyor', 'Çözüm müşteri tarafından onaylanmayı bekliyor.', '#14b8a6', 1),
('Kapatıldı', 'Talep başarıyla tamamlandı ve kapatıldı.', '#059669', 1),
('Yeniden açıldı', 'Müşteri çözümü onaylamadı ve talebi tekrar açtı.', '#ef4444', 1),
('İptal edildi', 'Talep iptal edildi.', '#9ca3af', 1),
('Birleştirildi', 'Başka bir ticket ile birleştirildi.', '#78716c', 1),
('Askıya alındı', 'İşlem donduruldu.', '#4b5563', 1);

-- Insert Default Priorities
INSERT INTO `priorities` (`name`, `description`, `level`, `first_response_time`, `intervention_time`, `resolution_time`) VALUES
('Düşük', 'Günlük çalışmayı doğrudan engellemeyen talepler.', 1, 480, 1440, 4320), -- 8h, 24h, 72h
('Normal', 'Standart destek talepleri.', 2, 240, 720, 2880), -- 4h, 12h, 48h
('Yüksek', 'İş süreçlerini önemli ölçüde etkileyen sorunlar.', 3, 60, 180, 720), -- 1h, 3h, 12h
('Kritik', 'Sistem veya operasyonun tamamen durmasına neden olan sorunlar.', 4, 15, 60, 240), -- 15m, 1h, 4h
('Acil', 'Can, güvenlik, veri kaybı veya şirket çapında kesinti riski oluşturan durumlar.', 5, 5, 15, 60); -- 5m, 15m, 1h

-- Insert Default Ticket Types
INSERT INTO `ticket_types` (`name`, `description`, `is_system`) VALUES
('Teknik sorun', 'Teknik problemler ve sistem hataları', 1),
('Hata bildirimi', 'Yazılımsal bug ve hatalar', 1),
('Yeni özellik talebi', 'Sisteme eklenmesi istenen yeni özellikler', 1),
('Kullanıcı desteği', 'Kullanım ve yapılandırma yardımı', 1),
('Kurulum talebi', 'Program veya donanım kurulum istekleri', 1),
('Eğitim talebi', 'Kullanıcı eğitimi talepleri', 1),
('Donanım sorunu', 'Fiziksel donanım arızaları', 1),
('Ağ ve internet sorunu', 'Network ve bağlantı problemleri', 1),
('Fatura ve ödeme sorunu', 'Muhasebe ve ödeme problemleri', 1),
('Ürün iade talebi', 'Satın alınan ürünlerin iade süreçleri', 1),
('Bilgi talebi', 'Genel bilgilendirme istekleri', 1),
('Şikâyet', 'Müşteri memnuniyetsizlik bildirimleri', 1),
('Öneri', 'Geliştirme ve iyileştirme tavsiyeleri', 1),
('Güvenlik bildirimi', 'Güvenlik zafiyeti bildirimleri', 1),
('Acil destek', 'Kritik seviyede anlık müdahale gerektiren durumlar', 1);
