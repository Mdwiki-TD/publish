
write code in do_db.py to scan all "reports_by_day" subdir/subfiles, then add the reports to the database table 'publish_reports' in the mdwiki database
while:
* result column is the file name.
* data column is the content of the file
* other columns are the same as in the data from the file
* date column from the file ("time_date") and if there is't "time_date" in the file, then you can get it from the file path like reports_by_day/2025/05/15

CREATE TABLE `publish_reports` (
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `title` varchar(255) NOT NULL,
  `user` varchar(255) NOT NULL,
  `lang` varchar(255) NOT NULL,
  `sourcetitle` varchar(255) NOT NULL,
  `result` varchar(255) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

just pass the query to add_report(query, params) and I will write the add_report() code
