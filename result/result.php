<?php
set_time_limit(90);
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
try {
    $pdo = new PDO("mysql:dbname=zhihu;host=127.0.0.1;charset=utf8mb4", "root", "123456");
} catch (PDOException $e) {
    echo $e->getMessage();
}
function getData($type = '', $value = '')
{
    global $pdo;
    switch ($type) {
        case 'sex':
            $sex_stmt = $pdo->query('SELECT count(sex) AS amount FROM zhihu WHERE sex="1"');
            $man = $sex_stmt->fetch(PDO::FETCH_ASSOC)['amount'];

            $sex_stmt = $pdo->query('SELECT count(sex) AS amount FROM zhihu WHERE sex="0"');
            $woman = $sex_stmt->fetch(PDO::FETCH_ASSOC)['amount'];
            return array(
                    'man' => $man,
                    'woman' => $woman
            );
        case 'school':
            $school_stmt = $pdo->query("SELECT count(*) AS school_amount FROM zhihu WHERE school LIKE \"%{$value}%\"");
            $school = $school_stmt->fetch(PDO::FETCH_ASSOC)['school_amount'];
            return $school;
        case 'major':
            $major_stmt = $pdo->query("SELECT count(*) AS major_amount FROM zhihu WHERE major LIKE \"%{$value}%\"");
            $major = $major_stmt->fetch(PDO::FETCH_ASSOC)['major_amount'];
            return $major;
        case 'business':
            $business_stmt = $pdo->query("SELECT business, count(business) as business_amount FROM zhihu WHERE business <> 'bare' AND business <> '' GROUP BY business ORDER BY business DESC LIMIT 10");
            $business = $business_stmt->fetchAll(PDO::FETCH_ASSOC);
            return $business;
        case 'job':
            $job_stmt = $pdo->query("SELECT count(*) as job_amount FROM zhihu WHERE job LIKE \"%{$value}%\"");
            $job = $job_stmt->fetch(PDO::FETCH_ASSOC)['job_amount'];
            return $job;
        case 'company':
            $company_stmt = $pdo->query("SELECT count(*) as company_amount FROM zhihu WHERE company LIKE \"%{$value}%\"");
            $company = $company_stmt->fetch(PDO::FETCH_ASSOC)['company_amount'];
            return $company;
        case 'locations':
            $locations_stmt = $pdo->query("SELECT count(*) as locations_amount FROM zhihu WHERE locations LIKE \"%{$value}%\"");
            $locations = $locations_stmt->fetch(PDO::FETCH_ASSOC)['locations_amount'];
            return $locations;
        case 'follower':
            $follower_stmt = $pdo->query("SELECT count(follower_count) as amount FROM zhihu WHERE follower_count {$value}");
            $follower = $follower_stmt->fetch(PDO::FETCH_ASSOC)['amount'];
            return $follower;
        case 'following':
            $following_stmt = $pdo->query("SELECT count(following_count) as amount FROM zhihu WHERE following_count {$value}");
            $following = $following_stmt->fetch(PDO::FETCH_ASSOC)['amount'];
            return $following;
        default:
            echo "不知道的错误";
            break;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>数据统计</title>
    <script src="./highcharts/highcharts.js"></script>
    <script src="./highcharts/jquery-3.2.1.min.js"></script>
    <script src="https://img.hcharts.cn/highcharts/modules/exporting.js"></script>
    <script src="https://img.hcharts.cn/highcharts-plugins/highcharts-zh_CN.js"></script>
    <style type="text/css">
        #container1, #container2, #container3, #container4{
            float: right;
        }
    </style>
</head>
<body>
<div id="container" style="height: 400px"></div>
<div id="container1" style="height: 400px"></div>
<div id="container2" style="height: 400px"></div>
<div id="container3" style="height: 400px"></div>
<div id="container4" style="height: 400px"></div>
<script type="text/javascript">
    var document_width = $(document).width();
    $("#container").width(document_width / 2 - 20 + "px");
    $("#container1").width(document_width / 2 - 20 + "px");
    $("#container2").width(document_width / 2 - 20 + "px");
    $("#container3").width(document_width / 2 - 20 + "px");
    $("#container4").width(document_width / 2 - 20 + "px");
    var chart = Highcharts.chart('container', {
        chart: {
            type: 'pie'
        },
        title: {
            text: '男女比例'
        },
        tooltip: {
            headerFormat: '{series.name}<br>',
            pointFormat: '{point.name}: <b>{point.percentage:.1f}%</b>'
        },
        plotOptions: {
            series: {
                allowPointSelect: true
            }
        },
        series: [{
            data: [
                ['女',<?php $sex = getData('sex');$total = $sex['man'] + $sex['woman'];echo $sex['woman']/$total*100; ?>],
                ['男',<?php echo $sex['man']/$total*100; ?>]
            ]
        }]
    });
    Highcharts.chart('container1', {
        chart: {
            type: 'column'
        },
        title:{
            text:'名牌大学人数'
        },
        xAxis: {
            categories: ['清华大学', '北京大学', '复旦大学']
        },

        plotOptions: {
            series: {
                borderRadius: 5
            }
        },

        series: [{
            data: [<?php echo getData('school', '清华大学');?>, <?php echo getData('school', '北京大学'); ?>, <?php echo getData('school','复旦大学'); ?>]
        }]
    });
    Highcharts.chart('container2', {
        chart: {
            type: 'column'
        },
        title:{
            text: '前十行业'
        },
        xAxis: {
            categories: [<?php
                $business = getData('business');
                $column = '';
                foreach ($business as $val){
                    $column .= "\"".$val['business']."\",";
                }
                echo $column;
                ?>]
        },

        plotOptions: {
            series: {
                borderRadius: 5
            }
        },

        series: [{
            data: [<?php
                    $column = '';
                foreach ($business as $val){
                    $column .= $val['business_amount'].",";
                }
                echo $column;
                ?>]
        }]
    });
    Highcharts.chart('container3', {
        chart: {
            type: 'column'
        },
        title:{
            text:'在bat工作的人数'
        },
        xAxis: {
            categories: ['百度', '阿里', '腾讯']
        },

        plotOptions: {
            series: {
                borderRadius: 5
            }
        },

        series: [{
            data: [<?php echo getData('company', '百度');?>, <?php echo getData('company', '阿里'); ?>, <?php echo getData('company','腾讯'); ?>]
        }]
    });
    Highcharts.chart('container4', {
        chart: {
            type: 'column'
        },
        title:{
            text:'在北上广深工作的人数'
        },
        xAxis: {
            categories: ['北京', '上海', '广州', '深圳']
        },

        plotOptions: {
            series: {
                borderRadius: 5
            }
        },

        series: [{
            data: [<?php echo getData('locations', '北京');?>, <?php echo getData('locations', '上海'); ?>, <?php echo getData('locations','广州'); ?>, <?php echo getData('locations', '深圳'); ?>]
        }]
    });
</script>
</body>
</html>