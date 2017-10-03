## REST Application with Slim Microframework v2.

### Dependencies (Composer):
* Slim Framework v2
* RedBeanPHP Database ORM https://redbeanphp.com/index.php

### Instructions:
* Copy all contents in XAMPP htdocs/slim
* Create database `slim` (MySQL/MariaDb)
* Run `localhost/slim/create-table`
* Run `localhost/slim/demo` to authenticate yourself as a demo user for 5 minutes.

### Examples:
```
GET localhost/slim/articles
GET localhost/slim/articles/1

POST localhost/slim/articles 
{"title":"Le Random Title","url":"http://google.com","date":"2017-03-10"}

PUT localhost/slim/articles/3
{"title":"We modified yo!","url":"http://google.com","date":"2017-03-10"}

DELETE localhost/slim/articles/3
```

### References:
* https://www.ibm.com/developerworks/opensource/library/x-slim-rest/index.html#download
* https://github.com/slimphp
* https://www.slimframework.com/
* v2 Docs: http://docs.slimframework.com/middleware/how-to-use/
