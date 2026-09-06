-- DLHS Result Processing Module — Database Migration
-- Run this once on the DLHS database to create the required tables.

CREATE TABLE IF NOT EXISTS `dlhs_term_results` (
  `resultId`         INT NOT NULL AUTO_INCREMENT,
  `studentId`        INT NOT NULL,
  `subjectId`        INT NOT NULL,
  `academicTerm`     TINYINT NOT NULL COMMENT '1=First, 2=Second, 3=Third',
  `academicSession`  VARCHAR(20) NOT NULL COMMENT 'e.g. 2023/2024',
  `resultType`       ENUM('mid_term','end_of_term') NOT NULL DEFAULT 'end_of_term',
  -- Mid-term components
  `assignmentScore`  DECIMAL(5,2) DEFAULT NULL COMMENT 'Max 5',
  `projectScore`     DECIMAL(5,2) DEFAULT NULL COMMENT 'Max 5',
  `midTermTest`      DECIMAL(5,2) DEFAULT NULL COMMENT 'Max 10',
  -- End-of-term components
  `test1Score`       DECIMAL(5,2) DEFAULT NULL COMMENT 'CA1, Max 20',
  `test2Score`       DECIMAL(5,2) DEFAULT NULL COMMENT 'CA2, Max 20',
  `examScore`        DECIMAL(5,2) DEFAULT NULL COMMENT 'Exam, Max 60',
  `updatedAt`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`resultId`),
  UNIQUE KEY `unique_student_subject_term_session_type` (`studentId`, `subjectId`, `academicTerm`, `academicSession`, `resultType`),
  KEY `idx_student_term_session` (`studentId`, `academicTerm`, `academicSession`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `dlhs_student_assessments` (
  `assessmentId`        INT NOT NULL AUTO_INCREMENT,
  `studentId`           INT NOT NULL,
  `academicTerm`        TINYINT NOT NULL COMMENT '1=First, 2=Second, 3=Third',
  `academicSession`     VARCHAR(20) NOT NULL,
  -- Attendance
  `totalDays`           INT DEFAULT NULL,
  `presentDays`         INT DEFAULT NULL,
  -- Character Development (scale 1-5)
  `punctuality`         TINYINT DEFAULT NULL,
  `neatness`            TINYINT DEFAULT NULL,
  `politeness`          TINYINT DEFAULT NULL,
  `honesty`             TINYINT DEFAULT NULL,
  `teamSpirit`          TINYINT DEFAULT NULL,
  `leadership`          TINYINT DEFAULT NULL,
  `helpingOthers`       TINYINT DEFAULT NULL,
  `emotionalStability`  TINYINT DEFAULT NULL,
  `health`              TINYINT DEFAULT NULL,
  `attitudeToWork`      TINYINT DEFAULT NULL,
  `attentiveness`       TINYINT DEFAULT NULL,
  `perseverance`        TINYINT DEFAULT NULL,
  `spokenEnglish`       TINYINT DEFAULT NULL,
  -- Psychomotor Skills (scale 1-5)
  `handwriting`         TINYINT DEFAULT NULL,
  `verbalFluency`       TINYINT DEFAULT NULL,
  `sports`              TINYINT DEFAULT NULL,
  `handlingTools`       TINYINT DEFAULT NULL,
  `musical`             TINYINT DEFAULT NULL,
  `drawingPainting`     TINYINT DEFAULT NULL,
  -- Qualitative remarks
  `classTeacherComment` TEXT DEFAULT NULL,
  `principalRemark`     TEXT DEFAULT NULL,
  `updatedAt`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`assessmentId`),
  UNIQUE KEY `unique_student_term_session` (`studentId`, `academicTerm`, `academicSession`),
  KEY `idx_term_session` (`academicTerm`, `academicSession`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
