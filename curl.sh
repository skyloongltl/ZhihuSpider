#!/bin/bash
alive="ps aux|grep \/usr\/local\/apache2\/htdocs\/ZhihuSpider\/curlUser"
if [ $alive -eq 0]
then
php /usr/local/apache2/htdocs/ZhihuSpider/curlUser.php > /dev/null &
fi
