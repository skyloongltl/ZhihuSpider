<?php
$cookie = 'dc02bbaffc34fd5947555a70d904e43|1499238588000|1491479426000; d_c0="AIDCswSpkQuPTrAlILigvMiYBukzZJJMVkM=|1491533653"; _zap=58fc20de-693b-47d3-b5f1-8b1037b3868d; __utma=51854390.337722136.1492008424.1500529051.1500640514.16; __utmz=51854390.1494897227.11.6.utmcsr=zhihu.com|utmccn=(referral)|utmcmd=referral|utmcct=/; __utmv=51854390.000--|2=registration_date=20160105=1^3=entry_date=20170406=1; q_c1=adc02bbaffc34fd5947555a70d904e43|1499238588000|1491479426000; r_cap_id="MjRjMDMwNDY5ZWI3NDk2NmFjODM3OTU0ODVhYTdiYmI=|1500529047|fb26fba335176328957412eee3ba49553c8f4896"; cap_id="NDgyMzRkMTJiOTU4NDdjZTk0YTg1ODQyMTAwMjdkMzA=|1500529047|2a6271e95e358ae562681ce58f24f03f1f2a177d"; l_cap_id="MmEyNjBjYTM5ZTg2NDFmZmIyNWQxMTQ0NjFjZmU1NTM=|1500529047|01b4cfce528a96d7c9c97c15c211b6573cfb6fce"; _xsrf=8eda2fa4d43031f045e558338897ac2c; __utmc=51854390; _xsrf=8eda2fa4d43031f045e558338897ac2c';
$url = 'https://www.zhihu.com/api/v4/members/ltl-29/followees?include=data%5B*%5D.answer_count%2Carticles_count%2Cgender%2Cfollower_count%2Cis_followed%2Cis_following%2Cbadge%5B%3F(type%3Dbest_answerer)%5D.topics&offset=0&limit=20'; //此处mora-hu代表用户ID
$header = array(
        'Authorization: Bearer 2|1:0|10:1500900647|4:z_c0|92:Mi4wQUZDQ3ZEckNtQXNBZ01LekJLbVJDeVlBQUFCZ0FsVk5KM3FkV1FCelVYeGdBMDdoMkJsVDFob0puU040Tm16aHlR|fc953414e362fc4dd8efdae84ce99cf1f57654e7df9953ed04fa0db125a93dea',
    );
$ch = curl_init($url); //初始化会话
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
curl_setopt($ch, CURLOPT_COOKIE, $cookie);  //设置请求COOKIE
curl_setopt($ch, CURLOPT_REFERER, 'https://www.zhihu.com');
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  //将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
var_dump(curl_error($ch));
file_put_contents('ltl-28.html', $result);
curl_close($ch);
echo 'httpCode:'.$httpCode;
