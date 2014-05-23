-- --------------------------------------------------------
LOCK TABLES `test_sample` WRITE;
INSERT INTO `test_sample` VALUES
  ( 1, 'Test Row One', 'This is varchar content', 'This is text content', 'No binary content handy', 1, 100, 100.5, NOW(), '1976-05-07', '00:00:01', 1976, 1, 0, 0, NOW(), NOW(), 1),
  ( 2, 'Test Row Two', 'This is varchar content', 'This is text content', 'No binary content handy', 1, 100, 100.5, NOW(), '1976-05-07', '00:00:01', 1976, 1, 0, 0, NOW(), NOW(), 1);
UNLOCK TABLES;