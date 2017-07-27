CREATE TABLE `zhihu` (
  `user_id` char(50) DEFAULT NULL DEFAULT '' COMMENT '用户id',
  `sex` char(4) DEFAULT NULL DEFAULT '' COMMENT '性别',
  `university` char(30) DEFAULT NULL DEFAULT '' COMMENT '大学',
  `profession` char(30) DEFAULT NULL DEFAULT '' COMMENT '专业',
  `job` char(30) DEFAULT NULL DEFAULT '' COMMENT '工作',
  `jobex` char(30) DEFAULT NULL DEFAULT '' COMMENT '工作经历',
  UNIQUE KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;