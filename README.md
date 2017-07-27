#php爬取知乎
再爬取知乎之前我们要先登录到知乎，然后从控制台获取cookie，获取到cookie后，我们可以
这样爬取知乎：
```
<?php
$cookie = '在这写上获取到的cookie";
$url = 'https://www.zhihu.com/';
$ch = curl_init($url); //初始化会话
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_COOKIE, $cookie); //设置请求COOKIE
curl_setopt($ch, CURLOPT_REFERER, 'https://www.zhihu.com');
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
$result = curl_exec($ch);
curl_close($ch);
```

这样就爬取到了知乎的界面。
如果我们要爬取知乎，就得先知道知乎用户的id，知乎个人用户的首页链接是这样的https://www.zhihu.com/people/excited-vczh/activities,这是轮子哥的个人首页，我们可以看到excited-vczh就是轮子哥的id。
那我们要怎么获得这个id呢？我们可以用chrome的调试工具查看在点击关注列表时出现的链接，会发现有https://www.zhihu.com/api/v4/members/excited-vczh/followees?include=data%5B*%5D.answer_count%2Carticles_count%2Cgender%2Cfollower_count%2Cis_followed%2Cis_following%2Cbadge%5B%3F(type%3Dbest_answerer)%5D.topics&offset=20&limit=20
这样的一个链接，这个链接就是返回个人的关注列表。再仔细观察这个链接，可以看出大概是这么个样子"https://www.zhihu.com/api/v4/members/{$user_id}/{$type}?include={$include}&offset={$offset}&limit={$limit}",其中
user_id就是知乎用户的id，type是决定返回的是关注了列表还是关注者列表，而include就是一串固定的字符串，offset是偏移量，limit是每页返回多少个用户。在这里要注意了，如果你在浏览器上查看的这个人关注者列表或关注了的人没有一页以上，就不会出现这个链接。。。
但是没有关系，在爬取关注列表时仍然能通过这个链接获取到关注列表。
这个链接返回的是unicode的json信息，所以我们要先将unicode转化，才能json解析成功，这里是我从网上找的一个代码：
```
function replace_unicode_escape_sequence($match) {
  return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
}
$name = '\u65b0\u6d6a\u5fae\u535a';
$str = preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $name);
echo $str; //输出： 新浪微博
```
这样就能获取到返回的json信息了，然后可以从json信息里获取到用户的user_id。而且在返回的json信息里，通过在paging字段里的is_end能判断是否是关注列表最后一页，如果是true就可以结束这个人的链接爬取了。其他字段就自己研究下吧。。。。
在我们把鼠标移到关注的人的头像时，会有一个ajax请求，可以用调试工具查看，这个请求会返回一些个人信息。
但是，它返回的信息比较少，如果想获得更多信息，可以获取到个人主页的html文档，再正则匹配出想要的信息。
"https://www.zhihu.com/people/{$user_id}/activities"个人主页的链接类似这个。如果不用正则的话，也可以用像[Simple-HTML-DOM](http://simplehtmldom.sourceforge.net/manual.htm)，[phpQuery](https://github.com/TobiaszCudnik/phpquery)这些DOM操作工具
获取需要的信息。
在爬取的时候我们用redis的集合做请求队列，在爬取一个人以后就将user_id放进already_reuest_queue中，将爬取到的关注列表先判断是否存在already_quest_queue中，存在的话就不放进request_queue中，否则就放进去。
然后再从request_queue中获取下一个爬取的用户。再爬取时如果返回的http码为0就重启一下php-fpm，如果不行，就先
`killall php-fpm`，再启动php-fpm，就可以了。

在爬取了一万七千多用户的时候，知乎就返回了错误，需要验证。。。。。
返回这样的一个链接https://www.zhihu.com/account/unhuman?type=unhuman&message=%E7%B3%BB%E7%BB%9F%E6%A3%80%E6%B5%8B%E5%88%B0%E6%82%A8%E7%9A%84%E5%B8%90%E5%8F%B7%E6%88%96IP%E5%AD%98%E5%9C%A8%E5%BC%82%E5%B8%B8%E6%B5%81%E9%87%8F%EF%BC%8C%E8%AF%B7%E8%BE%93%E5%85%A5%E4%BB%A5%E4%B8%8B%E5%AD%97%E7%AC%A6%E7%94%A8%E4%BA%8E%E7%A1%AE%E8%AE%A4%E8%BF%99%E4%BA%9B%E8%AF%B7%E6%B1%82%E4%B8%8D%E6%98%AF%E8%87%AA%E5%8A%A8%E7%A8%8B%E5%BA%8F%E5%8F%91%E5%87%BA%E7%9A%84
打开ia这个链接时，它会先请求https://www.zhihu.com/api/v4/anticrawl/captcha_appeal获取到图片的base64编码数据，然后再访问data:image/png:base64,{$str}就可以获取到验证码图片，最后似乎是把验证码以json的形式post到获取图片的地址去，然后请求就可以的。至于识别图片里的验证码。。。。我不会啊！！！！！（这个josn是咋样的我忘了。。。没记下来。。可以用抓包工具fiddler抓包看一下）。
（这个base64编码的数据其实也可以用php的base64_decode解码？）
