CREATE TABLE `zhihu` (
  `user_id` char(50) DEFAULT '' COMMENT '用户id',
  `sex` char(4) DEFAULT '' COMMENT '性别',
  `school` char(30) DEFAULT '' COMMENT '学校',
  `major` char(30) DEFAULT '' COMMENT '专业',
  `business` char(30) DEFAULT '' COMMENT '行业',
  `job` char(30) DEFAULT '' COMMENT '工作',
  `company` char(30) DEFAULT '' COMMENT '公司',
  `locations` char(30) DEFAULT '' COMMENT '居住地',
  `follower_count` INT DEFAULT 0 COMMENT '关注者',
  `following_count` INT DEFAULT 0 COMMENT '关注了',
  UNIQUE KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;